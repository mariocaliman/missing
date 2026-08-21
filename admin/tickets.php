<?php
include('header.php');

ensure_support_tickets_table();

$case = !empty($_GET['case']) ? make_safe($_GET['case']) : 'open';
$allowed = array('open', 'answered', 'closed', 'view');
if (!in_array($case, $allowed, true)) {
    $case = 'open';
}

if ($case === 'view') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo notification('warning', 'Invalid ticket id.');
        include('footer.php');
        exit;
    }

    $ticket_query = $mysqli->query("SELECT * FROM support_tickets WHERE id='" . intval($id) . "' LIMIT 1");
    $ticket = $ticket_query && $ticket_query->num_rows > 0 ? $ticket_query->fetch_assoc() : 0;

    if ($ticket == 0) {
        echo notification('warning', 'Ticket not found.');
        include('footer.php');
        exit;
    }

    if (isset($_POST['save'])) {
        try {
            NoCSRF::check('ticket_token', $_POST, true, 60 * 10, false);

            $ticket_status = isset($_POST['ticket_status']) ? make_safe($_POST['ticket_status']) : 'open';
            $admin_reply = isset($_POST['admin_reply']) ? trim((string) $_POST['admin_reply']) : '';
            $send_reply_email = isset($_POST['send_reply_email']) ? 1 : 0;

            if (!in_array($ticket_status, array('open', 'answered', 'closed'), true)) {
                $message = notification('warning', 'Invalid ticket status.');
            } else {
                $email_sent = false;
                if ($send_reply_email === 1) {
                    if ($admin_reply === '') {
                        $message = notification('warning', 'Type a reply message before sending email.');
                    } else {
                        $safe_name = str_replace(array("\r", "\n"), ' ', (string) $ticket['visitor_name']);
                        $safe_email = str_replace(array("\r", "\n"), '', (string) $ticket['visitor_email']);
                        $safe_subject = str_replace(array("\r", "\n"), ' ', (string) $ticket['ticket_subject']);

                        $mail_to = $safe_email;
                        $mail_subject = '[Missing USA Support] Reply for Ticket ' . $ticket['ticket_code'];
                        $mail_body = "Hello " . $safe_name . ",\n\n";
                        $mail_body .= "We have an update for your support ticket.\n\n";
                        $mail_body .= "Ticket Code: " . $ticket['ticket_code'] . "\n";
                        $mail_body .= "Subject: " . $safe_subject . "\n\n";
                        $mail_body .= "Admin Reply:\n" . $admin_reply . "\n\n";
                        $mail_body .= "Thank you,\nMissing USA Support Team\n";

                        $mail_headers = array(
                            'MIME-Version: 1.0',
                            'Content-Type: text/plain; charset=UTF-8',
                            'From: Missing USA Support <no-reply@missing-usa.com>',
                            'Reply-To: contact@missing-usa.com'
                        );

                        $email_sent = @mail($mail_to, $mail_subject, $mail_body, implode("\r\n", $mail_headers));
                        if (!$email_sent) {
                            $message = notification('danger', 'Could not send email reply. Save still completed without email delivery.');
                        }
                    }
                }

                $final_status = $ticket_status;
                if ($email_sent && $final_status === 'open') {
                    $final_status = 'answered';
                }

                $stmt = $mysqli->prepare("UPDATE support_tickets SET status=?, admin_reply=?, updated_at=NOW(), replied_at=IF(?=1, NOW(), replied_at) WHERE id=? LIMIT 1");
                $stmt->bind_param('ssii', $final_status, $admin_reply, $email_sent, $id);
                if ($stmt->execute()) {
                    if ($email_sent) {
                        $message = notification('success', 'Ticket updated and reply email sent to visitor.');
                    } elseif (!isset($message)) {
                        $message = notification('success', 'Ticket updated successfully.');
                    }
                } else {
                    $message = notification('danger', 'Error happened while updating ticket.');
                }
                $stmt->close();

                $ticket_query = $mysqli->query("SELECT * FROM support_tickets WHERE id='" . intval($id) . "' LIMIT 1");
                $ticket = $ticket_query && $ticket_query->num_rows > 0 ? $ticket_query->fetch_assoc() : 0;
            }
        } catch (Exception $e) {
            $message = notification('danger', 'Security token error. Try again.');
        }
    }

    $ticket_token = NoCSRF::generate('ticket_token');

    echo '<div class="page-header page-heading"><h1>Ticket ' . htmlspecialchars($ticket['ticket_code'], ENT_QUOTES, 'UTF-8') . ' <a href="tickets.php" class="btn btn-default pull-right"><span class="fa fa-arrow-right"></span></a></h1></div>';
    if (isset($message)) {
        echo $message;
    }
?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>From:</strong> <?php echo htmlspecialchars($ticket['visitor_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($ticket['visitor_email'], ENT_QUOTES, 'UTF-8'); ?>)</div>
    <div class="panel-body">
        <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['ticket_subject'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Status:</strong> <span class="label label-info"><?php echo htmlspecialchars($ticket['status'], ENT_QUOTES, 'UTF-8'); ?></span></p>
        <p><strong>Created:</strong> <?php echo htmlspecialchars($ticket['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Updated:</strong> <?php echo htmlspecialchars((string) $ticket['updated_at'], ENT_QUOTES, 'UTF-8'); ?></p>
        <hr>
        <p><strong>Visitor Message</strong></p>
        <div class="well" style="white-space:pre-wrap"><?php echo htmlspecialchars($ticket['ticket_message'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
</div>
<form method="POST" action="" class="panel panel-default panel-body">
    <div class="form-group">
        <label for="ticket_status">Ticket Status</label>
        <select name="ticket_status" id="ticket_status" class="form-control">
            <option value="open" <?php if ($ticket['status'] == 'open') { echo 'selected'; } ?>>Open</option>
            <option value="answered" <?php if ($ticket['status'] == 'answered') { echo 'selected'; } ?>>Answered</option>
            <option value="closed" <?php if ($ticket['status'] == 'closed') { echo 'selected'; } ?>>Closed</option>
        </select>
    </div>
    <div class="form-group">
        <label for="admin_reply">Admin Reply</label>
        <textarea name="admin_reply" id="admin_reply" class="form-control" rows="6"><?php echo htmlspecialchars((string) $ticket['admin_reply'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        <p class="help-block">If "Send reply email" is checked, this text is sent to the visitor.</p>
    </div>
    <div class="checkbox">
        <label><input type="checkbox" name="send_reply_email" value="1"> Send reply email to visitor now</label>
    </div>
    <input type="hidden" name="ticket_token" value="<?php echo $ticket_token; ?>">
    <button type="submit" name="save" class="btn btn-primary">Save Ticket</button>
</form>
<?php
    include('footer.php');
    exit;
}

$status = $case;
$page = 1;
$size = 25;
if (isset($_GET['page'])) {
    $page = (int) $_GET['page'];
}

$count_q = $mysqli->query("SELECT id FROM support_tickets WHERE status='" . $status . "'");
$total_records = $count_q ? $count_q->num_rows : 0;
?>
<div class="page-header page-heading">
    <h1>
        <i class="fa fa-life-ring"></i> Support Tickets: <?php echo ucfirst($status); ?>
        <div class="pull-right btn-group">
            <a href="tickets.php?case=open" class="btn btn-default <?php if ($status == 'open') { echo 'active'; } ?>">Open</a>
            <a href="tickets.php?case=answered" class="btn btn-default <?php if ($status == 'answered') { echo 'active'; } ?>">Answered</a>
            <a href="tickets.php?case=closed" class="btn btn-default <?php if ($status == 'closed') { echo 'active'; } ?>">Closed</a>
        </div>
    </h1>
</div>
<?php
if ($total_records == 0) {
    echo notification('warning', 'No tickets found in this section.');
    include('footer.php');
    exit;
}

$pagination = new Pagination();
$pagination->setLink('?case=' . $status . '&page=%s');
$pagination->setPage($page);
$pagination->setSize($size);
$pagination->setTotalRecords($total_records);

$sql = "SELECT id, ticket_code, visitor_name, visitor_email, ticket_subject, status, created_at, replied_at FROM support_tickets WHERE status='" . $status . "' ORDER BY id DESC " . $pagination->getLimitSql();
$query = $mysqli->query($sql);
?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Ticket</th>
            <th class="hidden-xs">Visitor</th>
            <th class="hidden-xs">Email</th>
            <th>Status</th>
            <th class="hidden-xs">Created</th>
            <th width="90"></th>
        </tr>
    </thead>
    <tbody>
<?php while ($row = $query->fetch_assoc()) { ?>
        <tr>
            <td>
                <strong><?php echo htmlspecialchars($row['ticket_code'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                <small><?php echo htmlspecialchars($row['ticket_subject'], ENT_QUOTES, 'UTF-8'); ?></small>
            </td>
            <td class="hidden-xs"><?php echo htmlspecialchars($row['visitor_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="hidden-xs"><?php echo htmlspecialchars($row['visitor_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="label label-info"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td class="hidden-xs"><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="text-right"><a href="tickets.php?case=view&id=<?php echo (int) $row['id']; ?>" class="btn btn-xs btn-primary">View</a></td>
        </tr>
<?php } ?>
    </tbody>
</table>
<div class="news-actions">
    <div class="row">
        <div class="col-xs-12"><?php echo $pagination->create_links(); ?></div>
    </div>
</div>
<?php
include('footer.php');
?>