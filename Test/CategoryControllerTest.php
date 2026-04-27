<?php
declare(strict_types=1);

use DolzeZampa\WS\Http\Controller\CategoryController;
use DolzeZampa\WS\Service\PS\Category;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CategoryControllerTest extends TestCase
{
    public function test_category_list_returns_success_payload(): void
    {
        $categoryService = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['categoriesList'])
            ->getMock();

        $categoryService->expects($this->once())
            ->method('categoriesList')
            ->willReturn(new Collection([
                [
                    'id' => 12,
                    'name' => 'Cani',
                    'url' => 'https://shop.example.com/12-cani',
                    'short_description' => 'Promo cani',
                    'title' => 'Categoria Cani',
                    'meta_description' => 'Acquista prodotti per cani',
                ],
            ]));

        $controller = new CategoryController($categoryService);
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $result = $controller->categoryList($request, $response);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame([
            'success' => true,
            'data' => [
                [
                    'id' => 12,
                    'name' => 'Cani',
                    'url' => 'https://shop.example.com/12-cani',
                    'short_description' => 'Promo cani',
                    'title' => 'Categoria Cani',
                    'meta_description' => 'Acquista prodotti per cani',
                ],
            ],
        ], json_decode((string) $result->getBody(), true));
    }
}
