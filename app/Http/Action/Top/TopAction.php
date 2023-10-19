<?php

declare(strict_types=1);

namespace App\Http\Action\Top;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment as View;

class TopAction {
    public function __construct(
        protected ResponseFactoryInterface $responseFactory,
        protected View $twig
    ) {

    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $name = $queryParams['name'] ?? '世界';

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write("<p>こんにちは{$name}！</p>");

        return $response;
    }
}
