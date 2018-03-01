<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\MoreBundle\Relation\RelationResolverInterface;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PartsController extends Controller
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Relation\RelationResolverInterface
     */
    protected $relationResolver;

    public function __construct(RelationResolverInterface $relationResolver)
    {
        $this->relationResolver = $relationResolver;
    }

    /**
     * Action for rendering related items of a provided content.
     */
    public function viewRelatedItems(Request $request, Content $content, string $fieldDefinitionIdentifier, string $template): Response
    {
        return $this->render(
            $template,
            [
                'content' => $content,
                'field_identifier' => $fieldDefinitionIdentifier,
                'related_items' => $this->relationResolver->loadRelations($content, $fieldDefinitionIdentifier),
                'view_type' => $request->attributes->get('viewType') ?? 'line',
            ]
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
     * @param mixed $locationId
     */
    public function viewRelatedMultimediaItems($locationId, string $template, bool $includeChildren = false, array $contentTypeIdentifiers = ['image']): Response
    {
        $location = $this->getSite()->getLoadService()->loadLocation($locationId);
        $content = $location->content;

        // Add current location in the multimedia item list
        $multimediaItems = [$content];

        // Get children objects and add them in multimedia item list
        if ($includeChildren) {
            $galleryItems = [];
            foreach ($location->filterChildren($contentTypeIdentifiers) as $child) {
                $galleryItems[] = $child->content;
            }

            $multimediaItems = array_merge($multimediaItems, $galleryItems);
        }

        // Finally, check if related_multimedia field exists and has content
        $relatedMultimediaLocationIds = [];
        if ($content->hasField('related_multimedia')) {
            if (!$content->getField('related_multimedia')->isEmpty()) {
                $relatedMultimediaField = $content->getField('related_multimedia')->value;

                // We need to work with location IDs, because we need to check if related object has location, to prevent
                // possible problems with related items in trash.
                // Also, we need location IDs for fetching images from related ng_gallery objects
                $relatedMultimediaLocationIds = !empty($relatedMultimediaField->destinationLocationIds) ? $relatedMultimediaField->destinationLocationIds : [];
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

                if ($relatedMultimediaLocation->invisible || !$relatedMultimediaLocation->contentInfo->published) {
                    continue;
                }

                // ng_gallery - Find children objects and add them in multimedia item list
                if ($relatedMultimediaLocation->contentInfo->contentTypeIdentifier === 'ng_gallery') {
                    $galleryItems = [];
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
            [
                'multimedia_items' => $multimediaItems,
            ]
        );
    }
}
