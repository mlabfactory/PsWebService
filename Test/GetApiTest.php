<?php

use Budgetcontrol\Core\Http\Controller\PaymentTypesController;
use MLAB\PHPITest\Entity\Json;
use MLAB\PHPITest\Assertions\JsonAssert;
use Slim\Http\Interfaces\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetApiTest extends \PHPUnit\Framework\TestCase
{

    public function test_get_payment_types()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $controller = new PaymentTypesController();
        $result = $controller->index($request, $response);
        $contentArray = json_decode((string) $result->getBody());

        $this->assertEquals(200, $result->getStatusCode());

        $assertionContent = new JsonAssert(new Json($contentArray));
        $assertions = json_decode(file_get_contents(__DIR__ . '/assertions/payment-types.json'));
        $assertionContent->assertJsonIsEqualToJson(
            $assertions,
            [
                'created_at',
                'updated_at',
                'uuid'
            ]
        );

        $assertionContent->assertJsonStructure((array) $assertions);
    }

}
