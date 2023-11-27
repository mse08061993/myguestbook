<?php

namespace App\Notification;

use App\Entity\Comment;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class CommentReviewNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(private Comment $comment)
    {
        parent::__construct('New comment posted!');
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient);
        $message->getMessage()
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->context(['comment' => $this->comment])
        ;

        return $message;
    }

    public function getChannels(RecipientInterface $recipient): array
    {
        if (preg_match('{\b(great|awesome)\b}i', $this->comment->getText())) {
            return ['email', 'chat/telegram'];
        }

        $this->importance(Notification::IMPORTANCE_LOW);

        return ['email'];
    }
}
