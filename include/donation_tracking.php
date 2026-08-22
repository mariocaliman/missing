<?php

function donation_tracking_ensure_table($mysqli)
{
    if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS donation_tracking (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        donation_ref VARCHAR(64) NOT NULL,
        page_path VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(255) NOT NULL,
        clicked_at INT UNSIGNED NOT NULL,
        status ENUM('pending','cancel','success') NOT NULL DEFAULT 'pending',
        status_updated_at INT UNSIGNED DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_donation_ref (donation_ref),
        KEY idx_clicked_at (clicked_at),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    return $mysqli->query($sql) ? true : false;
}

function donation_tracking_generate_ref()
{
    return bin2hex(random_bytes(16));
}

function donation_tracking_get_ip()
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return substr($_SERVER['HTTP_CF_CONNECTING_IP'], 0, 45);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return substr(trim($parts[0]), 0, 45);
    }
    return !empty($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '';
}

function donation_tracking_register_click($mysqli, $page_path)
{
    if (!donation_tracking_ensure_table($mysqli)) {
        return false;
    }

    $ref = donation_tracking_generate_ref();
    $safe_path = trim((string) $page_path);
    if ($safe_path === '') {
        $safe_path = '/';
    }
    $safe_path = substr($safe_path, 0, 255);

    $ip = donation_tracking_get_ip();
    $user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    $clicked_at = time();

    $stmt = $mysqli->prepare("INSERT INTO donation_tracking (donation_ref, page_path, ip_address, user_agent, clicked_at, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ssssi', $ref, $safe_path, $ip, $user_agent, $clicked_at);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok ? $ref : false;
}

function donation_tracking_mark_status($mysqli, $donation_ref, $status)
{
    if (!donation_tracking_ensure_table($mysqli)) {
        return false;
    }

    $donation_ref = trim((string) $donation_ref);
    if ($donation_ref === '' || !in_array($status, array('cancel', 'success'), true)) {
        return false;
    }

    $updated_at = time();
    $stmt = $mysqli->prepare("UPDATE donation_tracking SET status = ?, status_updated_at = ? WHERE donation_ref = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sis', $status, $updated_at, $donation_ref);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function donation_tracking_build_paypal_url($siteurl, $donation_ref)
{
    $base = 'https://www.paypal.com/donate/?hosted_button_id=R7F6DQKU83LJQ';
    $siteurl = rtrim((string) $siteurl, '/');

    if ($siteurl === '') {
        $return_url = './thank-you-for-donating?donation_status=success&donation_ref=' . rawurlencode($donation_ref);
        $cancel_url = './?donation_status=cancel&donation_ref=' . rawurlencode($donation_ref);
    } else {
        $return_url = $siteurl . '/thank-you-for-donating?donation_status=success&donation_ref=' . rawurlencode($donation_ref);
        $cancel_url = $siteurl . '/?donation_status=cancel&donation_ref=' . rawurlencode($donation_ref);
    }

    return $base . '&return=' . urlencode($return_url) . '&cancel_return=' . urlencode($cancel_url);
}
