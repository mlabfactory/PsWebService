<?php
declare(strict_types=1);

namespace PS\Webservice\Domain\Models;

use Mdf\JsonStorage\Domain\Model\JsonModelInterface;
use PS\Webservice\Domain\Entities\CouponEntity;

class CouponStorage implements JsonModelInterface
{
    private array $data;

    public function __construct(CouponEntity $coupon)
    {
        $this->data = $coupon->toArray();
    }
    public function getId(): string
    {
        return $this->data['id'] ?? '';
    }

    public function toArray(): array
    {
        return $this->data;
    }
}