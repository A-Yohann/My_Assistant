<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Webhook;
use Stripe\Stripe;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handle(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): Response {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $secret    = $_ENV['STRIPE_WEBHOOK_SECRET'];

        // Vérification de la signature Stripe
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            $logger->error('[Stripe Webhook] Signature invalide : ' . $e->getMessage());
            return new Response('Signature invalide', 400);
        }

        $object = $event->data->object;

        try {
            switch ($event->type) {

                case 'checkout.session.completed':
                    $email = $object->customer_details->email ?? null;
                    if (!$email) {
                        $logger->warning('[Stripe Webhook] checkout.session.completed : email manquant');
                        break;
                    }

                    $user = $userRepository->findOneBy(['email' => $email]);
                    if (!$user) {
                        $logger->warning('[Stripe Webhook] checkout.session.completed : user introuvable pour ' . $email);
                        break;
                    }

                    $user->setPlan('pro');
                    $user->setPlanExpiresAt(null);
                    $user->setStripeCustomerId($object->customer);
                    $user->setStripeSubscriptionId($object->subscription);
                    $em->flush();

                    $logger->info('[Stripe Webhook] User passé en Pro : ' . $email);

                    $mail = (new Email())
                        ->from('noreply@tonassistant.fr')
                        ->to($user->getEmail())
                        ->subject('Bienvenue dans My Assistant Pro 🎉')
                        ->html('<p>Votre abonnement Pro est bien activé. Merci pour votre confiance !</p>');

                    $mailer->send($mail);
                    break;

                case 'customer.subscription.deleted':
                case 'customer.subscription.updated':
                    $status     = $object->status ?? null;
                    $customerId = $object->customer ?? null;

                    if (!$customerId) {
                        $logger->warning('[Stripe Webhook] subscription event : customerId manquant');
                        break;
                    }

                    $user = $userRepository->findOneBy(['stripeCustomerId' => $customerId]);
                    if (!$user) {
                        $logger->warning('[Stripe Webhook] subscription event : user introuvable pour customer ' . $customerId);
                        break;
                    }

                    if (in_array($status, ['canceled', 'unpaid', 'past_due'])) {
                        $user->setPlan('free');
                        $user->setPlanExpiresAt(new \DateTimeImmutable());
                        $user->setStripeSubscriptionId(null);
                        $em->flush();

                        $logger->info('[Stripe Webhook] User repassé en Free : ' . $user->getEmail() . ' (status: ' . $status . ')');
                    }
                    break;

                default:
                    $logger->info('[Stripe Webhook] Événement ignoré : ' . $event->type);
            }

        } catch (\Exception $e) {
            // On log l'erreur mais on retourne 200 pour éviter que Stripe réessaie en boucle
            $logger->error('[Stripe Webhook] Erreur inattendue : ' . $e->getMessage(), [
                'event_type' => $event->type,
                'trace'      => $e->getTraceAsString(),
            ]);
        }

        return new Response('OK', 200);
    }
}