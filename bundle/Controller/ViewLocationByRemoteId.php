<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Netgen\IbexaSiteApi\API\LoadService;
use Symfony\Component\HttpFoundation\Response;

final class ViewLocationByRemoteId extends Controller
{
    public function __construct(private LoadService $loadService) {}

    public function __invoke(string $remoteId): Response
    {
        $content = $this->loadService->loadLocationByRemoteId($remoteId);

        return $this->redirectToRoute('ibexa.location.view', ['locationId' => $content->id]);
    }
}
