<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConferenceController extends AbstractController
{
    #[Route('/', 'app_homepage')]
    public function index(Request $request): Response
    {
        return new Response('<html><body><img src="/images/under-construction.gif"></body></html>');
    }
}
