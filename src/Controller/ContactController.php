<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email   = $request->request->get('nom');
            $subject = $request->request->get('email');
            $message = $request->request->get('message');

            if (!$email || !$subject || !$message) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
            } elseif (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) {
                $this->addFlash('error', 'Veuillez entrer une adresse email valide.');
            } else {
                // ✅ Anti-spam : max 3 messages par heure
                $session = $request->getSession();
                $now     = time();
                $history = $session->get('contact_history', []);
                $history = array_filter($history, function($item) use ($now, $email) {
                    return $item['email'] === $email && ($now - $item['time']) < 3600;
                });

                if (count($history) >= 3) {
                    $this->addFlash('error', 'Vous avez atteint la limite de 3 messages par heure.');
                } else {
                    $from = $_ENV['MAILER_FROM'] ?? 'contact@yohanndufresne.fr';
                    $to   = $_ENV['MAILER_TO']   ?? 'contact@yohanndufresne.fr';

                    $mail = (new Email())
                        ->from($from)
                        ->to($to)
                        ->replyTo($email)
                        ->subject('[My Assistant] ' . $subject)
                        ->html(
                            '<p><strong>De :</strong> ' . htmlspecialchars($email) . '</p>' .
                            '<p><strong>Sujet :</strong> ' . htmlspecialchars($subject) . '</p>' .
                            '<p><strong>Message :</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>'
                        );

                    try {
                        $mailer->send($mail);
                        $this->addFlash('success', 'Votre message a bien été envoyé !');
                        $history[] = ['email' => $email, 'time' => $now];
                        $session->set('contact_history', $history);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
                    }
                }
            }
        }

        return $this->render('contact/contact.html.twig');
    }
}