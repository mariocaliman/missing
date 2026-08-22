<?php
include(__DIR__ . '/include/autoloader.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    header('Location:' . $general_setting['siteurl'] . '/not-found');
    exit;
}

$api_options = array();
if (isset($general) && $general) {
    $api_options = $general->get_options('AI');
}

$recaptcha_site_key = isset($api_options['google_recaptcha_site_key']) ? trim((string) $api_options['google_recaptcha_site_key']) : '';
$recaptcha_secret_key = isset($api_options['google_recaptcha_secret_key']) ? trim((string) $api_options['google_recaptcha_secret_key']) : '';

ensure_news_tips_table();

$news_id = 0;
if (isset($_GET['news_id'])) {
    $news_id = intval($_GET['news_id']);
}
if (isset($_POST['news_id'])) {
    $news_id = intval($_POST['news_id']);
}

if ($news_id <= 0) {
    header('Location:' . $general_setting['siteurl'] . '/not-found');
    exit;
}

$article_stmt = $mysqli->prepare("SELECT id, title, thumbnail FROM news WHERE published='1' AND id=? LIMIT 1");
$article_stmt->bind_param('i', $news_id);
$article_stmt->execute();
$article_result = $article_stmt->get_result();
$article = $article_result ? $article_result->fetch_assoc() : null;
$article_stmt->close();

if (!$article) {
    header('Location:' . $general_setting['siteurl'] . '/not-found');
    exit;
}

if (empty($_SESSION['tip_form_token'])) {
    $_SESSION['tip_form_token'] = bin2hex(random_bytes(24));
}

$form_message = '';
$form_message_type = '';
$form_data = array(
    'tip_name' => '',
    'tip_email' => '',
    'tip_phone' => '',
    'tip_location' => '',
    'tip_message' => '',
    'tip_terms_agreed' => 0
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['tip_form_token']) ? (string) $_POST['tip_form_token'] : '';
    $honeypot = isset($_POST['website']) ? trim((string) $_POST['website']) : '';
    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? (string) $_POST['g-recaptcha-response'] : '';

    $form_data['tip_name'] = isset($_POST['tip_name']) ? trim((string) $_POST['tip_name']) : '';
    $form_data['tip_email'] = isset($_POST['tip_email']) ? trim((string) $_POST['tip_email']) : '';
    $form_data['tip_phone'] = isset($_POST['tip_phone']) ? trim((string) $_POST['tip_phone']) : '';
    $form_data['tip_location'] = isset($_POST['tip_location']) ? trim((string) $_POST['tip_location']) : '';
    $form_data['tip_message'] = isset($_POST['tip_message']) ? trim((string) $_POST['tip_message']) : '';
    $form_data['tip_terms_agreed'] = isset($_POST['tip_terms_agreed']) ? 1 : 0;

    if ($token === '' || !hash_equals($_SESSION['tip_form_token'], $token)) {
        $form_message_type = 'danger';
        $form_message = 'Invalid request token. Refresh the page and try again.';
    } elseif ($recaptcha_site_key !== '' && $recaptcha_secret_key !== '' && !verify_google_recaptcha($recaptcha_secret_key, $recaptcha_response, isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '')) {
        $form_message_type = 'warning';
        $form_message = 'Please complete the reCAPTCHA verification.';
    } elseif ($honeypot !== '') {
        $form_message_type = 'danger';
        $form_message = 'Invalid submission.';
    } elseif ($form_data['tip_terms_agreed'] !== 1) {
        $form_message_type = 'warning';
        $form_message = 'You must accept the Terms and Conditions before submitting a tip.';
    } elseif ($form_data['tip_name'] === '' || $form_data['tip_email'] === '' || $form_data['tip_message'] === '') {
        $form_message_type = 'warning';
        $form_message = 'Please fill in your name, email, and tip details.';
    } elseif (!filter_var($form_data['tip_email'], FILTER_VALIDATE_EMAIL)) {
        $form_message_type = 'warning';
        $form_message = 'Please provide a valid email address.';
    } else {
        $missing_name = html_entity_decode((string) $article['title'], ENT_QUOTES, 'UTF-8');
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 64) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';

        $insert = $mysqli->prepare("INSERT INTO news_tips (news_id, missing_name, tip_name, tip_email, tip_phone, tip_location, tip_message, status, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
        if ($insert) {
            $insert->bind_param(
                'issssssss',
                $news_id,
                $missing_name,
                $form_data['tip_name'],
                $form_data['tip_email'],
                $form_data['tip_phone'],
                $form_data['tip_location'],
                $form_data['tip_message'],
                $ip_address,
                $user_agent
            );

            if ($insert->execute()) {
                $form_message_type = 'success';
                $form_message = 'Thank you. Your tip was submitted and is awaiting admin review.';
                $form_data = array(
                    'tip_name' => '',
                    'tip_email' => '',
                    'tip_phone' => '',
                    'tip_location' => '',
                    'tip_message' => '',
                    'tip_terms_agreed' => 0
                );
                $_SESSION['tip_form_token'] = bin2hex(random_bytes(24));
            } else {
                $form_message_type = 'danger';
                $form_message = 'Unable to submit your tip right now. Please try again.';
            }
            $insert->close();
        } else {
            $form_message_type = 'danger';
            $form_message = 'Unable to submit your tip right now. Please try again.';
        }
    }
}

$poster_url = $general_setting['siteurl'] . '/news/' . $article['id'] . '/' . slugit($article['title']);
$poster_url = str_replace(':/', '://', str_replace('//', '/', $poster_url));

$smarty->assign('is_tip_form', 1);
$smarty->assign('tip_article', $article);
$smarty->assign('tip_poster_url', $poster_url);
$smarty->assign('tip_form_token', $_SESSION['tip_form_token']);
$smarty->assign('form_message', $form_message);
$smarty->assign('form_message_type', $form_message_type);
$smarty->assign('form_data', $form_data);
$smarty->assign('recaptcha_site_key', $recaptcha_site_key);

$smarty->assign('seo_title', 'Submit a Tip - ' . htmlspecialchars_decode($article['title'], ENT_QUOTES));
$smarty->assign('seo_keywords', title_to_keywords(htmlspecialchars_decode($article['title'], ENT_QUOTES)));
$smarty->assign('seo_description', 'Submit a confidential tip for this missing person case.');

$smarty->display('submit-tip.html');
?>