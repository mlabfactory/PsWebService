<?php

declare(strict_types=1);

namespace DolzeZampa\WS\Service\PS;

use DolzeZampa\WS\Domain\Entities\CustomerEntity;
use DolzeZampa\WS\Service\HttpServiceInterface;
use Illuminate\Support\Facades\Log;

class Customer extends PrestashopService implements PrestashopServiceInterface
{
    public function register(CustomerEntity $customer): HttpServiceInterface
    {
        return $this->post('/register', $customer, 'register', $customer->toArray()['customer']['email'] ?? null);
    }

    public function createCustomer(CustomerEntity $customer): HttpServiceInterface
    {
        return $this->post('/customers', $customer, 'create', $customer->toArray()['customer']['email'] ?? null);
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function login(array $credentials): HttpServiceInterface
    {
        return $this->post('/login', $credentials, 'login', $credentials['email'] ?? null);
    }

    /**
     * @param array<string, mixed>|CustomerEntity $payload
     */
    private function post(string $url, array|CustomerEntity $payload, string $action, ?string $email = null): HttpServiceInterface
    {
        $this->httpService->setUrl($url);

        try {
            $response = $this->httpService->invoke('POST', $payload);
        } catch (\Exception $e) {
            Log::error("Exception occurred while attempting to {$action} customer {$email}: " . $e->getMessage());
            throw new \RuntimeException("Unable to {$action} customer", 500, $e);
        }

        return $response;
    }
}
