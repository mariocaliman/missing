<?php
// sanitize inputs 
function make_safe($str)
{
    global $mysqli;
	$str = $mysqli->real_escape_string($str);
	return strip_tags(trim($str));
}

function get_email_delivery_options()
{
    static $options = null;
    if ($options !== null) {
        return $options;
    }

    $options = array(
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_secure' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => 'no-reply@missing-usa.com',
        'smtp_from_name' => 'Missing USA',
        'smtp_retry_attempts' => '3'
    );

    global $mysqli;
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return $options;
    }

    $keys = array_keys($options);
    $escaped = array();
    foreach ($keys as $key) {
        $escaped[] = "'" . $mysqli->real_escape_string($key) . "'";
    }

    $sql = "SELECT option_name, option_value FROM options WHERE option_name IN (" . implode(',', $escaped) . ")";
    $query = $mysqli->query($sql);
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $options[$row['option_name']] = (string) $row['option_value'];
        }
    }

    if (!in_array($options['smtp_secure'], array('none', 'tls', 'ssl'), true)) {
        $options['smtp_secure'] = 'tls';
    }

    return $options;
}

function ensure_email_logs_table()
{
    static $checked = false;
    if ($checked) {
        return true;
    }

    global $mysqli;
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS email_delivery_logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        context_name VARCHAR(120) NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        subject_line VARCHAR(255) NOT NULL,
        transport VARCHAR(20) NOT NULL,
        attempt SMALLINT UNSIGNED NOT NULL,
        status VARCHAR(20) NOT NULL,
        response_message TEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_context_created (context_name, created_at),
        KEY idx_status_created (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $checked = $mysqli->query($sql) ? true : false;
    return $checked;
}

function log_email_delivery_attempt($context, $to, $subject, $transport, $attempt, $status, $response)
{
    if (!ensure_email_logs_table()) {
        return;
    }

    global $mysqli;
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return;
    }

    $stmt = $mysqli->prepare("INSERT INTO email_delivery_logs (context_name, recipient_email, subject_line, transport, attempt, status, response_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        return;
    }

    $context = mb_substr((string) $context, 0, 120, 'UTF-8');
    $to = mb_substr((string) $to, 0, 255, 'UTF-8');
    $subject = mb_substr((string) $subject, 0, 255, 'UTF-8');
    $transport = mb_substr((string) $transport, 0, 20, 'UTF-8');
    $status = mb_substr((string) $status, 0, 20, 'UTF-8');
    $response = (string) $response;

    $stmt->bind_param('ssssiss', $context, $to, $subject, $transport, $attempt, $status, $response);
    $stmt->execute();
    $stmt->close();
}

function smtp_read_response($socket)
{
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }
    return trim($response);
}

function smtp_send_command($socket, $command, $expected_codes, &$response)
{
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = smtp_read_response($socket);
    foreach ((array) $expected_codes as $code) {
        if (strpos($response, (string) $code) === 0) {
            return true;
        }
    }
    return false;
}

function smtp_send_email_message($config, $to, $subject, $body, $reply_to, &$error)
{
    $host = trim((string) $config['smtp_host']);
    $port = (int) $config['smtp_port'];
    if ($port <= 0) {
        $port = 587;
    }
    $secure = trim((string) $config['smtp_secure']);
    $username = trim((string) $config['smtp_username']);
    $password = (string) $config['smtp_password'];
    $from_email = trim((string) $config['smtp_from_email']);
    $from_name = trim((string) $config['smtp_from_name']);

    if ($host === '' || $username === '' || $password === '' || $from_email === '' || $to === '') {
        $error = 'Missing SMTP configuration values.';
        return false;
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        $error = 'SMTP connect failed: ' . $errstr;
        return false;
    }

    stream_set_timeout($socket, 20);
    $response = '';
    if (!smtp_send_command($socket, null, array('220'), $response)) {
        $error = 'SMTP greeting failed: ' . $response;
        fclose($socket);
        return false;
    }

    $ehlo_host = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
    if (!smtp_send_command($socket, 'EHLO ' . $ehlo_host, array('250'), $response)) {
        $error = 'SMTP EHLO failed: ' . $response;
        fclose($socket);
        return false;
    }

    if ($secure === 'tls') {
        if (!smtp_send_command($socket, 'STARTTLS', array('220'), $response)) {
            $error = 'SMTP STARTTLS failed: ' . $response;
            fclose($socket);
            return false;
        }

        $crypto_ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($crypto_ok !== true) {
            $error = 'SMTP TLS negotiation failed.';
            fclose($socket);
            return false;
        }

        if (!smtp_send_command($socket, 'EHLO ' . $ehlo_host, array('250'), $response)) {
            $error = 'SMTP EHLO after TLS failed: ' . $response;
            fclose($socket);
            return false;
        }
    }

    if (!smtp_send_command($socket, 'AUTH LOGIN', array('334'), $response)) {
        $error = 'SMTP AUTH command failed: ' . $response;
        fclose($socket);
        return false;
    }
    if (!smtp_send_command($socket, base64_encode($username), array('334'), $response)) {
        $error = 'SMTP username rejected: ' . $response;
        fclose($socket);
        return false;
    }
    if (!smtp_send_command($socket, base64_encode($password), array('235'), $response)) {
        $error = 'SMTP password rejected: ' . $response;
        fclose($socket);
        return false;
    }

    if (!smtp_send_command($socket, 'MAIL FROM:<' . $from_email . '>', array('250'), $response)) {
        $error = 'SMTP MAIL FROM failed: ' . $response;
        fclose($socket);
        return false;
    }
    if (!smtp_send_command($socket, 'RCPT TO:<' . $to . '>', array('250', '251'), $response)) {
        $error = 'SMTP RCPT TO failed: ' . $response;
        fclose($socket);
        return false;
    }
    if (!smtp_send_command($socket, 'DATA', array('354'), $response)) {
        $error = 'SMTP DATA failed: ' . $response;
        fclose($socket);
        return false;
    }

    $encoded_subject = '=?UTF-8?B?' . base64_encode((string) $subject) . '?=';
    $from_header = $from_email;
    if ($from_name !== '') {
        $from_header = '=?UTF-8?B?' . base64_encode($from_name) . '?= <' . $from_email . '>';
    }

    $headers = array(
        'Date: ' . date('r'),
        'From: ' . $from_header,
        'To: <' . $to . '>',
        'Subject: ' . $encoded_subject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit'
    );
    if ($reply_to !== '') {
        $headers[] = 'Reply-To: ' . $reply_to;
    }

    $body = preg_replace("/\r\n|\r|\n/", "\r\n", (string) $body);
    $body = preg_replace('/^\./m', '..', $body);
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
    fwrite($socket, $data);

    if (!smtp_send_command($socket, null, array('250'), $response)) {
        $error = 'SMTP message rejected: ' . $response;
        fclose($socket);
        return false;
    }

    smtp_send_command($socket, 'QUIT', array('221', '250'), $response);
    fclose($socket);
    $error = '';
    return true;
}

function send_transactional_email($to, $subject, $body, $reply_to = '', $context = 'general')
{
    $to = trim((string) $to);
    $subject = trim((string) $subject);
    $body = (string) $body;
    $reply_to = trim((string) $reply_to);
    $context = trim((string) $context);

    if ($to === '' || $subject === '' || $body === '') {
        return false;
    }

    $options = get_email_delivery_options();
    $attempts = (int) $options['smtp_retry_attempts'];
    if ($attempts < 1) {
        $attempts = 1;
    }
    if ($attempts > 5) {
        $attempts = 5;
    }

    $use_smtp = !empty($options['smtp_host']) && !empty($options['smtp_username']) && !empty($options['smtp_password']) && !empty($options['smtp_from_email']);
    $transport = $use_smtp ? 'smtp' : 'mail';
    $last_error = '';

    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        $success = false;
        if ($use_smtp) {
            $success = smtp_send_email_message($options, $to, $subject, $body, $reply_to, $last_error);
        } else {
            $from_email = !empty($options['smtp_from_email']) ? $options['smtp_from_email'] : 'no-reply@missing-usa.com';
            $from_name = !empty($options['smtp_from_name']) ? $options['smtp_from_name'] : 'Missing USA';
            $mail_headers = array(
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            );
            if ($reply_to !== '') {
                $mail_headers[] = 'Reply-To: ' . $reply_to;
            }
            $success = @mail($to, $subject, $body, implode("\r\n", $mail_headers));
            $last_error = $success ? '' : 'mail() returned false';
        }

        log_email_delivery_attempt($context, $to, $subject, $transport, $attempt, $success ? 'success' : 'failed', $last_error);
        if ($success) {
            return true;
        }

        if ($attempt < $attempts) {
            usleep(250000);
        }
    }

    return false;
}
// get first image in string using html dom
function get_first_image($html){
    if (!empty($html)) {
		require_once('simple_html_dom.php');
    $post_dom = str_get_html($html);
    if ($post_dom && $post_dom->find('img', 0) !== null) {
	$first_img = $post_dom->find('img', 0);
	$image = $first_img->src;
	if (strtok($image, '?') != '') {
	$image = strtok($image, '?');
	} else {
	$image = $image;
	}
        return $image;
    }
    return null;
	} else {
	return null;
	}
}

function get_article_data_from_url($url, $fallback_html = '') {
    $details = $fallback_html;
    $image = null;

    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        return array('details' => $details, 'image' => $image);
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\nAccept-Language: en-US,en;q=0.8\r\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n",
            'timeout' => 20,
            'ignore_errors' => true,
            'follow_location' => true
        )
    ));

    $html = @file_get_contents($url, false, $context);
    if ($html === false || empty($html)) {
        $html = $fallback_html;
    }

    $html = (string) $html;
    if (!empty($html)) {
        require_once('simple_html_dom.php');
        $post_dom = str_get_html($html);

        if ($post_dom) {
            $selectors = array('article', 'main', '.post-content', '.entry-content', '.content', '.article-content', '.story', 'div.post', 'div.entry');
            $article_node = null;

            foreach ($selectors as $selector) {
                $nodes = $post_dom->find($selector, 0);
                if ($nodes !== null) {
                    $article_node = $nodes;
                    break;
                }
            }

            if ($article_node !== null) {
                $details = $article_node->innertext;
            } else {
                $details = $html;
            }

            $meta_image = $post_dom->find('meta[property="og:image"]', 0);
            if ($meta_image && !empty($meta_image->content)) {
                $image = $meta_image->content;
            }

            if (empty($image)) {
                $image = get_first_image($details);
            }

            if (empty($image)) {
                $image = get_first_image($html);
            }

            $details = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $details);
            $details = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $details);
            $details = preg_replace('/<\/?(noscript|svg|iframe|object|embed|applet|form|button|input|select|textarea|nav|header|footer|aside|script|style)[^>]*>/is', '', $details);
        }
    }

    if (empty($details)) {
        $details = $fallback_html;
    }

    if (!empty($image)) {
        $image = strtok($image, '?');
    }

    return array('details' => $details, 'image' => $image);
}

function extract_case_profile_fields($details)
{
    $text = html_entity_decode((string) $details, ENT_QUOTES, 'UTF-8');
    $text = str_ireplace(array('<br />', '<br/>', '<br>', '</p>', '</div>', '</li>'), "\n", $text);
    $text = strip_tags($text);
    $text = preg_replace('/\r\n|\r/u', "\n", $text);
    $text = preg_replace('/\n{2,}/u', "\n", $text);

    $lines = array();
    foreach (explode("\n", $text) as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $lines[] = $line;
        }
    }

    if (empty($lines)) {
        return false;
    }

    $fields = array(
        'name' => '',
        'age' => '',
        'date' => '',
        'city' => '',
        'agency' => '',
        'ncmec' => ''
    );

    foreach ($lines as $line) {
        if ($fields['ncmec'] === '' && preg_match('~https?://[^\s]*ncmec\.org[^\s]*~i', $line, $m)) {
            $fields['ncmec'] = trim($m[0]);
        }

        if (!preg_match('/^([A-Za-z][A-Za-z\s]{1,40})\s*[:\-]\s*(.+)$/', $line, $parts)) {
            continue;
        }

        $label = strtolower(trim($parts[1]));
        $value = trim($parts[2]);
        if ($value === '') {
            continue;
        }

        if ($fields['name'] === '' && (strpos($label, 'name') !== false || strpos($label, 'missing child') !== false || strpos($label, 'missing person') !== false)) {
            $fields['name'] = normalize_import_name_prefix($value);
            continue;
        }
        if ($fields['age'] === '' && strpos($label, 'age') !== false) {
            $fields['age'] = $value;
            continue;
        }
        if ($fields['date'] === '' && (strpos($label, 'date') !== false || strpos($label, 'last seen') !== false)) {
            $fields['date'] = $value;
            continue;
        }
        if ($fields['city'] === '' && (strpos($label, 'city') !== false || strpos($label, 'location') !== false || strpos($label, 'county') !== false || strpos($label, 'state') !== false)) {
            $fields['city'] = $value;
            continue;
        }
        if ($fields['agency'] === '' && (strpos($label, 'department') !== false || strpos($label, 'police') !== false || strpos($label, 'sheriff') !== false || strpos($label, 'agency') !== false || strpos($label, 'law enforcement') !== false)) {
            $fields['agency'] = $value;
            continue;
        }
        if ($fields['ncmec'] === '' && strpos($label, 'ncmec') !== false) {
            $fields['ncmec'] = $value;
        }
    }

    $filled = 0;
    foreach (array('name', 'age', 'date', 'city', 'agency') as $k) {
        if ($fields[$k] !== '') {
            $filled++;
        }
    }

    return ($filled >= 3) ? $fields : false;
}

function build_case_profile_text($title, $fields)
{
    $headline = trim(html_entity_decode((string) $title, ENT_QUOTES, 'UTF-8'));
    $headline = normalize_import_name_prefix($headline);
    if ($headline === '') {
        $headline = !empty($fields['name']) ? $fields['name'] : 'This case';
    }

    $person = $fields['name'] !== '' ? $fields['name'] : $headline;
    $intro = $headline . ' remains an active missing persons case. Behind every report is a family waiting for answers, a community hoping for safe return, and a timeline where every verified lead can matter.';
    $context = 'This page organizes the available information in a clear and respectful format so readers can understand the case quickly, share accurate details, and avoid spreading unverified claims.';

    $snapshot = array();
    if ($fields['name'] !== '') {
        $snapshot[] = 'Name: ' . $fields['name'];
    }
    if ($fields['age'] !== '') {
        $snapshot[] = 'Age: ' . $fields['age'];
    }
    if ($fields['date'] !== '') {
        $snapshot[] = 'Date reported/last seen: ' . $fields['date'];
    }
    if ($fields['city'] !== '') {
        $snapshot[] = 'City/Area: ' . $fields['city'];
    }
    if ($fields['agency'] !== '') {
        $snapshot[] = 'Law enforcement agency: ' . $fields['agency'];
    }
    if (empty($snapshot)) {
        $snapshot[] = 'Primary identifiers are being confirmed from the source record.';
    }

    $known_parts = array();
    if ($fields['date'] !== '') {
        $known_parts[] = 'The case report references the date as ' . $fields['date'];
    }
    if ($fields['city'] !== '') {
        $known_parts[] = 'the location as ' . $fields['city'];
    }
    if ($fields['agency'] !== '') {
        $known_parts[] = 'and the responsible agency as ' . $fields['agency'];
    }
    $known_text = 'What We Know So Far' . "\n";
    if (!empty($known_parts)) {
        $known_text .= implode(', ', $known_parts) . '. These details come from the source material available at the time of publication and may be updated by authorities as new evidence is reviewed.';
    } else {
        $known_text .= 'This report includes verified identifiers and official references. Continue monitoring trusted updates as the case develops.';
    }

    $impact_text = 'Why Continued Attention Matters' . "\n";
    $impact_text .= 'Cases like this can lose visibility over time, especially when updates are limited. Responsible sharing helps keep the search active, supports families who are still waiting, and increases the chance that the right person sees the right detail at the right moment.';

    $safety_text = 'Safety And Verification Notes' . "\n";
    $safety_text .= 'If you come across possible information about ' . $person . ', avoid public speculation and do not interfere with potential evidence. Document what you observed, keep screenshots or location references when possible, and forward that information directly to official channels.';

    $help_text = 'How You Can Help' . "\n";
    $help_text .= 'If you recognize the person or have credible information, contact local law enforcement immediately and reference the case details above. Even details that seem minor can help investigators connect timelines, verify sightings, or prioritize follow-up.';
    if (!empty($fields['ncmec'])) {
        $help_text .= "\nOfficial source: " . $fields['ncmec'];
    }

    $blocks = array(
        $intro,
        $context,
        'Case Snapshot' . "\n" . implode("\n", $snapshot),
        $known_text,
        $impact_text,
        $safety_text,
        $help_text
    );

    return trim(implode("\n\n", array_filter($blocks)));
}

// rewrite imported article details into richer multi-paragraph narrative
function rewrite_feed_article_details($title, $details)
{
    if (!is_import_rewrite_enabled()) {
        return $details;
    }

    $case_fields = extract_case_profile_fields($details);
    if ($case_fields !== false) {
        return build_case_profile_text($title, $case_fields);
    }

    $plain = html_entity_decode((string) $details, ENT_QUOTES, 'UTF-8');
    $plain = trim(strip_tags($plain));
    $plain = preg_replace('/\s+/u', ' ', $plain);

    if ($plain === '' || mb_strlen($plain, 'UTF-8') < 80) {
        return $details;
    }

    $sentences = preg_split('/(?<=[.!?])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
    if (!$sentences || count($sentences) < 2) {
        return $details;
    }

    $headline = trim(html_entity_decode((string) $title, ENT_QUOTES, 'UTF-8'));
    $headline = normalize_import_name_prefix($headline);
    if ($headline === '') {
        $headline = 'This case';
    }

    $first = normalize_rewrite_sentence($sentences[0]);
    $second = normalize_rewrite_sentence(isset($sentences[1]) ? $sentences[1] : '');
    $third = normalize_rewrite_sentence(isset($sentences[2]) ? $sentences[2] : '');

    $paragraph1 = $headline . ' is a case that deserves care, urgency, and continued public attention.';
    $paragraph1 .= ' Each missing person case affects real families and communities who continue searching for answers.';

    $facts = array();
    if ($first !== '') {
        $facts[] = $first;
    }
    if ($second !== '') {
        $facts[] = $second;
    }
    if ($third !== '') {
        $facts[] = $third;
    }
    $paragraph2 = implode(' ', $facts);

    $paragraph3 = 'Every verified detail shared at the right time can help authorities move faster. If any part of this report is familiar, contact the responsible agency immediately and share only factual, checkable information.';
    $paragraph4 = 'Families and communities are deeply affected in situations like this, and keeping the case visible can make a meaningful difference over time. Continued public attention often helps preserve momentum while formal investigations continue.';
    $paragraph5 = 'Readers can support responsible coverage by avoiding speculation, checking the original sources, and passing along relevant details through official channels. Accuracy, urgency, and compassion are all essential in cases involving missing people.';

    $rewritten = trim(implode("\n\n", array_filter(array($paragraph1, $paragraph2, $paragraph3, $paragraph4, $paragraph5))));
    return $rewritten !== '' ? $rewritten : $details;
}

function is_import_rewrite_enabled()
{
    static $enabled = null;

    if ($enabled !== null) {
        return $enabled;
    }

    $enabled = true;

    global $mysqli;
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return $enabled;
    }

    $query = $mysqli->query("SELECT option_value FROM options WHERE option_name='rewrite_imported_news' LIMIT 1");
    if ($query && $query->num_rows > 0) {
        $row = $query->fetch_assoc();
        $enabled = ((int) $row['option_value'] === 1);
    }

    return $enabled;
}

function is_remote_image_import_enabled()
{
    static $enabled = null;

    if ($enabled !== null) {
        return $enabled;
    }

    $enabled = false;

    global $mysqli;
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return $enabled;
    }

    $query = $mysqli->query("SELECT option_value FROM options WHERE option_name='use_source_image_url' LIMIT 1");
    if ($query && $query->num_rows > 0) {
        $row = $query->fetch_assoc();
        $enabled = ((int) $row['option_value'] === 1);
    }

    return $enabled;
}

function is_disallowed_feed_image_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return false;
    }

    $normalized = strtolower($url);
    $blocked_patterns = array(
        'missingkids/images/icons/',
        'mk_new_large_logo',
        '/logo.',
        '_logo.',
        '/icon.',
        '/icons/'
    );

    foreach ($blocked_patterns as $pattern) {
        if (strpos($normalized, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function normalize_import_name_prefix($text)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/^\s*#\s*:\s*/u', '', $text);
    $text = preg_replace('/^\s*[:\-]+\s*/u', '', $text);
    return trim((string) $text);
}

function normalize_import_title($title)
{
    $clean = html_entity_decode((string) $title, ENT_QUOTES, 'UTF-8');
    $clean = normalize_import_name_prefix($clean);
    return $clean;
}

function normalize_rewrite_sentence($text)
{
    $text = trim((string) $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($text === '') {
        return '';
    }
    if (!preg_match('/[.!?]$/u', $text)) {
        $text .= '.';
    }
    return $text;
}

function ensure_news_tips_table()
{
    static $checked = false;

    if ($checked) {
        return true;
    }

    global $mysqli;
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS news_tips (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        news_id INT UNSIGNED NOT NULL,
        missing_name VARCHAR(255) NOT NULL,
        tip_name VARCHAR(255) NOT NULL,
        tip_email VARCHAR(255) NOT NULL,
        tip_phone VARCHAR(80) NOT NULL,
        tip_location VARCHAR(255) NOT NULL,
        tip_message TEXT NOT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        admin_note TEXT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        reviewed_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY idx_news_id (news_id),
        KEY idx_status (status),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $checked = $mysqli->query($sql) ? true : false;
    return $checked;
}

function ensure_support_tickets_table()
{
    static $checked = false;

    if ($checked) {
        return true;
    }

    global $mysqli;
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        ticket_code VARCHAR(24) NOT NULL,
        visitor_name VARCHAR(255) NOT NULL,
        visitor_email VARCHAR(255) NOT NULL,
        ticket_subject VARCHAR(255) NOT NULL,
        ticket_message TEXT NOT NULL,
        status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
        admin_reply TEXT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        replied_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_ticket_code (ticket_code),
        KEY idx_status (status),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $checked = $mysqli->query($sql) ? true : false;
    return $checked;
}

// fallback feed reader used when SimplePie classes are unavailable
function fetch_feed_items($rss_link, $limit = 10) {
    $items = array();
    $limit = intval($limit);
    $unlimited = $limit <= 0;

    if (empty($rss_link) || !filter_var($rss_link, FILTER_VALIDATE_URL)) {
        return $items;
    }

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\nAccept: application/rss+xml, application/atom+xml, application/xml;q=0.9, */*;q=0.8\r\n",
            'timeout' => 20,
            'ignore_errors' => true
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    ));

    $raw_xml = @file_get_contents($rss_link, false, $context);
    if ($raw_xml === false || trim($raw_xml) === '') {
        return $items;
    }

    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($raw_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$xml) {
        return $items;
    }

    if (isset($xml->channel->item)) {
        foreach ($xml->channel->item as $entry) {
            $link = (string) $entry->link;
            $feedburner = $entry->children('http://rssnamespace.org/feedburner/ext/1.0');
            if (strpos($link, 'feedproxy') !== false && !empty($feedburner->origLink)) {
                $link = (string) $feedburner->origLink;
            }

            $contentNode = $entry->children('http://purl.org/rss/1.0/modules/content/');
            $mediaNode = $entry->children('http://search.yahoo.com/mrss/');

            $items[] = array(
                'permalink' => $link,
                'title' => (string) $entry->title,
                'description' => (string) $entry->description,
                'content' => isset($contentNode->encoded) ? (string) $contentNode->encoded : (string) $entry->description,
                'enclosure' => isset($entry->enclosure['url']) ? (string) $entry->enclosure['url'] : (isset($mediaNode->content['url']) ? (string) $mediaNode->content['url'] : '')
            );
        }
    } elseif (isset($xml->entry)) {
        foreach ($xml->entry as $entry) {
            $link = '';
            if (isset($entry->link)) {
                foreach ($entry->link as $lnk) {
                    $attrs = $lnk->attributes();
                    $href = isset($attrs['href']) ? (string) $attrs['href'] : '';
                    $rel = isset($attrs['rel']) ? (string) $attrs['rel'] : '';
                    if (!empty($href) && ($rel === '' || $rel === 'alternate')) {
                        $link = $href;
                        break;
                    }
                }
            }

            $items[] = array(
                'permalink' => $link,
                'title' => (string) $entry->title,
                'description' => (string) $entry->summary,
                'content' => (string) $entry->content,
                'enclosure' => ''
            );
        }
    }

    if (empty($items)) {
        return $items;
    }

    $items = array_reverse($items);
    if ($unlimited) {
        return $items;
    }
    return array_slice($items, 0, $limit);
}

// check if the article exists befor
function check_item_url($permalink,$source_id) {
	global $mysqli;
	$sql = "SELECT permalink,source_id FROM news WHERE permalink='$permalink' AND source_id='$source_id' LIMIT 1";
	$query = $mysqli->query($sql);
	return $query->num_rows;
}

// check if the source exists befor
function check_source_url($url) {
	global $mysqli;
	$sql = "SELECT rss_link FROM sources WHERE rss_link='$url' LIMIT 1";
	$query = $mysqli->query($sql);
	return $query->num_rows;
}

// create notifications
function notification($type,$text) {
return '<div class="alert alert-'.$type.'">'.$text.'</div>';
}

// get category name by ID
function get_category($id) {
global $mysqli;
$sql = "SELECT category FROM categories WHERE id='$id' LIMIT 1";
$query = $mysqli->query($sql);
$row = $query->fetch_assoc();
return $row['category'];
}
// get source title by ID
function get_source($id) {
if ($id == 0) {
return 'Private';	
} else {
global $mysqli;
$sql = "SELECT title FROM sources WHERE id='$id' LIMIT 1";
$query = $mysqli->query($sql);
$row = $query->fetch_assoc();
return $row['title'];
}
}
// count news in source
function get_source_news($id) {
global $mysqli;
$sql = "SELECT source_id FROM news WHERE source_id='$id'";
$query = $mysqli->query($sql);
$number = $query->num_rows;
return $number;
}
// count news in category
function get_category_news($id) {
global $mysqli;
$sql = "SELECT category_id FROM news WHERE category_id='$id'";
$query = $mysqli->query($sql);
$number = $query->num_rows;
return $number;
}
// count sources in category
function get_category_sources($id) {
global $mysqli;
$sql = "SELECT category_id FROM sources WHERE category_id='$id'";
$query = $mysqli->query($sql);
$number = $query->num_rows;
return $number;
}

// protect against XSS
function xss_clean($data)
{
        // Fix &entity\n;
        $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do
        {
                // Remove really unwanted tags
                $old_data = $data;
                $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        }
        while ($old_data !== $data);

        // we are done...
        return $data;
}


// empty smarty template cache files	
function empty_templates_cache($str){
         if(is_file($str)){
             return @unlink($str);
         }
         elseif(is_dir($str)){
             $scan = glob(rtrim($str,'/').'/*');
             foreach($scan as $index=>$path){
			 if (str_replace($str,'',$path) === 'index.html') continue;
                 empty_templates_cache($path);
             }
         return true;
		 }
}

// generate month and years select for news statistics
function generate_statics_select($year,$month) {
	$result = '';
	if ($year == date('Y')) {
	$result .= '<optgroup label="'.$year.'">';
	for($i=$month;$i<date('n')+1;$i++) {
	$result .= '<option value="?year='.$year.'&month='.$i.'">'.month_name($i).'</option>';
	}	
	$result .= '</optgroup>';
	} else {
	$result .= '<optgroup label="'.$year.'">';
	for($i=$month;$i<13;$i++) {
	$result .= '<option value="?year='.$year.'&month='.$i.'">'.month_name($i).'</option>';
	}	
	$result .= '</optgroup>';
	for($y=$year+1;$y<date('Y')+1;$y++) {
	$result .= '<optgroup label="'.$y.'">';
	for($m=1;$m<13;$m++) {
	$result .= '<option value="?year='.$y.'&month='.$m.'">'.month_name($m).'</option>';
	if ($y == date('Y') AND $m == date('n')) {
		break;
	}
	}	
	$result .= '</optgroup>';	
	}
	}
return $result;	
}

// convert month number to name
function month_name($month) {
$month_lang = array(
1 => 'January',
2 => 'February',
3 => 'March',
4 => 'April',
5 => 'May',
6 => 'June',
7 => 'July',
8 => 'August',
9 => 'September',
10 => 'October',
11 => 'November',
12 => 'December'
);
return $month_lang[$month];
}
?>