<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rotas para integração com Stripe
Route::prefix('stripe')->group(function () {
    // Criar conta conectada
    Route::post('/connected-accounts', [StripeController::class, 'createConnectedAccount']);
    
    // Criar assinatura para conta conectada
    Route::post('/subscriptions', [StripeController::class, 'createSubscription']);
    
    // Criar produto na conta conectada
    Route::post('/products', [StripeController::class, 'createProduct']);
    
    // Processar pagamento com divisão
    Route::post('/payments/split', [StripeController::class, 'processPaymentWithSplit']);
    
    // Cancelar assinatura
    Route::post('/subscriptions/cancel', [StripeController::class, 'cancelSubscription']);
    
    // Rotas para onboarding
    Route::get('/onboarding/refresh', [StripeController::class, 'refreshOnboarding'])->name('stripe.onboarding.refresh');
    Route::get('/onboarding/complete', function () {
        return response()->json(['success' => true, 'message' => 'Onboarding completed']);
    })->name('stripe.onboarding.complete');

    // No arquivo routes/api.php
// No arquivo routes/api.php
Route::post('/stripe/products', [StripeController::class, 'createSubscriptionProduct']);

});

Route::post('/stripe/subscriptions/customer', [StripeController::class, 'createCustomerSubscription']);

Route::post('/stripe/subscriptions/customers', [StripeController::class, 'createCheckoutSession']);

Route::get('/stripe/list-prices', [StripeController::class, 'listConnectedAccountPrices']);

Route::get('/stripe/connected-account/requirements', [StripeController::class, 'checkAccountRequirements']);

Route::post('/stripe/connected-account/onboarding-link', [StripeController::class, 'createAccountOnboardingLink']);

Route::post('/stripe/onboarding/refresh', [StripeController::class, 'refreshOnboarding'])->name('stripe.onboarding.refresh');
Route::get('/stripe/onboarding/complete', function () {
    return response()->json(['success' => true, 'message' => 'Onboarding completed']);
})->name('stripe.onboarding.complete');

Route::post('/stripe/subscriptions/cancel', [StripeController::class, 'cancelCustomerSubscription']);

Route::post('/stripe/webhooks/checkout', [StripeController::class, 'handleCheckoutWebhook']);

Route::post('/stripe/webhooks/accounts', [StripeController::class, 'handleAccountWebhook']);