<?php
/* Custom code: FC-2026-08-13: Forever business imports and manager access administration */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Title;

defined('ALTUMCODE') || die();

class AdminForeverBusiness extends Controller {

    private function redirect_back(array $query = []): void {
        redirect('admin/forever-business' . (!empty($query) ? '?' . http_build_query(array_filter($query)) : ''));
    }

    private function staging_directory(): string {
        $directory = rtrim(UPLOADS_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'forever-business-imports' . DIRECTORY_SEPARATOR;
        if(!is_dir($directory)) {
            @mkdir($directory, 0750, true);
        }
        if(!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('Mapa za privremeni import nije dostupna za pisanje.');
        }
        return $directory;
    }

    private function handle_preview(): void {
        if(empty($_POST['preview_import'])) return;

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            $this->redirect_back();
        }

        $file = $_FILES['report_file'] ?? null;
        $root_fbo_id = forever_business_normalize_fbo_id($_POST['root_fbo_id'] ?? '');
        $root_name = input_clean($_POST['root_name'] ?? '', 160);
        $report_period = forever_business_period_from_label($_POST['report_period'] ?? '') ?: date('Y-m-01');

        if(!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            Alerts::add_error('Odaberi ispravnu CSV ili XLSX datoteku.');
            $this->redirect_back();
        }
        if((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > 15 * 1024 * 1024) {
            Alerts::add_error('Datoteka mora biti manja od 15 MB.');
            $this->redirect_back();
        }

        $original_name = mb_substr(basename((string) $file['name']), 0, 255);
        $extension = mb_strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if(!in_array($extension, ['csv', 'xlsx'], true)) {
            Alerts::add_error('Automatski import podržava CSV i XLSX. PDF se prvo mora ručno provjeriti.');
            $this->redirect_back();
        }

        try {
            $token = bin2hex(random_bytes(20));
            $path = $this->staging_directory() . $token . '.' . $extension;
            if(!move_uploaded_file($file['tmp_name'], $path)) {
                throw new \RuntimeException('Datoteku nije moguće spremiti za pregled.');
            }

            $report = forever_business_parse_report($path, $original_name, $root_fbo_id, $root_name, $report_period);
            if(!empty($report['errors'])) {
                @unlink($path);
                throw new \RuntimeException(implode(' ', array_slice($report['errors'], 0, 5)));
            }

            $_SESSION['forever_business_preview'] = [
                'token' => $token,
                'path' => $path,
                'original_name' => $original_name,
                'file_sha256' => hash_file('sha256', $path),
                'root_fbo_id' => $root_fbo_id,
                'root_name' => $root_name,
                'report_period' => $report_period,
                'summary' => $report['summary'],
                'warnings' => $report['warnings'],
                'created_at' => time(),
            ];
            Alerts::add_success('Izvještaj je provjeren. Pregledaj sažetak i potvrdi import.');
        } catch(\Throwable $exception) {
            Alerts::add_error($exception->getMessage());
        }

        $this->redirect_back();
    }

    private function handle_apply(): void {
        if(empty($_POST['apply_import'])) return;

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            $this->redirect_back();
        }

        $preview = $_SESSION['forever_business_preview'] ?? null;
        try {
            if(!$preview || !hash_equals((string) ($preview['token'] ?? ''), (string) ($_POST['preview_token'] ?? ''))) {
                throw new \RuntimeException('Pregled je istekao. Ponovno odaberi izvještaj.');
            }
            if(time() - (int) ($preview['created_at'] ?? 0) > 3600 || !is_file($preview['path'] ?? '')) {
                throw new \RuntimeException('Pregled je istekao. Ponovno odaberi izvještaj.');
            }
            if(!hash_equals((string) $preview['file_sha256'], (string) hash_file('sha256', $preview['path']))) {
                throw new \RuntimeException('Datoteka se promijenila nakon pregleda. Import je zaustavljen.');
            }

            $report = forever_business_parse_report($preview['path'], $preview['original_name'], $preview['root_fbo_id'], $preview['root_name'], $preview['report_period'] ?? '');
            $result = forever_business_import_report($report, $preview['file_sha256'], (int) $this->user->user_id);
            @unlink($preview['path']);
            unset($_SESSION['forever_business_preview']);

            Alerts::add_success($result['duplicate']
                ? 'Ova ista datoteka već je bila uvezena; podaci nisu duplicirani.'
                : 'Import je dovršen i timski pregled je osvježen.');
        } catch(\Throwable $exception) {
            Alerts::add_error($exception->getMessage());
        }

        $this->redirect_back();
    }

    public function index() {
        forever_business_ensure_tables();
        forever_business_provision_fcc_members();
        forever_business_enforce_self_only_access();

        if(!empty($_POST)) {
            $this->handle_preview();
            $this->handle_apply();
        }

        $period = forever_business_period_from_label($_GET['period'] ?? '') ?: '';
        $data = forever_business_get_admin_data((int) $this->user->user_id, $period);
        $data['preview'] = $_SESSION['forever_business_preview'] ?? null;
        $data['default_root_fbo_id'] = forever_business_extract_user_fbo_id($this->user->preferences ?? null);
        $data['default_root_name'] = (string) ($this->user->name ?? 'Glavni tim');

        Title::set('Forever poslovanje');
        $view = new \Altum\View('admin/forever-business/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
}

/* /Custom code: FC-2026-08-13 */
