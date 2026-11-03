<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $roles = $token->getRoleNames();

        if (in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->router->generate('admin_index'));
        }

        if (in_array('ROLE_ETUDIANT', $roles)) {
            return new RedirectResponse($this->router->generate('etudiant_index'));
        }

        if (in_array('ROLE_ENSEIGNANT', $roles)) {
            return new RedirectResponse($this->router->generate('enseignant_index'));
        }

        if (in_array('ROLE_ADMINISTRATION', $roles)) {
            return new RedirectResponse($this->router->generate('administration_index'));
        }

        if (in_array('ROLE_COMPTABLE', $roles)) {
            return new RedirectResponse($this->router->generate('comptable_index'));
        }

        // Par défaut si aucun rôle ne correspond
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
