<?php
declare(strict_types=1);

namespace PS\Webservice\Domain\Object;

use PS\Webservice\Domain\Entities\CarrierEntity;
use PS\Webservice\Domain\Entities\CartRuleEntity;
use PS\Webservice\Domain\ObjectInterface;
use PS\Webservice\Service\PS\PrestashopServiceInterface;
use PS\Webservice\Traits\UuidGenerator;

class OrderSession implements ObjectInterface
{
    use UuidGenerator;
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
        $cartId = $data['cart_id'] ?? null;

        $this->data = [
            'mode' => 'payment',
            'success_url' => $this->appendQueryParam($data['success_url'] ?? '', 'cart_id', $cartId),
            'cancel_url' => $this->appendQueryParam($data['cancel_url'] ?? '', 'cart_id', $cartId),
            'line_items' => $data['line_items'] ?? [],
            // Only include IDs with positive integer values; null, empty strings, '0',
            // and negative values are excluded as all PrestaShop entity IDs must be > 0.
            'metadata' => array_filter([
                'cart_id' => $cartId,
                'id_customer' => $data['id_customer'],
                'id_guest' => $data['id_guest'],
                'id_carrier' => $data['id_carrier'],
            ], fn($v) => $v !== null && (int) $v > 0),
        ];
    }

    /**
     * Appends a query parameter to a URL, correctly handling existing query strings.
     */
    private function appendQueryParam(string $url, string $param, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . urlencode($param) . '=' . urlencode((string) $value);
    }

    public function addLineItem(string $name, int $quantity, float $price, string $type = 'product'): void
    {
        $this->data['line_items'][] = [
            'type' => $type,
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

    public function addCartRule(CartRuleEntity $cartRule): void
    {
        $cartRules = $cartRule->data;
        /** @var Rule $rule */
        foreach($cartRule as $rule) {
            $id = $rule->stripe_id;
            $this->data['discounts']['coupon'] = $id; //FIXME: we need to create a coupon in Stripe for this cart rule and use its ID here
        }
    }

    public function addCarrier(CarrierEntity $carrier): void
    {
        $this->data['shipping_options']['shipping_rate'] = 'shr_1TVqLaK37RWIfqdNW4HF98Df'; //FIXME: we need to create a shipping rate in Stripe for this carrier and use its ID here 
    }

    public function generatePayload(): PayloadServiceData
    {
        return new PayloadServiceData($this->data, ['id_cart' => 'cart', 'id_customer' => 'customer', 'id_guest' => 'guest']);
    }

}