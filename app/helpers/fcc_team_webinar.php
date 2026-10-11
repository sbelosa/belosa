<?php
defined('ALTUMCODE') || die();

/** The administrator's shared team entrance is independent of personal link quotas. */
function fcc_team_webinar_destination(array $link, array $owner): ?string {
    if(($link['url'] ?? '') !== 'vip-edukacija' || ($link['type'] ?? '') !== 'link'
        || (int)($link['domain_id'] ?? -1) !== 0
        || (int)($owner['type'] ?? 0) !== 1 || (int)($owner['status'] ?? 0) !== 1) return null;
    $settings = json_decode($link['settings'] ?? '{}', true);
    if(!is_array($settings)) return null;
    // Respect a deliberate pause, password or schedule on the original record.
    if(!(int)($link['is_enabled'] ?? 0) && empty($settings['plan_auto_disabled'])) return null;
    if(!empty($settings['password']) || !empty($settings['sensitive_content'])
        || !empty($link['start_date']) || !empty($link['end_date'])) return null;
    $target = trim((string)($link['location_url'] ?? ''));
    if(preg_match('/[\x00-\x20\x7f]/', $target)) return null;
    $url = parse_url($target);
    $host = strtolower($url['host'] ?? '');
    if(!$url || ($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass'])
        || (isset($url['port']) && $url['port'] !== 443)
        || ($host !== 'zoom.us' && !str_ends_with($host, '.zoom.us'))
        || !preg_match('~^/(?:j|my|wc)/[^/]+~', $url['path'] ?? '')) return null;
    return $target;
}
