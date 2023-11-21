<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\WorkflowInterface;
use Doctrine\ORM\EntityManagerInterface;

class AdminController extends AbstractController
{
    #[Route('/admin/comment/review/{id}', 'app_review_comment')]
    public function reviewComment(
        Request $request,
        Comment $comment,
        WorkflowInterface $commentStateMachine,
        EntityManagerInterface $entityManager,
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

        return $this->render('admin/review_comment.html.twig', [
            'transition' => $transition,
            'comment' => $comment,
        ]);
    }
}
