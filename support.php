<?php
include(__DIR__ . '/include/autoloader.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    header('Location:' . $general_setting['siteurl'] . '/not-found');
    exit;
}

ensure_support_tickets_table();

$prefill_subject = '';
if (isset($_GET['subject'])) {
    $prefill_subject = trim((string) $_GET['subject']);
    $prefill_subject = preg_replace('/[\r\n]+/', ' ', $prefill_subject);
    if (mb_strlen($prefill_subject, 'UTF-8') > 255) {
        $prefill_subject = mb_substr($prefill_subject, 0, 255, 'UTF-8');
    }
}

if (empty($_SESSION['support_form_token'])) {
    $_SESSION['support_form_token'] = bin2hex(random_bytes(24));
}

$form_message = '';
$form_message_type = '';
$created_ticket_code = '';
$form_data = array(
    'visitor_name' => '',
    'visitor_email' => '',
    'ticket_subject' => $prefill_subject,
    'ticket_message' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['support_form_token']) ? (string) $_POST['support_form_token'] : '';
    $honeypot = isset($_POST['website']) ? trim((string) $_POST['website']) : '';

    $form_data['visitor_name'] = isset($_POST['visitor_name']) ? trim((string) $_POST['visitor_name']) : '';
    $form_data['visitor_email'] = isset($_POST['visitor_email']) ? trim((string) $_POST['visitor_email']) : '';
    $form_data['ticket_subject'] = isset($_POST['ticket_subject']) ? trim((string) $_POST['ticket_subject']) : '';
    $form_data['ticket_message'] = isset($_POST['ticket_message']) ? trim((string) $_POST['ticket_message']) : '';

    if ($token === '' || !hash_equals($_SESSION['support_form_token'], $token)) {
        $form_message_type = 'danger';
        $form_message = 'Invalid request token. Refresh the page and try again.';
    } elseif ($honeypot !== '') {
        $form_message_type = 'danger';
        $form_message = 'Invalid submission.';
    } elseif ($form_data['visitor_name'] === '' || $form_data['visitor_email'] === '' || $form_data['ticket_subject'] === '' || $form_data['ticket_message'] === '') {
        $form_message_type = 'warning';
        $form_message = 'Please fill in all required fields.';
    } elseif (!filter_var($form_data['visitor_email'], FILTER_VALIDATE_EMAIL)) {
        $form_message_type = 'warning';
        $form_message = 'Please provide a valid email address.';
    } else {
        $ticket_code = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 64) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : '';

        $insert = $mysqli->prepare("INSERT INTO support_tickets (ticket_code, visitor_name, visitor_email, ticket_subject, ticket_message, status, ip_address, user_agent, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'open', ?, ?, NOW(), NOW())");
        if ($insert) {
            $insert->bind_param(
                'sssssss',
                $ticket_code,
                $form_data['visitor_name'],
                $form_data['visitor_email'],
                $form_data['ticket_subject'],
                $form_data['ticket_message'],
                $ip_address,
                $user_agent
            );

            if ($insert->execute()) {
                $form_message_type = 'success';
                $created_ticket_code = $ticket_code;
                $form_message = 'Support ticket created successfully. Save your ticket code for reference.';
                $form_data = array(
                    'visitor_name' => '',
                    'visitor_email' => '',
                    'ticket_subject' => '',
                    'ticket_message' => ''
                );
                $_SESSION['support_form_token'] = bin2hex(random_bytes(24));
            } else {
                $form_message_type = 'danger';
                $form_message = 'Unable to create your support ticket right now. Please try again.';
            }
            $insert->close();
        } else {
            $form_message_type = 'danger';
            $form_message = 'Unable to create your support ticket right now. Please try again.';
        }
    }
}

$smarty->assign('is_support', 1);
$smarty->assign('support_form_token', $_SESSION['support_form_token']);
$smarty->assign('form_message', $form_message);
$smarty->assign('form_message_type', $form_message_type);
$smarty->assign('created_ticket_code', $created_ticket_code);
$smarty->assign('form_data', $form_data);

$smarty->assign('seo_title', 'Support Tickets - ' . $general_setting['seo_title']);
$smarty->assign('seo_keywords', 'support ticket, help, contact, missing usa');
$smarty->assign('seo_description', 'Open a support ticket and receive answers by email from Missing USA admins.');

$smarty->display('support.html');
?>