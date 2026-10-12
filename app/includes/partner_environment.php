<?php
/** Optional host-local configuration, shared by web requests and CLI workers. */
defined('ALTUMCODE') || die();
$fcc_runtime_path=dirname(rtrim(ROOT_PATH,DIRECTORY_SEPARATOR)).'/fcc-partner-runtime.php';
if(is_file($fcc_runtime_path)) {
    $fcc_runtime=require $fcc_runtime_path;
    if(!is_array($fcc_runtime)) throw new RuntimeException('Invalid Partner runtime configuration.');
    $fcc_runtime_allowed=['FCC_LOCAL','FCC_PARTNER_ENABLED','FCC_PARTNER_ROLLOUT','FCC_PARTNER_SEED_EXCLUDE_EMAILS','FCC_JOURNEY_ENABLED','FCC_JOURNEY90_ENABLED','FCC_JOURNEY_V5_ENABLED','FCC_WEBINAR_INVITES_ENABLED','FCC_WEBINAR_SECRET','FCC_BUSINESS_ENABLED','FCC_BUSINESS_SECRET','FCC_PUSH_SEND','FCC_PUSH_PUBLIC_KEY','FCC_PUSH_PRIVATE_KEY','FCC_PUSH_SUBJECT','FCC_UI_THEME','FCC_UI_THEME_PREVIEW'];
    foreach($fcc_runtime as $fcc_key=>$fcc_value) {
        if(!in_array($fcc_key,$fcc_runtime_allowed,true)||!is_scalar($fcc_value)||str_contains((string)$fcc_value,"\0")) throw new RuntimeException('Unsupported Partner runtime option.');
        putenv($fcc_key.'='.(string)$fcc_value);
    }
    unset($fcc_runtime,$fcc_runtime_allowed,$fcc_key,$fcc_value);
}
unset($fcc_runtime_path);
