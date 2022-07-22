<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\OpenGraph\Handler;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\Bundle\OpenGraphBundle\Exception\FieldEmptyException;
use Netgen\Bundle\OpenGraphBundle\Handler\FieldType\Handler;
use Netgen\Bundle\OpenGraphBundle\Handler\FieldType\Image as ImageHandler;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value as RemoteImageValue;
use Netgen\Bundle\RemoteMediaBundle\OpenGraph\Handler\RemoteMediaHandler;

use function implode;
use function is_array;
use function sprintf;

final class Image extends Handler
{
    private ImageHandler $ezImageHandler;

    private ?RemoteMediaHandler $remoteMediaHandler;

    public function __construct(
        FieldHelper $fieldHelper,
        TranslationHelper $translationHelper,
        ImageHandler $ezImageHandler,
        ?RemoteMediaHandler $remoteMediaHandler = null
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
                'Field type handlers require at least a field identifier.',
            );
        }

        $fieldIdentifiers = is_array($params[0]) ? $params[0] : [$params[0]];

        foreach ($fieldIdentifiers as $fieldIdentifier) {
            $field = $this->validateField($fieldIdentifier);
            $params[0] = $fieldIdentifier;

            try {
                if ($this->remoteMediaHandler !== null && $field->value instanceof RemoteImageValue) {
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
            sprintf('%s or $params[0]', implode(',', $fieldIdentifiers)),
            'Either field does not provide valid value or fallback is not properly set up.',
        );
    }

    protected function supports(Field $field): bool
    {
        return $field->value instanceof ImageValue || $field->value instanceof RemoteImageValue;
    }
}
