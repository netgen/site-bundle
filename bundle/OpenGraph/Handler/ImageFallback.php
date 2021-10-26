<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\OpenGraph\Handler;

use Netgen\Bundle\OpenGraphBundle\Handler\ContentAware;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Values\Content\Field;
use Netgen\Bundle\OpenGraphBundle\Handler\HandlerInterface;
use Netgen\Bundle\OpenGraphBundle\Handler\FieldType\Handler;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value as RemoteImageValue;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\Bundle\OpenGraphBundle\Exception\FieldEmptyException;
use Netgen\Bundle\OpenGraphBundle\Handler\FieldType\Image as ImageHandler;
use Netgen\Bundle\RemoteMediaBundle\OpenGraph\Handler\RemoteMediaHandler;

final class ImageFallback extends Handler implements HandlerInterface, ContentAware
{
    private $ezImageHandler;

    private $remoteMediaHandler;

    public function __construct(
        FieldHelper $fieldHelper,
        TranslationHelper $translationHelper,
        ImageHandler $ezImageHandler,
        RemoteMediaHandler $remoteMediaHandler
    ) {
        parent::__construct($fieldHelper, $translationHelper);

        $this->ezImageHandler = $ezImageHandler;
        $this->remoteMediaHandler = $remoteMediaHandler;
    }

    public function getMetaTags($tagName, array $params = []): array
    {
        if (!isset($params[0])) {
            throw new InvalidArgumentException(
                '$params[0]',
                'Field type handlers require at least a field identifier.'
            );
        }

        $fieldIdentifiers = is_array($params[0]) ? $params[0] : array($params[0]);
        $fieldValue = $this->getFallbackValue($tagName, $params);

        foreach ($fieldIdentifiers as $fieldIdentifier) {
            $field = $this->validateField($fieldIdentifier);
            $params[0] = [$fieldIdentifier];

            try {
                if ($field->value instanceof RemoteImageValue) {
                    $this->remoteMediaHandler->setContent($this->content);
                    return $this->remoteMediaHandler->getMetaTags($tagName, $params);
                }

                if ($field->value instanceof ImageValue) {
                    $this->ezImageHandler->setContent($this->content);
                    return $this->ezImageHandler->getMetaTags($tagName, $params);
                }

                break;
            } catch (FieldEmptyException $e) {
                // do nothing
            }
        }

        throw new InvalidArgumentException(
            sprintf('%s or $params[0]', implode($fieldIdentifiers, ',')),
            'Either field does not provide valid value or fallback is not properly set up.',
        );
    }

    /**
     * Returns if this field type handler supports current field.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field $field
     *
     * @return bool
     */
    protected function supports(Field $field)
    {
        return $field->value instanceof ImageValue || $field->value instanceof RemoteImageValue;
    }
}
