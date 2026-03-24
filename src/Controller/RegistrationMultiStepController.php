<?php
namespace App\Controller;

use App\Entity\RegistrationToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationMultiStepController extends AbstractController
{
    // ✅ Étape 1 — Saisie email
    #[Route('/register/step1', name: 'register_step1')]
    public function step1(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Adresse email invalide.');
                return $this->redirectToRoute('register_step1');
            }

            // Vérifier si l'email est déjà utilisé
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                return $this->redirectToRoute('register_step1');
            }

            // Supprimer les anciens tokens pour cet email
            $oldTokens = $em->getRepository(RegistrationToken::class)->findBy(['email' => $email]);
            foreach ($oldTokens as $old) {
                $em->remove($old);
            }

            // Créer le token
            $token = bin2hex(random_bytes(32));
            $regToken = new RegistrationToken();
            $regToken->setEmail($email);
            $regToken->setToken($token);
            $regToken->setExpiresAt(new \DateTime('+1 hour'));

            $em->persist($regToken);
            $em->flush();

            // Envoyer le mail
            $confirmUrl = $this->generateUrl('register_step2', [
                'token' => $token
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $from = $_ENV['MAILER_FROM'] ?? 'no-reply@my-assistant.fr';

            $emailMessage = (new Email())
                ->from($from)
                ->to($email)
                ->subject('Confirmez votre inscription — My Assistant')
                ->html('
                    <p>Bonjour,</p>
                    <p>Merci de votre inscription sur <strong>My Assistant</strong>.</p>
                    <p>Cliquez sur le lien ci-dessous pour confirmer votre email et finaliser votre inscription :</p>
                    <p>
                        <a href="' . $confirmUrl . '" style="background:#3B0764;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;">
                            Confirmer mon email
                        </a>
                    </p>
                    <p>Ce lien est valable <strong>1 heure</strong>.</p>
                    <p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.</p>
                ');

            try {
                $mailer->send($emailMessage);
                $this->addFlash('success', 'Un email de confirmation a été envoyé à ' . $email . '. Vérifiez votre boîte mail !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi du mail.');
            }

            return $this->redirectToRoute('register_step1');
        }

        return $this->render('registration/step1.html.twig');
    }

    // ✅ Étape 2 — Saisie infos personnelles + mot de passe
    #[Route('/register/step2/{token}', name: 'register_step2')]
    public function step2(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, string $token): Response
    {
        $regToken = $em->getRepository(RegistrationToken::class)->findOneBy(['token' => $token]);

        if (!$regToken || $regToken->getExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Lien invalide ou expiré. Veuillez recommencer l\'inscription.');
            return $this->redirectToRoute('register_step1');
        }

        if ($request->isMethod('POST')) {
            $nom       = $request->request->get('nom');
            $prenom    = $request->request->get('prenom');
            $telephone = $request->request->get('telephone');
            $password  = $request->request->get('password');
            $confirm   = $request->request->get('password_confirm');

            if (!$nom || !$prenom || !$password) {
                $this->addFlash('error', 'Tous les champs obligatoires doivent être remplis.');
                return $this->redirectToRoute('register_step2', ['token' => $token]);
            }

            if ($password !== $confirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('register_step2', ['token' => $token]);
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('register_step2', ['token' => $token]);
            }

            // ✅ Créer l'utilisateur
            $user = new User();
            $user->setEmail($regToken->getEmail());
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setTelephone($telephone);
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $user->setPassword($hasher->hashPassword($user, $password));

            $em->persist($user);
            $em->remove($regToken);
            $em->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/step2.html.twig', [
            'token' => $token,
            'email' => $regToken->getEmail(),
        ]);
    }
}