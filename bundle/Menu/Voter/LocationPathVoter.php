<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Voter;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\LocationValueView;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function array_map;
use function in_array;

class LocationPathVoter implements VoterInterface
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

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
        $masterRequest = $this->requestStack->getMasterRequest();
        if (!$masterRequest instanceof Request) {
            return null;
        }

        $locationView = $masterRequest->attributes->get('view');
        if (!$locationView instanceof LocationValueView) {
            return null;
        }

        if (!$locationView->getSiteLocation() instanceof Location || !$item->getExtra('ezlocation') instanceof Location) {
            return null;
        }

        $locationPath = array_map('intval', $locationView->getSiteLocation()->path);

        if (!in_array($item->getExtra('ezlocation')->id, $locationPath, true)) {
            return null;
        }

        return true;
    }
}
