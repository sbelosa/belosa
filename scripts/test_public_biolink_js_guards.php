<?php
/* Custom code: FC-2026-08-01: regression checks for optional public biolink PWA controls */

$partial_path = dirname(__DIR__) . '/themes/altum/views/l/partials/biolink.php';
$source = file_get_contents($partial_path);

if($source === false) {
    fwrite(STDERR, "Could not load the public biolink partial.\n");
    exit(1);
}

$assertions = [
    'removed dependency on missing urlinput field' => !str_contains($source, 'getElementById("urlinput")'),
    'manifest placeholder is checked before use' => str_contains($source, 'if(manifest_placeholder)'),
    'optional home-screen control is checked before use' => str_contains($source, 'if(add_to_home_screen)'),
    'beforeinstallprompt exits when the optional control is absent' => str_contains($source, 'if(!add_to_home_screen)'),
    'service worker rejection is handled' => str_contains($source, "register('service-worker.js').catch"),
];

$failed = [];

foreach($assertions as $description => $passed) {
    if(!$passed) {
        $failed[] = $description;
    }
}

if($failed) {
    fwrite(STDERR, "Public biolink JavaScript regression check failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Public biolink JavaScript guard checks passed.\n";

/* /Custom code: FC-2026-08-01 */
