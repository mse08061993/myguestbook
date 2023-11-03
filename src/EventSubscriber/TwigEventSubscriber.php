<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConferenceRepository $conferenceRepository,
        private Environment $twig,
    ) {
    }

    public function onKernelController(): void
    {
        $conferences = $this->conferenceRepository->findAll();
        $this->twig->addGlobal('conferences', $conferences);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
