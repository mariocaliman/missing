<?php
function smarty_modifier_article_thumbnail($thumbnail, $source_id, $additional_class = false, $lazeload = 0, $alt_text = '')
{
    global $mysqli;

    $project_root = dirname(dirname(dirname(__DIR__)));
    $base_class = 'img-responsive';
    if (!empty($additional_class)) {
        $base_class .= ' ' . $additional_class;
    }

    $build_img = function ($relative_path, $fallback_alt) use ($project_root, $base_class, $lazeload, $alt_text) {
        $full_path = $project_root . '/' . $relative_path;
        if (!is_file($full_path)) {
            return '';
        }

        $dimensions = @getimagesize($full_path);
        $width = ($dimensions !== false && isset($dimensions[0])) ? intval($dimensions[0]) : 0;
        $height = ($dimensions !== false && isset($dimensions[1])) ? intval($dimensions[1]) : 0;
        $src = $relative_path;
        $alt = trim((string) $alt_text);
        if ($alt === '') {
            $alt = $fallback_alt;
        }

        $attrs = array(
            'src="' . $src . '"',
            'class="' . htmlspecialchars($base_class, ENT_QUOTES) . '"',
            'alt="' . htmlspecialchars($alt, ENT_QUOTES) . '"',
            'decoding="async"'
        );

        if ($width > 0) {
            $attrs[] = 'width="' . $width . '"';
        }
        if ($height > 0) {
            $attrs[] = 'height="' . $height . '"';
        }
        if ($lazeload == 1) {
            $attrs[] = 'loading="lazy"';
        } else {
            $attrs[] = 'loading="eager"';
        }

        return '<img ' . implode(' ', $attrs) . ' />';
    };

    $build_remote_img = function ($url, $fallback_alt) use ($base_class, $lazeload, $alt_text) {
        $url = trim((string) $url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $alt = trim((string) $alt_text);
        if ($alt === '') {
            $alt = $fallback_alt;
        }

        $attrs = array(
            'src="' . htmlspecialchars($url, ENT_QUOTES) . '"',
            'class="' . htmlspecialchars($base_class, ENT_QUOTES) . '"',
            'alt="' . htmlspecialchars($alt, ENT_QUOTES) . '"',
            'decoding="async"'
        );

        if ($lazeload == 1) {
            $attrs[] = 'loading="lazy"';
        } else {
            $attrs[] = 'loading="eager"';
        }

        return '<img ' . implode(' ', $attrs) . ' />';
    };

    if (!empty($thumbnail)) {
        $remote_thumb = $build_remote_img($thumbnail, 'Article thumbnail');
        if (!empty($remote_thumb)) {
            return $remote_thumb;
        }

        $thumb = $build_img('upload/news/' . $thumbnail, 'Article thumbnail');
        if (!empty($thumb)) {
            return $thumb;
        }
    }

    $sql = "SELECT thumbnail,title FROM sources WHERE id='$source_id'";
    $query = $mysqli->query($sql);
    $row = $query ? $query->fetch_assoc() : array();

    if (!empty($row['thumbnail'])) {
        $thumb = $build_img('upload/sources/' . $row['thumbnail'], !empty($row['title']) ? $row['title'] : 'Source thumbnail');
        if (!empty($thumb)) {
            return $thumb;
        }
    }

    return $build_img('upload/noimage.jpg', 'No image available');
}

?>