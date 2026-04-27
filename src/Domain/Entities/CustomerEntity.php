<?php

declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Entities;

use DolzeZampa\WS\Domain\ObjectInterface;
use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;

class CustomerEntity implements ObjectInterface
{
	/** @var array<string, mixed> */
	private array $data;
    private PrestashopServiceInterface $service;

    private function __construct(array $data, PrestashopServiceInterface $service)
    {
        $this->service = $service;
        $this->data = $data;
        $this->normalizeData();
	}

	public static function create(array $data, PrestashopServiceInterface $service): self
	{
		return new self($data, $service);
	}

    public function getId(): int
	{
		return (int) ($this->data['id'] ?? 0);
	}

	public function toArray(): array
	{
		return $this->data;
	}

	public function toJson($options = 0): string
	{
		return json_encode($this->toArray(), $options);
	}

	public function __get(string $name): mixed
	{
		if (!array_key_exists($name, $this->data)) {
			throw new \InvalidArgumentException('No argument found with ' . $name);
		}

		return $this->data[$name];
	}

	public function normalizeData(): void
	{
        if (isset($this->data['customer']) && is_array($this->data['customer'])) {
            $this->data = [
                'customer' => $this->normalizeCustomerPayload($this->data['customer']),
            ];

            return;
        }

        if (array_key_exists('id', $this->data)) {
            $this->data['id'] = (int) $this->data['id'];
        }

        if (array_key_exists('newsletter', $this->data)) {
            $this->data['newsletter'] = (bool) $this->data['newsletter'];
        }
	}

    /**
     * @param array<string, mixed> $customer
     * @return array<string, mixed>
     */
    private function normalizeCustomerPayload(array $customer): array
    {
        $normalized = [
            'email' => (string) ($customer['email'] ?? ''),
            'password' => (string) ($customer['password'] ?? ''),
            'firstname' => (string) ($customer['firstname'] ?? ''),
            'lastname' => (string) ($customer['lastname'] ?? ''),
            'newsletter' => (bool) ($customer['newsletter'] ?? false),
        ];

        if (isset($customer['delivery_address']) && is_array($customer['delivery_address'])) {
            $normalized['delivery_address'] = $this->normalizeDeliveryAddress($customer['delivery_address']);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $deliveryAddress
     * @return array<string, mixed>
     */
    private function normalizeDeliveryAddress(array $deliveryAddress): array
    {
        return [
            'alias' => (string) ($deliveryAddress['alias'] ?? ''),
            'address1' => (string) ($deliveryAddress['address1'] ?? ''),
            'city' => (string) ($deliveryAddress['city'] ?? ''),
            'postcode' => (string) ($deliveryAddress['postcode'] ?? ''),
            'id_country' => (int) ($deliveryAddress['id_country'] ?? 0),
            'phone_mobile' => (string) ($deliveryAddress['phone_mobile'] ?? ''),
        ];
    }
}
