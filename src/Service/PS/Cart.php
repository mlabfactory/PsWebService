<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service\PS;

use DolzeZampa\WS\Domain\Entities\CartEntity;

class Cart extends PrestashopService implements PrestashopServiceInterface {

    public function getCartListFromUserId(int $customerId): CartEntity
    {
        $this->httpService->setUrl("/api/carts");

        try {
            $response = $this->httpService->invoke('GET');
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving cart for customer {$customerId}" . $e->getMessage());
            return null;
        }

        return CartEntity::create($response->toArray(), $this);
    }

    public function createNewCart(array $cartOptions): CartEntity
    {
        $this->httpService->setUrl("/api/carts");

        //create a payload
        $payload = CartEntity::create($cartOptions, $this);

        try {
            $response = $this->httpService->invoke('POST', $payload);
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving cart for customer {$customerId}" . $e->getMessage());
            return null;
        }

        return CartEntity::create($response->toArray(), $this);
    }

}