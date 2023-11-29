<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\ConferenceRepository;
use App\Repository\CommentRepository;
use App\Message\CommentMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Doctrine\ORM\EntityManagerInterface;

class ConferenceController extends AbstractController
{
    #[Route('/{_locale<%supported_locales%>}/', 'app_homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('/conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }

    #[Route('/{_locale<%supported_locales%>}/conference/{slug}', 'app_conference')]
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
        #[Autowire("%photo_directory%")] string $photoDirectory,
        MessageBusInterface $messageBus,
        NotifierInterface $notifier,
        UrlGeneratorInterface $urlGenerator,
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
            $reviewUrl = $urlGenerator->generate(
                'app_review_comment',
                ['id' => $comment->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $message = new CommentMessage($comment->getId(), $reviewUrl, $context);
            $messageBus->dispatch($message);

            $notification = new Notification('Thank you for your feedback. Your comment will be posted after moderation.', ['browser']);
            $notifier->send($notification);

            return $this->redirectToRoute('app_conference', ['slug' => $conference->getSlug()]);
        }

        if ($form->isSubmitted()) {
            $notification = new Notification('Can you check your submission? There are some problems with it.', ['browser']);
            $notifier->send($notification);
        }

        $offset = $request->query->getInt('offset', 0);
        $comments = $commentRepository->getPaginator($conference, $offset);

        return $this->render('/conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $comments,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next' => min(count($comments), $offset + CommentRepository::COMMENTS_PER_PAGE),
            'comment_form' => $form,
        ]);
    }

    #[Route('/{_locale<%supported_locales%>}/conference_header', 'app_conference_header')]
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }

	#[Route('/')]
	public function indexNoLocale(): Response
	{
		return $this->redirectToRoute('app_homepage', ['_locale' => 'en']);
	}
}
