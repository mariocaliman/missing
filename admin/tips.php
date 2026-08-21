<?php
include('header.php');

ensure_news_tips_table();

$case = !empty($_GET['case']) ? make_safe($_GET['case']) : 'pending';
$allowed = array('pending', 'approved', 'rejected', 'view');
if (!in_array($case, $allowed, true)) {
    $case = 'pending';
}

if ($case === 'view') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo notification('warning', 'Invalid tip id.');
        include('footer.php');
        exit;
    }

    if (isset($_POST['save'])) {
        try {
            NoCSRF::check('tip_token', $_POST, true, 60 * 10, false);

            $action = isset($_POST['moderation_action']) ? make_safe($_POST['moderation_action']) : '';
            $admin_note = isset($_POST['admin_note']) ? make_safe(xss_clean($_POST['admin_note'])) : '';

            if (!in_array($action, array('approved', 'rejected', 'pending', 'delete'), true)) {
                $message = notification('warning', 'Invalid moderation action.');
            } elseif ($action === 'delete') {
                $del = $mysqli->query("DELETE FROM news_tips WHERE id='" . intval($id) . "' LIMIT 1");
                if ($del) {
                    $message = notification('success', 'Tip deleted successfully.');
                } else {
                    $message = notification('danger', 'Error happened while deleting tip.');
                }
            } else {
                $stmt = $mysqli->prepare("UPDATE news_tips SET status=?, admin_note=?, reviewed_at=NOW() WHERE id=? LIMIT 1");
                $stmt->bind_param('ssi', $action, $admin_note, $id);
                if ($stmt->execute()) {
                    $message = notification('success', 'Tip updated successfully.');
                } else {
                    $message = notification('danger', 'Error happened while updating tip.');
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $message = notification('danger', 'Security token error. Try again.');
        }
    }

    $tip_token = NoCSRF::generate('tip_token');
    $sql = "SELECT t.*, n.title AS news_title FROM news_tips t LEFT JOIN news n ON n.id=t.news_id WHERE t.id='" . intval($id) . "' LIMIT 1";
    $query = $mysqli->query($sql);
    $tip = $query && $query->num_rows > 0 ? $query->fetch_assoc() : 0;

    echo '<div class="page-header page-heading"><h1>Review Tip <a href="tips.php" class="btn btn-default pull-right"><span class="fa fa-arrow-right"></span></a></h1></div>';
    if (isset($message)) { echo $message; }

    if ($tip == 0) {
        echo notification('warning', 'Tip not found.');
        include('footer.php');
        exit;
    }

    $poster_link = '../news/' . intval($tip['news_id']) . '/' . strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', html_entity_decode((string) $tip['news_title'], ENT_QUOTES, 'UTF-8')), '-'));
?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>Case:</strong> <a href="<?php echo $poster_link; ?>" target="_BLANK"><?php echo htmlspecialchars_decode($tip['news_title'], ENT_QUOTES); ?></a></div>
    <div class="panel-body">
        <p><strong>Missing Name:</strong> <?php echo htmlspecialchars($tip['missing_name'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>From:</strong> <?php echo htmlspecialchars($tip['tip_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($tip['tip_email'], ENT_QUOTES, 'UTF-8'); ?>)</p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($tip['tip_phone'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($tip['tip_location'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Status:</strong> <span class="label label-info"><?php echo htmlspecialchars($tip['status'], ENT_QUOTES, 'UTF-8'); ?></span></p>
        <p><strong>Submitted:</strong> <?php echo htmlspecialchars($tip['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
        <hr>
        <p><strong>Tip Details</strong></p>
        <div class="well" style="white-space:pre-wrap"><?php echo htmlspecialchars($tip['tip_message'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
</div>
<form method="POST" action="" class="panel panel-default panel-body">
    <div class="form-group">
        <label for="moderation_action">Moderation Action</label>
        <select name="moderation_action" id="moderation_action" class="form-control">
            <option value="pending" <?php if ($tip['status'] == 'pending') { echo 'selected'; } ?>>Pending</option>
            <option value="approved" <?php if ($tip['status'] == 'approved') { echo 'selected'; } ?>>Approve</option>
            <option value="rejected" <?php if ($tip['status'] == 'rejected') { echo 'selected'; } ?>>Reject</option>
            <option value="delete">Delete Permanently</option>
        </select>
    </div>
    <div class="form-group">
        <label for="admin_note">Admin Note</label>
        <textarea name="admin_note" id="admin_note" class="form-control" rows="4"><?php echo htmlspecialchars($tip['admin_note'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>
    <input type="hidden" name="tip_token" value="<?php echo $tip_token; ?>">
    <button type="submit" name="save" class="btn btn-primary">Save Review</button>
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

$count_q = $mysqli->query("SELECT id FROM news_tips WHERE status='" . $status . "'");
$total_records = $count_q ? $count_q->num_rows : 0;

?>
<div class="page-header page-heading">
    <h1>
        <i class="fa fa-shield"></i> Tips Moderation: <?php echo ucfirst($status); ?>
        <div class="pull-right btn-group">
            <a href="tips.php?case=pending" class="btn btn-default <?php if ($status == 'pending') { echo 'active'; } ?>">Pending</a>
            <a href="tips.php?case=approved" class="btn btn-default <?php if ($status == 'approved') { echo 'active'; } ?>">Approved</a>
            <a href="tips.php?case=rejected" class="btn btn-default <?php if ($status == 'rejected') { echo 'active'; } ?>">Rejected</a>
        </div>
    </h1>
</div>
<?php
if ($total_records == 0) {
    echo notification('warning', 'No tips found in this section.');
    include('footer.php');
    exit;
}

$pagination = new Pagination();
$pagination->setLink('?case=' . $status . '&page=%s');
$pagination->setPage($page);
$pagination->setSize($size);
$pagination->setTotalRecords($total_records);

$sql = "SELECT t.id, t.news_id, t.missing_name, t.tip_name, t.tip_email, t.status, t.created_at, n.title AS news_title FROM news_tips t LEFT JOIN news n ON n.id=t.news_id WHERE t.status='" . $status . "' ORDER BY t.id DESC " . $pagination->getLimitSql();
$query = $mysqli->query($sql);
?>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Case</th>
            <th class="hidden-xs">From</th>
            <th class="hidden-xs">Email</th>
            <th>Status</th>
            <th class="hidden-xs">Date</th>
            <th width="90"></th>
        </tr>
    </thead>
    <tbody>
<?php while ($row = $query->fetch_assoc()) { ?>
        <tr>
            <td>
                <strong><?php echo htmlspecialchars($row['missing_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                <small><?php echo htmlspecialchars_decode((string) $row['news_title'], ENT_QUOTES); ?></small>
            </td>
            <td class="hidden-xs"><?php echo htmlspecialchars($row['tip_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="hidden-xs"><?php echo htmlspecialchars($row['tip_email'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="label label-info"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td class="hidden-xs"><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="text-right"><a href="tips.php?case=view&id=<?php echo (int) $row['id']; ?>" class="btn btn-xs btn-primary">Review</a></td>
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