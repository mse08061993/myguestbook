<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/comment/review/{id}', 'app_review_comment')]
    public function reviewComment(
        Request $request,
        Comment $comment,
        WorkflowInterface $commentStateMachine,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
    ): Response {
        $reject = $request->query->get('reject');
        if ($commentStateMachine->can($comment, 'publish')) {
            $transition = $reject ? 'reject' : 'publish';
        } elseif ($commentStateMachine->can($comment, 'publish_ham')) {
            $transition = $reject ? 'reject_ham' : 'publish_ham';
        } else {
            return new Response('Comment is already reviewed or in wrong state.');
        }
        $commentStateMachine->apply($comment, $transition);
        $entityManager->flush();

        if (!$reject) {
            $messageBus->dispatch(new CommentMessage($comment->getId()));
        }

        return $this->render('admin/review_comment.html.twig', [
            'transition' => $transition,
            'comment' => $comment,
        ]);
    }

    #[Route('/http-cache/{uri<.*>}')]
    public function purgeCache(
        Request $request,
        KernelInterface $kernel,
        StoreInterface $store,
        string $uri,
    ): Response {
        if ('prod' === $kernel->getEnvironment()) {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        $store->purge($request->getSchemeAndHttpHost() . '/' . $uri);
        return new Response('Done!');
    }
}
