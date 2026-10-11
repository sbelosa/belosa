<?php

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Helpers\FccProductEducationCatalog;

defined('ALTUMCODE') || die();

class FccProductEducation extends Controller {
    public function index() {
        \Altum\Authentication::guard();
        $isAdmin = \Altum\Authentication::is_admin();
        if((int) ($this->user->type ?? 0) !== 0 && !$isAdmin) {
            redirect('dashboard');
        }

        \Altum\Router::$controller_settings['wrapper'] = 'app_wrapper';
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow, noarchive');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if(!in_array($method, ['GET', 'POST'], true)) {
            \Altum\Router::$controller_settings['has_view'] = false;
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        $section = $this->queryChoice('section', ['learn', 'products', 'needs', 'routines', 'favorites', 'review'], 'learn');
        if($section === 'review' && !$isAdmin) {
            throw_404();
        }

        require_once APP_PATH . 'helpers/fcc_product_education.php';
        require_once APP_PATH . 'helpers/fcc_product_education_i18n.php';
        require_once APP_PATH . 'helpers/fcc_product_education_packaging.php';
        if(is_file(APP_PATH . 'helpers/fcc_product_education_sharing.php')) {
            require_once APP_PATH . 'helpers/fcc_product_education_sharing.php';
        }
        require_once APP_PATH . 'helpers/FccProductEducationCatalog.php';
        require_once APP_PATH . 'helpers/FccProductCatalogRepository.php';

        $language = fcc_product_education_i18n_locale();
        $content = fcc_product_education_localize_content(fcc_product_education_content(), $language);
        \Altum\Title::set($content['title'] . ' · FCCA', true);
        $selectedLesson = $this->selectContent($content['lessons'], 'id', $_GET['lesson'] ?? null);
        $selectedProductKey = $_GET['product'] ?? null;
        $this->selectContent($content['products'], 'product_key', $selectedProductKey);
        if(isset($_GET['lesson']) && isset($_GET['product'])) {
            throw_404();
        }
        if($selectedLesson !== null) {
            $section = 'learn';
        }

        try {
            $preferences = $this->readPreferences((int) $this->user->user_id);
        } catch(\Throwable $exception) {
            \Altum\Router::$controller_settings['has_view'] = false;
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            error_log('FCC product education preferences unavailable: ' . get_class($exception));
            echo '<p>' . htmlspecialchars(fcc_product_education_t('Napredak trenutačno nije dostupan. Osvježi stranicu i pokušaj ponovno.', $language), ENT_QUOTES, 'UTF-8') . '</p>';
            return;
        }
        $this->refreshPreferences($preferences);
        $quizResult = null;
        $quizAnswers = [];

        if($method === 'POST') {
            if(!is_string($_POST['token'] ?? null)
                || !hash_equals((string) \Altum\Csrf::get(), $_POST['token'])) {
                http_response_code(403);
                Alerts::add_error(fcc_product_education_t('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.', $language));
            } else {
                try {
                    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
                    $result = $this->saveAction((int) $this->user->user_id, $content, $action, $_POST);
                    $preferences = $result['preferences'];
                    $this->refreshPreferences($preferences);

                    if($action === 'quiz') {
                        $selectedLesson = $this->selectContent($content['lessons'], 'id', $_POST['lesson_id'] ?? null);
                        $section = 'learn';
                        $quizResult = $result['result'];
                        $quizResult['feedback'] = array_column($quizResult['feedback'] ?? [], null, 'question_id');
                        foreach(($selectedLesson['quiz'] ?? []) as $question) {
                            $answer = $_POST['answers'][$question['id']] ?? null;
                            if(is_string($answer) && array_key_exists($answer, $question['options'])) {
                                $quizAnswers[$question['id']] = $answer;
                            }
                        }
                    } elseif($action === 'favorite') {
                        Alerts::add_success(fcc_product_education_t(!empty($result['result']['is_favorite']) ? 'Proizvod je spremljen u favorite.' : 'Proizvod je uklonjen iz favorita.', $language));
                        $query = ['section' => $section];
                        if(is_string($selectedProductKey)) {
                            $query['product'] = $selectedProductKey;
                        }
                        redirect('fcc-product-education?' . http_build_query($query));
                    }
                } catch(\InvalidArgumentException $exception) {
                    http_response_code(422);
                    Alerts::add_error(fcc_product_education_t($exception->getMessage(), $language));
                } catch(\Throwable $exception) {
                    http_response_code(503);
                    error_log('FCC product education saving failed: ' . get_class($exception));
                    Alerts::add_error(fcc_product_education_t('Spremanje trenutačno nije uspjelo. Pokušaj ponovno.', $language));
                }
            }
        }

        $catalog = new FccProductEducationCatalog();
        $products = $catalog->studyCards($content['products'], 'hr', 'hr');
        $content['packaging_review'] = ['calculated' => [], 'differences' => [], 'incomplete' => []];
        foreach($products as &$product) {
            $product['package_plans'] = fcc_product_education_package_plans($product);
            $plans = (array) ($product['package_plans']['plans'] ?? []);
            $calculated = false;
            $different = false;
            foreach($plans as $plan) {
                if(($plan['declared_daily_count'] ?? null) !== null && ($plan['days_per_pack'] ?? null) !== null) $calculated = true;
                if(!empty($plan['discrepancy']) || !empty($plan['continuous_period_blocked'])) $different = true;
            }
            $reference = ['product_key' => $product['product_key'], 'name' => $product['name'], 'plans' => $plans];
            if($calculated) $content['packaging_review']['calculated'][] = $reference;
            else $content['packaging_review']['incomplete'][] = $reference;
            if($different) $content['packaging_review']['differences'][] = $reference;
        }
        unset($product);
        if(function_exists('fcc_product_education_product_images')) {
            try {
                $products = fcc_product_education_product_images($products);
            } catch(\Throwable $exception) {
                error_log('FCC product education images unavailable: ' . get_class($exception));
            }
        }
        if(function_exists('fcc_product_education_share_targets')) {
            try {
                $products = fcc_product_education_share_targets($products, (int) $this->user->user_id);
            } catch(\Throwable $exception) {
                error_log('FCC product education sharing unavailable: ' . get_class($exception));
            }
        }
        $content['coverage'] = fcc_product_education_photo_coverage($content['coverage'], $products);
        foreach($products as &$product) {
            if(($product['education_visibility'] ?? '') === 'archive' || ($product['availability_status'] ?? '') === 'discontinued') $product['shop_share_url'] = '';
        }
        unset($product);
        $products = fcc_product_education_localize_content($products, $language);
        $selectedProduct = $this->selectContent($products, 'product_key', $selectedProductKey);
        if($selectedProduct !== null && $selectedLesson === null) {
            $section = 'products';
        }

        $selectedRecommendation = null;
        $browseQuery = [];
        if(in_array($section, ['needs', 'routines'], true)) {
            $recommendations = array_merge($content['educational_entries'], $content['published_recommendation_entries']);
            if($section === 'routines') $recommendations = array_values(array_filter($recommendations, static fn(array $entry): bool => count((array) ($entry['products'] ?? [])) > 1));
            $selectedRecommendation = $this->selectContent($recommendations, 'id', $_GET['entry'] ?? null);
            if(is_string($_GET['q'] ?? null)) $browseQuery['q'] = mb_substr(trim($_GET['q']), 0, 200);
            if(is_string($_GET['kind'] ?? null) && in_array($_GET['kind'], ['complementary', 'alternatives', 'sequence', 'single'], true)) $browseQuery['kind'] = $_GET['kind'];
            if(is_string($_GET['shown'] ?? null) && ctype_digit($_GET['shown'])) $browseQuery['shown'] = min(count($recommendations), max(12, (int) $_GET['shown']));
        }

        $data = [
            'content' => $content,
            'progress' => fcc_product_education_progress($preferences, $content),
            'products' => $products,
            'section' => $section,
            'is_admin' => $isAdmin,
            'language' => $language,
            'selected_lesson' => $selectedLesson,
            'selected_product' => $selectedLesson === null ? $selectedProduct : null,
            'selected_recommendation' => $selectedRecommendation,
            'browse_query' => $browseQuery,
            'quiz_result' => $quizResult,
            'quiz_answers' => $quizAnswers,
            'catalog_notice' => $catalog->notice(),
            'catalog_status' => $catalog->status(),
        ];

        $view = new \Altum\View('fcc-product-education/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }

    private function queryChoice(string $key, array $choices, string $default): string {
        if(!isset($_GET[$key])) {
            return $default;
        }
        if(!is_string($_GET[$key]) || !in_array($_GET[$key], $choices, true)) {
            throw_404();
        }
        return $_GET[$key];
    }

    private function selectContent(array $items, string $key, $identity): ?array {
        if($identity === null) {
            return null;
        }
        if(!is_string($identity) || strlen($identity) > 120) {
            throw_404();
        }
        foreach($items as $item) {
            if(($item[$key] ?? null) === $identity) {
                return $item;
            }
        }
        throw_404();
        return null;
    }

    private function readPreferences(int $userId): object {
        $statement = $this->query('SELECT preferences FROM users WHERE user_id=?', [$userId]);
        $row = $statement->get_result()->fetch_assoc();
        if(!$row) {
            throw_404();
        }
        return $this->decodePreferences($row['preferences'] ?? '{}');
    }

    private function decodePreferences($value): object {
        if($value === null || $value === '') {
            return new \stdClass();
        }
        $preferences = is_string($value) ? json_decode($value, false, 512, JSON_THROW_ON_ERROR) : $value;
        if(!is_object($preferences) && !is_array($preferences)) {
            throw new \RuntimeException('Invalid stored preferences.');
        }
        return fcc_product_education_preferences_object($preferences);
    }

    /** Serialize updates on the user row and apply the action to its fresh state. */
    private function saveAction(int $userId, array $content, string $action, array $input): array {
        if(!database()->begin_transaction()) {
            throw new \RuntimeException('Preferences transaction unavailable.');
        }
        try {
            $statement = $this->query('SELECT preferences,status,type FROM users WHERE user_id=? FOR UPDATE', [$userId]);
            $row = $statement->get_result()->fetch_assoc();
            if(!$row || (int) ($row['status'] ?? 0) !== 1) {
                throw new \InvalidArgumentException('Račun više nije aktivan. Osvježi stranicu.');
            }
            if((int) ($row['type'] ?? 0) !== (int) ($this->user->type ?? 0)) {
                throw new \InvalidArgumentException('Prava računa promijenila su se. Osvježi stranicu prije spremanja.');
            }
            $current = $this->decodePreferences($row['preferences'] ?? '{}');
            $result = fcc_product_education_update_preferences($current, $content, $action, $input, get_date());
            if(!$result['ok']) {
                throw new \InvalidArgumentException((string) ($result['error'] ?? 'Provjeri unesene podatke.'));
            }

            $shouldSave = $action === 'favorite' || ($action === 'quiz' && !empty($result['result']['passed']));
            if($shouldSave) {
                $encoded = json_encode($result['preferences'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $this->query('UPDATE users SET preferences=? WHERE user_id=?', [$encoded, $userId]);
            } else {
                $result['preferences'] = $current;
            }

            if(!database()->commit()) {
                throw new \RuntimeException('Preferences commit unavailable.');
            }
        } catch(\Throwable $exception) {
            database()->rollback();
            throw $exception;
        }

        if($shouldSave) {
            cache()->deleteItemsByTag('user_id=' . $userId);
            cache()->deleteItem('user?user_id=' . $userId);
        }
        return $result;
    }

    private function refreshPreferences(object $preferences): void {
        $this->user->preferences = $preferences;
        if(is_object(\Altum\Authentication::$user ?? null)) {
            \Altum\Authentication::$user->preferences = $preferences;
        }
    }

    private function query(string $sql, array $values): \mysqli_stmt {
        $statement = database()->prepare($sql);
        if(!$statement) {
            throw new \RuntimeException('Preferences storage unavailable.');
        }
        $types = '';
        foreach($values as $value) {
            $types .= is_int($value) ? 'i' : 's';
        }
        if($values && !$statement->bind_param($types, ...$values)) {
            throw new \RuntimeException('Preferences parameters unavailable.');
        }
        if(!$statement->execute()) {
            throw new \RuntimeException('Preferences operation unavailable.');
        }
        return $statement;
    }
}
