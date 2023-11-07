<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\ConferenceRepository;
use App\Repository\CommentRepository;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/conference/{slug}', 'app_conference')]
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository
    ): Response {
        $offset = $request->query->getInt('offset', 0);
        $comments = $commentRepository->getPaginator($conference, $offset);

        return $this->render('/conference/conference.html.twig', [
            'conference' => $conference,
            'comments' => $comments,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next' => min(count($comments), $offset + CommentRepository::COMMENTS_PER_PAGE),
        ]);
    }
}
