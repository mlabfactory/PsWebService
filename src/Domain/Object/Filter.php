<?php
declare(strict_types=1);

namespace PS\Webservice\Domain\Object;

use PS\Webservice\Traits\UuidGenerator;

final class Filter
{
    use UuidGenerator;

    private array $data;

    const ALLOWED_FILTER = ['features', 'attributes', 'price_range', 'manufacturers'];

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->normalizeData();
    }

    public function normalizeData(array $toDecode = []): void
    {
        foreach($this->data as $k => $value) {
            $this->addFilter($k, $value);
        }
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name];
    }

    public function toArray(): array
    {
        return $this->data;
    }

    private function addFilter(string $key, mixed $value): void
    {
        switch ($key) {
            case 'colors':
            case 'color':
                $this->data['attributes'][] = $value;
                break;
            default:
                break;
        }
    }

    
}