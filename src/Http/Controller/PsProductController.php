<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Http\Controller;

use DolzeZampa\WS\Http\Controller\Controller;
use DolzeZampa\WS\Service\PS\Product;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Support\Facades\Cache;

class PsProductController extends Controller
{
    private Product $productService;

    public function __construct(Product $productService)
    {
        $this->productService = $productService;
    }

    public function productList(Request $request, Response $response)
    {
        $productList = $this->productService->productsList();
        return response([
            'success' => true,
            'message' => 'Product list endpoint is working',
            'data' => $productList->toArray()
        ]);
    }

    public function featuredProducts(Request $request, Response $response)
    {
        $featuredProducts = $this->productService->getFeaturedProducts();
        

        return response([
            'success' => true,
            'data' => $featuredProducts->toArray()
        ]);
    }

    public function productByCategory(Request $request, Response $response)
    {
        $category = $request->getQueryParams()['category'] ?? null;
        if (!$category) {
            return response([
                'success' => false,
                'message' => 'Category query parameter is required'
            ], 400);
        }

        $categoryId = (int) $category;
        $productsByCategory = $this->productService->getProductByCategory($categoryId);
        return response([
            'success' => true,
            'data' => $productsByCategory->toArray()
        ]);
    }

    public function productDetail(Request $request, Response $response, array $args)
    {
        $slug = $args['slug'] ?? null;
        if (!$slug) {
            return response([
                'success' => false,
                'message' => 'Product slug is required'
            ], 400);
        }

        $productDetail = $this->productService->getProductDetail($slug);
        if (!$productDetail) {
            return response([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response([
            'success' => true,
            'data' => $productDetail->toArray()
        ]);
    }
}