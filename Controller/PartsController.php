<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Netgen\Bundle\MoreBundle\Helper\SortClauseHelper;

class PartsController extends Controller
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SortClauseHelper
     */
    protected $sortClauseHelper;

    /**
     * Constructor.
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\SortClauseHelper $sortClauseHelper
     */
    public function __construct(SortClauseHelper $sortClauseHelper)
    {
        $this->sortClauseHelper = $sortClauseHelper;
    }

    /**
     * Action for rendering related items.
     *
     * @param mixed $contentId
     * @param string $fieldDefinitionIdentifier
     * @param string $viewType
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewRelatedItems($contentId, $fieldDefinitionIdentifier, $template, $viewType = 'line')
    {
        $relatedItems = array();

        $content = $this->getSite()->getLoadService()->loadContent($contentId);

        if ($content->hasField($fieldDefinitionIdentifier) && !$content->getField($fieldDefinitionIdentifier)->isEmpty()) {
            /** @var \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value $fieldValue */
            $fieldValue = $content->getField($fieldDefinitionIdentifier)->value;
            if (!empty($fieldValue->destinationLocationIds)) {
                foreach ($fieldValue->destinationLocationIds as $locationId) {
                    try {
                        if (!empty($locationId)) {
                            $location = $this->getSite()->getLoadService()->loadLocation($locationId);
                            if (!$location->invisible) {
                                $relatedItems[] = $location;
                            }
                        }
                    } catch (NotFoundException $e) {
                        // Do nothing if there's no location
                        continue;
                    }
                }
            }
        }

        return $this->render(
            $template,
            array(
                'related_items' => $relatedItems,
                'view_type' => $viewType,
            )
        );
    }

    /**
     * Action for rendering related multimedia items
     * If more than one multimedia item is found, it will display a slider
     * Items included:
     * 1. current content object - if we want multimedia slider to include some field value from the object itself (like image or video that are not in related_multimedia field),
     * we just need to add twig template to site bundle in /Resources/views/parts/related_multimedia_items/ with name pattern $contentTypeIdentifier.html.twig
     * (i.e. for ng_article content type:  /Resources/views/parts/related_multimedia_items/ng_Article.html.twig). Check already implemented templates for other content types
     * to see how the template logic for slider/non-slider is done
     * 2. children objects - if $includeChildren parameter is set, all children content objects will be added in the multimedia items list
     * 3. related objects from related_multimedia object relation field ( related images, images from related galleries, banners, videos )
     * - to enable this feature for some content type, add object relations field with content type identifier 'related_multimedia'.
     *
     * @param int $locationId
     * @param string $template
     * @param bool $includeChildren
     * @param array $contentTypeIdentifiers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewRelatedMultimediaItems($locationId, $template, $includeChildren = false, array $contentTypeIdentifiers = array('image'))
    {
        $location = $this->getSite()->getLoadService()->loadLocation($locationId);
        $content = $location->content;

        // Add current location in the multimedia item list
        $multimediaItems = array($content);

        // Get children objects and add them in multimedia item list
        if ($includeChildren) {
            $galleryItems = array();
            foreach ($location->filterChildren($contentTypeIdentifiers) as $child) {
                $galleryItems[] = $child->content;
            }

            $multimediaItems = array_merge($multimediaItems, $galleryItems);
        }

        // Finally, check if related_multimedia field exists and has content
        $relatedMultimediaLocationIds = array();
        if ($content->hasField('related_multimedia')) {
            if (!$content->getField('related_multimedia')->isEmpty()) {
                $relatedMultimediaField = $content->getField('related_multimedia')->value;

                // We need to work with location IDs, because we need to check if related object has location, to prevent
                // possible problems with related items in trash.
                // Also, we need location IDs for fetching images from related ng_gallery objects
                $relatedMultimediaLocationIds = !empty($relatedMultimediaField->destinationLocationIds) ? $relatedMultimediaField->destinationLocationIds : array();
            }
        }

        // If there are related multimedia objects
        if (!empty($relatedMultimediaLocationIds)) {
            foreach ($relatedMultimediaLocationIds as $relatedMultimediaLocationId) {
                try {
                    $relatedMultimediaLocation = $this->getSite()->getLoadService()->loadLocation($relatedMultimediaLocationId);
                } catch (NotFoundException $e) {
                    // Skip non-existing locations (item in trash or missing location due to some other reason)
                    continue;
                }

                if (!$relatedMultimediaLocation->contentInfo->published) {
                    continue;
                }

                // ng_gallery - Find children objects and add them in multimedia item list
                if ($relatedMultimediaLocation->contentInfo->contentTypeIdentifier === 'ng_gallery') {
                    $galleryItems = array();
                    foreach ($relatedMultimediaLocation->filterChildren($contentTypeIdentifiers) as $child) {
                        $galleryItems[] = $child->content;
                    }

                    $multimediaItems = array_merge($multimediaItems, $galleryItems);
                } else {
                    $multimediaItems[] = $relatedMultimediaLocation->content;
                }
            }
        }

        return $this->render(
            $template,
            array(
                'multimedia_items' => $multimediaItems,
            )
        );
    }
}
