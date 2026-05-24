<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Controllers\AuthController;
use App\Controllers\InventoryController;
use App\Controllers\RecipeController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\ImportExportController;

// Container
$container = new Container();
AppFactory::setContainer($container);

// Database
$container->set('db', function () {
    $path = (getenv('DATA_PATH') ?: '/data') . '/bar.db';
    $pdo  = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');
    return $pdo;
});

// Settings helper
$container->set('settings', function ($c) {
    $rows = $c->get('db')->query("SELECT key, value FROM settings")->fetchAll();
    $s = [];
    foreach ($rows as $r) $s[$r['key']] = $r['value'];
    return $s;
});

// Register controllers
$container->set(AuthController::class,         fn($c) => new AuthController($c));
$container->set(InventoryController::class,    fn($c) => new InventoryController($c));
$container->set(RecipeController::class,       fn($c) => new RecipeController($c));
$container->set(AdminController::class,        fn($c) => new AdminController($c));
$container->set(ApiController::class,          fn($c) => new ApiController($c));
$container->set(ImportExportController::class, fn($c) => new ImportExportController($c));

session_start();

$app = AppFactory::create();
$app->addErrorMiddleware(false, true, true);

// Routes
require __DIR__ . '/../src/routes.php';

$app->run();
