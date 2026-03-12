<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function contact(Request $request): Response
    {
        $data = [];
        $success = null;
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            // Ici tu peux ajouter l'envoi d'email ou le stockage
            // Simule une condition d'envoi (exemple: message non vide)
            if (!empty($data['message'])) {
                $success = true;
                $this->addFlash('success', 'Votre message a bien été envoyé !');
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
