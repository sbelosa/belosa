<?php
if(PHP_SAPI!=='cli')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
require dirname(__DIR__).'/app/init.php';
if(getenv('FCC_LOCAL')==='1'&&(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db'))throw new RuntimeException('Expected isolated local database.');
if(function_exists('fcc_business_enabled')&&fcc_business_enabled()){try{fcc_business_retention();fcc_business_run_jobs();fcc_business_reminders();fcc_business_admin_reminders();}catch(Throwable $e){error_log('Business background: '.get_class($e));}}
// Webinar failures are isolated from the existing notification cycle.
if(function_exists('fcc_webinar_worker_enabled')&&fcc_webinar_worker_enabled()) {
 try { fcc_webinar_apply_retention(); fcc_webinar_run_jobs(); } catch(Throwable $e) { error_log('Webinar background: '.get_class($e)); }
}
if(function_exists('fcc_events_enabled')&&fcc_events_enabled()){try{fcc_events_tick();}catch(Throwable $e){error_log('Calendar background: '.get_class($e));}}
try { echo json_encode(fcc_partner_notifications_run()).PHP_EOL; } catch(Throwable $e) {error_log('Partner notifications: '.get_class($e).': '.$e->getMessage());exit(1);}
