<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class AccountController extends AbstractController
{
    #[Route('/account/manage', name: 'account_manage')]
    public function manage(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($request->isMethod('POST') && $request->files->get('profile_image')) {
            $file = $request->files->get('profile_image');
            if ($file && $file->isValid()) {
                $filename = uniqid().'.'.$file->guessExtension();
                $file->move($this->getParameter('kernel.project_dir').'/public/uploads/', $filename);
                $user->setAvatar('/uploads/'.$filename);
                $em->persist($user);
                $em->flush();
            }
        }
        return $this->render('account/manage.html.twig', [
            'avatar' => $user->getAvatar() ?? '/default-avatar.png',
        ]);
    }
}
