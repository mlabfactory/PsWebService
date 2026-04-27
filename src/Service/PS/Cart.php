<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service\PS;

use DolzeZampa\WS\Domain\Entities\CartEntity;
use DolzeZampa\WS\Service\HttpServiceInterface;
use Illuminate\Support\Facades\Log;

class Cart extends PrestashopService implements PrestashopServiceInterface {

    public function getCartListFromUserId(?int $customerId = null, ?int $guestId = null): ?CartEntity
    {
        $queryString = http_build_query([
            !is_null($customerId) ? 'id_customer' : 'id_guest' => !is_null($customerId) ? $customerId : $guestId
        ]);

        $this->httpService->setUrl("/api/carts?{$queryString}");

        try {
            $response = $this->httpService->invoke('GET');
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving cart for customer {$customerId}" . $e->getMessage());
            return null;
        }

        return CartEntity::create($response->toArray(), $this);
    }

    public function getCartFromId(int $cartId): ?CartEntity
    {
        $queryString = http_build_query([
            'id_cart' => $cartId
        ]);
        $this->httpService->setUrl("/api/carts?{$queryString}");

        try {
            $response = $this->httpService->invoke('GET');
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving cart with id {$cartId}" . $e->getMessage());
            return null;
        }

        return CartEntity::create($response->toArray(), $this);
    }

    public function newCart(array $cartOptions): HttpServiceInterface
    {
        $this->httpService->setUrl("/api/carts");

        //create a payload
        $payload = CartEntity::create($cartOptions, $this);

        try {
            $response = $this->httpService->invoke('POST', $payload);
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving cart for customer {$payload->getId()}" . $e->getMessage());
        }

        return $response;
    }

}