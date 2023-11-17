<?php

namespace App;

use App\Entity\Comment;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SpamChecker
{
    private string $endpoint;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire("%env(AKISMET_KEY)%")]
        string $akismetKey,
    ) {
        $this->endpoint = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $akismetKey);
    }

    public function getSpamScore(Comment $comment, array $context)
    {
        $parameters = [
            'blog' => 'https://localhost:8000',
            'comment_type' => 'comment',
            'comment_author' => $comment->getAuthor(),
            'comment_author_email' => $comment->getEmail(),
            'comment_content' => $comment->getText(),
            'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
            'blog_lang' => 'en',
            'blog_charset' => 'UTF-8',
            'is_test' => true,
        ];
        $response = $this->httpClient->request('POST', $this->endpoint, ['body' => array_merge($parameters, $context)]);

        $headers = $response->getHeaders();
        if (isset($headers['x-akismet-pro-tip']) && 'discard' === $headers['x-akismet-pro-tip'][0] ?? '') {
            return 2;
        }

        $content = $response->getContent();
        if ('invalid' === $content) {
            throw new \RuntimeException(sprintf('Unable to check for spam: %s.', $content));
        }

        return 'true' === $content ? 1 : 0;
    }
}
