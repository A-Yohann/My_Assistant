<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class AboutController extends AbstractController
{
    #[Route('/about', name: 'about')]
    public function about(Request $request): Response
    {
        return $this->render('about/about.html.twig');
    }
}
