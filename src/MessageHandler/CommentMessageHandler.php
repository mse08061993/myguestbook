<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use App\ImageOptimizer;
use App\Notification\CommentReviewNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private SpamChecker $spamChecker,
        private EntityManagerInterface $entityManager,
        private WorkflowInterface $commentStateMachine,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private ImageOptimizer $imageOptimizer,
        #[Autowire("%photo_directory%")] private string $photoDirectory,
        private NotifierInterface $notifier,
    ) {
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = match ($score) {
                2 => 'reject_spam',
                1 => 'might_be_spam',
                0 => 'accept',
            };
            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
            if ('reject_spam' !== $transition) {
                $this->messageBus->dispatch($message);
            }
        } elseif (
            $this->commentStateMachine->can($comment, 'publish')
            || $this->commentStateMachine->can($comment, 'publish_ham')
        ) {
            $notification = new CommentReviewNotification($comment, $message->getReviewUrl());
            $this->notifier->send($notification, ...$this->notifier->getAdminRecipients());
        } elseif ($this->commentStateMachine->can($comment, 'optimize')) {
            $photo = $comment->getPhotoFileName();
            if ($photo) {
                $photoPath = $this->photoDirectory . '/' . $comment->getPhotoFileName();
                $this->imageOptimizer->resize($photoPath);
            }
            $this->commentStateMachine->apply($comment, 'optimize');
            $this->entityManager->flush();
        } else {
            $this->logger->debug('Dropping comment message', [
                'comment_id' => $comment->getId(),
                'comment_state' => $comment->getState()
            ]);
        }
    }
}
