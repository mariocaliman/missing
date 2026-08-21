<?php
include('header.php'); 
if (!function_exists('rss_parse_size_to_bytes')) {
function rss_parse_size_to_bytes($value) {
	$value = trim((string) $value);
	if ($value === '') {
		return 0;
	}
	$unit = strtolower(substr($value, -1));
	$number = (float) $value;
	switch ($unit) {
		case 'g':
			$number *= 1024;
			// no break
		case 'm':
			$number *= 1024;
			// no break
		case 'k':
			$number *= 1024;
	}
	return (int) $number;
}
}

if (!function_exists('rss_upload_error_message')) {
function rss_upload_error_message($error_code) {
	switch ((int) $error_code) {
		case UPLOAD_ERR_INI_SIZE:
			return 'The image exceeds the server upload limit.';
		case UPLOAD_ERR_FORM_SIZE:
			return 'The image exceeds the form upload limit.';
		case UPLOAD_ERR_PARTIAL:
			return 'The image upload was incomplete. Please try again.';
		case UPLOAD_ERR_NO_FILE:
			return 'No image was selected.';
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'Temporary upload directory is missing on server.';
		case UPLOAD_ERR_CANT_WRITE:
			return 'Server failed to write the image to disk.';
		case UPLOAD_ERR_EXTENSION:
			return 'Upload blocked by a server extension.';
		default:
			return 'Unknown upload error.';
	}
}
}

if (!function_exists('rss_manual_permalink_slug')) {
function rss_manual_permalink_slug($title) {
	$slug = strtolower(trim((string) $title));
	$slug = preg_replace('/[^a-z0-9]+/','-',$slug);
	$slug = trim($slug,'-');
	if ($slug === '') {
		$slug = 'article';
	}
	return $slug;
}
}

if (!function_exists('rss_is_editorial_category_name')) {
function rss_is_editorial_category_name($category_name) {
	$name = strtolower(trim((string) $category_name));
	$editorial = array('explained', 'case & stories', 'case & sotories', 'cases & stories', 'cases & sotories');
	return in_array($name,$editorial);
}
}

if (!function_exists('rss_is_editorial_category')) {
function rss_is_editorial_category($category_id, $general) {
	$category_id = intval($category_id);
	if ($category_id <= 0 || !$general || !method_exists($general,'category')) {
		return false;
	}
	$category = $general->category($category_id);
	if ($category == 0 || !isset($category['category'])) {
		return false;
	}
	return rss_is_editorial_category_name($category['category']);
}
}

if (!function_exists('rss_middle_image_dir')) {
function rss_middle_image_dir() {
	return '../upload/news/middle/';
}
}

if (!function_exists('rss_find_middle_image')) {
function rss_find_middle_image($article_id) {
	$article_id = intval($article_id);
	if ($article_id <= 0) {
		return '';
	}
	$dir = rss_middle_image_dir();
	if (!is_dir($dir)) {
		return '';
	}
	$matches = glob($dir.$article_id.'_*');
	if (!$matches || count($matches) === 0) {
		return '';
	}
	return basename($matches[0]);
}
}

if (!function_exists('rss_delete_middle_image')) {
function rss_delete_middle_image($article_id) {
	$article_id = intval($article_id);
	if ($article_id <= 0) {
		return;
	}
	$dir = rss_middle_image_dir();
	if (!is_dir($dir)) {
		return;
	}
	$matches = glob($dir.$article_id.'_*');
	if ($matches) {
		foreach ($matches as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
	}
}
}

if (!function_exists('rss_store_middle_image')) {
function rss_store_middle_image($file, $article_id, $max_bytes, &$error_message = '') {
	$article_id = intval($article_id);
	if ($article_id <= 0) {
		$error_message = 'Invalid article id for middle image.';
		return '';
	}
	if (!isset($file['name']) || trim((string) $file['name']) === '') {
		return '';
	}
	$upload_status = isset($file['error']) ? intval($file['error']) : UPLOAD_ERR_OK;
	if ($upload_status !== UPLOAD_ERR_OK) {
		$error_message = rss_upload_error_message($upload_status);
		return '';
	}
	if (!isset($file['size']) || intval($file['size']) > intval($max_bytes)) {
		$error_message = 'Middle image must be up to 12MB.';
		return '';
	}
	$info = @getimagesize($file['tmp_name']);
	$allow_webp = defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : -1;
	if ($info === FALSE) {
		$error_message = 'Invalid middle image file.';
		return '';
	}
	if (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG) && ($info[2] !== $allow_webp)) {
		$error_message = 'Middle image format must be GIF, JPG, PNG, or WEBP.';
		return '';
	}
	$dir = rss_middle_image_dir();
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	if (!is_dir($dir)) {
		$error_message = 'Could not create middle image directory.';
		return '';
	}
	rss_delete_middle_image($article_id);
	$filenameWebp = $article_id . '_' . time() . '_' . mt_rand(1000,9999) . '.webp';
	$targetWebp = $dir . $filenameWebp;
	$saved = false;
	if (function_exists('imagewebp')) {
		$mime = isset($info['mime']) ? $info['mime'] : '';
		$image = false;
		if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
			$image = @imagecreatefromjpeg($file['tmp_name']);
		} elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
			$image = @imagecreatefrompng($file['tmp_name']);
		} elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
			$image = @imagecreatefromgif($file['tmp_name']);
		} elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
			$image = @imagecreatefromwebp($file['tmp_name']);
		}
		if ($image !== false) {
			if (function_exists('imagepalettetotruecolor')) {
				@imagepalettetotruecolor($image);
			}
			imagealphablending($image, true);
			imagesavealpha($image, true);
			$saved = @imagewebp($image, $targetWebp, 82);
			imagedestroy($image);
		}
	}
	if ($saved) {
		return $filenameWebp;
	}
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if ($ext === '') {
		$ext = 'jpg';
	}
	$fallbackName = $article_id . '_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
	$fallbackTarget = $dir . $fallbackName;
	if (!move_uploaded_file($file['tmp_name'], $fallbackTarget)) {
		$error_message = 'Could not save middle image file.';
		return '';
	}
	return $fallbackName;
}
}

if (!function_exists('rss_generate_article_with_ai')) {
function rss_generate_article_with_ai($topic, $word_count, $language, $required_terms_links, &$error_message = '') {
	global $ai_config;
	global $general;

	$ai_options = array();
	if ($general && method_exists($general,'get_options')) {
		$ai_options = $general->get_options('AI');
	}

	$api_key = getenv('OPENAI_API_KEY');
	if (($api_key === false || trim($api_key) === '') && isset($ai_options['openai_api_key'])) {
		$api_key = (string) $ai_options['openai_api_key'];
	}
	if (($api_key === false || trim($api_key) === '') && isset($ai_config['api_key'])) {
		$api_key = (string) $ai_config['api_key'];
	}
	if ($api_key === false || trim($api_key) === '') {
		$error_message = 'API key is not configured. Set it in Settings > APIs, OPENAI_API_KEY, or include/config.php.';
		return false;
	}

	$model = getenv('OPENAI_MODEL');
	if (($model === false || trim($model) === '') && isset($ai_options['openai_model'])) {
		$model = (string) $ai_options['openai_model'];
	}
	if (($model === false || trim($model) === '') && isset($ai_config['model'])) {
		$model = (string) $ai_config['model'];
	}
	if ($model === false || trim($model) === '') {
		$model = 'gpt-4o-mini';
	}

	$endpoint = getenv('OPENAI_BASE_URL');
	if (($endpoint === false || trim($endpoint) === '') && isset($ai_options['openai_base_url'])) {
		$endpoint = (string) $ai_options['openai_base_url'];
	}
	if (($endpoint === false || trim($endpoint) === '') && isset($ai_config['base_url'])) {
		$endpoint = (string) $ai_config['base_url'];
	}
	if ($endpoint === false || trim($endpoint) === '') {
		$endpoint = 'https://api.openai.com/v1/chat/completions';
	}

	$topic = trim((string) $topic);
	$word_count = max(200, min(2500, intval($word_count)));
	$language = trim((string) $language);
	$required_terms_links = trim((string) $required_terms_links);
	if ($language === '') {
		$language = 'Portuguese';
	}
	$link_instructions = '';
	if ($required_terms_links !== '') {
		$link_instructions = 'You MUST include the following required words and links naturally in the article body. For each URL, include a clickable HTML link (<a href="...">text</a>) exactly as requested when possible. Required list: ' . $required_terms_links;
	}

	$messages = array(
		array(
			'role' => 'system',
			'content' => 'You are a professional newsroom writer. Return only valid JSON using UTF-8 with keys: title and html. The html must follow an Explained-style reading structure with a short opening paragraph, 3 to 5 informative sections using h2 headings, rich multi-paragraph body text, and a concise closing paragraph. Do not include markdown fences.'
		),
		array(
			'role' => 'user',
			'content' => 'Write an informative article in ' . $language . ' about: ' . $topic . '. Target length: about ' . $word_count . ' words. Use an Explained-style editorial structure for reading pages: engaging intro, clear h2 section headings, deep but readable paragraphs, and a brief conclusion. ' . $link_instructions . ' Return JSON only.'
		)
	);

	$payload = array(
		'model' => $model,
		'messages' => $messages,
		'temperature' => 0.7
	);

	$ch = curl_init($endpoint);
	if ($ch === false) {
		$error_message = 'Could not initialize cURL.';
		return false;
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer ' . $api_key
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($ch, CURLOPT_TIMEOUT, 90);

	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	$http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
	curl_close($ch);

	if ($response === false) {
		$error_message = 'AI request failed: ' . $curl_error;
		return false;
	}

	$data = json_decode($response, true);
	if (!is_array($data)) {
		$error_message = 'AI returned an invalid response.';
		return false;
	}

	if ($http_code >= 400) {
		$api_error = '';
		if (isset($data['error']['message'])) {
			$api_error = $data['error']['message'];
		}
		$error_message = 'AI API error (' . $http_code . '): ' . $api_error;
		return false;
	}

	$content = '';
	if (isset($data['choices'][0]['message']['content'])) {
		$content = (string) $data['choices'][0]['message']['content'];
	}
	if ($content === '') {
		$error_message = 'AI did not return content.';
		return false;
	}

	$content = trim($content);
	if (strpos($content, '```') === 0) {
		$content = preg_replace('/^```(?:json)?\s*/i', '', $content);
		$content = preg_replace('/\s*```\s*$/', '', $content);
	}

	$json = json_decode($content, true);
	if (!is_array($json)) {
		return array(
			'title' => $topic,
			'html' => nl2br(htmlspecialchars_decode($content, ENT_QUOTES))
		);
	}

	$title = isset($json['title']) ? trim((string) $json['title']) : $topic;
	$html = isset($json['html']) ? trim((string) $json['html']) : '';
	if ($title === '') {
		$title = $topic;
	}
	if ($html === '') {
		$error_message = 'AI returned empty article body.';
		return false;
	}

	return array(
		'title' => $title,
		'html' => $html
	);
}
}

if (!function_exists('rss_ai_base_root')) {
function rss_ai_base_root($endpoint) {
	$endpoint = trim((string) $endpoint);
	if ($endpoint === '') {
		return '';
	}
	$endpoint = preg_replace('#/chat/completions/?$#i', '', $endpoint);
	$endpoint = preg_replace('#/images/generations/?$#i', '', $endpoint);
	return rtrim($endpoint, '/');
}
}

if (!function_exists('rss_generate_image_with_ai')) {
function rss_generate_image_with_ai($prompt, &$error_message = '') {
	global $ai_config;
	global $general;

	$ai_options = array();
	if ($general && method_exists($general,'get_options')) {
		$ai_options = $general->get_options('AI');
	}

	$api_key = getenv('OPENAI_API_KEY');
	if (($api_key === false || trim($api_key) === '') && isset($ai_options['openai_api_key'])) {
		$api_key = (string) $ai_options['openai_api_key'];
	}
	if (($api_key === false || trim($api_key) === '') && isset($ai_config['api_key'])) {
		$api_key = (string) $ai_config['api_key'];
	}
	if ($api_key === false || trim($api_key) === '') {
		$error_message = 'API key is not configured.';
		return false;
	}

	$image_model = getenv('OPENAI_IMAGE_MODEL');
	if (($image_model === false || trim($image_model) === '') && isset($ai_options['openai_image_model'])) {
		$image_model = (string) $ai_options['openai_image_model'];
	}
	if (($image_model === false || trim($image_model) === '') && isset($ai_config['image_model'])) {
		$image_model = (string) $ai_config['image_model'];
	}
	if ($image_model === false || trim($image_model) === '') {
		$image_model = 'gpt-image-1';
	}

	$endpoint = getenv('OPENAI_IMAGE_ENDPOINT');
	if (($endpoint === false || trim($endpoint) === '') && isset($ai_options['openai_image_endpoint'])) {
		$endpoint = (string) $ai_options['openai_image_endpoint'];
	}
	if (($endpoint === false || trim($endpoint) === '') && isset($ai_config['image_base_url'])) {
		$endpoint = (string) $ai_config['image_base_url'];
	}
	if ($endpoint === false || trim($endpoint) === '') {
		$chat_endpoint = '';
		if (isset($ai_options['openai_base_url'])) {
			$chat_endpoint = (string) $ai_options['openai_base_url'];
		}
		if ($chat_endpoint === '' && isset($ai_config['base_url'])) {
			$chat_endpoint = (string) $ai_config['base_url'];
		}
		$root = rss_ai_base_root($chat_endpoint);
		if ($root === '') {
			$root = 'https://api.openai.com/v1';
		}
		$endpoint = $root . '/images/generations';
	}

	$payload = array(
		'model' => $image_model,
		'prompt' => trim((string) $prompt),
		'size' => '1024x1024'
	);

	$ch = curl_init($endpoint);
	if ($ch === false) {
		$error_message = 'Could not initialize cURL for image generation.';
		return false;
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer ' . $api_key
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);

	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	$http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
	curl_close($ch);

	if ($response === false) {
		$error_message = 'Image request failed: ' . $curl_error;
		return false;
	}

	$data = json_decode($response, true);
	if (!is_array($data)) {
		$error_message = 'Image API returned an invalid response.';
		return false;
	}

	if ($http_code >= 400) {
		$api_error = '';
		if (isset($data['error']['message'])) {
			$api_error = $data['error']['message'];
		}
		$error_message = 'Image API error (' . $http_code . '): ' . $api_error;
		return false;
	}

	if (isset($data['data'][0]['b64_json']) && trim((string) $data['data'][0]['b64_json']) !== '') {
		$binary = base64_decode((string) $data['data'][0]['b64_json'], true);
		if ($binary !== false && strlen($binary) > 0) {
			return $binary;
		}
	}

	if (isset($data['data'][0]['url']) && trim((string) $data['data'][0]['url']) !== '') {
		$image_url = trim((string) $data['data'][0]['url']);
		$ch = curl_init($image_url);
		if ($ch !== false) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_TIMEOUT, 90);
			$binary = curl_exec($ch);
			curl_close($ch);
			if ($binary !== false && strlen($binary) > 0) {
				return $binary;
			}
		}
	}

	$error_message = 'Image API returned no image data.';
	return false;
}
}

if (!function_exists('rss_save_generated_news_image')) {
function rss_save_generated_news_image($binary, $prefix, &$error_message = '', $article_id = 0, $is_middle = false) {
	$binary = (string) $binary;
	if ($binary === '') {
		$error_message = 'Generated image bytes are empty.';
		return '';
	}

	if ($is_middle) {
		$dir = rss_middle_image_dir();
		if ($article_id > 0) {
			rss_delete_middle_image($article_id);
		}
	} else {
		$dir = '../upload/news/';
	}

	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	if (!is_dir($dir)) {
		$error_message = 'Could not create image directory.';
		return '';
	}

	$base_name = $prefix . '_' . time() . '_' . mt_rand(1000,9999);
	if ($is_middle && $article_id > 0) {
		$base_name = intval($article_id) . '_' . time() . '_' . mt_rand(1000,9999);
	}

	if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
		$error_message = 'Server does not support WebP conversion for generated images.';
		return '';
	}

	$image = @imagecreatefromstring($binary);
	if ($image === false) {
		$error_message = 'Could not decode generated image for WebP conversion.';
		return '';
	}

	if (function_exists('imagepalettetotruecolor')) {
		@imagepalettetotruecolor($image);
	}
	imagealphablending($image, true);
	imagesavealpha($image, true);
	$filename = $base_name . '.webp';
	$target = $dir . $filename;
	$saved = @imagewebp($image, $target, 82);
	imagedestroy($image);
	if ($saved) {
		return $filename;
	}

	$error_message = 'Could not save generated image as WebP.';
	return '';
}
}

$max_article_image_bytes = 12 * 1024 * 1024;
$post_max_bytes = rss_parse_size_to_bytes(ini_get('post_max_size'));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']) && ((int) $_SERVER['CONTENT_LENGTH'] > 0)) {
	$message = notification('danger','Your submission is too large for the server limit (post_max_size=' . ini_get('post_max_size') . '). Please reduce image size or increase server limits.');
}
if (!empty($_GET['case'])) {
$case = make_safe($_GET['case']);	
} else {
$case = '';	
}
switch ($case) {
case 'add':
$form_title = isset($_POST['title']) ? (string) $_POST['title'] : '';
$form_details = isset($_POST['details']) ? (string) $_POST['details'] : '';
$form_category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$form_published = isset($_POST['published']) ? 1 : 0;
$ai_topic = isset($_POST['ai_topic']) ? trim((string) $_POST['ai_topic']) : '';
$ai_words = isset($_POST['ai_words']) ? intval($_POST['ai_words']) : 700;
$ai_language = isset($_POST['ai_language']) ? trim((string) $_POST['ai_language']) : 'Portuguese';
$ai_required_terms_links = isset($_POST['ai_required_terms_links']) ? trim((string) $_POST['ai_required_terms_links']) : '';
$ai_image_prompt = isset($_POST['ai_image_prompt']) ? trim((string) $_POST['ai_image_prompt']) : '';
$ai_generate_images = isset($_POST['ai_generate_images']) ? 1 : 0;
if ($ai_words <= 0) {
$ai_words = 700;
}
if ($ai_language === '') {
$ai_language = 'Portuguese';
}
if (isset($_POST['generate_ai'])) {
try
{
NoCSRF::check('news_token', $_POST, true, 60*10, true );
if ($ai_topic === '') {
$message = notification('warning','Type a subject before generating.');
} else {
$ai_error = '';
$ai_article = rss_generate_article_with_ai($ai_topic,$ai_words,$ai_language,$ai_required_terms_links,$ai_error);
if ($ai_article === false) {
$message = notification('danger','AI generation failed: '.htmlspecialchars($ai_error,ENT_QUOTES));
} else {
$form_title = $ai_article['title'];
$form_details = $ai_article['html'];
$message = notification('success','Draft generated. Review and click Save to publish.');
}
}
}
catch ( Exception $e )
{
echo $e->getMessage() . ' Form ignored.';
}
}
if (isset($_POST['submit'])) {
try
{
NoCSRF::check('news_token', $_POST, true, 60*10, false );
$title = make_safe(xss_clean(htmlspecialchars($_POST['title'],ENT_QUOTES)));
$details = htmlspecialchars($_POST['details'],ENT_QUOTES);
$category_id = make_safe(xss_clean(intval($_POST['category_id'])));
if (isset($_POST['published'])) {
$published = make_safe(xss_clean(intval($_POST['published'])));	
} else {
$published = 0;	
}
if (empty($title)) {
$message = notification('warning','Insert The Title Please.');	
} elseif (empty($details)) {
$message = notification('warning','Write Some Details Please.');	
} elseif (empty($category_id)) {
$message = notification('warning','Choose a Category Please.');	
} else {
$upload_error = '';
if (!empty($_FILES['thumbnail']['name'])) {
$upload_status = isset($_FILES['thumbnail']['error']) ? intval($_FILES['thumbnail']['error']) : UPLOAD_ERR_OK;
if ($upload_status !== UPLOAD_ERR_OK) {
$upload_error = rss_upload_error_message($upload_status);
} elseif (!isset($_FILES['thumbnail']['size']) || intval($_FILES['thumbnail']['size']) > $max_article_image_bytes) {
$upload_error = 'Image must be up to 12MB.';
} else {
$info = @getimagesize($_FILES['thumbnail']['tmp_name']);
$allow_webp = defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : -1;
if ($info === FALSE) {
$upload_error = 'Invalid image file.';
} elseif (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG) && ($info[2] !== $allow_webp)) {
$upload_error = 'Only GIF, JPG, PNG, and WEBP images are allowed.';
} else {
$up = new fileDir('../upload/news/');
$thumbnail = $up->upload($_FILES['thumbnail']);
if ($thumbnail === 'File could not be uploaded') {
$upload_error = 'Upload failed while moving the image on server.';
}
}
}
} else {
$thumbnail = '';
}
$title_size = mb_strlen($title,'UTF-8');
if ($title_size > 500) {
$message = notification('warning','Title is too long. Maximum supported size is 500 characters.');
} elseif (!empty($upload_error)) {
$message = notification('warning',$upload_error);
} else {
$is_editorial_category = rss_is_editorial_category($category_id,$general);
	$generated_thumbnail_error = '';
	$generated_middle_error = '';
	$generated_middle_bytes = '';
	if ($ai_generate_images == 1 && $thumbnail === '') {
		$image_subject = $ai_image_prompt;
		if ($image_subject === '') {
			$image_subject = htmlspecialchars_decode($title,ENT_QUOTES);
		}
		if ($image_subject === '') {
			$image_subject = $ai_topic;
		}
		if ($image_subject === '') {
			$image_subject = 'missing person awareness in the United States';
		}

		$cover_prompt = 'Create a photorealistic editorial cover image about: ' . $image_subject . '. No text, no logos, no watermarks.';
		$cover_bytes = rss_generate_image_with_ai($cover_prompt, $generated_thumbnail_error);
		if ($cover_bytes !== false) {
			$saved_thumbnail = rss_save_generated_news_image($cover_bytes, 'ai-cover', $generated_thumbnail_error, 0, false);
			if ($saved_thumbnail !== '') {
				$thumbnail = $saved_thumbnail;
			}
		}

		if ($is_editorial_category) {
			$middle_prompt = 'Create a second photorealistic supporting image about: ' . $image_subject . '. Different scene from the cover, no text, no logos, no watermarks.';
			$middle_bytes = rss_generate_image_with_ai($middle_prompt, $generated_middle_error);
			if ($middle_bytes !== false) {
				$generated_middle_bytes = $middle_bytes;
			}
		}
	}

$datetime = time();
$day = date('j');
$month = date('n');
$year = date('Y');	
$permalink = 'manual-' . rss_manual_permalink_slug(htmlspecialchars_decode($title,ENT_QUOTES)) . '-' . $datetime . '-' . mt_rand(1000,9999);
$sql = "INSERT INTO news (title,details,permalink,source_id,category_id,thumbnail,datetime,hits,published,day,month,year) VALUES ('$title','$details','$permalink','0','$category_id','$thumbnail','$datetime','0','$published','$day','$month','$year')";
$query = $mysqli->query($sql);
if ($query) {
$article_id = intval($mysqli->insert_id);
$middle_image_error = '';
if ($is_editorial_category && !empty($_FILES['middle_image']['name'])) {
	rss_store_middle_image($_FILES['middle_image'],$article_id,$max_article_image_bytes,$middle_image_error);
}
if ($is_editorial_category && empty($_FILES['middle_image']['name']) && $generated_middle_bytes !== '') {
	rss_save_generated_news_image($generated_middle_bytes, 'ai-middle', $middle_image_error, $article_id, true);
}
if (!$is_editorial_category && !empty($_FILES['middle_image']['name'])) {
	$middle_image_error = 'Middle image was ignored because this category is not Explained/Cases & Stories.';
}
	$warnings = array();
	if ($middle_image_error !== '') {
		$warnings[] = 'middle image was not saved: ' . htmlspecialchars($middle_image_error,ENT_QUOTES);
	}
	if ($generated_thumbnail_error !== '') {
		$warnings[] = 'cover image generation failed: ' . htmlspecialchars($generated_thumbnail_error,ENT_QUOTES);
	}
	if ($generated_middle_error !== '') {
		$warnings[] = 'middle image generation failed: ' . htmlspecialchars($generated_middle_error,ENT_QUOTES);
	}
	if (count($warnings) > 0) {
		$message = notification('warning','Article added, but ' . implode(' | ', $warnings));
	} else {
		$message = notification('success','Article Added Successfully.');
	}
} else {
$message = notification('danger','Error while saving article: '.htmlspecialchars($mysqli->error,ENT_QUOTES));	
}
}
}
}
catch ( Exception $e )
{
echo $e->getMessage() . ' Form ignored.';
}
}
$news_token = NoCSRF::generate('news_token');
?>
			<div class="page-header page-heading">
				<h1>Add Article
				<a href="news.php" class="btn btn-default  pull-right"><span class="fa fa-arrow-right"></span></a>
				</h1>
			</div>
			<?php if (isset($message)) {echo $message;} ?>
		<form role="form" method="POST" action="" enctype="multipart/form-data">
		  <div class="alert alert-info">
		  <b>AI Writer:</b> enter a subject and target word count, click <b>Generate Draft</b>, review, then save.<br>
		  Configure API in <b>Settings &gt; APIs</b> (recommended) or via <code>OPENAI_API_KEY</code>. You can also auto-generate cover and middle images on save.
		  </div>
		  <div class="row">
		  <div class="col-md-7">
			<div class="form-group">
			  <label for="ai_topic">AI Subject</label>
			  <input type="text" class="form-control" name="ai_topic" id="ai_topic" value="<?php echo htmlspecialchars($ai_topic,ENT_QUOTES); ?>" placeholder="Ex: Impacto da IA no jornalismo local" />
			</div>
		  </div>
		  <div class="col-md-3">
			<div class="form-group">
			  <label for="ai_words">Word Count</label>
			  <input type="number" class="form-control" name="ai_words" id="ai_words" min="200" max="2500" step="50" value="<?php echo intval($ai_words); ?>" />
			</div>
		  </div>
		  <div class="col-md-2">
			<div class="form-group">
			  <label for="ai_language">Language</label>
			  <select class="form-control" name="ai_language" id="ai_language">
				<option value="Portuguese" <?php if ($ai_language == 'Portuguese') {echo 'SELECTED';} ?>>Portuguese</option>
				<option value="English" <?php if ($ai_language == 'English') {echo 'SELECTED';} ?>>English</option>
				<option value="Spanish" <?php if ($ai_language == 'Spanish') {echo 'SELECTED';} ?>>Spanish</option>
				<option value="French" <?php if ($ai_language == 'French') {echo 'SELECTED';} ?>>French</option>
			  </select>
			</div>
		  </div>
		  <div class="col-md-2">
			<div class="form-group" style="margin-top:24px;">
			  <button type="submit" name="generate_ai" class="btn btn-info btn-block">Generate Draft</button>
			</div>
		  </div>
		  </div>
		  <div class="form-group">
			<label for="ai_required_terms_links">Required Words and Links</label>
			<textarea class="form-control" name="ai_required_terms_links" id="ai_required_terms_links" rows="4" placeholder="One item per line. Examples:&#10;Artificial Intelligence&#10;https://example.com|Official source&#10;Miami" ><?php echo htmlspecialchars($ai_required_terms_links,ENT_QUOTES); ?></textarea>
			<p class="help-block">Optional: words and URLs that must appear in the generated article.</p>
		  </div>
		  <div class="form-group">
			<label for="ai_image_prompt">AI Image Subject (optional)</label>
			<input type="text" class="form-control" name="ai_image_prompt" id="ai_image_prompt" value="<?php echo htmlspecialchars($ai_image_prompt,ENT_QUOTES); ?>" placeholder="Ex: child safety awareness in Texas" />
			<p class="help-block">Used for generating cover and middle image with AI on Save. If empty, article title or AI subject is used.</p>
			<div style="margin-top:8px;">
				<button type="button" class="btn btn-default btn-xs" id="suggest-image-subject">Suggest from title/context</button>
			</div>
		  </div>
		  <div class="form-group">
			<input type="checkbox" name="ai_generate_images" id="ai_generate_images" value="1" <?php if ($ai_generate_images == 1) {echo 'CHECKED';} ?> /> <span class="checkbox-label">Generate Cover + Middle Image with AI when saving</span>
		  </div>
		  <div class="form-group">
			<label for="category">Title <span>*</span></label>
			<input type="text" class="form-control" name="title" id="title" value="<?php echo htmlspecialchars($form_title,ENT_QUOTES); ?>" />
		  </div>
		  <div class="form-group">
			<label for="category_id">Category <span>*</span></label>
			<select class="form-control" name="category_id" id="category_id">
			<?php 
			$categories = $general->categories('category_order ASC');
			foreach ($categories AS $category) {
			?>
			<option value="<?php echo $category['id']; ?>" <?php if ($form_category_id == intval($category['id'])) {echo 'SELECTED';} ?>><?php echo $category['category']; ?></option>
			<?php			
			}
			?>
			</select>
		  </div>
		  <div class="form-group">
			<label for="category_id">Image</label>
			<div class="fileinput fileinput-new input-group" data-provides="fileinput">
			  <div class="form-control" data-trigger="fileinput"><i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span></div>
			  <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span><span class="fileinput-exists">Change</span><input type="file" name="thumbnail"></span>
			  <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
			</div>
			</div>
		  <div class="form-group" id="middle-image-group">
			<label for="middle_image">Middle Image (Explained/Cases &amp; Stories only)</label>
			<div class="fileinput fileinput-new input-group" data-provides="fileinput">
			  <div class="form-control" data-trigger="fileinput"><i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span></div>
			  <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span><span class="fileinput-exists">Change</span><input type="file" name="middle_image"></span>
			  <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
			</div>
			<p class="help-block">This image is inserted in the middle of the article body on editorial categories.</p>
		  </div>
		  <div class="form-group">
			<label for="details">Details</label>
			<textarea class="form-control wysiwyg" name="details" id="details" rows="15" ><?php echo htmlspecialchars($form_details,ENT_QUOTES); ?></textarea>
		  </div>
		  <div class="form-group">
			<input type="checkbox" name="published" id="published" value="1" <?php if ($form_published == 1) {echo 'CHECKED';} ?> /> <span class="checkbox-label">Publish Article ?</span>
		  </div>
			<input type="hidden" name="news_token" id="news_token" value="<?php echo $news_token; ?>" />
		  <button type="submit" name="submit" class="btn btn-primary">Save</button>
		</form>
<script type="text/javascript">
(function(){
	function isEditorialCategory(label) {
		var t = (label || '').toLowerCase();
		return t.indexOf('explained') !== -1 || t.indexOf('case & stories') !== -1 || t.indexOf('cases & stories') !== -1 || t.indexOf('case & sotories') !== -1 || t.indexOf('cases & sotories') !== -1;
	}
	function toggleMiddleImageField() {
		var select = document.getElementById('category_id');
		var group = document.getElementById('middle-image-group');
		if (!select || !group) {
			return;
		}
		var text = '';
		if (select.options && select.selectedIndex >= 0) {
			text = select.options[select.selectedIndex].text || '';
		}
		group.style.display = isEditorialCategory(text) ? 'block' : 'none';
	}
	function getSuggestedImageSubject() {
		var aiTopic = document.getElementById('ai_topic');
		var title = document.getElementById('title');
		var topicValue = aiTopic ? (aiTopic.value || '').trim() : '';
		var titleValue = title ? (title.value || '').trim() : '';
		return topicValue || titleValue || 'missing person awareness in the United States';
	}
	function refreshImageSubjectHint() {
		var imageSubject = document.getElementById('ai_image_prompt');
		if (!imageSubject) {
			return;
		}
		var suggested = getSuggestedImageSubject();
		imageSubject.placeholder = 'Suggested: ' + suggested;
	}
	document.addEventListener('DOMContentLoaded', function(){
		var select = document.getElementById('category_id');
		var imageSubject = document.getElementById('ai_image_prompt');
		var suggestButton = document.getElementById('suggest-image-subject');
		var titleField = document.getElementById('title');
		var aiTopicField = document.getElementById('ai_topic');
		if (select) {
			select.addEventListener('change', toggleMiddleImageField);
		}
		if (suggestButton && imageSubject) {
			suggestButton.addEventListener('click', function(){
				imageSubject.value = getSuggestedImageSubject();
				imageSubject.focus();
			});
		}
		if (titleField) {
			titleField.addEventListener('input', refreshImageSubjectHint);
		}
		if (aiTopicField) {
			aiTopicField.addEventListener('input', refreshImageSubjectHint);
		}
		refreshImageSubjectHint();
		toggleMiddleImageField();
	});
})();
</script>
<?php
break;
case 'edit':
$id = abs(intval(make_safe(xss_clean($_GET['id']))));
if (isset($_POST['submit'])) {
try
{
NoCSRF::check('news_token', $_POST, true, 60*10, false );
$title = make_safe(xss_clean(htmlspecialchars($_POST['title'],ENT_QUOTES)));
$details = htmlspecialchars($_POST['details'],ENT_QUOTES);
$category_id = make_safe(xss_clean(intval($_POST['category_id'])));
if (isset($_POST['published'])) {
$published = make_safe(xss_clean(intval($_POST['published'])));	
} else {
$published = 0;	
}	
if (empty($title)) {
$message = notification('warning','Insert The Title Please.');	
} elseif (empty($details)) {
$message = notification('warning','Write Some Details Please.');	
} elseif (empty($category_id)) {
$message = notification('warning','Choose a Category Please.');	
} else {
$upload_error = '';
if (!empty($_FILES['thumbnail']['name'])) {
$upload_status = isset($_FILES['thumbnail']['error']) ? intval($_FILES['thumbnail']['error']) : UPLOAD_ERR_OK;
if ($upload_status !== UPLOAD_ERR_OK) {
$upload_error = rss_upload_error_message($upload_status);
$thumbnail = make_safe(xss_clean($_POST['old_thumbnail']));
} elseif (!isset($_FILES['thumbnail']['size']) || intval($_FILES['thumbnail']['size']) > $max_article_image_bytes) {
$upload_error = 'Image must be up to 12MB.';
$thumbnail = make_safe(xss_clean($_POST['old_thumbnail']));
} else {
$info = @getimagesize($_FILES['thumbnail']['tmp_name']);
$allow_webp = defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : -1;
if ($info === FALSE) {
$upload_error = 'Invalid image file.';
$thumbnail = make_safe(xss_clean($_POST['old_thumbnail']));
} elseif (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG) && ($info[2] !== $allow_webp)) {
$upload_error = 'Only GIF, JPG, PNG, and WEBP images are allowed.';
$thumbnail = make_safe(xss_clean($_POST['old_thumbnail']));
} else {
$up = new fileDir('../upload/news/');
$thumbnail = $up->upload($_FILES['thumbnail']);
if ($thumbnail === 'File could not be uploaded') {
$upload_error = 'Upload failed while moving the image on server.';
$thumbnail = make_safe(xss_clean($_POST['old_thumbnail']));
} else {
$up->delete("$_POST[old_thumbnail]");
}
}
}
} else {
$thumbnail = make_safe(xss_clean($_POST['old_thumbnail']));
}
$title_size = mb_strlen($title,'UTF-8');
if ($title_size > 500) {
$message = notification('warning','Title is too long. Maximum supported size is 500 characters.');
} elseif (!empty($upload_error)) {
$message = notification('warning',$upload_error);
} else {
$sql = "UPDATE news SET title='$title',details='$details',category_id='$category_id',thumbnail='$thumbnail',published='$published' WHERE id='$id'";
$query = $mysqli->query($sql);
if ($query) {
$is_editorial_category = rss_is_editorial_category($category_id,$general);
$middle_image_error = '';
if ($is_editorial_category && !empty($_FILES['middle_image']['name'])) {
	rss_store_middle_image($_FILES['middle_image'],$id,$max_article_image_bytes,$middle_image_error);
}
if (!$is_editorial_category) {
	rss_delete_middle_image($id);
	if (!empty($_FILES['middle_image']['name'])) {
		$middle_image_error = 'Middle image was ignored because this category is not Explained/Cases & Stories.';
	}
}
if ($middle_image_error !== '') {
$message = notification('warning','Article edited, but middle image was not saved: '.htmlspecialchars($middle_image_error,ENT_QUOTES));
} else {
$message = notification('success','Article Edited Successfully.');
}
} else {
$message = notification('danger','Error while saving article: '.htmlspecialchars($mysqli->error,ENT_QUOTES));	
}
}
}
}
catch ( Exception $e )
{
echo $e->getMessage() . ' Form ignored.';
}
}
$news_token = NoCSRF::generate('news_token');
$news = $general->news($id);
$middle_image = rss_find_middle_image($id);
?>
			<div class="page-header page-heading">
				<h1>Edit Article
				<a href="news.php" class="btn btn-default  pull-right"><span class="fa fa-arrow-right"></span></a>
				</h1>
			</div>
			<?php if (isset($message)) {echo $message;} ?>
		<form role="form" method="POST" action="" enctype="multipart/form-data">
		  <div class="form-group">
			<label for="category">Title <span>*</span></label>
			<input type="text" class="form-control" name="title" id="title" value="<?php echo htmlspecialchars_decode($news['title'],ENT_QUOTES); ?>" />
		  </div>
		  <div class="form-group">
			<label for="category_id">Category <span>*</span></label>
			<select class="form-control" name="category_id" id="category_id">
			<?php 
			$categories = $general->categories('category_order ASC');
			foreach ($categories AS $category) {
			?>
			<option value="<?php echo $category['id']; ?>" <?php if ($news['category_id'] == $category['id']) {echo 'SELECTED';} ?>><?php echo $category['category']; ?></option>
			<?php			
			}
			?>
			</select>
		  </div>
		  <div class="form-group">
			<label for="category_id">Image</label>
			<div class="fileinput fileinput-new input-group" data-provides="fileinput">
			  <div class="form-control" data-trigger="fileinput"><i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span></div>
			  <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span><span class="fileinput-exists">Change</span><input type="file" name="thumbnail"></span>
			  <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
			</div>
			<?php if (!empty($news['thumbnail'])) { ?>
			<p><a href="javascript:void();" class="delete-image" id="<?php echo $news['id']; ?>" data-toggle="tooltip" data-placement="top" title="Delete Image"><span class="fa fa-close"></span></a> Current Image : <a href="javascript:void();" data-toggle="popover" data-placement="top" title="Current Image" data-content="<img src='../upload/news/<?php echo $news['thumbnail']; ?>' class='img-responsive' />"><?php echo $news['thumbnail']; ?></a></p>
			<?php } ?>
			</div>
		  <div class="form-group" id="middle-image-group">
			<label for="middle_image">Middle Image (Explained/Cases &amp; Stories only)</label>
			<div class="fileinput fileinput-new input-group" data-provides="fileinput">
			  <div class="form-control" data-trigger="fileinput"><i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span></div>
			  <span class="input-group-addon btn btn-default btn-file"><span class="fileinput-new">Select file</span><span class="fileinput-exists">Change</span><input type="file" name="middle_image"></span>
			  <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
			</div>
			<?php if (!empty($middle_image)) { ?>
			<p>Current Middle Image : <a href="javascript:void();" data-toggle="popover" data-placement="top" title="Current Middle Image" data-content="<img src='../upload/news/middle/<?php echo $middle_image; ?>' class='img-responsive' />"><?php echo $middle_image; ?></a></p>
			<?php } ?>
			<p class="help-block">If you upload a new one, the old middle image is replaced.</p>
		  </div>
		  <div class="form-group">
			<label for="details">Details</label>
			<textarea class="wysiwyg form-control" name="details" id="details" rows="15" ><?php echo htmlspecialchars_decode($news['details'],ENT_QUOTES); ?></textarea>
		  </div>
		  <div class="form-group">
			<input type="checkbox" name="published" id="published" value="1" <?php if ($news['published'] == 1) {echo 'CHECKED';} ?> /> <span class="checkbox-label">Publish Article ?</span>
		  </div>
		  <input type="hidden" name="old_thumbnail" value="<?php echo $news['thumbnail']; ?>" />
			<input type="hidden" name="news_token" id="news_token" value="<?php echo $news_token; ?>" />
		  <button type="submit" name="submit" class="btn btn-primary">Save</button>
		</form>
<script type="text/javascript">
(function(){
	function isEditorialCategory(label) {
		var t = (label || '').toLowerCase();
		return t.indexOf('explained') !== -1 || t.indexOf('case & stories') !== -1 || t.indexOf('cases & stories') !== -1 || t.indexOf('case & sotories') !== -1 || t.indexOf('cases & sotories') !== -1;
	}
	function toggleMiddleImageField() {
		var select = document.getElementById('category_id');
		var group = document.getElementById('middle-image-group');
		if (!select || !group) {
			return;
		}
		var text = '';
		if (select.options && select.selectedIndex >= 0) {
			text = select.options[select.selectedIndex].text || '';
		}
		group.style.display = isEditorialCategory(text) ? 'block' : 'none';
	}
	document.addEventListener('DOMContentLoaded', function(){
		var select = document.getElementById('category_id');
		if (select) {
			select.addEventListener('change', toggleMiddleImageField);
		}
		toggleMiddleImageField();
	});
})();
</script>
<?php
break;
case 'delete':
$id = abs(intval(make_safe(xss_clean($_GET['id']))));
if (isset($_POST['unpublish'])) {
$delete = $mysqli->query("UPDATE news SET published='0' WHERE id='$id'");
if ($delete) {
$message = notification('success','Article Have Been Unpublished Successfully.');
$done = true;
} else {
$message = notification('danger','Error Happened.');
}
}
if (isset($_POST['delete'])) {
$sql = "SELECT * FROM news WHERE id='$id'";
$query = $mysqli->query($sql);
if ($query->num_rows > 0) {
$row = $query->fetch_assoc();
if (!empty($row['thumbnail']) AND file_exists('../upload/news/'.$row['thumbnail'])) {
@unlink('../upload/news/'.$row['thumbnail']);	
}
rss_delete_middle_image($id);
}
$delete = $mysqli->query("DELETE FROM news WHERE id='$id'");
if ($delete) {
$message = notification('success','Article Deleted Successfully.');
$done = true;
} else {
$message = notification('danger','Error Happened.');
}
}
$news = $general->news($id);
?>
			<div class="page-header page-heading">
				<h1>Delete Article
				<a href="news.php" class="btn btn-default pull-right"><span class="fa fa-arrow-right"></span></a>
				</h1>
			</div>
			<?php if (isset($message)) {echo $message;} ?>
		  <form role="form" method="POST" action="">
		  <?php if (!isset($done)) { ?>
			<div class="alert alert-warning">You Can Either <b>Unpublish</b> or <b>Delete</b> the Article : <b><?php echo htmlspecialchars_decode($news['title'],ENT_QUOTES); ?></b>. If you Choose to Delete you Can't Undo this Action Later.</div>
		  <?php } ?>
		  <?php if (isset($done)) { ?>
		  <a href="news.php" class="btn btn-default">Back To News</a>
		  <?php } else { ?>
		  <button type="submit" name="unpublish" class="btn btn-warning">Unpublish</button>
		  <button type="submit" name="delete" class="btn btn-danger">Permanent Delete</button>
		  <?php } ?>
		</form>
<?php
break;
case 'search':
$q = make_safe(xss_clean($_GET['q']));
$published = intval(make_safe(xss_clean($_GET['published'])));
?>
<div class="page-header page-heading">
	<h1 class="row"><div class="col-md-6"><i class="fa fa-search"></i> Search For <?php echo $q; ?> In <?php if ($published == 1) {echo 'Published';} else {echo 'Deleted';} ?> News</div>
	<div class="col-md-6">
	<div class="pull-right search-form">
	<form method="GET" action="news.php">
		<div class="input-group">
		  <input type="hidden" name="case" value="search" />
		  <input type="hidden" name="published" value="<?php echo $published; ?>" />
		  <input type="text" name="q" class="form-control" placeholder="Search" value="<?php echo $q; ?>" />
		  <span class="input-group-addon"><button class="btn-link"><span class="fa fa-search"></span></button></span>
		</div>
	</form>
	</div>
	<a href="news.php?case=add" class="btn btn-success pull-right" data-toggle="tooltip" data-placement="top" title="Add New Article"><span class="fa fa-plus"></span></a>
	<a href="news.php?case=deleted" class="btn btn-danger pull-right" data-toggle="tooltip" data-placement="top" title="Deleted News"><span class="fa fa-trash"></span></a>
	<a href="news.php" class="btn btn-default pull-right" data-toggle="tooltip" data-placement="top" title="Published News"><span class="fa fa-newspaper-o"></span></a>
	</div>
	</h1>
</div>
<?php
if (isset($message)) {echo $message;}
$page = 1;
$size = 20;
if (isset($_GET['page'])){ $page = (int) $_GET['page']; }
$sqls = "SELECT * FROM news WHERE published='$published' AND title LIKE '%$q%' ORDER BY id DESC";
$query = $mysqli->query($sqls);
$total_records = $query->num_rows;
if ($total_records == 0) {
echo notification('warning','There Are No Results.');
} else {
$pagination = new Pagination();
$pagination->setLink("?case=search&published=$published&page=%s&q=$q");
$pagination->setPage($page);
$pagination->setSize($size);
$pagination->setTotalRecords($total_records);
$get = "SELECT * FROM news WHERE published='$published' AND title LIKE '%$q%' ORDER BY id DESC ".$pagination->getLimitSql();
$q = $mysqli->query($get);
?>
<table width="100%" cellpadding="5" cellspacing="0" class="table table-striped">
    <thead>
        <tr>
			<th>Title</th>
			<th class="hidden-xs">Category</th>
			<th class="hidden-xs">Source</th>
			<th class="hidden-xs">Publish Date</th>
            <th width="80"></th>
        </tr>
    </thead>
	<tbody>
<?php 
while ($row = $q->fetch_assoc()) {
?>
		<tr>
			<td><?php if (!empty($row['thumbnail'])) { ?><span class="fa fa-photo has-image"></span><?php } ?><?php echo htmlspecialchars_decode($row['title'],ENT_QUOTES); ?></td>
			<td class="hidden-xs"><a href="news.php?case=category&id=<?php echo $row['category_id']; ?>"><?php echo get_category($row['category_id']); ?></a></td>
			<td class="hidden-xs"><a href="news.php?case=source&id=<?php echo $row['source_id']; ?>"><?php echo get_source($row['source_id']); ?></a></td>
			<td class="hidden-xs"><?php echo date('Y-n-j h:i a',$row['datetime']); ?></td>
			<td align="right">
				<a class="btn btn-default btn-xs" href="news.php?case=edit&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
				<a class="btn btn-danger btn-xs" href="news.php?case=delete&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-close"></span></a>
			</td>
		</tr>
<?php
}
?>
	</tbody>
</table>
<div class="news-actions">
<div class="row">
<div class="col-xs-12"><?php echo $pagination->create_links(); ?></div>
</div>
</div>
<?php
}		
break;
case 'category':
$id = intval(make_safe(xss_clean($_GET['id'])));
if (isset($_POST['delete']) AND isset($_POST['id'])) {
	$ids = $_POST['id'];
	$count= count($ids);
	for($i=0;$i<$count;$i++){
	$del_id = $ids[$i];
	$sql = "UPDATE news SET published='0' WHERE id='$del_id'";
	$res = $mysqli->query($sql);
	if ($res) {
	$message = notification('success','The Selected News Was Deleted Successfully.');
	} else {
	$message = notification('error','Error Happened');
	}
	}
}
$category = $general->category($id);
?>
<div class="page-header page-heading">
	<h1><i class="fa fa-reorder"></i> News About <?php echo $category['category']; ?></h1>
</div>
<?php
if (isset($message)) {echo $message;}
$page = 1;
$size = 20;
if (isset($_GET['page'])){ $page = (int) $_GET['page']; }
$sqls = "SELECT * FROM news WHERE published='1' AND category_id='$id' ORDER BY id DESC";
$query = $mysqli->query($sqls);
$total_records = $query->num_rows;
if ($total_records == 0) {
echo notification('warning','There Are No Published News About '.$category['category'].'.');
} else {
$pagination = new Pagination();
$pagination->setLink("?case=category&id=$id&page=%s");
$pagination->setPage($page);
$pagination->setSize($size);
$pagination->setTotalRecords($total_records);
$get = "SELECT * FROM news WHERE published='1' AND category_id='$id' ORDER BY id DESC ".$pagination->getLimitSql();
$q = $mysqli->query($get);
?>
<form role="form" method="POST" action="">
<table width="100%" cellpadding="5" cellspacing="0" class="table table-striped">
    <thead>
        <tr>
			<th width="15"><input type="checkbox" class="parentCheckBox" /></th>
			<th>Title</th>
			<th class="hidden-xs">Source</th>
			<th class="hidden-xs">Publish Date</th>
            <th width="80"></th>
        </tr>
    </thead>
	<tbody>
<?php 
while ($row = $q->fetch_assoc()) {
?>
		<tr>
			<td><input type="checkbox" name="id[]" class="childCheckBox" value="<?php echo $row['id']; ?>" /></td>
			<td><?php if (!empty($row['thumbnail'])) { ?><span class="fa fa-photo has-image"></span><?php } ?><?php echo htmlspecialchars_decode($row['title'],ENT_QUOTES); ?></td>
			<td class="hidden-xs"><a href="news.php?case=source&id=<?php echo $row['source_id']; ?>"><?php echo get_source($row['source_id']); ?></a></td>
			<td class="hidden-xs"><?php echo date('Y-n-j h:i a',$row['datetime']); ?></td>
			<td align="right">
				<a class="btn btn-default btn-xs" href="news.php?case=edit&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
				<a class="btn btn-danger btn-xs" href="news.php?case=delete&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-close"></span></a>
			</td>
		</tr>
<?php
}
?>
	</tbody>
</table>
<div class="news-actions">
<div class="row">
<div class="col-sm-3 col-md-4">
<button type="submit" name="delete" class="btn btn-danger"><span class="fa fa-trash"></span> Delete</button>
</div>
<div class="col-sm-9 col-md-8"><?php echo $pagination->create_links(); ?></div>
</div>
</div>
</form>
<?php
}	
break;
case 'source':
$id = intval(make_safe(xss_clean($_GET['id'])));
if (isset($_POST['delete']) AND isset($_POST['id'])) {
	$ids = $_POST['id'];
	$count= count($ids);
	for($i=0;$i<$count;$i++){
	$del_id = $ids[$i];
	$sql = "UPDATE news SET published='0' WHERE id='$del_id'";
	$res = $mysqli->query($sql);
	if ($res) {
	$message = notification('success','The Selected News Was Deleted Successfully.');
	} else {
	$message = notification('error','Error Happened');
	}
	}
}
$source = $general->source($id);
?>
<div class="page-header page-heading">
	<h1><i class="fa fa-rss"></i> <?php if ($id == 0) { echo 'Private News'; } else { echo 'News From '.$source['title']; } ?></h1>
</div>
<?php
if (isset($message)) {echo $message;}
$page = 1;
$size = 20;
if (isset($_GET['page'])){ $page = (int) $_GET['page']; }
$sqls = "SELECT * FROM news WHERE published='1' AND source_id='$id' ORDER BY id DESC";
$query = $mysqli->query($sqls);
$total_records = $query->num_rows;
if ($total_records == 0) {
echo notification('warning','There Are No Published News From '.$source['title'].'.');
} else {
$pagination = new Pagination();
$pagination->setLink("?case=source&id=$id&page=%s");
$pagination->setPage($page);
$pagination->setSize($size);
$pagination->setTotalRecords($total_records);
$get = "SELECT * FROM news WHERE published='1' AND source_id='$id' ORDER BY id DESC ".$pagination->getLimitSql();
$q = $mysqli->query($get);
?>
<form role="form" method="POST" action="">
<table width="100%" cellpadding="5" cellspacing="0" class="table table-striped">
    <thead>
        <tr>
			<th width="15"><input type="checkbox" class="parentCheckBox" /></th>
			<th>Title</th>
			<th class="hidden-xs">Category</th>
			<th class="hidden-xs">Publish Date</th>
            <th width="80"></th>
        </tr>
    </thead>
	<tbody>
<?php 
while ($row = $q->fetch_assoc()) {
?>
		<tr>
			<td><input type="checkbox" name="id[]" class="childCheckBox" value="<?php echo $row['id']; ?>" /></td>
			<td><?php if (!empty($row['thumbnail'])) { ?><span class="fa fa-photo has-image"></span><?php } ?><?php echo htmlspecialchars_decode($row['title'],ENT_QUOTES); ?></td>
			<td class="hidden-xs"><a href="news.php?case=category&id=<?php echo $row['category_id']; ?>"><?php echo get_category($row['category_id']); ?></a></td>			
			<td class="hidden-xs"><?php echo date('Y-n-j h:i a',$row['datetime']); ?></td>
			<td align="right">
				<a class="btn btn-default btn-xs" href="news.php?case=edit&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
				<a class="btn btn-danger btn-xs" href="news.php?case=delete&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-close"></span></a>
			</td>
		</tr>
<?php
}
?>
	</tbody>
</table>
<div class="news-actions">
<div class="row">
<div class="col-sm-3 col-md-4">
<button type="submit" name="delete" class="btn btn-danger"><span class="fa fa-trash"></span> Delete</button>
</div>
<div class="col-sm-9 col-md-8"><?php echo $pagination->create_links(); ?></div>
</div>
</div>
</form>
<?php
}	
break;
case 'deleted':
if (isset($_POST['restore']) AND isset($_POST['id'])) {
	$ids = $_POST['id'];
	$count= count($ids);
	for($i=0;$i<$count;$i++){
	$del_id = $ids[$i];
	$sql = "UPDATE news SET published='1' WHERE id='$del_id'";
	$res = $mysqli->query($sql);
	if ($res) {
	$message = notification('success','The Selected News Was Restored Successfully.');
	} else {
	$message = notification('error','Error Happened');
	}
	}
}
if (isset($_POST['delete']) AND isset($_POST['id'])) {
	$ids = $_POST['id'];
	$count= count($ids);
	for($i=0;$i<$count;$i++){
	$del_id = $ids[$i];
	$sql = "SELECT id,thumbnail FROM news WHERE id='$del_id'";
	$query = $mysqli->query($sql);
	$row = $query->fetch_assoc();
		if (file_exists('../upload/news/'.$row['thumbnail'])) {
			@unlink('../upload/news/'.$row['thumbnail']);
		}
	rss_delete_middle_image($del_id);
	$delete = $mysqli->query("DELETE FROM news WHERE id='$del_id'");
	if ($delete) {
	$message = notification('success','The Selected News Was Deleted Permanently.');
	} else {
	$message = notification('error','Error Happened');
	}
	}
}
?>
<div class="page-header page-heading">
	<h1 class="row"><div class="col-md-6"><i class="fa fa-trash"></i> Deleted News</div>
	<div class="col-md-6">
	<div class="pull-right search-form">
	<form method="GET" action="news.php">
		<div class="input-group">
		  <input type="hidden" name="case" value="search" />
		  <input type="hidden" name="published" value="0" />
		  <input type="text" name="q" class="form-control" placeholder="Search">
		  <span class="input-group-addon"><button class="btn-link"><span class="fa fa-search"></span></button></span>
		</div>
	</form>
	</div>
	<a href="news.php?case=add" class="btn btn-success pull-right" data-toggle="tooltip" data-placement="top" title="Add New Article"><span class="fa fa-plus"></span></a>
	<a href="news.php" class="btn btn-default pull-right" data-toggle="tooltip" data-placement="top" title="Published News"><span class="fa fa-newspaper-o"></span></a>
	</div>
	</h1>
</div>
<?php
if (isset($message)) {echo $message;}
$page = 1;
$size = 20;
if (isset($_GET['page'])){ $page = (int) $_GET['page']; }
$sqls = "SELECT * FROM news WHERE published='0' ORDER BY id DESC";
$query = $mysqli->query($sqls);
$total_records = $query->num_rows;
if ($total_records == 0) {
echo notification('warning','There Are No Deleted News.');
} else {
$pagination = new Pagination();
$pagination->setLink("?case=deleted&page=%s");
$pagination->setPage($page);
$pagination->setSize($size);
$pagination->setTotalRecords($total_records);
$get = "SELECT * FROM news WHERE published='0' ORDER BY id DESC ".$pagination->getLimitSql();
$q = $mysqli->query($get);
?>
<form role="form" method="POST" action="">
<table width="100%" cellpadding="5" cellspacing="0" class="table table-striped">
    <thead>
        <tr>
			<th width="15"><input type="checkbox" class="parentCheckBox" /></th>
			<th>Title</th>
			<th class="hidden-xs">Category</th>
			<th class="hidden-xs">Source</th>
			<th class="hidden-xs">Publish Date</th>
            <th width="80"></th>
        </tr>
    </thead>
	<tbody>
<?php 
while ($row = $q->fetch_assoc()) {
?>
		<tr>
			<td><input type="checkbox" name="id[]" class="childCheckBox" value="<?php echo $row['id']; ?>" /></td>
			<td><?php if (!empty($row['thumbnail'])) { ?><span class="fa fa-photo has-image"></span><?php } ?><?php echo htmlspecialchars_decode($row['title'],ENT_QUOTES); ?></td>
			<td class="hidden-xs"><a href="news.php?case=category&id=<?php echo $row['category_id']; ?>"><?php echo get_category($row['category_id']); ?></a></td>
			<td class="hidden-xs"><a href="news.php?case=source&id=<?php echo $row['source_id']; ?>"><?php echo get_source($row['source_id']); ?></a></td>
			<td class="hidden-xs"><?php echo date('Y-n-j h:i a',$row['datetime']); ?></td>
			<td align="right">
				<a class="btn btn-default btn-xs" href="news.php?case=edit&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
				<a class="btn btn-danger btn-xs" href="news.php?case=delete&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-close"></span></a>
			</td>
		</tr>
<?php
}
?>
	</tbody>
</table>
<div class="news-actions">
<div class="row">
<div class="col-sm-3 col-md-4">
<button type="submit" name="restore" class="btn btn-success"><span class="fa fa-refresh"></span> Restore</button>
<button type="submit" name="delete" class="btn btn-danger"><span class="fa fa-trash"></span> Permanent Delete</button>
</div>
<div class="col-sm-9 col-md-8"><?php echo $pagination->create_links(); ?></div>
</div>
</div>
</form>
<?php
}		
break;
default:
if (isset($_POST['delete']) AND isset($_POST['id'])) {
	$ids = $_POST['id'];
	$count= count($ids);
	for($i=0;$i<$count;$i++){
	$del_id = $ids[$i];
	$sql = "UPDATE news SET published='0' WHERE id='$del_id'";
	$res = $mysqli->query($sql);
	if ($res) {
	$message = notification('success','The Selected News Was Deleted Successfully.');
	} else {
	$message = notification('error','Error Happened');
	}
	}
}
?>
<div class="page-header page-heading">
	<h1 class="row"><div class="col-md-6"><i class="fa fa-newspaper-o"></i> Published News</div>
	<div class="col-md-6">
	<div class="pull-right search-form">
	<form method="GET" action="news.php">
		<div class="input-group">
		  <input type="hidden" name="case" value="search" />
		  <input type="hidden" name="published" value="1" />
		  <input type="text" name="q" class="form-control" placeholder="Search">
		  <span class="input-group-addon"><button class="btn-link"><span class="fa fa-search"></span></button></span>
		</div>
	</form>
	</div>
	<a href="news.php?case=add" class="btn btn-success pull-right" data-toggle="tooltip" data-placement="top" title="Add New Article"><span class="fa fa-plus"></span></a>
	<a href="news.php?case=deleted" class="btn btn-danger pull-right" data-toggle="tooltip" data-placement="top" title="Deleted News"><span class="fa fa-trash"></span></a>
	</div>
	</h1>
</div>
<?php
if (isset($message)) {echo $message;}
$page = 1;
$size = 20;
if (isset($_GET['page'])){ $page = (int) $_GET['page']; }
$sqls = "SELECT * FROM news WHERE published='1' ORDER BY id DESC";
$query = $mysqli->query($sqls);
$total_records = $query->num_rows;
if ($total_records == 0) {
echo notification('warning','There Are No Published News.');
} else {
$pagination = new Pagination();
$pagination->setLink("?page=%s");
$pagination->setPage($page);
$pagination->setSize($size);
$pagination->setTotalRecords($total_records);
$get = "SELECT * FROM news WHERE published='1' ORDER BY id DESC ".$pagination->getLimitSql();
$q = $mysqli->query($get);
?>
<form role="form" method="POST" action="">
<table width="100%" cellpadding="5" cellspacing="0" class="table table-striped">
    <thead>
        <tr>
			<th width="15"><input type="checkbox" class="parentCheckBox" /></th>
			<th>Title</th>
			<th class="hidden-xs">Category</th>
			<th class="hidden-xs">Source</th>
			<th class="hidden-xs">Hits</th>
			<th class="hidden-xs">Publish Date</th>
            <th width="80"></th>
        </tr>
    </thead>
	<tbody>
<?php 
while ($row = $q->fetch_assoc()) {
?>
		<tr>
			<td><input type="checkbox" name="id[]" class="childCheckBox" value="<?php echo $row['id']; ?>" /></td>
			<td><?php if (!empty($row['thumbnail'])) { ?><span class="fa fa-photo has-image"></span><?php } ?><?php echo htmlspecialchars_decode($row['title'],ENT_QUOTES); ?></td>
			<td class="hidden-xs"><a href="news.php?case=category&id=<?php echo $row['category_id']; ?>"><?php echo get_category($row['category_id']); ?></a></td>
			<td class="hidden-xs"><a href="news.php?case=source&id=<?php echo $row['source_id']; ?>"><?php echo get_source($row['source_id']); ?></a></td>
			<td class="hidden-xs"><?php echo $row['hits']; ?></td>
			<td class="hidden-xs"><?php echo date('Y-n-j h:i a',$row['datetime']); ?></td>
			<td align="right">
				<a class="btn btn-default btn-xs" href="news.php?case=edit&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Edit"><span class="fa fa-edit"></span></a>
				<a class="btn btn-danger btn-xs" href="news.php?case=delete&id=<?php echo $row['id']; ?>" data-toggle="tooltip" data-placement="top" title="Delete"><span class="fa fa-close"></span></a>
			</td>
		</tr>
<?php
}
?>
	</tbody>
</table>
<div class="news-actions">
<div class="row">
<div class="col-sm-2 col-md-3"><button type="submit" name="delete" class="btn btn-danger"><span class="fa fa-trash"></span> Delete</button></div>
<div class="col-sm-10 col-md-9"><?php echo $pagination->create_links(); ?></div>
</div>
</div>
</form>
<?php
} 
} 
include('footer.php');
?>