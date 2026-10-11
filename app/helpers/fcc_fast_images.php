<?php
defined('ALTUMCODE') || die();

/** Use a prebuilt public image derivative. Never resize during a page request. */
function fcc_fast_image_url(string $url): string {
    static $manifest, $resolved = [];
    if(isset($resolved[$url])) return $resolved[$url];
    $resolved[$url] = $url;
    // External images, referrals and signed URLs retain their original destination.
    if(!str_starts_with($url, SITE_URL)) return $url;
    $relative = substr($url, strlen(SITE_URL));
    $parts = parse_url($relative);
    if($parts === false || isset($parts['fragment']) || (isset($parts['query']) && !preg_match('/^v=[0-9]+$/D', $parts['query']))) return $url;
    $path = $parts['path'] ?? '';
    if($manifest === null) {
        $file = APP_PATH . 'config/fcc_optimized_images.json';
        $manifest = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    }
    $image = $manifest[$path] ?? null;
    if(!$image || !preg_match('~^uploads/fcc-optimized/[a-f0-9]{24}\.webp$~D', $image['path'] ?? '')) return $url;
    $original = ROOT_PATH . $path;
    $optimized = ROOT_PATH . $image['path'];
    // A new upload at the old URL must immediately bypass an older derivative.
    if(!is_file($original) || !is_file($optimized) || filesize($original) !== $image['original_bytes'] || filemtime($original) > filemtime($optimized)) return $url;
    return $resolved[$url] = SITE_URL . $image['path'];
}
