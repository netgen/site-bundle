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
        return 'information_collection';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'value',
            TextLineFieldType::class,
            [
                'label' => 'ngsite.collected_info.contact_form.middle_name',
                'attr' => [
                    'class' => 'sender-middle-name',
                    'placeholder' => 'ngsite.collected_info.contact_form.middle_name',
                    'tabIndex' => '-1',
                ],
                'translation_domain' => 'messages',
                'empty_data' => '',
            ],
        );
    }
}
