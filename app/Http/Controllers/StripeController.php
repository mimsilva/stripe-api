<?php

namespace App\Http\Controllers;

use Exception;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\Product;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class StripeController extends Controller
{
    public function __construct()
    {
        // Configurar a chave API do Stripe
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    /**
     * Criar uma conta conectada no Stripe
     */
    public function createConnectedAccount(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'name' => 'required|string',
                'business_type' => 'required|string|in:individual,company',
                'country' => 'required|string|size:2',
                'phone' => 'required|string',
            ]);

            // Criar conta conectada
            $account = Account::create([
                'type' => 'express',
                'country' => $validated['country'],
                'email' => $validated['email'],
                'business_type' => $validated['business_type'],
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                    'name' => $validated['name'],
                    'support_phone' => $validated['phone'],
                ],
            ]);

            // Criar link de onboarding
            $accountLink = \Stripe\AccountLink::create([
                'account' => $account->id,
                'refresh_url' => route('stripe.onboarding.refresh'),
                'return_url' => route('stripe.onboarding.complete'),
                'type' => 'account_onboarding',
            ]);

            return response()->json([
                'success' => true,
                'account_id' => $account->id,
                'onboarding_url' => $accountLink->url
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar uma assinatura para uma conta conectada
     */
    public function createSubscription(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_id' => 'required|string',
                'payment_method' => 'required|string',
                'price_id' => 'required|string',
                'email' => 'required|email',
                'name' => 'required|string',
            ]);

            // Criar ou recuperar cliente
            $customer = Customer::create([
                'email' => $validated['email'],
                'name' => $validated['name'],
                'payment_method' => $validated['payment_method'],
                'invoice_settings' => [
                    'default_payment_method' => $validated['payment_method'],
                ],
            ]);

            // Criar assinatura
            $subscription = Subscription::create([
                'customer' => $customer->id,
                'items' => [
                    ['price' => $validated['price_id']],
                ],
                'application_fee_percent' => 10, // Taxa da plataforma (10%)
                'transfer_data' => [
                    'destination' => $validated['account_id'],
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            return response()->json([
                'success' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar um produto na conta conectada
     */
    public function createProduct(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_id' => 'required|string',
                'name' => 'required|string',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'currency' => 'required|string|size:3',
                'interval' => 'nullable|string|in:day,week,month,year',
                'interval_count' => 'nullable|integer',
            ]);

            // Configurar a conta conectada para criar o produto
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            // Criar produto
            $product = Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ], ['stripe_account' => $validated['account_id']]);

            // Verificar se é um produto de assinatura ou único
            if (isset($validated['interval'])) {
                // Criar preço recorrente (assinatura)
                $price = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $validated['price'] * 100, // Stripe usa centavos
                    'currency' => $validated['currency'],
                    'recurring' => [
                        'interval' => $validated['interval'],
                        'interval_count' => $validated['interval_count'] ?? 1,
                    ],
                ], ['stripe_account' => $validated['account_id']]);
            } else {
                // Criar preço único
                $price = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $validated['price'] * 100, // Stripe usa centavos
                    'currency' => $validated['currency'],
                ], ['stripe_account' => $validated['account_id']]);
            }

            return response()->json([
                'success' => true,
                'product_id' => $product->id,
                'price_id' => $price->id,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Processar pagamento com divisão para múltiplas contas conectadas
     */
    public function processPaymentWithSplit(Request $request)
    {
        try {
            $validated = $request->validate([
                'payment_method' => 'required|string',
                'amount' => 'required|numeric',
                'currency' => 'required|string|size:3',
                'description' => 'nullable|string',
                'transfers' => 'required|array',
                'transfers.*.account' => 'required|string',
                'transfers.*.amount' => 'required|numeric',
                'customer_email' => 'required|email',
                'customer_name' => 'required|string',
            ]);

            // Verificar se a soma dos valores de transferência é menor ou igual ao valor total
            $transferTotal = array_sum(array_column($validated['transfers'], 'amount'));
            if ($transferTotal > $validated['amount']) {
                return response()->json([
                    'success' => false,
                    'message' => 'A soma das transferências não pode exceder o valor total'
                ], 400);
            }

            // Criar ou recuperar cliente
            $customer = Customer::create([
                'email' => $validated['customer_email'],
                'name' => $validated['customer_name'],
                'payment_method' => $validated['payment_method'],
            ]);

            // Preparar dados de transferência
            $transferGroup = 'order_' . time();
            $transferData = [];
            
            foreach ($validated['transfers'] as $transfer) {
                $transferData[] = [
                    'amount' => $transfer['amount'] * 100, // Stripe usa centavos
                    'destination' => $transfer['account'],
                    'currency' => $validated['currency'],
                ];
            }

            // Criar intent de pagamento
            $paymentIntent = PaymentIntent::create([
                'amount' => $validated['amount'] * 100, // Stripe usa centavos
                'currency' => $validated['currency'],
                'customer' => $customer->id,
                'payment_method' => $validated['payment_method'],
                'off_session' => true,
                'confirm' => true,
                'description' => $validated['description'] ?? null,
                'transfer_group' => $transferGroup,
            ]);

            // Processar transferências após pagamento confirmado
            if ($paymentIntent->status === 'succeeded') {
                $transfers = [];
                foreach ($transferData as $transfer) {
                    $transfers[] = \Stripe\Transfer::create([
                        'amount' => $transfer['amount'],
                        'currency' => $transfer['currency'],
                        'destination' => $transfer['destination'],
                        'transfer_group' => $transferGroup,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'transfers' => array_map(function($t) { return $t->id; }, $transfers),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento não foi concluído',
                    'status' => $paymentIntent->status,
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar uma assinatura
     */
    public function cancelSubscription(Request $request)
    {
        try {
            $validated = $request->validate([
                'subscription_id' => 'required|string',
                'cancel_at_period_end' => 'nullable|boolean',
            ]);

            $cancelAtPeriodEnd = $validated['cancel_at_period_end'] ?? false;

            if ($cancelAtPeriodEnd) {
                // Cancelar no final do período atual
                $subscription = Subscription::update($validated['subscription_id'], [
                    'cancel_at_period_end' => true,
                ]);
            } else {
                // Cancelar imediatamente
                $subscription = Subscription::retrieve($validated['subscription_id']);
                $subscription->cancel();
            }

            return response()->json([
                'success' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

/**
 * Cliente final assina um produto e fornece dados de pagamento
 */
public function createCustomerSubscription(Request $request)
{
    try {
        // Validação básica
        $validated = $request->validate([
            // Dados do produto/plano
            'price_id' => 'required|string',
            'account_id' => 'required|string', // ID da conta conectada do fornecedor
            
            // Dados do cliente
            'email' => 'required|email',
            'name' => 'required|string',
            
            // Tipo de pagamento
            'payment_type' => 'required|string|in:card,pix,boleto',
            
            // Dados de cobrança
            'phone' => 'nullable|string',
            'address_line1' => 'nullable|string',
            'address_city' => 'nullable|string',
            'address_state' => 'nullable|string',
            'address_postal_code' => 'nullable|string',
            'address_country' => 'nullable|string',
            'tax_id' => 'nullable|string', // CPF/CNPJ para métodos brasileiros
            
            // Configurações da assinatura
            'application_fee_percent' => 'nullable|numeric',
        ]);
        
        // Validação específica para cartão
        if ($validated['payment_type'] === 'card') {
            $cardValidated = $request->validate([
                'card_number' => 'required|string',
                'card_exp_month' => 'required|integer',
                'card_exp_year' => 'required|integer',
                'card_cvc' => 'required|string',
            ]);
            $validated = array_merge($validated, $cardValidated);
        }

        // Configurar a chave API
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        
        // Passo 1: Criar o cliente
        $customerData = [
            'email' => $validated['email'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => [
                'line1' => $validated['address_line1'] ?? null,
                'city' => $validated['address_city'] ?? null,
                'state' => $validated['address_state'] ?? null,
                'postal_code' => $validated['address_postal_code'] ?? null,
                'country' => $validated['address_country'] ?? null,
            ],
        ];
        
        // Adicionar tax_id para métodos brasileiros
        if (in_array($validated['payment_type'], ['pix', 'boleto']) && isset($validated['tax_id'])) {
            $customerData['tax_id'] = $validated['tax_id'];
        }
        
        $customer = \Stripe\Customer::create($customerData);
        
        // Passo 2: Configurar o método de pagamento com base no tipo
        $paymentMethodId = null;
        
        if ($validated['payment_type'] === 'card') {
            // Criar token do cartão
            $token = \Stripe\Token::create([
                'card' => [
                    'number' => $validated['card_number'],
                    'exp_month' => $validated['card_exp_month'],
                    'exp_year' => $validated['card_exp_year'],
                    'cvc' => $validated['card_cvc'],
                ],
            ]);
            
            // Adicionar cartão ao cliente
            $card = \Stripe\Customer::createSource(
                $customer->id,
                ['source' => $token->id]
            );
            
            // Definir como padrão
            \Stripe\Customer::update($customer->id, [
                'default_source' => $card->id,
            ]);
            
            $paymentMethodId = $card->id;
        } elseif ($validated['payment_type'] === 'pix' || $validated['payment_type'] === 'boleto') {
            // Para PIX e Boleto, criamos um PaymentMethod
            $paymentMethodData = [
                'type' => $validated['payment_type'],
                'billing_details' => [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'address' => [
                        'line1' => $validated['address_line1'] ?? null,
                        'city' => $validated['address_city'] ?? null,
                        'state' => $validated['address_state'] ?? null,
                        'postal_code' => $validated['address_postal_code'] ?? null,
                        'country' => $validated['address_country'] ?? null,
                    ],
                ],
            ];
            
            // Adicionar dados específicos para Boleto
            if ($validated['payment_type'] === 'boleto' && isset($validated['tax_id'])) {
                $paymentMethodData['boleto'] = [
                    'tax_id' => $validated['tax_id'],
                ];
            }
            
            // Criar o método de pagamento
            $paymentMethod = \Stripe\PaymentMethod::create($paymentMethodData);
            
            // Anexar ao cliente
            $paymentMethod->attach(['customer' => $customer->id]);
            
            // Definir como padrão
            \Stripe\Customer::update($customer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod->id,
                ],
            ]);
            
            $paymentMethodId = $paymentMethod->id;
        }
        
        // Passo 3: Criar a assinatura
        $subscriptionData = [
            'customer' => $customer->id,
            'items' => [
                ['price' => $validated['price_id']],
            ],
            'expand' => ['latest_invoice.payment_intent'],
        ];
        
        // Adicionar método de pagamento padrão
        if ($validated['payment_type'] === 'card') {
            $subscriptionData['default_source'] = $paymentMethodId;
        } else {
            $subscriptionData['default_payment_method'] = $paymentMethodId;
        }
        
        // Adicionar taxa de aplicação, se especificada
        if (isset($validated['application_fee_percent'])) {
            $subscriptionData['application_fee_percent'] = $validated['application_fee_percent'];
        }
        
        // Adicionar dados de transferência para a conta conectada
        $subscriptionData['transfer_data'] = [
            'destination' => $validated['account_id'],
        ];
        dd(json_encode($subscriptionData));
        
        $subscription = \Stripe\Subscription::create($subscriptionData);
        
        // Verificar o status da assinatura e do pagamento
        $status = $subscription->status;
        $paymentStatus = null;
        $paymentIntent = null;
        
        if (isset($subscription->latest_invoice->payment_intent)) {
            $paymentIntent = $subscription->latest_invoice->payment_intent;
            $paymentStatus = $paymentIntent->status;
        }
        
        // Preparar resposta
        $response = [
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name,
            ],
            'payment_method' => [
                'id' => $paymentMethodId,
                'type' => $validated['payment_type'],
            ],
            'subscription' => [
                'id' => $subscription->id,
                'status' => $status,
                'current_period_start' => date('Y-m-d H:i:s', $subscription->current_period_start),
                'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
            ],
        ];
        
        // Adicionar informações específicas do cartão
        if ($validated['payment_type'] === 'card' && isset($card)) {
            $response['payment_method']['card'] = [
                'brand' => $card->brand,
                'last4' => $card->last4,
                'exp_month' => $card->exp_month,
                'exp_year' => $card->exp_year,
            ];
        }
        
        // Adicionar informações de pagamento, se disponíveis
        if ($paymentIntent) {
            $response['payment'] = [
                'id' => $paymentIntent->id,
                'status' => $paymentStatus,
                'amount' => $paymentIntent->amount / 100, // Converter de centavos
                'currency' => $paymentIntent->currency,
            ];
            
            // Se for PIX, adicionar código QR e informações de pagamento
            if ($validated['payment_type'] === 'pix' && isset($paymentIntent->next_action->pix_display_qr_code)) {
                $response['payment']['pix'] = [
                    'qr_code' => $paymentIntent->next_action->pix_display_qr_code->data,
                    'expires_at' => date('Y-m-d H:i:s', $paymentIntent->next_action->pix_display_qr_code->expires_at),
                ];
            }
            
            // Se for Boleto, adicionar informações do boleto
            if ($validated['payment_type'] === 'boleto' && isset($paymentIntent->next_action->boleto_display_details)) {
                $response['payment']['boleto'] = [
                    'pdf' => $paymentIntent->next_action->boleto_display_details->pdf,
                    'expires_at' => date('Y-m-d H:i:s', $paymentIntent->next_action->boleto_display_details->expires_at),
                ];
            }
        }
        
        return response()->json($response);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(), // Adicionar stack trace para depuração
        ], 500);
    }
}

/**
 * Criar sessão de checkout com split de pagamento
 */
/**
 * Criar sessão de checkout com split de pagamento
 */
public function createCheckoutSession(Request $request)
{
    try {
        $validated = $request->validate([
            'price_id' => 'required|string',
            'account_id' => 'required|string', // ID da conta conectada do fornecedor
            'application_fee_percent' => 'nullable|numeric',
            'success_url' => 'required|string',
            'cancel_url' => 'required|string',
            'customer_email' => 'nullable|email',
        ]);

        // Configurar a chave API
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        
        // IMPORTANTE: Verificar se o preço existe na conta conectada
        try {
            // Tentar recuperar o preço no contexto da conta conectada
            $price = \Stripe\Price::retrieve(
                $validated['price_id'],
                ['stripe_account' => $validated['account_id']]
            );
            
            // Se chegou aqui, o preço existe na conta conectada
            $priceExists = true;
            $priceLocation = 'connected_account';
        } catch (\Exception $e) {
            // Preço não existe na conta conectada, tentar na conta da plataforma
            try {
                $price = \Stripe\Price::retrieve($validated['price_id']);
                $priceExists = true;
                $priceLocation = 'platform_account';
            } catch (\Exception $e2) {
                // Preço não existe em nenhum lugar
                return response()->json([
                    'success' => false,
                    'message' => 'Preço não encontrado em nenhuma conta: ' . $e2->getMessage(),
                    'price_id' => $validated['price_id'],
                    'account_id' => $validated['account_id'],
                ], 404);
            }
        }
        
        // Criar a sessão de checkout com base na localização do preço
        if ($priceLocation === 'connected_account') {
            // O preço está na conta conectada, criar a sessão no contexto da conta conectada
            $session = \Stripe\Checkout\Session::create(
                [
                    'payment_method_types' => ['card'],
                    'line_items' => [
                        [
                            'price' => $validated['price_id'],
                            'quantity' => 1,
                        ],
                    ],
                    'mode' => 'subscription',
                    'success_url' => $validated['success_url'],
                    'cancel_url' => $validated['cancel_url'],
                    'customer_email' => $validated['customer_email'] ?? null,
                ],
                ['stripe_account' => $validated['account_id']] // Especificar a conta conectada
            );
        } else {
            // O preço está na conta da plataforma, criar a sessão com split de pagamento
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $validated['price_id'],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'subscription',
                'subscription_data' => [
                    'application_fee_percent' => $validated['application_fee_percent'] ?? 10,
                    'transfer_data' => [
                        'destination' => $validated['account_id'],
                    ],
                ],
                'success_url' => $validated['success_url'],
                'cancel_url' => $validated['cancel_url'],
                'customer_email' => $validated['customer_email'] ?? null,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'price_location' => $priceLocation,
            'price_details' => [
                'id' => $price->id,
                'product' => $price->product,
                'unit_amount' => $price->unit_amount / 100,
                'currency' => $price->currency,
            ],
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'request_data' => $request->all(),
        ], 500);
    }
}

/**
 * Listar preços de uma conta conectada (fornecedor)
 */
public function listConnectedAccountPrices(Request $request)
{
    try {
        // Validar parâmetros
        $validated = $request->validate([
            'account_id' => 'required|string',
        ]);
        
        $accountId = $validated['account_id'];
        $limit = $request->input('limit', 100);
        $active = $request->has('active') ? $request->boolean('active') : null;
        $productId = $request->input('product_id');
        
        // Configurar a chave API da plataforma
        $apiKey = env('STRIPE_SECRET_KEY');
        \Stripe\Stripe::setApiKey($apiKey);
        
        // Preparar parâmetros para a consulta
        $params = ['limit' => $limit];
        if ($active !== null) {
            $params['active'] = $active;
        }
        if ($productId) {
            $params['product'] = $productId;
        }
        
        // Buscar preços da conta conectada
        $prices = \Stripe\Price::all(
            $params,
            ['stripe_account' => $accountId] // Especificar a conta conectada
        );
        
        // Formatar a resposta
        $priceList = [];
        foreach ($prices->data as $price) {
            $priceList[] = [
                'id' => $price->id,
                'product' => $price->product,
                'unit_amount' => $price->unit_amount / 100,
                'currency' => $price->currency,
                'active' => $price->active,
                'recurring' => $price->recurring ? [
                    'interval' => $price->recurring->interval,
                    'interval_count' => $price->recurring->interval_count,
                ] : null,
                'created' => date('Y-m-d H:i:s', $price->created),
            ];
        }
        
        // Buscar detalhes dos produtos relacionados da conta conectada
        $productIds = array_unique(array_column($priceList, 'product'));
        $products = [];
        
        if (!empty($productIds)) {
            foreach ($productIds as $id) {
                try {
                    $product = \Stripe\Product::retrieve(
                        $id,
                        ['stripe_account' => $accountId] // Especificar a conta conectada
                    );
                    $products[$id] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'active' => $product->active,
                    ];
                } catch (\Exception $e) {
                    $products[$id] = [
                        'id' => $id,
                        'name' => 'Produto não encontrado',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'account_id' => $accountId,
            'prices' => $priceList,
            'products' => $products,
            'count' => count($priceList),
            'api_mode' => strpos($apiKey, 'sk_test_') === 0 ? 'test' : 'live',
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'api_mode' => strpos(env('STRIPE_SECRET_KEY'), 'sk_test_') === 0 ? 'test' : 'live',
        ], 500);
    }
}

/**
 * Verificar detalhadamente os requisitos de uma conta conectada
 */
public function checkAccountRequirements(Request $request)
{
    try {
        $accountId = $request->input('account_id');
        
        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'ID da conta é obrigatório',
            ], 400);
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        
        // Recuperar a conta com todos os detalhes
        $account = \Stripe\Account::retrieve([
            'id' => $accountId,
            'expand' => ['requirements'],
        ]);
        
        // Verificar se a conta existe
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Conta não encontrada',
            ], 404);
        }
        
        // Formatar a resposta com informações detalhadas
        $response = [
            'success' => true,
            'account_id' => $account->id,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
            'requirements' => [
                'disabled_reason' => $account->requirements->disabled_reason,
                'currently_due' => $account->requirements->currently_due,
                'eventually_due' => $account->requirements->eventually_due,
                'past_due' => $account->requirements->past_due,
                'pending_verification' => $account->requirements->pending_verification,
            ],
            'capabilities' => $account->capabilities,
            'business_profile' => $account->business_profile,
            'business_type' => $account->business_type,
            'created' => date('Y-m-d H:i:s', $account->created),
            'details_submitted' => $account->details_submitted,
        ];
        
        // Adicionar informações sobre como resolver os problemas
        $response['next_steps'] = [
            'description' => 'A conta precisa completar o processo de onboarding para habilitar cobranças',
            'action' => 'Gere um novo link de onboarding e envie para o dono da conta',
            'endpoint' => '/api/stripe/connected-account/create-link',
            'payload_example' => [
                'account_id' => $accountId,
                'refresh_url' => 'https://seu-site.com/refresh',
                'return_url' => 'https://seu-site.com/return',
            ],
        ];
        
        return response()->json($response);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
/**
 * Gerar um novo link de onboarding para uma conta conectada
 */
public function createAccountOnboardingLink(Request $request)
{
    try {
        $validated = $request->validate([
            'account_id' => 'required|string',
            'refresh_url' => 'required|string',
            'return_url' => 'required|string',
        ]);

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        
        // Verificar o status atual da conta
        $account = \Stripe\Account::retrieve($validated['account_id']);
        
        // Criar um link de onboarding
        $accountLink = \Stripe\AccountLink::create([
            'account' => $validated['account_id'],
            'refresh_url' => $validated['refresh_url'],
            'return_url' => $validated['return_url'],
            'type' => 'account_onboarding',
            'collect' => 'eventually_due', // Coletar todos os requisitos pendentes
        ]);
        
        return response()->json([
            'success' => true,
            'account_id' => $validated['account_id'],
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
            'details_submitted' => $account->details_submitted,
            'onboarding_url' => $accountLink->url,
            'expires_at' => date('Y-m-d H:i:s', $accountLink->expires_at),
            'instructions' => 'Envie este URL para o dono da conta conectada. Ele precisa acessar este link e fornecer todas as informações solicitadas pelo Stripe para habilitar cobranças.',
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Rota para atualizar o link de onboarding
 */
public function refreshOnboarding(Request $request)
{
    try {
        $validated = $request->validate([
            'account_id' => 'required|string',
        ]);

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        // Verificar se a conta existe
        $account = \Stripe\Account::retrieve($validated['account_id']);
        
        // Criar um novo link de onboarding
        $accountLink = \Stripe\AccountLink::create([
            'account' => $validated['account_id'],
            'refresh_url' => route('stripe.onboarding.refresh'),
            'return_url' => route('stripe.onboarding.complete'),
            'type' => 'account_onboarding',
            'collect' => 'currently_due', // Focar nos requisitos pendentes atuais
        ]);

        return response()->json([
            'success' => true,
            'account_id' => $validated['account_id'],
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
            'onboarding_url' => $accountLink->url,
            'expires_at' => date('Y-m-d H:i:s', $accountLink->expires_at),
            'instructions' => 'Envie este URL para o dono da conta conectada completar a verificação pendente.'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Cancelar assinatura do cliente
 */
public function cancelCustomerSubscription(Request $request)
{
    try {
        $validated = $request->validate([
            'subscription_id' => 'required|string',
            'cancel_at_period_end' => 'nullable|boolean',
            'cancellation_reason' => 'nullable|string',
            'connected_account_id' => 'nullable|string', // Opcional, apenas se a assinatura estiver em uma conta conectada
        ]);

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        
        $subscriptionId = $validated['subscription_id'];
        $cancelAtPeriodEnd = $validated['cancel_at_period_end'] ?? false;
        $cancellationReason = $validated['cancellation_reason'] ?? null;
        $connectedAccountId = $validated['connected_account_id'] ?? null;
        
        // Determinar se a assinatura está em uma conta conectada
        $options = [];
        if ($connectedAccountId) {
            $options['stripe_account'] = $connectedAccountId;
        }
        
        // Recuperar a assinatura para verificar seu status atual
        try {
            $subscription = \Stripe\Subscription::retrieve(
                $subscriptionId,
                $options
            );
        } catch (\Exception $e) {
            // Se não conseguir encontrar na conta especificada, tentar na conta principal
            if ($connectedAccountId) {
                try {
                    $subscription = \Stripe\Subscription::retrieve($subscriptionId);
                    // Se encontrou na conta principal, atualizar as opções
                    $options = [];
                    $connectedAccountId = null;
                } catch (\Exception $innerE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Assinatura não encontrada em nenhuma conta: ' . $innerE->getMessage(),
                    ], 404);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Assinatura não encontrada: ' . $e->getMessage(),
                ], 404);
            }
        }
        
        // Verificar se a assinatura já está cancelada
        if ($subscription->status === 'canceled') {
            return response()->json([
                'success' => true,
                'message' => 'Esta assinatura já está cancelada.',
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
            ]);
        }
        
        // Verificar se a assinatura já está configurada para cancelar no final do período
        if ($subscription->cancel_at_period_end && $cancelAtPeriodEnd) {
            return response()->json([
                'success' => true,
                'message' => 'Esta assinatura já está configurada para cancelar no final do período atual.',
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
                'cancel_at' => isset($subscription->cancel_at) ? date('Y-m-d H:i:s', $subscription->cancel_at) : null,
            ]);
        }
        
        // Preparar os metadados de cancelamento
        $existingMetadata = $subscription->metadata ? $subscription->metadata->toArray() : [];
        $newMetadata = array_merge($existingMetadata, [
            'cancellation_reason' => $cancellationReason,
            'canceled_at' => time(),
        ]);
        
        // Processar o cancelamento
        if ($cancelAtPeriodEnd) {
            // Cancelar no final do período atual
            $updatedSubscription = \Stripe\Subscription::update(
                $subscriptionId, 
                [
                    'cancel_at_period_end' => true,
                    'metadata' => $newMetadata,
                ],
                $options
            );
            
            $message = 'Assinatura configurada para cancelar no final do período atual.';
        } else {
            // Cancelar imediatamente - CORRIGIDO: usar o método cancel() na instância
            // Atualizar metadados primeiro
            if (!empty($newMetadata)) {
                $subscription->metadata = $newMetadata;
                $subscription->save($options);
            }
            
            // Depois chamar o método cancel() na instância
            $updatedSubscription = $subscription->cancel(['prorate' => true]);
            
            $message = 'Assinatura cancelada imediatamente.';
        }
        
        // Registrar o cancelamento no seu sistema (opcional)
        // Exemplo: \App\Models\SubscriptionLog::create([...]);
        
        // Preparar a resposta
        $response = [
            'success' => true,
            'message' => $message,
            'subscription_id' => $updatedSubscription->id,
            'status' => $updatedSubscription->status,
            'cancel_at_period_end' => $updatedSubscription->cancel_at_period_end,
            'current_period_end' => date('Y-m-d H:i:s', $updatedSubscription->current_period_end),
        ];
        
        // Adicionar informações adicionais se disponíveis
        if (isset($updatedSubscription->canceled_at)) {
            $response['canceled_at'] = date('Y-m-d H:i:s', $updatedSubscription->canceled_at);
        }
        
        if (isset($updatedSubscription->cancel_at)) {
            $response['cancel_at'] = date('Y-m-d H:i:s', $updatedSubscription->cancel_at);
        }
        
        return response()->json($response);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Processar webhook de checkout completado
 */
public function handleCheckoutWebhook(Request $request)
{
    $payload = $request->getContent();
    $sig_header = $request->header('Stripe-Signature');
    $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
    
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
        $session = $event->data->object;
        Log::info('Nova assinatura criada via checkout', [
            'event' => $event
        ]);
        // Processar o evento
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            Log::info('Nova assinatura criada via checkout', [
                'session' => $session
            ]);
            // Verificar se é uma assinatura
            if ($session->mode === 'subscription' && isset($session->subscription)) {
                $subscriptionId = $session->subscription;
                $customerId = $session->customer;
                
                // Salvar a assinatura no banco de dados
                // Exemplo:
                /*
                \App\Models\CustomerSubscription::create([
                    'subscription_id' => $subscriptionId,
                    'customer_id' => $customerId,
                    'session_id' => $session->id,
                    'status' => 'active',
                    'connected_account_id' => $session->metadata->connected_account_id ?? null,
                ]);
                */
                
                Log::info('Nova assinatura criada via checkout', [
                    'subscription_id' => $subscriptionId,
                    'customer_id' => $customerId,
                    'session_id' => $session->id,
                ]);
            }
        }
        
        return response()->json(['status' => 'success']);
    } catch (\UnexpectedValueException $e) {
        Log::info(json_encode(['error' => 'Webhook error: ' . $e->getMessage()]));
        return response()->json(['error' => 'Webhook error: ' . $e->getMessage()], 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        Log::info(json_encode(['error' => 'Webhook error: ' . $e->getMessage()]));
        return response()->json(['error' => 'Webhook signature error: ' . $e->getMessage()], 400);
    } catch (Exception $e) {
        Log::info(json_encode(['error' => 'Webhook error: ' . $e->getMessage()]));
        return response()->json(['error' => 'Webhook error: ' . $e->getMessage()], 400);
    }
}

/**
 * Processar webhook de atualização de conta conectada
 */
public function handleAccountWebhook(Request $request)
{
    $payload = $request->getContent();
    $sig_header = $request->header('Stripe-Signature');
    $endpoint_secret = env('STRIPE_ACCOUNT_WEBHOOK_SECRET');
    
    // Ambiente de desenvolvimento - pular verificação de assinatura
    if (app()->environment('local', 'development')) {
        try {
            $data = json_decode($payload, true);
            $event = json_decode($payload);
            
            // Verificar se é um evento válido
            if (!isset($event->type) || $event->type !== 'account.updated') {
                return response()->json(['error' => 'Evento inválido'], 400);
            }
            
            // Processar o evento
            $account = $event->data->object;
            $accountId = $account->id;
            
            Log::info('Webhook de conta recebido (modo de teste)', [
                'event_type' => $event->type,
                'account_id' => $accountId,
            ]);
            
            // Usar a função compartilhada para verificar e processar o status da conta
            $this->processAccountStatus($account);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook error: ' . $e->getMessage()], 400);
        }
    }
    
    // Ambiente de produção - verificar assinatura
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
        
        // Verificar se é um evento de atualização de conta
        if ($event->type === 'account.updated') {
            $account = $event->data->object;
            
            // Usar a função compartilhada para verificar e processar o status da conta
            $this->processAccountStatus($account);
        }
        
        return response()->json(['status' => 'success']);
    } catch (\UnexpectedValueException $e) {
        \Log::error('Webhook error: ' . $e->getMessage());
        return response()->json(['error' => 'Webhook error: ' . $e->getMessage()], 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        \Log::error('Webhook signature error: ' . $e->getMessage());
        return response()->json(['error' => 'Webhook signature error: ' . $e->getMessage()], 400);
    } catch (Exception $e) {
        \Log::error('Webhook error: ' . $e->getMessage());
        return response()->json(['error' => 'Webhook error: ' . $e->getMessage()], 400);
    }
}

/**
 * Função compartilhada para processar o status da conta
 * Pode ser usada tanto pelo webhook quanto pela verificação direta
 */
private function processAccountStatus($account)
{
    $accountId = $account->id;
    
    // Verificar se a conta está habilitada para cobranças
    if ($account->charges_enabled) {
        // Verificar se a conta já estava habilitada antes (para evitar logs duplicados)
        $wasEnabledBefore = $this->wasAccountEnabledBefore($accountId);
        
        if (!$wasEnabledBefore) {
            // Marcar como habilitada no banco de dados
            $this->markAccountAsEnabled($accountId);
            
            // Gerar log
            Log::info('Conta conectada agora está pronta para vender', [
                'account_id' => $accountId,
                'business_name' => $account->business_profile->name ?? 'N/A',
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled ?? false,
            ]);
        }
        
        return true; // Conta está pronta
    }
    
    return false; // Conta não está pronta
}

/**
 * Endpoint para verificar diretamente se uma conta está pronta
 */
public function checkAccountReadiness(Request $request)
{
    $accountId = $request->input('account_id');
    
    if (!$accountId) {
        return response()->json([
            'success' => false,
            'message' => 'ID da conta é obrigatório'
        ], 400);
    }
    
    try {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        
        // Buscar a conta diretamente do Stripe
        $account = \Stripe\Account::retrieve($accountId);
        
        // Usar a mesma função que o webhook usa
        $isReady = $this->processAccountStatus($account);
        
        return response()->json([
            'success' => true,
            'account_id' => $accountId,
            'is_ready' => $isReady,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled ?? false,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Verificar se a conta já estava habilitada anteriormente
 */
private function wasAccountEnabledBefore($accountId)
{
    // Implementação depende do seu modelo de dados
    // Exemplo:
    // return \App\Models\ConnectedAccount::where('account_id', $accountId)
    //     ->where('charges_enabled', true)
    //     ->exists();
    
    // Implementação temporária para exemplo
    return false;
}

/**
 * Marcar a conta como habilitada no banco de dados
 */
private function markAccountAsEnabled($accountId)
{
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'apikey' => env('SUPABASE_API_KEY') // Armazene sua chave API no .env
    ])->post('https://jmjzswpmxsrusdgltlkx.supabase.co/rest/v1/rpc/atualizacao_status_cc', [
        'id_contaconectada' => $accountId,
        'novostatus' => true
    ]);
    
    if ($response->successful()) {
        return response()->json([
            'success' => true,
            'message' => 'Status da conta conectada atualizado com sucesso',
            'data' => $response->json()
        ]);
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao atualizar status da conta conectada',
            'error' => $response->body()
        ], $response->status());
    }
    Log::info('Conta marcada como habilitada', ['account_id' => $accountId]);
}
}