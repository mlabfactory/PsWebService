<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service\PS;

use DolzeZampa\WS\Domain\Entities\CartEntity;
use DolzeZampa\WS\Domain\Entities\OrderEntity;
use DolzeZampa\WS\Domain\Object\ConfirmOrderSession;
use DolzeZampa\WS\Service\HttpServiceInterface;
use Illuminate\Support\Facades\Log;

class Order extends Cart implements PrestashopServiceInterface {

    public function __construct(HttpServiceInterface $httpService)
    {
        parent::__construct($httpService);
    }

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

    public function newOrder(CartEntity $cart): CartEntity
    {
        $this->httpService->setUrl("/api/orders");

        try {
            $response = $this->httpService->invoke('POST', $cart->toArray());
        } catch (\Exception $e) {
            Log::error("Exception occurred while creating order for cart {$cart->getId()}: " . $e->getMessage());
            throw new \RuntimeException("Failed to create order: " . $e->getMessage());
        }

        return $cart;
    }

    public function confirmOrder(ConfirmOrderSession $confirmSession): OrderEntity
    {
        $errors = $confirmSession->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }

        $this->httpService->setUrl("/orders?ws_key={$this->httpService->getConfig()->apikey}");

        $orderData = [
            'id_cart' => $confirmSession->id_cart,
            'payment_module' => $confirmSession->payment_module,
            'payment_label' => $confirmSession->payment_label,
            'id_order_state' => $confirmSession->id_order_state,
            'amount_paid' => $confirmSession->amount_paid,
            'id_carrier' => $confirmSession->id_carrier
        ];

        // Add customer/guest identification
        if ($confirmSession->id_customer !== null) {
            $orderData['id_customer'] = $confirmSession->id_customer;
        } elseif ($confirmSession->id_guest !== null) {
            $orderData['id_guest'] = $confirmSession->id_guest;
        }

        // Add guest registration data if provided
        if ($confirmSession->shouldCreateAccount()) {
            $orderData['create_account'] = true;
            $orderData['email'] = $confirmSession->getCustomer()->email;
            $orderData['firstname'] = $confirmSession->getCustomer()->firstname;
            $orderData['lastname'] = $confirmSession->getCustomer()->lastname;
            
            if ($confirmSession->getCustomer()->password !== null) {
                $orderData['password'] = $confirmSession->getCustomer()->password;
            }
        } elseif ($confirmSession->getCustomer()->email !== null) {
            $orderData['email'] = $confirmSession->getCustomer()->email;
            $orderData['firstname'] = $confirmSession->getCustomer()->firstname;
            $orderData['lastname'] = $confirmSession->getCustomer()->lastname;
        }

        // Add addresses if provided
        if ($confirmSession->getDeliveryAddress() !== null) {
            $orderData['delivery_address'] = $confirmSession->getDeliveryAddress();
        }
        
        if ($confirmSession->getInvoiceAddress() !== null) {
            $orderData['invoice_address'] = $confirmSession->getInvoiceAddress();
        }

        try {
            $response = $this->httpService->invoke('POST', $orderData);

            $dataResponse = $response->toArray();
            if ($response->failed() || $dataResponse['success'] === false) {
                throw new \RuntimeException("Order confirmation failed: " . $response->getBody() . ' code: ' . $response->getHttpCode());
            }

            Log::debug("Order confirmation response for cart {$confirmSession->id_cart}: " . json_encode($dataResponse) . ' code: ' . $response->getHttpCode());
            return OrderEntity::create($dataResponse['data'], $this);
        } catch (\Exception $e) {
            Log::error("Exception occurred while confirming order for cart {$confirmSession->id_cart}: " . $e->getMessage());
            throw new \RuntimeException("Failed to confirm order: " . $e->getMessage());
        }
    }

}
