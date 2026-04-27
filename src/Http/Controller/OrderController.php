<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Http\Controller;

use DolzeZampa\WS\Domain\Entities\CartEntity;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DolzeZampa\WS\Service\PS\Order;

class OrderController extends CartController {

    private Order $orderService;

    public function __construct(Order $orderService)
    {
        $this->orderService = $orderService;
    }

    public function orderHistory(Request $request, Response $response, array $argv): Response
    {
        $customerId = (int) $argv['customerId'];
        $cartList = $this->orderService->getOrderListFromUserId($customerId);
        
        if(is_null($cartList)) {
            return response([], 404);
        }

        return response($cartList);

    }

    public function getOrder(Request $request, Response $response, array $argv): Response
    {
        $orderId = (int) $argv['orderId'];
        $cartList = $this->orderService->orderDetails($orderId);
        
        if(is_null($cartList)) {
            return response([], 404);
        }

        return response($cartList);

    }

    public function createOrder(Request $request, Response $response, array $argv): Response
    {
        $payload = $request->getParsedBody();

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Invalid payload format', 400);
        }

        $this->validateCartPayload($payload);
        
        $cart = $this->orderService->newOrder(CartEntity::create($payload, $this->orderService));
        
        if($cart->failed()) {
            return response([], 500);
        }

        return response($cart->toArray(), 201);
    }

}