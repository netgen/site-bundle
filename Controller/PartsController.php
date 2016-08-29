<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\MoreBundle\Helper\SortClauseHelper;
use Netgen\EzPlatformSite\API\Values\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class PartsController extends Controller
{
    /**
     * @var \Netgen\EzPlatformSite\API\LoadService
     */
    protected $loadService;

    /**
     * @var \Netgen\EzPlatformSite\API\FindService
     */
    protected $findService;

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

        $this->loadService = $this->getSite()->getLoadService();
        $this->findService = $this->getSite()->getFindService();
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

        $content = $this->loadService->loadContent($contentId);

        if ($content->hasField($fieldDefinitionIdentifier) && !$content->getField($fieldDefinitionIdentifier)->isEmpty()) {
            /** @var \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value $fieldValue */
            $fieldValue = $content->getField($fieldDefinitionIdentifier)->value;
            if (!empty($fieldValue->destinationLocationIds)) {
                foreach ($fieldValue->destinationLocationIds as $locationId) {
                    try {
                        if (!empty($locationId)) {
                            $location = $this->loadService->loadLocation($locationId);
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
        $location = $this->loadService->loadLocation($locationId);
        $content = $this->loadService->loadContent($location->contentId);

        // Add current location in the multimedia item list
        $multimediaItems = array($content);

        // Get children objects and add them in multimedia item list
        if ($includeChildren) {
            $galleryItems = $this->getChildren($location, $contentTypeIdentifiers);
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
                    $relatedMultimediaLocation = $this->loadService->loadLocation($relatedMultimediaLocationId);
                } catch (NotFoundException $e) {
                    // Skip non-existing locations (item in trash or missing location due to some other reason)
                    continue;
                }

                if (!$relatedMultimediaLocation->contentInfo->published) {
                    continue;
                }

                // ng_gallery - Find children objects and add them in multimedia item list
                if ($relatedMultimediaLocation->contentInfo->contentTypeIdentifier == 'ng_gallery') {
                    $galleryItems = $this->getChildren($relatedMultimediaLocation, $contentTypeIdentifiers);
                    $multimediaItems = array_merge($multimediaItems, $galleryItems);
                } else {
                    $relatedMultimediaContent = $this->loadService->loadContent($relatedMultimediaLocation->contentId);
                    $multimediaItems[] = $relatedMultimediaContent;
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

    /**
     * Helper method for fetching children items from specified location.
     *
     * @param \Netgen\EzPlatformSite\API\Values\Location $location
     * @param array $contentTypeIdentifiers
     *
     * @return \Netgen\EzPlatformSite\API\Values\Content[]
     */
    protected function getChildren(Location $location, array $contentTypeIdentifiers = array('image'))
    {
        $query = new LocationQuery();
        $contentList = array();

        $criteria = array(
            new Criterion\ParentLocationId($location->id),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        );

        if (!empty($contentTypeIdentifiers)) {
            $criteria[] = new Criterion\ContentTypeIdentifier($contentTypeIdentifiers);
        }

        $query->filter = new Criterion\LogicalAnd($criteria);

        $query->sortClauses = array(
            $this->sortClauseHelper->getSortClauseBySortField(
                $location->sortField,
                $location->sortOrder
            ),
        );

        $result = $this->findService->findNodes($query);

        foreach ($result->searchHits as $searchHit) {
            $contentList[] = $searchHit->valueObject;
        }

        return $contentList;
    }
}
