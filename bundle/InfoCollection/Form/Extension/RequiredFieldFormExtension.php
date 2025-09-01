<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Form\Extension;

use Netgen\Bundle\SiteBundle\InfoCollection\Validator\Constraints\RequiredField;
use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Netgen\Bundle\InformationCollectionBundle\Ibexa\ContentForms\InformationCollectionFieldType;
use Netgen\Bundle\InformationCollectionBundle\Ibexa\ContentForms\InformationCollectionType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RequiredFieldFormExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [InformationCollectionType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'validators',
            'attr' => [
                'class' => 'embed-form js-form-embed',
            ],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($builder->getData()->getCollectedFields() as $identifier => $fieldData) {
            /** @var FieldData $fieldData */
            if ($fieldData->fieldDefinition->isRequired === true) {
                $fieldOptions = $builder->get($identifier)->getOptions();

                $hasRequiredFieldConstraint = false;

                foreach ($fieldOptions['constraints'] as $constraint) {
                    if ($constraint instanceof RequiredField) {
                        $hasRequiredFieldConstraint = true;

                        break;
                    }
                }

                if (!$hasRequiredFieldConstraint) {
                    $fieldOptions['constraints'][] = new RequiredField();
                    $builder->add($identifier, InformationCollectionFieldType::class, $fieldOptions);
                }
            }
        }
    }
}
