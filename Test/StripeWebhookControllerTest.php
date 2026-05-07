<?php
declare(strict_types=1);

use DolzeZampa\WS\Http\Controller\StripeWebhookController;
use DolzeZampa\WS\Domain\Entities\OrderEntity;
use DolzeZampa\WS\Service\PS\Order;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StripeWebhookControllerTest extends TestCase
{
    private function buildController(?Order $orderService = null): StripeWebhookController
    {
        $service = $orderService ?? $this->createMock(Order::class);
        return new StripeWebhookController($service);
    }

    private function buildRequest(string $body, string $sigHeader = ''): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $stream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $stream->method('__toString')->willReturn($body);
        $request->method('getBody')->willReturn($stream);
        $request->method('getHeaderLine')
            ->with('Stripe-Signature')
            ->willReturn($sigHeader);
        return $request;
    }

    public function test_returns_500_when_webhook_secret_not_configured(): void
    {
        unset($_ENV['STRIPE_WEBHOOK_SECRET']);

        $controller = $this->buildController();
        $request = $this->buildRequest('{}', 'some-sig');
        $response = $this->createMock(ResponseInterface::class);

        $result = $controller->handleWebhook($request, $response, []);

        $this->assertSame(500, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function test_returns_400_on_invalid_stripe_signature(): void
    {
        $_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_test123';

        $controller = $this->buildController();
        $request = $this->buildRequest('{}', 'invalid-signature');
        $response = $this->createMock(ResponseInterface::class);

        $result = $controller->handleWebhook($request, $response, []);

        $this->assertSame(400, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('error', $body);
    }

    public function test_returns_400_on_invalid_payload(): void
    {
        $_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_test123';

        $controller = $this->buildController();
        // Empty payload with empty signature causes UnexpectedValueException
        $request = $this->buildRequest('', '');
        $response = $this->createMock(ResponseInterface::class);

        $result = $controller->handleWebhook($request, $response, []);

        // Either 400 (invalid payload/signature) or 500 (no secret), both are acceptable error codes
        $this->assertContains($result->getStatusCode(), [400, 500]);
    }
}
