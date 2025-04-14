<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (SignatureVerificationException $e) {
            // Assinatura inválida
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Lidar com o evento
        switch ($event->type) {
            case 'account.updated':
                $account = $event->data->object;
                // Lógica para quando uma conta conectada é atualizada
                Log::info('Account updated: ' . $account->id);
                break;
                
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                // Lógica para quando um pagamento é bem-sucedido
                Log::info('Payment succeeded: ' . $paymentIntent->id);
                break;
                
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                // Lógica para quando um pagamento falha
                Log::error('Payment failed: ' . $paymentIntent->id);
                break;
                
            case 'subscription.created':
            case 'subscription.updated':
            case 'subscription.deleted':
                $subscription = $event->data->object;
                // Lógica para eventos de assinatura
                Log::info('Subscription event: ' . $event->type . ' - ' . $subscription->id);
                break;
                
            default:
                // Evento não tratado
                Log::info('Unhandled event: ' . $event->type);
        }

        return response()->json(['success' => true]);
    }
}

