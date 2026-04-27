<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service\PS;

use DolzeZampa\WS\Domain\Entities\CartEntity;
use DolzeZampa\WS\Domain\Entities\OrderEntity;
use DolzeZampa\WS\Service\HttpServiceInterface;
use Illuminate\Support\Facades\Log;

class Order extends PrestashopService implements PrestashopServiceInterface {

    public function getOrderListFromUserId(?int $customerId = null): ?OrderEntity
    {
        $queryString = http_build_query([
            'id_customer' => $customerId
        ]);

        $this->httpService->setUrl("/api/orders?{$queryString}");

        try {
            $response = $this->httpService->invoke('GET');
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving orders for customer {$customerId}" . $e->getMessage());
            return null;
        }

        return OrderEntity::create($response->toArray(), $this);
    }

    public function orderDetails(int $orderId): ?OrderEntity
    {
        $queryString = http_build_query([
            'id_order' => $orderId
        ]);
        $this->httpService->setUrl("/api/orders?{$queryString}");

        try {
            $response = $this->httpService->invoke('GET');
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving order with id {$orderId}" . $e->getMessage());
            return null;
        }

        return OrderEntity::create($response->toArray(), $this);
    }

    public function newOrder(CartEntity $cart): HttpServiceInterface
    {
        $this->httpService->setUrl("/api/orders");

        //create a payload
        $payload = OrderEntity::createFromCart($cart, $this);

        try {
            $response = $this->httpService->invoke('POST', $payload);
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving cart for customer {$payload->getId()}" . $e->getMessage());
        }

        return $response;

    }

}