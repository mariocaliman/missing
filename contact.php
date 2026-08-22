<?php
include(__DIR__ . '/include/autoloader.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($general_setting['siteurl'])) {
    $general_setting['siteurl'] = './';
}

$api_options = array();
if (isset($general) && $general) {
    $api_options = $general->get_options('AI');
}

$recaptcha_site_key = isset($api_options['google_recaptcha_site_key']) ? trim((string) $api_options['google_recaptcha_site_key']) : '';
$recaptcha_secret_key = isset($api_options['google_recaptcha_secret_key']) ? trim((string) $api_options['google_recaptcha_secret_key']) : '';

if (empty($_SESSION['contact_form_token'])) {
    $_SESSION['contact_form_token'] = bin2hex(random_bytes(24));
}

$form_message = '';
$form_message_type = '';
$form_data = array(
    'contact_name' => '',
    'contact_email' => '',
    'contact_subject' => '',
    'contact_message' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['contact_form_token']) ? (string) $_POST['contact_form_token'] : '';
    $honeypot = isset($_POST['website']) ? trim((string) $_POST['website']) : '';
    $recaptcha_response = isset($_POST['g-recaptcha-response']) ? (string) $_POST['g-recaptcha-response'] : '';

    $form_data['contact_name'] = isset($_POST['contact_name']) ? trim((string) $_POST['contact_name']) : '';
    $form_data['contact_email'] = isset($_POST['contact_email']) ? trim((string) $_POST['contact_email']) : '';
    $form_data['contact_subject'] = isset($_POST['contact_subject']) ? trim((string) $_POST['contact_subject']) : '';
    $form_data['contact_message'] = isset($_POST['contact_message']) ? trim((string) $_POST['contact_message']) : '';

    if ($token === '' || !hash_equals($_SESSION['contact_form_token'], $token)) {
        $form_message_type = 'danger';
        $form_message = 'Invalid request token. Refresh the page and try again.';
    } elseif ($recaptcha_site_key !== '' && $recaptcha_secret_key !== '' && !verify_google_recaptcha($recaptcha_secret_key, $recaptcha_response, isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '')) {
        $form_message_type = 'warning';
        $form_message = 'Please complete the reCAPTCHA verification.';
    } elseif ($honeypot !== '') {
        $form_message_type = 'danger';
        $form_message = 'Invalid submission.';
    } elseif ($form_data['contact_name'] === '' || $form_data['contact_email'] === '' || $form_data['contact_subject'] === '' || $form_data['contact_message'] === '') {
        $form_message_type = 'warning';
        $form_message = 'Please fill in all required fields.';
    } elseif (!filter_var($form_data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $form_message_type = 'warning';
        $form_message = 'Please provide a valid email address.';
    } else {
        $safe_name = str_replace(array("\r", "\n"), ' ', $form_data['contact_name']);
        $safe_email = str_replace(array("\r", "\n"), '', $form_data['contact_email']);
        $safe_subject = str_replace(array("\r", "\n"), ' ', $form_data['contact_subject']);

        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : 'unknown';

        $mail_to = 'contact@missing-usa.com';
        $mail_subject = '[Missing USA] Contact Form: ' . $safe_subject;
        $mail_body = "New contact form submission\n\n";
        $mail_body .= "Name: " . $safe_name . "\n";
        $mail_body .= "Email: " . $safe_email . "\n";
        $mail_body .= "Subject: " . $safe_subject . "\n";
        $mail_body .= "Message:\n" . $form_data['contact_message'] . "\n\n";
        $mail_body .= "IP: " . $ip_address . "\n";
        $mail_body .= "User-Agent: " . $user_agent . "\n";
        $mail_body .= "Date: " . date('Y-m-d H:i:s') . "\n";

        $sent = send_transactional_email(
            $mail_to,
            $mail_subject,
            $mail_body,
            $safe_name . ' <' . $safe_email . '>',
            'contact_form'
        );
        if ($sent) {
            $form_message_type = 'success';
            $form_message = 'Thank you. Your message has been sent.';
            $form_data = array(
                'contact_name' => '',
                'contact_email' => '',
                'contact_subject' => '',
                'contact_message' => ''
            );
            $_SESSION['contact_form_token'] = bin2hex(random_bytes(24));
        } else {
            $form_message_type = 'danger';
            $form_message = 'Unable to send your message right now. Please try again later.';
        }
    }
}

$smarty->assign('is_contact', 1);
$smarty->assign('contact_form_token', $_SESSION['contact_form_token']);
$smarty->assign('form_message', $form_message);
$smarty->assign('form_message_type', $form_message_type);
$smarty->assign('form_data', $form_data);
$smarty->assign('recaptcha_site_key', $recaptcha_site_key);

$smarty->assign('seo_title', 'Contact Us - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'contact, missing persons, report information');
$smarty->assign('seo_description', 'Contact Missing USA to share information or ask questions.');

$smarty->display('contact.html');
?>