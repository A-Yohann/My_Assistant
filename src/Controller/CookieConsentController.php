<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CookieConsentController extends AbstractController
{
    #[Route('/save-cookie-consent', name: 'app_save_cookie_consent', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $choice = $request->request->get('choice', 'decline');
        $referer = $request->headers->get('referer', '/');

        $response = $this->redirect($referer);
        $response->headers->setCookie(
            Cookie::create('cookie_consent')
                ->withValue($choice)
                ->withExpires(strtotime('+6 months'))
                ->withHttpOnly(true)
                ->withSameSite('lax')
        );

        return $response;
    }
}