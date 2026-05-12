<?php
declare(strict_types=1);

namespace PS\Webservice\Facades;

use Mdf\JsonStorage\Service\DbService;

final class JsonDataStorage
{
    public static function coupon(): DbService
    {
        return new DbService('coupons');
    }

    public static function carriers(): DbService
    {
        return new DbService('coupons');
    }
}