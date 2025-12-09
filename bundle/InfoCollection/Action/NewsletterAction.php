<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Action;

use Netgen\Bundle\SiteBundle\InfoCollection\NewsletterService;
use Netgen\InformationCollection\API\Action\ActionInterface;
use Netgen\InformationCollection\API\Value\Event\InformationCollected;

final class NewsletterAction implements ActionInterface
{
    public static string $defaultName = 'newsletter';

    public function __construct(
        private NewsletterService $newsletterService,
    ) {}

    public function act(InformationCollected $event): void
    {
        $this->newsletterService->subscribeToNewsletters(
            $event->getContent(),
            $event->getInformationCollectionStruct()->getCollectedFields(),
        );
    }
}
