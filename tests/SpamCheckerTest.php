<?php

namespace App\Tests;

use App\SpamChecker;
use App\Entity\Comment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

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

    private function getSpamChecker(string $body = '', array $info = [])
    {
        $response = new MockResponse($body, $info);
        $httpClient = new MockHttpClient($response);
        return new SpamChecker($httpClient, '12345');
    }
}
