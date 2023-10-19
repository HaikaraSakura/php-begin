<?php

declare(strict_types=1);

use App\Http\Action\Top\TopAction;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

// 何はともあれオートローダーを読み込む
require_once __DIR__ . '/../vendor/autoload.php';

$container = require_once __DIR__ . '/../bootstrap/dependencies.php';
assert($container instanceof ContainerInterface);

// Routerを取得
$router = $container->get(Router::class);
assert($router instanceof Router);

// ルーティング
$router->get('/', TopAction::class);

// Requestを取得
$request = $container->get(ServerRequestInterface::class);
assert($request instanceof ServerRequestInterface);

$response = $router->dispatch($request);

(new SapiEmitter)->emit($response);
