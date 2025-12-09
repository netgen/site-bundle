<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Voter;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Netgen\Bundle\IbexaSiteApiBundle\View\LocationValueView;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function array_map;
use function in_array;

final class LocationPathVoter implements VoterInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /**
     * Checks whether an item is current.
     *
     * If the voter is not able to determine a result,
     * it should return null to let other voters do the job.
     *
     * This voter specifically marks the item as current if it is in
     * path of the currently displayed item. This takes care of marking
     * items in menus of arbitrary depths.
     */
    public function matchItem(ItemInterface $item): ?bool
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof Request) {
            return null;
        }

        $locationView = $request->attributes->get('view');
        if (!$locationView instanceof LocationValueView) {
            return null;
        }

        if (!$locationView->getSiteLocation() instanceof Location || !$item->getExtra('ibexa_location') instanceof Location) {
            return null;
        }

        $locationPath = array_map('intval', $locationView->getSiteLocation()->pathArray);

        if (!in_array($item->getExtra('ibexa_location')->id, $locationPath, true)) {
            return null;
        }

        return true;
    }
}
