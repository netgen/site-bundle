<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener;

use Netgen\InformationCollection\API\Events;
use Netgen\InformationCollection\API\Exception\MissingAdditionalParameterException;
use Netgen\InformationCollection\API\Value\Event\InformationCollected;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This listener runs before any other listener attached to INFORMATION_COLLECTED
 * event. In case of honeypot field being filled with any content, it will simply
 * disable propagation of the event to other listeners which perform actions like
 * writing to the database, emailing and so on.
 *
 * Effectively, the collection submission process will complete as normal, except
 * nothing will be written to the database nor any emails will be sent. The user
 * will not have any feedback that anything was out of the ordinary, which is
 * the point of the honeypot.
 */
final class HoneypotEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::INFORMATION_COLLECTED => ['onInformationCollected', 255],
        ];
    }

    public function onInformationCollected(InformationCollected $event): void
    {
        $content = $event->getContent();

        if (!isset($content->fields['honeypot_field_name'])) {
            return;
        }

        $honeyPotFieldName = $content->getFieldValue('honeypot_field_name')->text;
        if ($honeyPotFieldName === '') {
            return;
        }

        try {
            /** @var \Symfony\Component\Form\FormInterface $form */
            $form = $event->getAdditionalParameter('form');
        } catch (MissingAdditionalParameterException) {
            return;
        }

        if ($form->get($honeyPotFieldName)->get('value')->getData()->text !== '') {
            $event->stopPropagation();
        }
    }
}
