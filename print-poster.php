<?php
include(__DIR__ . '/include/config.php');
include(__DIR__ . '/include/connect.php');

if (!function_exists('rss_is_remote_image_url')) {
  function rss_is_remote_image_url($value)
  {
    $value = trim((string) $value);
    if ($value === '') {
      return false;
    }
    return filter_var($value, FILTER_VALIDATE_URL) !== false;
  }
}

if (!function_exists('rss_normalize_share_image_url')) {
  function rss_normalize_share_image_url($url)
  {
    $url = trim((string) $url);
    if ($url === '') {
      return '';
    }

    $parts = @parse_url($url);
    if ($parts && isset($parts['scheme']) && strtolower($parts['scheme']) === 'http') {
      $url = 'https://' . ltrim(substr($url, 7), '/');
    }

    return $url;
  }
}

if (!function_exists('rss_extract_first_image_from_html')) {
  function rss_extract_first_image_from_html($html, $siteurl = '')
  {
    $html = (string) $html;
    if ($html === '') {
      return '';
    }

    if (!preg_match('/<img[^>]+src\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
      return '';
    }

    $src = trim((string) $m[1]);
    if ($src === '') {
      return '';
    }

    if (rss_is_remote_image_url($src)) {
      return rss_normalize_share_image_url($src);
    }

    $siteurl = rtrim((string) $siteurl, '/');
    if ($siteurl === '') {
      return '';
    }

    if ($src[0] !== '/') {
      $src = '/' . $src;
    }

    return $siteurl . $src;
  }
}

if (!($mysqli instanceof mysqli) || $mysqli->connect_errno) {
    exit('Database unavailable.');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    exit('Invalid case id.');
}

$stmt = $mysqli->prepare("SELECT id, title, details, thumbnail, datetime FROM news WHERE published='1' AND id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$article) {
    exit('Case not found.');
}

$title = html_entity_decode((string) $article['title'], ENT_QUOTES, 'UTF-8');
$details = html_entity_decode((string) $article['details'], ENT_QUOTES, 'UTF-8');
$rawDetailsHtml = (string) $article['details'];
$details = trim(strip_tags($details));
$details = preg_replace('/\s+/u', ' ', $details);
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$siteurl = $scheme . '://' . $host;
$posterUrl = $scheme . '://' . $host . '/news/' . $article['id'];

$posterImageUrl = '';
if (!empty($article['thumbnail'])) {
  if (rss_is_remote_image_url($article['thumbnail'])) {
    $posterImageUrl = rss_normalize_share_image_url($article['thumbnail']);
  } else {
    $localPath = __DIR__ . '/upload/news/' . $article['thumbnail'];
    if (is_file($localPath)) {
      $posterImageUrl = 'upload/news/' . $article['thumbnail'];
    }
  }
}

if ($posterImageUrl === '') {
  $posterImageUrl = rss_extract_first_image_from_html($rawDetailsHtml, $siteurl);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print Poster - <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f2f5f9;margin:0;padding:24px;color:#102039}
.poster-wrap{max-width:860px;margin:0 auto}
.poster-actions{display:flex;gap:10px;margin-bottom:14px}
.poster-actions button,.poster-actions a{background:#102039;color:#fff;border:0;border-radius:6px;padding:10px 14px;font-size:14px;text-decoration:none;cursor:pointer}
.poster{background:#fff;border:2px solid #102039;padding:18px}
.poster h1{margin:0 0 8px;font-size:36px;line-height:1.1}
.poster .label{display:inline-block;margin-bottom:10px;background:#d83636;color:#fff;padding:6px 10px;font-size:11px;letter-spacing:.09em;text-transform:uppercase;font-weight:700}
.poster-grid{display:grid;grid-template-columns:300px 1fr;gap:18px}
.poster-photo{background:#e8edf3;border:1px solid #d3dce8;min-height:350px;display:flex;align-items:center;justify-content:center;overflow:hidden}
.poster-photo img{width:100%;height:100%;object-fit:cover}
.poster-photo span{font-weight:700;color:#687489}
.poster-meta{font-size:14px;margin-bottom:10px}
.poster-meta strong{display:inline-block;width:110px}
.poster-text{font-size:16px;line-height:1.55;white-space:pre-wrap}
@media (max-width: 820px){.poster-grid{grid-template-columns:1fr}.poster h1{font-size:28px}}
@media print{body{background:#fff;padding:0}.poster-actions{display:none}.poster-wrap{max-width:none}.poster{border:1px solid #000;page-break-inside:avoid}}
</style>
</head>
<body>
<div class="poster-wrap">
  <div class="poster-actions">
    <button onclick="window.print();">Print Poster</button>
    <a href="news/<?php echo (int) $article['id']; ?>/<?php echo urlencode(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'))); ?>">Back to Case</a>
  </div>
  <div class="poster">
    <div class="label">Missing Child Alert</div>
    <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="poster-grid">
      <div class="poster-photo">
      <?php if (!empty($posterImageUrl)) { ?>
      <img src="<?php echo htmlspecialchars($posterImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
      <?php } else { ?>
      <span>Photo Not Available</span>
      <?php } ?>
      </div>
      <div>
        <div class="poster-meta"><strong>Case ID:</strong> #<?php echo (int) $article['id']; ?></div>
        <div class="poster-meta"><strong>Published:</strong> <?php echo date('Y-m-d H:i', (int) $article['datetime']); ?></div>
        <div class="poster-meta"><strong>Case URL:</strong> <?php echo htmlspecialchars($posterUrl, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="poster-text"><?php echo htmlspecialchars($details, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </div>
  </div>
</div>
<script>window.onload=function(){if(window.location.search.indexOf('autoprint=1')!==-1){window.print();}}</script>
</body>
</html>