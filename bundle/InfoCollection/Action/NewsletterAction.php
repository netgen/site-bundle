<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Action;

use Netgen\Bundle\SiteBundle\InfoCollection\NewsletterService;
use Netgen\InformationCollection\API\Action\ActionInterface;
use Netgen\InformationCollection\API\Value\Event\InformationCollected;

class NewsletterAction implements ActionInterface
{
    public static string $defaultName = 'newsletter';

    private NewsletterService $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    public function act(InformationCollected $event): void
    {
        $this->newsletterService->subscribeToNewsletters(
            $event->getContent(),
            $event->getInformationCollectionStruct()->getCollectedFields(),
        );
    }
}
