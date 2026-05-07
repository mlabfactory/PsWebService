<?php
declare(strict_types=1);

namespace PS\Webservice\Service\Payments;
 
use PS\Webservice\Domain\Object\OrderSession;

interface PaymentGatewayInterface
{
    public static function setApiKey(string $apiKey): self;

    public function createPaymentSession(OrderSession $order): string;
}