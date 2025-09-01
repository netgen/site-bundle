<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Form\Extension;

use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Netgen\Bundle\InformationCollectionBundle\Ibexa\ContentForms\InformationCollectionFieldType;
use Netgen\Bundle\InformationCollectionBundle\Ibexa\ContentForms\InformationCollectionType;
use Netgen\Bundle\SiteBundle\InfoCollection\Validator\Constraints\CustomEmail;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmailFormExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [InformationCollectionType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('translation_domain', 'validators');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($builder->getData()->getCollectedFields() as $identifier => $fieldData) {
            /** @var FieldData $fieldData */
            if ($fieldData->fieldDefinition->fieldTypeIdentifier === 'ezemail') {
                $emailOptions = $builder->get($identifier)->getOptions();

                $customEmailExists = false;
                foreach ($emailOptions['constraints'] as $constraint) {
                    if ($constraint instanceof CustomEmail) {
                        $customEmailExists = true;

                        break;
                    }
                }

                if (!$customEmailExists) {
                    $emailOptions['constraints'][] = new CustomEmail();
                }

                $builder->add($identifier, InformationCollectionFieldType::class, $emailOptions);
            }
        }
    }
}
