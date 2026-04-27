<?php
/**
 *  application apps
 */

$app->get('/product-list', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productList')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());
$app->get('/product-featured', DolzeZampa\WS\Http\Controller\PsProductController::class . ':featuredProducts')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());
$app->get('/product-by-category', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productByCategory')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());

$app->get('/product/{slug}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productDetail')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());