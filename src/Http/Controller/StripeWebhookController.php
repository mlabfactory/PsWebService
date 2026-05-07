<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Http\Controller;

use DolzeZampa\WS\Domain\Entities\CustomerEntity;
use DolzeZampa\WS\Domain\Object\ConfirmOrderSession;
use DolzeZampa\WS\Service\PS\Order;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StripeWebhookController extends Controller
{
    private Order $orderService;

    public function __construct(Order $orderService)
    {
        $this->orderService = $orderService;
    }

    public function handleWebhook(Request $request, Response $response, array $argv): Response
    {
        $payload = (string) $request->getBody();
        $sigHeader = $request->getHeaderLine('Stripe-Signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

        if (empty($endpointSecret)) {
            Log::error('Stripe webhook: STRIPE_WEBHOOK_SECRET is not configured');
            return response(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook: invalid payload received');
            return response(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook: invalid signature');
            return response(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            try {
                $this->handleCheckoutSessionCompleted($event->data->object);
            } catch (\Exception $e) {
                Log::error('Stripe webhook: failed to process checkout.session.completed: ' . $e->getMessage());
                return response(['error' => 'Failed to process event'], 500);
            }
        }

        return response(['received' => true], 200);
    }

    private function handleCheckoutSessionCompleted(\Stripe\Checkout\Session $session): void
    {
        $metadata = $session->metadata;
        $cartId = isset($metadata->cart_id) ? (int) $metadata->cart_id : 0;

        if ($cartId <= 0) {
            Log::warning('Stripe webhook: missing or invalid cart_id in metadata for session ' . $session->id);
            return;
        }

        // Idempotency: if an order for this cart already exists, skip processing
        $existingOrder = $this->orderService->getOrderByCartId($cartId);
        if ($existingOrder !== null) {
            Log::info('Stripe webhook: order for cart ' . $cartId . ' already confirmed, skipping');
            return;
        }

        $amountPaid = ($session->amount_total ?? 0) / 100;
        $idCustomer = isset($metadata->id_customer) ? (int) $metadata->id_customer : null;
        $idGuest = isset($metadata->id_guest) ? (int) $metadata->id_guest : null;
        $idCarrier = isset($metadata->id_carrier) ? (int) $metadata->id_carrier : 14;

        $customerDetails = $session->customer_details;
        $email = $customerDetails->email ?? '';
        $fullName = $customerDetails->name ?? '';
        $nameParts = explode(' ', trim($fullName), 2);
        $firstname = $nameParts[0] ?? '';
        $lastname = $nameParts[1] ?? '';

        $confirmSession = ConfirmOrderSession::create([
            'id_cart' => $cartId,
            'order_state' => ConfirmOrderSession::ORDER_STATE['payment_success'],
            'payment_label' => 'Stripe',
            'amount_paid' => $amountPaid,
            'id_customer' => $idCustomer,
            'id_guest' => $idGuest,
            'id_carrier' => $idCarrier,
            'create_account' => false,
        ], $this->orderService);

        $confirmSession->setCustomer(
            CustomerEntity::create([
                'email' => $email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'newsletter' => false,
            ], $this->orderService)
        );

        $this->orderService->confirmOrder($confirmSession);
        Log::info('Stripe webhook: order confirmed for cart ' . $cartId);
    }
}
