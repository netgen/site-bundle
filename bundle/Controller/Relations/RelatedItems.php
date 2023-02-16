<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\Relations;

use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Relation\LocationRelationResolverInterface;
use Netgen\IbexaSiteApi\API\Values\Content;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RelatedItems extends Controller
{
    public function __construct(private LocationRelationResolverInterface $locationResolver)
    {
    }

    /**
     * Action for rendering related items of a provided content.
     */
    public function __invoke(Request $request, Content $content, string $fieldDefinitionIdentifier, string $template): Response
    {
        return $this->render(
            $template,
            [
                'content' => $content,
                'location' => $content->mainLocation,
                'field_identifier' => $fieldDefinitionIdentifier,
                'related_items' => $this->locationResolver->loadRelations($content->mainLocation, $fieldDefinitionIdentifier),
                'view_type' => $request->attributes->get('viewType') ?? 'line',
            ],
        );
    }
}
