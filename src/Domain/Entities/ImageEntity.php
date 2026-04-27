<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Entities;

use DolzeZampa\WS\Domain\ObjectInterface;
use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;

class ImageEntity implements ObjectInterface {

	private array $data;
    private PrestashopServiceInterface $service;

	public function __construct(array $data, PrestashopServiceInterface $service)
	{
        $this->service = $service;
		$this->data = $data;
		$this->normalizeData();

	}

    public static function create(array $data, PrestashopServiceInterface $service): self
    {
        return new self(
            $data,
            $service
        );
    }

	public function getImageUrl(): string
	{
		return $this->data['url'] ?? '';
	}

	public function getImageId(): string
	{
		return $this->data['id'] ?? '';
	}

	public function toArray(): array
	{
		return [
			'url' => $this->data['url'] ?? '',
			'id' => $this->data['id'] ?? '',
		];
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
		$this->data = [
			'url' => (string) ($this->data['url'] ?? ''),
			'id' => (string) ($this->data['id'] ?? ''),
		];
	}
}
