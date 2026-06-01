<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\TemplateEngine;
use App\Core\View;
use App\Services\PricingService;
use App\Services\RankingService;

class PublicController
{
    public function home(): void
    {
        $tenants = Database::all('SELECT name, slug FROM tenants WHERE is_active = 1 ORDER BY name');
        View::render('public/home', ['tenants' => $tenants], 'public');
    }

    public function menu(string $slug): void
    {
        $tenant = $this->tenant($slug);
        if (!$tenant) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }
        $theme = $this->theme((int) $tenant['id']);
        $discounts = Database::all('SELECT * FROM discounts WHERE tenant_id = ? AND is_active = 1', [$tenant['id']]);

        $categories = Database::all(
            'SELECT * FROM categories WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, id',
            [$tenant['id']]
        );
        $items = Database::all(
            'SELECT * FROM items WHERE tenant_id = ? ORDER BY is_featured DESC, sort_order, id',
            [$tenant['id']]
        );

        // attach computed final price + discount to each item
        foreach ($items as &$it) {
            $d = PricingService::activeDiscountFor($it, $discounts);
            $it['discount'] = $d;
            $it['final_price'] = $d ? $d['final_price'] : (int) $it['price'];
            $it['rating'] = $it['rating_count'] > 0
                ? round($it['rating_sum'] / $it['rating_count'], 1)
                : 0;
        }
        unset($it);

        // group items by category
        $byCat = [];
        foreach ($items as $it) {
            $byCat[(int) $it['category_id']][] = $it;
        }

        $popular = RankingService::popularItems((int) $tenant['id'], 8);

        // Fully custom (uploaded) template -> render via the safe engine.
        if (($theme['template'] ?? '') === 'custom' && !empty($theme['custom_html'])) {
            $context = $this->buildContext($tenant, $theme, $categories, $byCat, $popular, $slug);
            $rendered = TemplateEngine::render($theme['custom_html'], $context);
            View::render('public/custom', [
                'theme'    => $theme,
                'rendered' => $rendered,
            ], 'public');
            return;
        }

        View::render('public/menu', [
            'tenant'     => $tenant,
            'theme'      => $theme,
            'categories' => $categories,
            'byCat'      => $byCat,
            'items'      => $items,
            'popular'    => $popular,
        ], 'public');
    }

    /**
     * Build the token context handed to a custom template. Everything is
     * pre-formatted (Persian prices, image URLs, booleans) so the designer
     * only places tokens — no logic needed in the template.
     */
    private function buildContext(array $tenant, array $theme, array $categories, array $byCat, array $popular, string $slug): array
    {
        $currency = $tenant['currency'] ?: 'تومان';

        $mapItem = function (array $it) use ($currency): array {
            $hasDiscount = !empty($it['discount']);
            $final = (int) ($it['final_price'] ?? $it['price']);
            $ratingCount = (int) ($it['rating_count'] ?? 0);
            $rating = $ratingCount > 0 ? round(($it['rating_sum'] ?? 0) / $ratingCount, 1) : 0;
            return [
                'id'             => (int) $it['id'],
                'name'           => $it['name'],
                'description'    => $it['description'] ?? '',
                'image'          => !empty($it['image_path']) ? url($it['image_path']) : '',
                'price'          => money($final),
                'old_price'      => $hasDiscount ? money($it['price']) : '',
                'has_discount'   => $hasDiscount,
                'discount_label' => $hasDiscount
                    ? ($it['discount']['type'] === 'percent'
                        ? fa_digits((string) $it['discount']['value']) . '٪'
                        : 'تخفیف')
                    : '',
                'currency'       => $currency,
                'available'      => (bool) $it['is_available'],
                'sold_out'       => !$it['is_available'],
                'featured'       => !empty($it['is_featured']),
                'rating'         => $rating ? fa_digits((string) $rating) : '',
                'rating_count'   => $ratingCount ? fa_digits((string) $ratingCount) : '',
            ];
        };

        $cats = [];
        foreach ($categories as $c) {
            $list = array_map($mapItem, $byCat[(int) $c['id']] ?? []);
            if (!$list) {
                continue;
            }
            $cats[] = ['id' => (int) $c['id'], 'name' => $c['name'], 'items' => $list];
        }

        $pop = array_map($mapItem, $popular);

        return [
            'currency' => $currency,
            'slug'     => $slug,
            'csrf'     => csrf_token(),
            'register_action' => url('m/' . $slug . '/register'),
            'survey_url'      => url('m/' . $slug . '/survey'),
            'tenant'   => [
                'name'    => $tenant['name'],
                'phone'   => $tenant['phone'] ?? '',
                'address' => $tenant['address'] ?? '',
                'currency' => $currency,
            ],
            'theme'    => [
                'logo'          => !empty($theme['logo_path']) ? url($theme['logo_path']) : '',
                'hero_image'    => !empty($theme['hero_image']) ? url($theme['hero_image']) : '',
                'hero_title'    => $theme['hero_title'] ?: $tenant['name'],
                'hero_subtitle' => $theme['hero_subtitle'] ?? '',
            ],
            'has_popular' => count($pop) > 0,
            'popular'     => $pop,
            'categories'  => $cats,
        ];
    }

    public function register(string $slug): void
    {
        $tenant = $this->tenant($slug);
        if (!$tenant || !csrf_check()) {
            redirect("m/{$slug}");
        }

        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $phone = normalize_ir_phone($_POST['phone'] ?? '');
        $birth = $_POST['birthdate'] ?? '';
        $birth = $birth !== '' ? $birth : null;

        if ($first === '' || $phone === null) {
            flash('error', 'نام و شماره موبایل معتبر الزامی است.');
            redirect("m/{$slug}");
        }

        try {
            Database::run(
                'INSERT INTO customers (tenant_id, first_name, last_name, phone, birthdate)
                 VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE first_name = VALUES(first_name),
                                         last_name  = VALUES(last_name),
                                         birthdate  = VALUES(birthdate)',
                [$tenant['id'], $first, $last, $phone, $birth]
            );
            flash('success', 'ثبت‌نام شما با موفقیت انجام شد. 🎉');
        } catch (\Throwable $e) {
            flash('error', 'خطا در ثبت اطلاعات.');
        }
        redirect("m/{$slug}");
    }

    public function rate(string $slug): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $tenant = $this->tenant($slug);
        if (!$tenant || !csrf_check()) {
            echo json_encode(['ok' => false]);
            return;
        }
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $stars  = max(1, min(5, (int) ($_POST['stars'] ?? 0)));
        $item = Database::one('SELECT id FROM items WHERE id = ? AND tenant_id = ?', [$itemId, $tenant['id']]);
        if (!$item) {
            echo json_encode(['ok' => false]);
            return;
        }
        Database::insert(
            'INSERT INTO ratings (tenant_id, item_id, stars) VALUES (?,?,?)',
            [$tenant['id'], $itemId, $stars]
        );
        RankingService::recomputeItemRating($itemId);
        $agg = Database::one('SELECT rating_sum, rating_count FROM items WHERE id = ?', [$itemId]);
        $avg = $agg['rating_count'] > 0 ? round($agg['rating_sum'] / $agg['rating_count'], 1) : 0;
        echo json_encode(['ok' => true, 'avg' => $avg, 'count' => (int) $agg['rating_count']]);
    }

    public function survey(string $slug): void
    {
        $tenant = $this->tenant($slug);
        if (!$tenant) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }
        $questions = Database::all(
            'SELECT * FROM survey_questions WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, id',
            [$tenant['id']]
        );
        foreach ($questions as &$q) {
            $q['options'] = Database::all(
                'SELECT * FROM survey_options WHERE question_id = ? ORDER BY sort_order, id',
                [$q['id']]
            );
        }
        unset($q);
        View::render('public/survey', [
            'tenant'    => $tenant,
            'theme'     => $this->theme((int) $tenant['id']),
            'questions' => $questions,
        ], 'public');
    }

    public function submitSurvey(string $slug): void
    {
        $tenant = $this->tenant($slug);
        if (!$tenant || !csrf_check()) {
            redirect("m/{$slug}/survey");
        }
        $answers = $_POST['answers'] ?? [];
        $responseId = Database::insert(
            'INSERT INTO survey_responses (tenant_id) VALUES (?)',
            [$tenant['id']]
        );
        foreach ($answers as $qid => $optId) {
            Database::insert(
                'INSERT INTO survey_answers (response_id, question_id, option_id) VALUES (?,?,?)',
                [$responseId, (int) $qid, (int) $optId]
            );
        }
        flash('success', 'ممنون از وقتی که گذاشتی! 🙏');
        redirect("m/{$slug}");
    }

    // --- helpers ---

    private function tenant(string $slug): ?array
    {
        return Database::one('SELECT * FROM tenants WHERE slug = ? AND is_active = 1', [$slug]);
    }

    private function theme(int $tenantId): array
    {
        $t = Database::one('SELECT * FROM themes WHERE tenant_id = ?', [$tenantId]);
        return $t ?: [
            'template' => 'classic', 'primary_color' => '#c0392b', 'secondary_color' => '#2c3e50',
            'bg_color' => '#faf7f2', 'text_color' => '#2c3e50', 'font_family' => 'Vazirmatn',
            'logo_path' => null, 'hero_image' => null, 'hero_title' => null, 'hero_subtitle' => null,
            'custom_css' => null,
        ];
    }
}
