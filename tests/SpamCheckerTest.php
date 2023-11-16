<?php

namespace App\Tests;

use App\SpamChecker;
use App\Entity\Comment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpamCheckerTest extends TestCase
{
    private Comment $comment;
    private array $context;

    protected function setUp(): void
    {
        $this->comment = new Comment();
        $this->comment->setCreatedAtToCurrentDate();
        $this->context = [];
    }

    public function testInvalidApiKey(): void
    {
        $spamChecker = $this->getSpamChecker('invalid');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid.');
        $spamChecker->getSpamScore($this->comment, $this->context);
    }

    public function testBlatanSpam(): void
    {
        $spamChecker = $this->getSpamChecker('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        $score = $spamChecker->getSpamScore($this->comment, $this->context);
        $this->assertEquals(2, $score);
    }

    public function testSpam(): void
    {
        $spamChecker = $this->getSpamChecker('true');
        $score = $spamChecker->getSpamScore($this->comment, $this->context);
        $this->assertEquals(1, $score);
    }

    public function testHam(): void
    {
        $spamChecker = $this->getSpamChecker('false');
        $score = $spamChecker->getSpamScore($this->comment, $this->context);
        $this->assertEquals(0, $score);
    }

    /**
     * @dataProvider provideComments
     */
    public function testSpamScore(int $expectedScore, ResponseInterface $response, $comment, $context): void
    {
        $httpClient = new MockHttpClient($response);
        $spamChecker = new SpamChecker($httpClient, '12345');

        $actualScore = $spamChecker->getSpamScore($comment, $context);
        $this->assertSame($expectedScore, $actualScore);
    }

    public static function provideComments(): iterable
    {
        $comment = new Comment();
        $comment->setCreatedAtToCurrentDate();
        $context = [];

        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        yield 'blatan_spam' => [2, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];

        $response = new MockResponse('false');
        yield 'ham' => [0, $response, $comment, $context];
    }

    private function getSpamChecker(string $body = '', array $info = [])
    {
        $response = new MockResponse($body, $info);
        $httpClient = new MockHttpClient($response);
        return new SpamChecker($httpClient, '12345');
    }
}
