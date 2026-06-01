<?php
/**
 * Front controller. Point your web server's document root here.
 * All requests are rewritten to this file by .htaccess.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Controllers\PublicController;
use App\Controllers\BotController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\ItemController;
use App\Controllers\Admin\PricingController;
use App\Controllers\Admin\DiscountController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\CampaignController;
use App\Controllers\Admin\SurveyController;
use App\Controllers\Admin\IntegrationController;
use App\Controllers\Admin\ThemeController;

$router = new Router();

// -------- Public site --------
$router->get('/', [PublicController::class, 'home']);
$router->get('/m/{slug}', [PublicController::class, 'menu']);
$router->post('/m/{slug}/register', [PublicController::class, 'register']);
$router->post('/m/{slug}/rate', [PublicController::class, 'rate']);
$router->get('/m/{slug}/survey', [PublicController::class, 'survey']);
$router->post('/m/{slug}/survey', [PublicController::class, 'submitSurvey']);

// -------- Bot webhooks (Telegram / Bale) --------
$router->post('/bot/{channel}/{tenant}/{secret}', [BotController::class, 'webhook']);

// -------- Admin auth --------
$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->get('/admin/logout', [AuthController::class, 'logout']);

// -------- Admin dashboard --------
$router->get('/admin', [DashboardController::class, 'index']);

$router->get('/admin/categories', [CategoryController::class, 'index']);
$router->post('/admin/categories', [CategoryController::class, 'store']);
$router->post('/admin/categories/{id}/update', [CategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [CategoryController::class, 'destroy']);

$router->get('/admin/items', [ItemController::class, 'index']);
$router->get('/admin/items/create', [ItemController::class, 'create']);
$router->post('/admin/items', [ItemController::class, 'store']);
$router->get('/admin/items/{id}/edit', [ItemController::class, 'edit']);
$router->post('/admin/items/{id}/update', [ItemController::class, 'update']);
$router->post('/admin/items/{id}/toggle', [ItemController::class, 'toggle']);
$router->post('/admin/items/{id}/feature', [ItemController::class, 'feature']);
$router->post('/admin/items/{id}/delete', [ItemController::class, 'destroy']);

$router->get('/admin/pricing', [PricingController::class, 'index']);
$router->post('/admin/pricing/shift', [PricingController::class, 'shift']);

$router->get('/admin/discounts', [DiscountController::class, 'index']);
$router->post('/admin/discounts', [DiscountController::class, 'store']);
$router->post('/admin/discounts/{id}/toggle', [DiscountController::class, 'toggle']);
$router->post('/admin/discounts/{id}/delete', [DiscountController::class, 'destroy']);

$router->get('/admin/customers', [CustomerController::class, 'index']);
$router->post('/admin/customers/recompute', [CustomerController::class, 'recompute']);
$router->post('/admin/customers/sync-accounting', [CustomerController::class, 'syncAccounting']);

$router->get('/admin/campaigns', [CampaignController::class, 'index']);
$router->post('/admin/campaigns', [CampaignController::class, 'store']);
$router->post('/admin/campaigns/{id}/send', [CampaignController::class, 'send']);

$router->get('/admin/surveys', [SurveyController::class, 'index']);
$router->post('/admin/surveys', [SurveyController::class, 'store']);
$router->post('/admin/surveys/{id}/delete', [SurveyController::class, 'destroy']);
$router->post('/admin/surveys/seed', [SurveyController::class, 'seedDefaults']);

$router->get('/admin/integrations', [IntegrationController::class, 'index']);
$router->post('/admin/integrations/{channel}/set-webhook', [IntegrationController::class, 'setWebhook']);
$router->post('/admin/integrations/{channel}', [IntegrationController::class, 'save']);

$router->get('/admin/theme', [ThemeController::class, 'index']);
$router->post('/admin/theme', [ThemeController::class, 'save']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
