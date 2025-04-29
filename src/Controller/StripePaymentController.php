<?php
// src/Controller/StripePaymentController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StripePaymentController extends AbstractController
{
    #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request): JsonResponse
    {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $amount = $request->request->get('amount');
        $orderId = $request->request->get('order_id');
        if (!$amount) {
            return new JsonResponse(['error' => 'Missing amount'], 400);
        }

        $YOUR_DOMAIN = 'http://127.0.0.1:8000'; // 🔥 Ne mets pas https si tu n'as pas de vrai certificat SSL local !

        try {
            $checkoutSession = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur', // ✅ Bien écrire en majuscule
                        'product_data' => [
                            'name' => 'Order Payment',
                        ],
                        'unit_amount' => intval($amount * 100), // montant en centimes
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $YOUR_DOMAIN . '/aya/order/confirm/' . $orderId,
                'cancel_url' => $YOUR_DOMAIN . '/payment-cancel',
            ]);

            return new JsonResponse(['id' => $checkoutSession->id]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
