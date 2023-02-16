<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Form\Extension;

use Netgen\Bundle\InformationCollectionBundle\Ibexa\ContentForms\InformationCollectionType;
use Netgen\Bundle\SiteBundle\InfoCollection\Form\Type\HoneypotType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class HoneypotExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield InformationCollectionType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \Netgen\InformationCollection\API\Value\InformationCollectionStruct $struct */
        $struct = $options['data'];
        $content = $struct->getContent();

        if (!isset($content->fields['honeypot_field_name'])) {
            return;
        }

        $honeyPotFieldName = $content->getFieldValue('honeypot_field_name')->text;
        if ($honeyPotFieldName === '') {
            return;
        }

        $honeyPotFieldLabel = '';
        if (isset($content->fields['honeypot_field_label'])) {
            $honeyPotFieldLabel = $content->getFieldValue('honeypot_field_label')->text;
        }

        $builder->add(
            $honeyPotFieldName,
            HoneypotType::class,
            [
                'mapped' => false,
                'label' => $honeyPotFieldLabel,
            ],
        );
    }
}
