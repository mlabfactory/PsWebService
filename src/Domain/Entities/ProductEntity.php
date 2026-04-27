<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Entities;

use DolzeZampa\WS\Domain\Enums\ImageTail;
use DolzeZampa\WS\Domain\ObjectInterface;
use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;
use DolzeZampa\WS\Traits\ProductBuilder;
use DolzeZampa\WS\Traits\ProductManipulation;

class ProductEntity implements ObjectInterface
{
    use ProductManipulation, ProductBuilder;
    
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
        return (int) $this->data['id'];
    }

    public function getName(): string
    {
        return (string) $this->data['name'];
    }

    public function getDescription(): string
    {
        return (string) $this->data['description'];
    }

    public function getPrice(): float
    {
        return (float) $this->data['price'];
    }

    public function toArray(): array
    {
        $this->calculateFullPrice(); // Ensure the price is calculated before converting to array
        return $this->data;
    }

    public function getImages(): array
    {
        return $this->data['associations']['images'] ?? [];
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
        unset($this->data['associations']['product_option_values']);
        $this->buildImageLink([ImageTail::ORIGINAL]);
        $this->buildCombinations();
        $this->buildProductFeatures();
        $this->buildAccessories();
        $this->buildCategories();
        $this->buildStockAvailables();
    }
}
