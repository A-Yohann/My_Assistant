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
        $data = [];
        $success = null;
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            if (!empty($data['message'])) {
                try {
                    $email = (new Email())
                        ->from('no-reply@myassistant.fr')
                        ->to('contact@myassistant.fr')
                        ->subject('Nouveau message de contact')
                        ->html('<p><strong>Email:</strong> '.htmlspecialchars($data['nom']).'</p>' .
                               '<p><strong>Sujet:</strong> '.htmlspecialchars($data['email']).'</p>' .
                               '<p><strong>Message:</strong><br>'.nl2br(htmlspecialchars($data['message'])).'</p>');
                    $mailer->send($email);
                    $success = true;
                    $this->addFlash('success', 'Votre message a bien été envoyé !');
                } catch (\Exception $e) {
                    $success = false;
                    $this->addFlash('error', 'Erreur lors de l\'envoi du message.');
                }
            } else {
                $success = false;
                $this->addFlash('error', 'Erreur : le message n\'a pas été envoyé.');
            }
        }
        return $this->render('contact/contact.html.twig', [
            'data' => $data,
            'success' => $success,
        ]);
    }
}
