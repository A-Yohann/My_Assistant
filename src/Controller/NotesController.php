<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotesController extends AbstractController
{
    #[Route('/notes', name: 'notes')]
    public function index(): Response
    {
        return $this->render('notes/notes.html.twig');
    }
}
