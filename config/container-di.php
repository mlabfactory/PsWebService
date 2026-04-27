<?php

// Slim Container configuration for dependency injection

$container = new \DI\Container();

$container->set(\DolzeZampa\WS\Service\HttpService::class, function ($c) {
    $webserviceCOnfig = new \DolzeZampa\WS\Domain\Object\WebserviceConfig(
        apiKey: env('PS_API_KEY'),
        domain: env('PS_BASE_URL'),
        headers: [
            "Output-Format" => "JSON",
        ]
    );
    return new \DolzeZampa\WS\Service\HttpService($webserviceCOnfig);
});

$container->set(\DolzeZampa\WS\Service\PS\Product::class, function ($c) {
    $httpService = $c->get(\DolzeZampa\WS\Service\HttpService::class);
    return new \DolzeZampa\WS\Service\PS\Product($httpService);
});

$container->set(\DolzeZampa\WS\Service\PS\Image::class, function ($c) {
    $httpService = $c->get(\DolzeZampa\WS\Service\HttpService::class);
    return new \DolzeZampa\WS\Service\PS\Image($httpService);
});

/** CONTROLLERS */
$container->set(\DolzeZampa\WS\Http\Controller\PsProductController::class, function ($c) {
    $productService = $c->get(\DolzeZampa\WS\Service\PS\Product::class);
    return new \DolzeZampa\WS\Http\Controller\PsProductController($productService);
});