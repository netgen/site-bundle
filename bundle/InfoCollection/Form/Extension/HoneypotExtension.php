<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Form\Extension;

use Netgen\Bundle\InformationCollectionBundle\Ibexa\ContentForms\InformationCollectionType;
use Netgen\Bundle\SiteBundle\InfoCollection\Form\Type\HoneypotType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class HoneypotExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield InformationCollectionType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'sender_middle_name',
            HoneypotType::class,
        );
    }
}
