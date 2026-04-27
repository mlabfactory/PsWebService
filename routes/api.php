<?php
/**
 *  application apps
 */

$app->get('/product-list', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productList')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());
$app->get('/product-featured', DolzeZampa\WS\Http\Controller\PsProductController::class . ':featuredProducts')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());
$app->get('/product-by-category', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productByCategory')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());

$app->get('/product/{slug}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productDetail')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());

/** Carts api */
$app->get('/cart/list/{userId}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':cartList');
$app->get('/cart/{cartId}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':getCart');
$app->post('/cart', DolzeZampa\WS\Http\Controller\PsProductController::class . ':createCart');
$app->get('/order/{orderId}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':getOrder');
$app->get('/order/history/{userId}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':orderHistory');
$app->post('/order', DolzeZampa\WS\Http\Controller\PsProductController::class . ':createOrder');