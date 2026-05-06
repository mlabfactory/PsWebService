<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service\Payments;
 
use DolzeZampa\WS\Domain\Object\OrderSession;

interface PaymentGatewayInterface
{
    public static function setApiKey(string $apiKey): self;

    public function createPaymentSession(OrderSession $order): string;
}