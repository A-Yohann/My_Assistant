<?php
namespace App\Controller;

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;

class PricingController extends AbstractController
{
    // ✅ Page de pricing
    #[Route('/pricing', name: 'pricing')]
    public function index(): Response
    {
        return $this->render('pricing/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    // ✅ Abonnement Pro via Stripe
    #[Route('/pricing/subscribe', name: 'pricing_subscribe')]
    public function subscribe(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'subscription',
            'customer_email'       => $user->getEmail(),
            'line_items'           => [[
                'price'    => $_ENV['STRIPE_PRICE_ID'], // ✅ ID du prix dans Stripe
                'quantity' => 1,
            ]],
            'success_url' => $this->generateUrl('pricing_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url'  => $this->generateUrl('pricing', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    // ✅ Succès abonnement
    #[Route('/pricing/success', name: 'pricing_success')]
    public function success(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user) {
            $user->setPlan('pro');
            $user->setPlanExpiresAt(new \DateTime('+1 month'));
            $em->flush();
        }

        $this->addFlash('success', '🎉 Bienvenue dans le plan Pro !');
        return $this->redirectToRoute('dashboard');
    }

    // ✅ Annuler l'abonnement
    #[Route('/pricing/cancel', name: 'pricing_cancel')]
    public function cancel(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user && $user->getStripeSubscriptionId()) {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            \Stripe\Subscription::cancel($user->getStripeSubscriptionId());
            $user->setPlan('free');
            $user->setPlanExpiresAt(null);
            $user->setStripeSubscriptionId(null);
            $em->flush();
            $this->addFlash('info', 'Votre abonnement a été annulé.');
        }

        return $this->redirectToRoute('pricing');
    }
}