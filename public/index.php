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
$router->get('/', [new PublicController(), 'home']);
$router->get('/m/{slug}', [new PublicController(), 'menu']);
$router->post('/m/{slug}/register', [new PublicController(), 'register']);
$router->post('/m/{slug}/rate', [new PublicController(), 'rate']);
$router->get('/m/{slug}/survey', [new PublicController(), 'survey']);
$router->post('/m/{slug}/survey', [new PublicController(), 'submitSurvey']);

// -------- Bot webhooks (Telegram / Bale) --------
$router->post('/bot/{channel}/{tenant}/{secret}', [new BotController(), 'webhook']);

// -------- Admin auth --------
$router->get('/admin/login', [new AuthController(), 'showLogin']);
$router->post('/admin/login', [new AuthController(), 'login']);
$router->get('/admin/logout', [new AuthController(), 'logout']);

// -------- Admin dashboard --------
$router->get('/admin', [new DashboardController(), 'index']);

$router->get('/admin/categories', [new CategoryController(), 'index']);
$router->post('/admin/categories', [new CategoryController(), 'store']);
$router->post('/admin/categories/{id}/update', [new CategoryController(), 'update']);
$router->post('/admin/categories/{id}/delete', [new CategoryController(), 'destroy']);

$router->get('/admin/items', [new ItemController(), 'index']);
$router->get('/admin/items/create', [new ItemController(), 'create']);
$router->post('/admin/items', [new ItemController(), 'store']);
$router->get('/admin/items/{id}/edit', [new ItemController(), 'edit']);
$router->post('/admin/items/{id}/update', [new ItemController(), 'update']);
$router->post('/admin/items/{id}/toggle', [new ItemController(), 'toggle']);
$router->post('/admin/items/{id}/feature', [new ItemController(), 'feature']);
$router->post('/admin/items/{id}/delete', [new ItemController(), 'destroy']);

$router->get('/admin/pricing', [new PricingController(), 'index']);
$router->post('/admin/pricing/shift', [new PricingController(), 'shift']);

$router->get('/admin/discounts', [new DiscountController(), 'index']);
$router->post('/admin/discounts', [new DiscountController(), 'store']);
$router->post('/admin/discounts/{id}/toggle', [new DiscountController(), 'toggle']);
$router->post('/admin/discounts/{id}/delete', [new DiscountController(), 'destroy']);

$router->get('/admin/customers', [new CustomerController(), 'index']);
$router->post('/admin/customers/recompute', [new CustomerController(), 'recompute']);
$router->post('/admin/customers/sync-accounting', [new CustomerController(), 'syncAccounting']);

$router->get('/admin/campaigns', [new CampaignController(), 'index']);
$router->post('/admin/campaigns', [new CampaignController(), 'store']);
$router->post('/admin/campaigns/{id}/send', [new CampaignController(), 'send']);

$router->get('/admin/surveys', [new SurveyController(), 'index']);
$router->post('/admin/surveys', [new SurveyController(), 'store']);
$router->post('/admin/surveys/{id}/delete', [new SurveyController(), 'destroy']);
$router->post('/admin/surveys/seed', [new SurveyController(), 'seedDefaults']);

$router->get('/admin/integrations', [new IntegrationController(), 'index']);
$router->post('/admin/integrations/{channel}/set-webhook', [new IntegrationController(), 'setWebhook']);
$router->post('/admin/integrations/{channel}', [new IntegrationController(), 'save']);

$router->get('/admin/theme', [new ThemeController(), 'index']);
$router->post('/admin/theme', [new ThemeController(), 'save']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
