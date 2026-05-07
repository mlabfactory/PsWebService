<?php
declare(strict_types=1);

namespace PS\Webservice\Service\PS;

use PS\Webservice\Domain\Entities\AccessoryEntity;
use PS\Webservice\Domain\Entities\ProductFeatureEntity;
use PS\Webservice\Domain\Enums\ImageTail;
use PS\Webservice\Domain\ObjectInterface;
use PS\Webservice\Service\HttpServiceInterface;

interface PrestashopServiceInterface
{
    public function __construct(HttpServiceInterface $httpService);

    public function getSpecificationsImage(int $productid, int $id, ImageTail $tail): ?ObjectInterface;

    public function getSpecificationsOption(int $id): ObjectInterface;

    public function getSpecificationsCombination(int $id): ObjectInterface;

    public function getSpecificationsProductFeature(int $id, int $featureValueId): ProductFeatureEntity;

    public function getSpecificationsAccessory(int $id): AccessoryEntity;


}