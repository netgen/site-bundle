<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Netgen\Bundle\IbexaSiteApiBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_array;

abstract class Controller extends BaseController
{
    /**
     * Configures the response with provided cache settings.
     */
    protected function processCacheSettings(Request $request, Response $response): void
    {
        $defaultSharedMaxAge = $this->getConfigResolver()->getParameter('view.shared_max_age', 'ngsite');

        $cacheSettings = $request->attributes->get('cacheSettings');
        if (!is_array($cacheSettings)) {
            $cacheSettings = ['sharedMaxAge' => $defaultSharedMaxAge];
        }

        $public = true;

        if (isset($cacheSettings['sharedMaxAge'])) {
            $response->setSharedMaxAge($cacheSettings['sharedMaxAge']);
            if (($cacheSettings['sharedMaxAge'] ?? 0) <= 0) {
                $public = false;
            }
        } elseif (isset($cacheSettings['maxAge'])) {
            $response->setMaxAge($cacheSettings['maxAge']);
            if (($cacheSettings['maxAge'] ?? 0) <= 0) {
                $public = false;
            }
        } else {
            $response->setSharedMaxAge($defaultSharedMaxAge);
        }

        $public ? $response->setPublic() : $response->setPrivate();
    }
}
