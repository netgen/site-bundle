<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\Relations;

use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Relation\LocationRelationResolverInterface;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated this controller is replaced by template based relations list
 */
final class RelatedItems extends Controller
{
    public function __construct(
        private LocationRelationResolverInterface $locationResolver,
    ) {}

    /**
     * Action for rendering related items of a provided content.
     */
    public function __invoke(Request $request, Content $content, string $fieldDefinitionIdentifier, string $template): Response
    {
        $relatedItems = [];
        if ($content->mainLocation instanceof Location) {
            $relatedItems = $this->locationResolver->loadRelations(
                $content->mainLocation,
                $fieldDefinitionIdentifier,
            );
        }

        return $this->render(
            $template,
            [
                'content' => $content,
                'location' => $content->mainLocation,
                'field_identifier' => $fieldDefinitionIdentifier,
                'related_items' => $relatedItems,
                'view_type' => $request->attributes->get('viewType') ?? 'line',
            ],
        );
    }
}
