<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class ErrorController extends AbstractController
{
    #[Route('/error', name: 'error')]
    public function about(Request $request): Response
    {
        return $this->render('error/error.html.twig');
    }
}
