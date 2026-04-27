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
        $customerId = (int) $argv['customerId'];
        $cartList = $this->cartService->getCartListFromUserId($customerId);
        
        if(is_null($cartList)) {
            return response([], 404);
        }

        return response($cartList);

    }

    public function getCart(Request $request, Response $response, array $argv): Response
    {
        $customerId = (int) $argv['customerId'];
        $cartList = $this->cartService->getCartListFromUserId($customerId);
        
        if(is_null($cartList)) {
            return response([], 404);
        }

        return response($cartList);

    }

    public function createCart(Request $request, Response $response, array $argv): Response
    {
        $payload = $request->getParsedBody();

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Invalid payload format', 400);
        }

        $this->validateCartPayload($payload);
        
        $cart = $this->cartService->newCart($payload);
        
        if($cart->failed()) {
            return response([], 500);
        }

        return response($cart->toArray(), 201);
    }

    protected function validateCartPayload(array $payload): bool
    {
        $requiredFields = [
            'id_customer',
            'id_currency',
            'id_lang',
            'id_carrier',
            'replace_products',
            'id_address_delivery',
            'id_address_invoice',
            'products',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new \InvalidArgumentException("Missing required field: {$field}", 400);
            }
        }

        $intFields = [
            'id_customer',
            'id_currency',
            'id_lang',
            'id_carrier',
            'id_address_delivery',
            'id_address_invoice',
        ];

        foreach ($intFields as $field) {
            if (!is_int($payload[$field]) || $payload[$field] <= 0) {
                throw new \InvalidArgumentException("Field {$field} must be a positive integer", 400);
            }
        }

        if (!is_bool($payload['replace_products'])) {
            throw new \InvalidArgumentException('Field replace_products must be a boolean', 400);
        }

        if (!is_array($payload['products']) || count($payload['products']) === 0) {
            throw new \InvalidArgumentException('Field products must be a non-empty array', 400);
        }

        foreach ($payload['products'] as $index => $product) {
            if (!is_array($product)) {
                throw new \InvalidArgumentException("Product at index {$index} must be an object", 400);
            }

            $requiredProductFields = ['id_product', 'id_product_attribute', 'quantity'];
            foreach ($requiredProductFields as $productField) {
                if (!array_key_exists($productField, $product)) {
                    throw new \InvalidArgumentException("Missing required product field {$productField} at index {$index}", 400);
                }
            }

            if (!is_int($product['id_product']) || $product['id_product'] <= 0) {
                throw new \InvalidArgumentException("Field id_product at index {$index} must be a positive integer", 400);
            }

            if (!is_int($product['id_product_attribute']) || $product['id_product_attribute'] < 0) {
                throw new \InvalidArgumentException("Field id_product_attribute at index {$index} must be an integer >= 0", 400);
            }

            if (!is_int($product['quantity']) || $product['quantity'] <= 0) {
                throw new \InvalidArgumentException("Field quantity at index {$index} must be a positive integer", 400);
            }
        }

        return true;
    }

}