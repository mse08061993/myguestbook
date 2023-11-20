<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\ConferenceRepository;
use App\Repository\CommentRepository;
use App\Message\CommentMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;

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
        EntityManagerInterface $entityManager,
        #[Autowire("%photo_directory%")]
        string $photoDirectory,
        MessageBusInterface $messageBus,
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form['photo']->getData();
            if ($photo) {
                $photoFileName = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
                $photo->move($photoDirectory, $photoFileName);
                $comment->setPhotoFileName($photoFileName);
            }
            $comment->setConference($conference);
            $entityManager->persist($comment);
            $entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            $message = new CommentMessage($comment->getId(), $context);
            $messageBus->dispatch($message);

            return $this->redirectToRoute('app_conference', ['slug' => $conference->getSlug()]);
        }

        $offset = $request->query->getInt('offset', 0);
        $comments = $commentRepository->getPaginator($conference, $offset);

        return $this->render('/conference/conference.html.twig', [
            'conference' => $conference,
            'comments' => $comments,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next' => min(count($comments), $offset + CommentRepository::COMMENTS_PER_PAGE),
            'form' => $form,
        ]);
    }
}
