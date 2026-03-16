<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class ErrorController extends AbstractController
{
    public function show404(): Response
    {
        return $this->render('error/error.html.twig'); // ton template 404
    }
}
