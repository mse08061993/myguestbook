<?php

namespace App;

use App\Entity\Comment;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

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

    public function getSpamScore(Comment $comment, Request $request)
    {
        $parameters = [
            'blog' => 'https://localhost:8000',
            'comment_type' => 'comment',
            'comment_author' => 'akismet‑guaranteed‑spam',//$comment->getAuthor(),
            'comment_author_email' => 'akismet-guaranteed-spam@example.com',//$comment->getEmail(),
            'comment_content' => $comment->getText(),
            'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
            'blog_lang' => 'en',
            'blog_charset' => 'UTF-8',
            'is_test' => true,
            'user_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('user-agent'),
            'referrer' => $request->headers->get('referer'),
            'permalink' => $request->getUri(),
        ];
        $response = $this->httpClient->request('POST', $this->endpoint, ['body' => $parameters]);

        $headers = $response->getHeaders();
        if (isset($headers['X-akismet-pro-tip']) && 'discard' === $headers['X-akismet-pro-tip'][0] ?? '') {
            return 2;
        }

        $content = $response->getContent();
        if (isset($headers['X-akismet-debug-help'])) {
            throw new \RuntimeException(sprintf('Unable to check for spam: %s (%s)', $content, $headers['X-akismet-debug-help'][0]));
        }

        return 'true' === $content ? 1 : 0;
    }
}
