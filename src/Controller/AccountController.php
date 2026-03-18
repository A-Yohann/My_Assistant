<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'account_show')]
    public function show(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('account/show.html.twig', [
            'user'      => $user,
            'avatar'    => $user->getAvatar() ?? '/default-avatar.png',
            'email'     => $user->getEmail(),
            'telephone' => $user->getTelephone() ?? '',
        ]);
    }

    #[Route('/account/manage', name: 'account_manage')]
    public function manage(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('home'); // sécurité si non connecté
        }

        // ----------------------------
        // SUPPRESSION DU COMPTE
        // ----------------------------
        if ($request->request->get('action') === 'delete') {

            // 🔒 Bloquer la suppression des admins
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Impossible de supprimer un compte admin.');
                return $this->redirectToRoute('account_manage');
            }

            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
return $this->redirectToRoute('app_home');         }

        // ----------------------------
        // MISE À JOUR DU COMPTE
        // ----------------------------
        if ($request->isMethod('POST')) {

            // ✅ Upload avatar
            $file = $request->files->get('profile_image');
            if ($file && $file->isValid()) {
                $filename = uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/', $filename);
                $user->setAvatar('/uploads/' . $filename);
            }

            // ✅ Nom & Prénom
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            if ($nom) $user->setNom($nom);
            if ($prenom) $user->setPrenom($prenom);

            // ✅ Email
            $email = $request->request->get('email');
            if ($email && $email !== $user->getEmail()) {
                $user->setEmail($email);
            }

            // ✅ Mot de passe (seulement si renseigné)
            $password = $request->request->get('password');
            if ($password && strlen($password) >= 6) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            } elseif ($password && strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('account_manage');
            }

            // ✅ Téléphone
            $telephone = $request->request->get('telephone');
            if ($telephone !== null) $user->setTelephone($telephone);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre compte a été mis à jour avec succès !');
            return $this->redirectToRoute('account_manage');
        }

        // Rendu final
        return $this->render('account/manage.html.twig', [
            'user'   => $user,
            'avatar' => $user->getAvatar() ?? '/default-avatar.png',
        ]);
    }
}