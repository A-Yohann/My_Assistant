<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MentionController extends AbstractController
{
    #[Route('/mention', name: 'mention')]
    public function mentions(Request $request): Response
    {
        return $this->render('mention/mention.html.twig');
    }
}
