<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Form\Type;

use Ibexa\ContentForms\Form\Type\FieldType\TextLineFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HoneypotType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'honeypot';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'value',
            TextLineFieldType::class,
            [
                'label' => $options['label'],
                'translation_domain' => $options['translation_domain'],
            ],
        );
    }
}
