<?php

declare(strict_types=1);

use App\Http\Action\Top\TopAction;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use League\Container\Container;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$container = new Container;

$container->add(ServerRequestInterface::class, function () {
    return ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
});

$container->add(Router::class, function () use ($container) {
    $strategy = new ApplicationStrategy;
    $strategy->setContainer($container);

    $router = new Router;
    $router->setStrategy($strategy);

    return $router;
});

$container->add(ResponseFactoryInterface::class, ResponseFactory::class);

$container->add(Environment::class, function () {
    $loader = new FilesystemLoader(__DIR__ . '/../resources/templates/');
    return new Environment($loader);
});

$container->add(TopAction::class)
    ->addArgument(ResponseFactoryInterface::class)
    ->addArgument(Environment::class);

return $container;
