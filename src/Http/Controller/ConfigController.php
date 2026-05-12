<?php
declare(strict_types=1);

namespace PS\Webservice\Http\Controller;

use PS\Webservice\Domain\Entities\CouponEntity;
use PS\Webservice\Domain\Models\CouponStorage;
use PS\Webservice\Facades\JsonDataStorage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConfigController extends CartController
{

public function makeCartRulesConfig(Request $request, Response $response, array $argv): Response
    {
        $rules = $this->cartService->getFeaturedCoupons();
        $bodyParams = $request->getParsedBody();

        if(!isset($bodyParams['rules'])) {
            throw new \InvalidArgumentException("The 'rules' parameter is mandatory in the request body for this endpoint.");
        }

        $requestedRules = $bodyParams['rules'];
        $coupons = [];
        $storage = JsonDataStorage::coupon();
        foreach ($rules as $key => $rule) {
            if(array_key_exists($rule->code, $requestedRules)) {
                $couponEntity = $rule->toArray();
                $couponEntity['id_relative'] = $requestedRules[$rule->code];

                $coupon = CouponEntity::create($couponEntity, $this->cartService);
                $coupons[] = $coupon->toArray();
                $storage->insert(new CouponStorage($coupon));
            }
        }

        return response($coupons, 201);
    }

    public function makeCarriersConfig(Request $request, Response $response, array $argv): Response
    {
        $carriers = $request->getParsedBody()['carriers'] ?? null;
        $storage = JsonDataStorage::carriers();
        foreach ($carriers as $carrier) {
            $storage->insert($carrier);
        }

        return response($carriers, 201);
    }

}