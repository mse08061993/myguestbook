<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback!');
    }

    public function testCommentSubmission()
    {
        $client = static::createClient();
        $client->request('GET', '/en/conference/paris-2024');
        $email = 'ivan@email.com';
        $client->submitForm('Submit', [
            'comment[author]' => 'Sergey',
            'comment[email]' => $email,
            'comment[text]' => 'This was a great conference',
            'comment[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);
        $this->assertResponseRedirects();

        $comment = self::getContainer()->get(CommentRepository::class)->findOneBy(['email' => $email]);
        $comment->setState('published');
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments.")');
    }

    public function testConferencePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains('Paris');
        $this->assertSelectorTextContains('h2', 'Paris - 2024 Conference');
        $this->assertSelectorExists('div:contains("There is one comment")');
    }
}
