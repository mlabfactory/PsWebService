<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service\PS;

use DolzeZampa\WS\Domain\Entities\AccessoryEntity;
use DolzeZampa\WS\Domain\Entities\ProductFeatureEntity;
use DolzeZampa\WS\Domain\Enums\ImageTail;
use DolzeZampa\WS\Domain\ObjectInterface;
use DolzeZampa\WS\Service\HttpServiceInterface;

interface PrestashopServiceInterface
{
    public function __construct(HttpServiceInterface $httpService);

    public function getSpecificationsImage(int $productid, int $id, ImageTail $tail): ?ObjectInterface;

    public function getSpecificationsOption(int $id): ObjectInterface;

    public function getSpecificationsCombination(int $id): ObjectInterface;

    public function getSpecificationsProductFeature(int $id, int $featureValueId): ProductFeatureEntity;

    public function getSpecificationsAccessory(int $id): AccessoryEntity;


}