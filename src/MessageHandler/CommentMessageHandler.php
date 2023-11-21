<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Mailer\MailerInterface;
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
        #[Autowire('%admin_email%')] private string $adminEmail,
        private MailerInterface $mailer,
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
            $this->messageBus->dispatch($message);
        } elseif (
            $this->commentStateMachine->can($comment, 'publish')
            || $this->commentStateMachine->can($comment, 'publish_ham')
        ) {
            $email = new NotificationEmail();
            $email
                ->subject('New comment posted')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->context([
                    'comment' => $comment,
                ])
            ;
            $this->mailer->send($email);
        } else {
            $this->logger->debug('Dropping comment message', [
                'comment_id' => $comment->getId(),
                'comment_state' => $comment->getState()
            ]);
        }
    }
}
