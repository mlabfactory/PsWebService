<?php
declare(strict_types=1);

namespace PS\Webservice\Http\Controller;

use PS\Webservice\Service\PS\Category;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoryController extends Controller
{
    private Category $categoryService;

    public function __construct(Category $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function categoryList(Request $request, Response $response): Response
    {
        $categories = $this->categoryService->categoriesList([
            'display' => 'full'
        ]);

        return response([
            'success' => true,
            'data' => $categories->toArray(),
        ]);
    }
}
