<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Entities;

use DolzeZampa\WS\Domain\ObjectInterface;
use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;

class OptionEntity implements ObjectInterface
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

	public function getAttributeGroupId(): int
	{
		return (int) ($this->data['id_attribute_group'] ?? 0);
	}

	public function getColor(): string
	{
		return (string) ($this->data['color'] ?? '');
	}

	public function getPosition(): int
	{
		return (int) ($this->data['position'] ?? 0);
	}

	public function getName(): string
	{
		return (string) ($this->data['name'] ?? '');
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

	/**
	 * @return array<string, mixed>
	 */
	public function normalizeData(): void
	{
		$this->data = [
			'id' => (int) ($this->data['id'] ?? 0),
			'id_attribute_group' => (int) ($this->data['id_attribute_group'] ?? 0),
			'color' => (string) ($this->data['color'] ?? ''),
			'type' => $this->data['color'] == '' ? 'custom' : 'color',
			'position' => (int) ($this->data['position'] ?? 0),
			'name' => (string) ($this->data['name'] ?? ''),
		];
	}
}
