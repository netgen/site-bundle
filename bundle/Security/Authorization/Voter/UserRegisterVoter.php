<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Security\Authorization\Voter;

use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Provides access to ngsite_user_register route, even if user does not have access to user/login policy.
 */
class UserRegisterVoter extends Voter
{
    private PermissionResolver $permissionResolver;

    private RequestStack $requestStack;

    public function __construct(PermissionResolver $permissionResolver, RequestStack $requestStack)
    {
        $this->permissionResolver = $permissionResolver;
        $this->requestStack = $requestStack;
    }

    protected function supports($attribute, $subject): bool
    {
        if (!$attribute instanceof Attribute) {
            return false;
        }

        $request = $this->requestStack->getMasterRequest();
        if (!$request instanceof Request) {
            return false;
        }

        if ($request->attributes->get('_route') !== 'ngsite_user_register') {
            return false;
        }

        return $attribute->module === 'user' && $attribute->function === 'login';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return $this->permissionResolver->hasAccess('user', 'register');
    }
}
