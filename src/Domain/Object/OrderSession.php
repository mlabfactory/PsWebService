<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Object;

use DolzeZampa\WS\Domain\ObjectInterface;
use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;

class OrderSession implements ObjectInterface
{

    protected array $data;

    private function __construct(array $data) {
        $this->data = $data;
        $this->normalizeData();
    }

    public static function create(array $data, PrestashopServiceInterface $service): self
    {
        return new self($data);
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function normalizeData(): void
    {
        $data = $this->data;
        $this->data = [
            'mode' => 'payment',
            'success_url' => $data['success_url'] ?? '',
            'cancel_url' => $data['cancel_url'] ?? '',
            'line_items' => $data['line_items'] ?? []
        ];

        $successUrl = $this->data['success_url'] . '?cart_id=' . ($data['cart_id']);
        $canelUrl = $this->data['cancel_url'] . '?cart_id=' . ($data['cart_id']);

        $this->data['success_url'] = $successUrl;
        $this->data['cancel_url'] = $canelUrl;
    }

    public function addLineItem(string $name, int $quantity, float $price): void
    {
        $this->data['line_items'][] = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $name
                ],
                'unit_amount' => (int)($price * 100),
            ],
            'quantity' => $quantity
        ];
    }

}