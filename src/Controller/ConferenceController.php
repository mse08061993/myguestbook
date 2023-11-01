<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\ConferenceRepository;
use App\Controller\CommentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConferenceController extends AbstractController
{
    #[Route('/', 'app_homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('/conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);
    }

    #[Route('/conference/{id}', 'app_conference')]
    public function show(Conference $conference, CommentRepository $commentRepository): Response
    {
        return $this->render('/conference/conference.html.twig', [
            'conference' => $conference,
            'comments' => $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'desc'])
        ]);
    }
}
