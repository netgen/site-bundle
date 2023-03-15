<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Security\Authorization\Voter;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Provides access to ngsite_user_register route, even if user does not have access to user/login policy.
 */
final class UserRegisterVoter implements VoterInterface
{
    public function __construct(private PermissionResolver $permissionResolver, private RequestStack $requestStack)
    {
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            if ($this->permissionResolver->hasAccess('user', 'register') === true) {
                return VoterInterface::ACCESS_GRANTED;
            }

            return VoterInterface::ACCESS_DENIED;
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }

    private function supportsAttribute(mixed $attribute): bool
    {
        if (!$attribute instanceof Attribute) {
            return false;
        }

        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof Request) {
            return false;
        }

        if ($request->attributes->get('_route') !== 'ngsite_user_register') {
            return false;
        }

        return $attribute->module === 'user' && $attribute->function === 'login';
    }
}
