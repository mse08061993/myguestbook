<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Conference;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $conference1 = new Conference();
        $conference1->setCity('London');
        $conference1->setYear('2023');
        $conference1->setIsInternational(true);
        $conference1->setSlug('london-2023');
        $manager->persist($conference1);

        $conference2 = new Conference();
        $conference2->setCity('Paris');
        $conference2->setYear('2024');
        $conference2->setIsInternational(false);
        $conference2->setSlug('paris-2024');
        $manager->persist($conference2);

        $comment1 = new Comment();
        $comment1->setConference($conference2);
        $comment1->setAuthor('Sergey');
        $comment1->setEmail('sergey@email.com');
        $comment1->setText('This was a great conference');
        $comment1->setState('published');
        $comment1->setCreatedAtToCurrentDate();
        $manager->persist($comment1);

        $comment2 = new Comment();
        $comment2->setConference($conference2);
        $comment2->setAuthor('Ivan');
        $comment2->setEmail('ivan@email.com');
        $comment2->setText('I think this one is going to be moderated.');
        $comment2->setCreatedAtToCurrentDate();
        $manager->persist($comment1);

        $admin = new Admin();
        $admin->setUsername('admin');
        $admin->setRoles(["ROLE_ADMIN"]);
        $passwordHash = $this->passwordHasherFactory->getPasswordHasher(Admin::class)->hash('admin');
        $admin->setPassword($passwordHash);
        $manager->persist($admin);

        $manager->flush();
    }
}
