<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\Relations;

use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Relation\LocationRelationResolverInterface;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RelatedMultimediaItems extends Controller
{
    public function __construct(private LocationRelationResolverInterface $multimediaResolver)
    {
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
     */
    public function __invoke(Request $request, Location $location, string $template, string $fieldDefinitionIdentifier = 'related_multimedia'): Response
    {
        $multimediaItems = $this->multimediaResolver->loadRelations(
            $location,
            $fieldDefinitionIdentifier,
            [
                'include_children' => $request->attributes->get('includeChildren') ?? false,
                'content_types' => $request->attributes->get('contentTypeIdentifiers') ?? ['image'],
            ],
        );

        return $this->render(
            $template,
            [
                'content' => $location->content,
                'location' => $location,
                'multimedia_items' => $multimediaItems,
            ],
        );
    }
}
