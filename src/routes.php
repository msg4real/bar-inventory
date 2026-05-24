<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AuthController;
use App\Controllers\InventoryController;
use App\Controllers\RecipeController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\ImportExportController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

$auth   = new AuthMiddleware($container);
$admin  = new RoleMiddleware($container, 'admin');
$editor = new RoleMiddleware($container, 'editor');

// Health
$app->get('/api/health', function ($req, $res) {
    $res->getBody()->write(json_encode(['ok' => true, 'time' => date('c')]));
    return $res->withHeader('Content-Type', 'application/json');
});

// Setup
$app->get('/setup',  AuthController::class . ':setupPage');
$app->post('/setup', AuthController::class . ':setupPost');

// Auth
$app->get('/login',           AuthController::class . ':loginPage');
$app->post('/login',          AuthController::class . ':loginPost');
$app->get('/logout',          AuthController::class . ':logout');
$app->get('/forgot-password', AuthController::class . ':forgotPage');
$app->post('/forgot-password',AuthController::class . ':forgotPost');
$app->get('/reset-password',  AuthController::class . ':resetPage');
$app->post('/reset-password', AuthController::class . ':resetPost');

// Inventory
$app->get('/',               InventoryController::class . ':index')->add($auth);
$app->get('/bottles/scan',   InventoryController::class . ':scanPage')->add($auth);
$app->get('/bottles/{id}',   InventoryController::class . ':show')->add($auth);

// Recipes
$app->get('/recipes',        RecipeController::class . ':index')->add($auth);
$app->get('/recipes/{id}',   RecipeController::class . ':show')->add($auth);

// Export (editor+admin, not admin-only)
$app->get('/export', ImportExportController::class . ':exportPage')->add($editor)->add($auth);

// Import (editor+admin)
$app->get('/import',         ImportExportController::class . ':importPage')->add($editor)->add($auth);
$app->post('/api/import/bottles', ImportExportController::class . ':importBottles')->add($editor)->add($auth);
$app->post('/api/import/recipes', ImportExportController::class . ':importRecipes')->add($editor)->add($auth);

// Admin pages
$app->group('/admin', function (RouteCollectorProxy $g) {
    $g->get('',                AdminController::class . ':index');
    $g->get('/fields',         AdminController::class . ':fields');
    $g->get('/users',          AdminController::class . ':users');
    $g->get('/theme',          AdminController::class . ':theme');
    $g->get('/branding',       AdminController::class . ':branding');
    $g->get('/email',          AdminController::class . ':emailSettings');
    $g->post('/pin/verify',    AdminController::class . ':verifyPin');
    $g->post('/users/{id}/reset-password', AuthController::class . ':adminResetPassword');
})->add($admin)->add($auth);

// API
$app->group('/api', function (RouteCollectorProxy $g) {
    $g->get('/bottles',             ApiController::class . ':listBottles');
    $g->post('/bottles',            ApiController::class . ':createBottle');
    $g->put('/bottles/{id}',        ApiController::class . ':updateBottle');
    $g->delete('/bottles/{id}',     ApiController::class . ':deleteBottle');
    $g->post('/bottles/bulk',       ApiController::class . ':bulkCreate');
    $g->get('/barcode/{code}',      ApiController::class . ':barcodeLookup');
    $g->get('/recipes',             ApiController::class . ':listRecipes');
    $g->post('/recipes',            ApiController::class . ':createRecipe');
    $g->put('/recipes/{id}',        ApiController::class . ':updateRecipe');
    $g->delete('/recipes/{id}',     ApiController::class . ':deleteRecipe');
    $g->get('/export/csv',          ApiController::class . ':exportXlsx');
    $g->post('/admin/settings',     ApiController::class . ':saveSettings');
    $g->post('/admin/fields',       ApiController::class . ':saveFields');
    $g->post('/admin/categories',   ApiController::class . ':saveCategories');
    $g->post('/admin/themes',       ApiController::class . ':saveCustomTheme');
    $g->delete('/admin/themes/{id}',ApiController::class . ':deleteCustomTheme');
    $g->post('/admin/smtp/test',    ApiController::class . ':testSmtp');
    $g->post('/admin/users',        ApiController::class . ':createUser');
    $g->put('/admin/users/{id}',    ApiController::class . ':updateUser');
    $g->delete('/admin/users/{id}', ApiController::class . ':deleteUser');
    $g->post('/admin/logo',         ApiController::class . ':uploadLogo');
})->add($auth);
