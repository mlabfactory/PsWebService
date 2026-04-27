<?php
/**
 *  application apps
 */

$app->get('/categories', DolzeZampa\WS\Http\Controller\CategoryController::class . ':categoryList')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());
$app->get('/product-list', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productList')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());
$app->get('/product-featured', DolzeZampa\WS\Http\Controller\PsProductController::class . ':featuredProducts')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());
$app->get('/product-by-category', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productByCategory')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());

$app->get('/product/{slug}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':productDetail')->addMiddleware(new \DolzeZampa\WS\Http\Middleware\CachingMiddleware());

/** Carts api */
$app->get('/cart/list/{customerId}', DolzeZampa\WS\Http\Controller\CartController::class . ':cartList');
$app->get('/cart/{cartId}', DolzeZampa\WS\Http\Controller\CartController::class . ':getCart');
$app->post('/cart', DolzeZampa\WS\Http\Controller\CartController::class . ':createCart');

/** Customer api */
$app->post('/api/register', DolzeZampa\WS\Http\Controller\CustomerController::class . ':register');
$app->post('/api/login', DolzeZampa\WS\Http\Controller\CustomerController::class . ':login');
$app->post('/api/customers', DolzeZampa\WS\Http\Controller\CustomerController::class . ':createCustomer');

/** Order api */
$app->get('/order/{orderId}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':getOrder');
$app->get('/order/history/{customerId}', DolzeZampa\WS\Http\Controller\PsProductController::class . ':orderHistory');
$app->post('/order', DolzeZampa\WS\Http\Controller\PsProductController::class . ':createOrder');
