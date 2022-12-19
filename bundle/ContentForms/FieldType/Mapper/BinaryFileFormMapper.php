<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\ContentForms\FieldType\Mapper;

use Ibexa\ContentForms\FieldType\DataTransformer\BinaryFileValueTransformer;
use Ibexa\ContentForms\Form\Type\FieldType\BinaryFileFieldType;
use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Ibexa\Contracts\ContentForms\FieldType\FieldValueFormMapperInterface;
use Ibexa\Contracts\Core\Repository\FieldTypeService;
use Ibexa\Core\FieldType\BinaryFile\Value;
use Symfony\Component\Form\FormInterface;

/**
 * Overridden to properly handle null value for information collection.
 */
class BinaryFileFormMapper implements FieldValueFormMapperInterface
{
    private FieldTypeService $fieldTypeService;

    public function __construct(FieldTypeService $fieldTypeService)
    {
        $this->fieldTypeService = $fieldTypeService;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function mapFieldValueForm(FormInterface $fieldForm, FieldData $data): void
    {
        $fieldDefinition = $data->fieldDefinition;
        $formConfig = $fieldForm->getConfig();
        $fieldType = $this->fieldTypeService->getFieldType($fieldDefinition->fieldTypeIdentifier);
        $value = $data->value ?? $fieldType->getEmptyValue();

        $fieldForm
            ->add(
                $formConfig->getFormFactory()->createBuilder()
                    ->create(
                        'value',
                        BinaryFileFieldType::class,
                        [
                            'required' => $fieldDefinition->isRequired,
                            'label' => $fieldDefinition->getName(),
                        ],
                    )
                    ->addModelTransformer(
                        new BinaryFileValueTransformer($fieldType, $value, Value::class),
                    )
                    ->setAutoInitialize(false)
                    ->getForm(),
            );
    }
}
