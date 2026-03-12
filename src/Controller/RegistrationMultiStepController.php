<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationMultiStepController extends AbstractController
{
    #[Route('/register/multi', name: 'app_register_multi')]
    public function register(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $step = $request->query->get('step', 1);
        $formData = $session->get('registration_data', []);

        if ($request->isMethod('POST')) {
            $postData = $request->request->all();
            $formData = array_merge($formData, $postData);
            $session->set('registration_data', $formData);

            if ($step == 1) {
                if (!isset($postData['email']) || empty($postData['email'])) {
                    $this->addFlash('error', 'Email requis');
                } else {
                    return $this->redirectToRoute('app_register_multi', ['step' => 2]);
                }
            } elseif ($step == 2) {
                // Validation mot de passe et confirmation
                if (!isset($postData['password']) || empty($postData['password'])) {
                    $this->addFlash('error', 'Mot de passe requis');
                } elseif (!isset($postData['password_confirm']) || empty($postData['password_confirm'])) {
                    $this->addFlash('error', 'Confirmation requise');
                } elseif ($postData['password'] !== $postData['password_confirm']) {
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas');
                } else {
                    // Création du User
                    $user = new \App\Entity\User();
                    $user->setEmail($formData['email']);
                    $user->setPassword(password_hash($postData['password'], PASSWORD_DEFAULT));
                    $user->setRoles(['ROLE_USER']);
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $session->remove('registration_data');
                    $this->addFlash('success', 'Inscription réussie !');
                    return $this->redirectToRoute('app_login');
                }
            }
        // ...existing code...
        }

        return $this->render('registration/register_multi.html.twig', [
            'step' => $step,
            'formData' => $formData,
        ]);
    }
}
