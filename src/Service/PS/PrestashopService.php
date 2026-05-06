<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service\PS;

use DolzeZampa\WS\Domain\Entities\AccessoryEntity;
use DolzeZampa\WS\Domain\Entities\CategoryEntity;
use DolzeZampa\WS\Domain\Entities\CombinationEntity;
use DolzeZampa\WS\Domain\Entities\ImageEntity;
use DolzeZampa\WS\Domain\Entities\OptionEntity;
use DolzeZampa\WS\Domain\Entities\ProductFeatureEntity;
use DolzeZampa\WS\Domain\Entities\StockAvailableEntity;
use DolzeZampa\WS\Domain\Enums\ImageTail;
use DolzeZampa\WS\Service\HttpServiceInterface;
use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;
use Illuminate\Support\Facades\Log;

class PrestashopService implements PrestashopServiceInterface {

    protected HttpServiceInterface $httpService;
    
    public function __construct(HttpServiceInterface $httpService) {
        $this->httpService = $httpService;
    }


    /**
     * Retrieves the image URL for a specific product image.
     *
     * @param int $productId The unique identifier of the product
     * @param int $imageId The unique identifier of the image
     * @param ImageTail $type The type/tail specification for the image
     *
     * @return ImageEntity|null The image entity containing the URL and related information, or null if retrieval fails
     */
    public function getSpecificationsImage(int $productId, int $imageId, ImageTail $type): ?ImageEntity 
    {
        $this->httpService->setUrl("/images/products/{$productId}/{$imageId}/{$type->value}");

        try {
            $response = $this->httpService->invoke('GET');
        } catch (\Exception $e) {
            Log::error("Exception occurred while retrieving image for product {$productId} with image ID {$imageId} and type {$type->value}: " . $e->getMessage());
            return null;
        }
        return ImageEntity::create($response->toArray(), $this);
    }

    /**
     * Retrieves the option details for a specific combination.
     *
     * @param int|null $id The unique identifier of the product
     *
     * @return OptionEntity The option entity containing the details of the option
     */
    public function getSpecificationsOption(int $id): OptionEntity 
    {
        $this->httpService->setUrl("/product_option_values/{$id}?display=full");
        $response = $this->httpService->invoke('GET');

        if($response->failed()) {
            throw new \RuntimeException("Failed to retrieve option: " . $response->getHttpCode());
        }
        return OptionEntity::create($response->toArray()['product_option_values'][0], $this);

    }

    /**
     * Retrieves the combination details for a specific combination.
     *
     * @param int $id The unique identifier of the combination
     *
     * @return CombinationEntity The combination entity containing the details of the combination
     */
    public function getSpecificationsCombination(int $id): CombinationEntity 
    {
        $this->httpService->setUrl("/combinations/{$id}?display=full");
        $response = $this->httpService->invoke('GET');

        if($response->failed()) {
            throw new \RuntimeException("Failed to retrieve combination: " . $response->getHttpCode());
        }

        foreach($response->toArray()['combinations'] as $combinationData) {
            $combination = CombinationEntity::create($combinationData, $this);
        }

        return $combination;
    }

    /**
     * Retrieves the product feature details.
     *
     * @param int $id The unique identifier of the product feature
     * @param int $featureValueId The unique identifier of the feature value
     *
     * @return ProductFeatureEntity The product feature entity containing the details
     */
    public function getSpecificationsProductFeature(int $id, int $featureValueId): ProductFeatureEntity 
    {
        $this->httpService->setUrl("/product_feature_values/{$featureValueId}?display=full");
        $response = $this->httpService->invoke('GET');

        if($response->failed()) {
            throw new \RuntimeException("Failed to retrieve product feature: " . $response->getHttpCode());
        }

        $value = $response->toArray()['product_feature_values'][0];
        $featureValue = ProductFeatureEntity::create($value, $this);

        return $featureValue;
    }

    /**
     * Retrieves the accessory product details.
     *
     * @param int $id The unique identifier of the accessory product
     *
     * @return AccessoryEntity The accessory entity containing the details
     */
    public function getSpecificationsAccessory(int $id): AccessoryEntity 
    {
        $this->httpService->setUrl("/products/{$id}?display=full");
        $response = $this->httpService->invoke('GET');

        if($response->failed()) {
            throw new \RuntimeException("Failed to retrieve accessory: " . $response->getHttpCode());
        }

        $accessoryData = [
            'id' => $id,
        ];

        return AccessoryEntity::create($accessoryData, $this);
    }

    /**
     * Retrieves the category details.
     *
     * @param int $id The unique identifier of the category
     *
     * @return CategoryEntity The category entity containing the details
     */
    public function getSpecificationsCategory(int $id): CategoryEntity 
    {
        $this->httpService->setUrl("/categories/{$id}?display=full");
        $response = $this->httpService->invoke('GET');

        if($response->failed()) {
            throw new \RuntimeException("Failed to retrieve category: " . $response->getHttpCode());
        }

        $categoryData = $response->toArray()['categories'][0] ?? [];
        
        return CategoryEntity::create($categoryData, $this);
    }

    /**
     * Retrieves the stock available details for a product.
     *
     * @param int $productId The unique identifier of the product
     *
     * @return array Array of StockAvailableEntity objects
     */
    public function getSpecificationsStockAvailables(int $productId): array 
    {
        $this->httpService->setUrl("/stock_availables?filter[id_product]={$productId}&display=full");
        $response = $this->httpService->invoke('GET');

        if($response->failed()) {
            throw new \RuntimeException("Failed to retrieve stock availables: " . $response->getHttpCode());
        }

        $stockAvailables = [];
        $stockData = $response->toArray()['stock_availables'] ?? [];
        
        foreach ($stockData as $stock) {
            $stockAvailables[] = StockAvailableEntity::create($stock, $this);
        }
        
        return $stockAvailables;
    }

}