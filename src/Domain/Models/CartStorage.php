<?php
declare(strict_types=1);

namespace PS\Webservice\Domain\Models;

use Mdf\JsonStorage\Domain\Model\JsonModelInterface;

class CartStorage implements JsonModelInterface
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // ── Order ─────────────────────────────────────────────────────────────────

    public function getId(): string
    {
        return (string) ($this->data['id'] ?? '');
    }

    public function getReference(): string
    {
        return $this->data['reference'] ?? '';
    }

    public function getIdCart(): int
    {
        return (int) ($this->data['id_cart'] ?? 0);
    }

    public function getIdCustomer(): int
    {
        return (int) ($this->data['id_customer'] ?? 0);
    }

    public function getIdCurrency(): int
    {
        return (int) ($this->data['id_currency'] ?? 0);
    }

    public function getCurrentState(): int
    {
        return (int) ($this->data['current_state'] ?? 0);
    }

    public function getCurrentStateName(): string
    {
        return $this->data['current_state_name'] ?? '';
    }

    public function getPayment(): string
    {
        return $this->data['payment'] ?? '';
    }

    public function getModule(): string
    {
        return $this->data['module'] ?? '';
    }

    public function isVirtual(): bool
    {
        return (bool) ($this->data['is_virtual'] ?? false);
    }

    public function getDateAdd(): string
    {
        return $this->data['date_add'] ?? '';
    }

    // ── Totals (flat) ─────────────────────────────────────────────────────────

    public function getTotalPaidTaxIncl(): float
    {
        return (float) ($this->data['total_paid_tax_incl'] ?? 0.0);
    }

    public function getTotalPaidTaxExcl(): float
    {
        return (float) ($this->data['total_paid_tax_excl'] ?? 0.0);
    }

    public function getTotalProductsWt(): float
    {
        return (float) ($this->data['total_products_wt'] ?? 0.0);
    }

    public function getTotalProducts(): float
    {
        return (float) ($this->data['total_products'] ?? 0.0);
    }

    public function getTotalShippingTaxIncl(): float
    {
        return (float) ($this->data['total_shipping_tax_incl'] ?? 0.0);
    }

    public function getTotalShippingTaxExcl(): float
    {
        return (float) ($this->data['total_shipping_tax_excl'] ?? 0.0);
    }

    // ── Totals (nested) ───────────────────────────────────────────────────────

    public function getTotals(): array
    {
        return $this->data['totals'] ?? [];
    }

    public function getTotalsProductsTaxIncl(): float
    {
        return (float) ($this->data['totals']['products_tax_incl'] ?? 0.0);
    }

    public function getTotalsProductsTaxExcl(): float
    {
        return (float) ($this->data['totals']['products_tax_excl'] ?? 0.0);
    }

    public function getTotalsShippingTaxIncl(): float
    {
        return (float) ($this->data['totals']['shipping_tax_incl'] ?? 0.0);
    }

    public function getTotalsShippingTaxExcl(): float
    {
        return (float) ($this->data['totals']['shipping_tax_excl'] ?? 0.0);
    }

    public function getTotalsDiscountsTaxIncl(): float
    {
        return (float) ($this->data['totals']['discounts_tax_incl'] ?? 0.0);
    }

    public function getTotalsDiscountsTaxExcl(): float
    {
        return (float) ($this->data['totals']['discounts_tax_excl'] ?? 0.0);
    }

    public function getTotalsPaidTaxIncl(): float
    {
        return (float) ($this->data['totals']['paid_tax_incl'] ?? 0.0);
    }

    public function getTotalsPaidTaxExcl(): float
    {
        return (float) ($this->data['totals']['paid_tax_excl'] ?? 0.0);
    }

    // ── Currency ──────────────────────────────────────────────────────────────

    public function getCurrency(): array
    {
        return $this->data['currency'] ?? [];
    }

    public function getCurrencyId(): int
    {
        return (int) ($this->data['currency']['id'] ?? 0);
    }

    public function getCurrencyIsoCode(): string
    {
        return $this->data['currency']['iso_code'] ?? '';
    }

    public function getCurrencySign(): string
    {
        return $this->data['currency']['sign'] ?? '';
    }

    // ── Customer ──────────────────────────────────────────────────────────────

    public function getCustomer(): array
    {
        return $this->data['customer'] ?? [];
    }

    public function getCustomerId(): int
    {
        return (int) ($this->data['customer']['id'] ?? 0);
    }

    public function getCustomerFirstname(): string
    {
        return $this->data['customer']['firstname'] ?? '';
    }

    public function getCustomerLastname(): string
    {
        return $this->data['customer']['lastname'] ?? '';
    }

    public function getCustomerEmail(): string
    {
        return $this->data['customer']['email'] ?? '';
    }

    public function getCustomerFullName(): string
    {
        return trim($this->getCustomerFirstname() . ' ' . $this->getCustomerLastname());
    }

    public function isCustomerActive(): bool
    {
        return (bool) ($this->data['customer']['active'] ?? false);
    }

    public function getCustomerSecureKey(): string
    {
        return $this->data['customer']['secure_key'] ?? '';
    }

    // ── Delivery address ──────────────────────────────────────────────────────

    public function getDeliveryAddress(): array
    {
        return $this->data['delivery_address'] ?? [];
    }

    public function getDeliveryAddressId(): int
    {
        return (int) ($this->data['delivery_address']['id'] ?? 0);
    }

    public function getDeliveryAddressFullName(): string
    {
        $first = $this->data['delivery_address']['firstname'] ?? '';
        $last  = $this->data['delivery_address']['lastname'] ?? '';

        return trim($first . ' ' . $last);
    }

    public function getDeliveryAddressLine1(): string
    {
        return $this->data['delivery_address']['address1'] ?? '';
    }

    public function getDeliveryAddressLine2(): string
    {
        return $this->data['delivery_address']['address2'] ?? '';
    }

    public function getDeliveryAddressPostcode(): string
    {
        return $this->data['delivery_address']['postcode'] ?? '';
    }

    public function getDeliveryAddressCity(): string
    {
        return $this->data['delivery_address']['city'] ?? '';
    }

    public function getDeliveryAddressIdCountry(): int
    {
        return (int) ($this->data['delivery_address']['id_country'] ?? 0);
    }

    public function getDeliveryAddressIdState(): int
    {
        return (int) ($this->data['delivery_address']['id_state'] ?? 0);
    }

    // ── Invoice address ───────────────────────────────────────────────────────

    public function getInvoiceAddress(): array
    {
        return $this->data['invoice_address'] ?? [];
    }

    public function getInvoiceAddressId(): int
    {
        return (int) ($this->data['invoice_address']['id'] ?? 0);
    }

    public function getInvoiceAddressFullName(): string
    {
        $first = $this->data['invoice_address']['firstname'] ?? '';
        $last  = $this->data['invoice_address']['lastname'] ?? '';

        return trim($first . ' ' . $last);
    }

    public function getInvoiceAddressLine1(): string
    {
        return $this->data['invoice_address']['address1'] ?? '';
    }

    public function getInvoiceAddressLine2(): string
    {
        return $this->data['invoice_address']['address2'] ?? '';
    }

    public function getInvoiceAddressPostcode(): string
    {
        return $this->data['invoice_address']['postcode'] ?? '';
    }

    public function getInvoiceAddressCity(): string
    {
        return $this->data['invoice_address']['city'] ?? '';
    }

    public function getInvoiceAddressIdCountry(): int
    {
        return (int) ($this->data['invoice_address']['id_country'] ?? 0);
    }

    public function getInvoiceAddressIdState(): int
    {
        return (int) ($this->data['invoice_address']['id_state'] ?? 0);
    }

    // ── Products ──────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getProducts(): array
    {
        return $this->data['products'] ?? [];
    }

    public function getProductCount(): int
    {
        return count($this->getProducts());
    }

    public function hasProducts(): bool
    {
        return $this->getProductCount() > 0;
    }

    // ── Serialization ─────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return $this->data;
    }
}