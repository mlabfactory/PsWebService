<?php
declare(strict_types=1);

namespace PS\Webservice\Domain\Object;

use PS\Webservice\Traits\UuidGenerator;

final class Filter
{
    use UuidGenerator;

    private array $data;

    const ALLOWED_FILTER = ['colors', 'id_attribute', 'id_default_combination', 'id_supplier', 'id', 'id_manufacturer', 'id_category_default','price','customizable'];

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->validate();
    }

    public function validate(array $toDecode = []): void
    {
        // check if filter is allowed
        foreach ($this->data as $filterKey => $filterValue) {
            if($filterKey == 'brands') {
                $this->data['id_manufacturer'] = $filterValue;
                unset($this->data['brands']);
                continue;
            }

            if (!in_array($filterKey, self::ALLOWED_FILTER, true)) {
                throw new \InvalidArgumentException("Filter '{$filterKey}' is not allowed.");
            }
        }
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name];
    }

    public function match(array $productData): bool
    {
        if(empty($this->data)) {
            return true; // No filters to apply, so the product matches by default
        }


        foreach ($this->data as $filterKey => $filterValue) {
            $filterValues = explode('|',$filterValue);
            if (!in_array($filterKey, self::ALLOWED_FILTER, true)) {
                continue; // Skip unsupported filters
            }

            if (isset($productData[$filterKey]) && in_array($productData[$filterKey], $filterValues)) {
                return true; // Product does not match the filter criteria
            }
        }
        return false; // Product matches all filter criteria
    }

    
}