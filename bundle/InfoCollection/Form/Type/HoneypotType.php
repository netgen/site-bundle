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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'value',
            TextLineFieldType::class,
            [
                'label' => 'ngsite.collected_info.contact_form.middle_name',
                'translation_domain' => 'messages',
                'empty_data' => '',
            ],
        );
    }
}
