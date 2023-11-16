<?php

namespace App\Tests;

use App\SpamChecker;
use App\Entity\Comment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SpamCheckerTest extends TestCase
{
    public function testSomething(): void
    {
        $response = new MockResponse('invalid');
        $httpClient = new MockHttpClient($response);
        $spamChecker = new SpamChecker($httpClient, '12345');

        $comment = new Comment();
        $comment->setCreatedAtToCurrentDate();
        $context = [];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid.');
        $spamChecker->getSpamScore($comment, $context);
    }
}
