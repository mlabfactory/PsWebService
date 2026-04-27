<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Http\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DolzeZampa\WS\Service\PS\Cart;

class CartController extends Controller {

    private Cart $cartService;

    public function __construct(Cart $cartService)
    {
        $this->cartService = $cartService;
    }

    public function getCartList(Request $request, Response $response, array $argv): Response
    {
        $cartList = $this->cartService->getCartList();
    }

}