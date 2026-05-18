<?php
function storedImageUrl($image_urls) {
    $image_urls = (string)$image_urls;

    if (trim($image_urls) === '') {
        return '';
    }

    $images = explode('||', $image_urls);

    foreach ($images as $image_url) {
        $image_url = trim(str_replace('\\', '/', (string)$image_url));

        if ($image_url === '') {
            continue;
        }

        if (preg_match('/^https?:\/\//i', $image_url)) {
            return $image_url;
        }

        $image_url = ltrim($image_url, '/');

        if (strpos($image_url, 'SW2/') === 0) {
            $image_url = substr($image_url, 4);
        }

        $file_name = basename($image_url);

        $candidates = [
            $image_url,
            'uploads/' . $file_name,
            'images/' . $file_name
        ];

        foreach ($candidates as $candidate) {
            $file_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);

            if (file_exists($file_path)) {
                return $candidate;
            }
        }
    }

    return '';
}
?>