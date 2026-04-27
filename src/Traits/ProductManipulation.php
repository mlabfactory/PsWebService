<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Traits;

trait ProductManipulation {
    protected function calculateFullPrice(): void
    {
        $price = $this->getPrice();
        // Implement any additional price calculations here (e.g., taxes, discounts)
        $vat = $price * 0.22; // Example VAT calculation (22%)
        $price += $vat;
        $this->data['price'] = $price; // Update the price in the data array
    }

}