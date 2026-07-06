<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
		private UrlGeneratorInterface $urlGenerator,
		private AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    public function authenticate(Request $request): Passport
    {
		// Tolérant: certains templates Symfony utilisent _username/_password,
		// d'autres email/password. On accepte les deux.
		$email = (string) ($request->request->get('email') ?? $request->request->get('_username') ?? '');
		$password = (string) ($request->request->get('password') ?? $request->request->get('_password') ?? '');

        // stocke le dernier email pour pré-remplir le champ login
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

		return new Passport(
			new UserBadge($email),
			new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', (string) $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        // 1) admin -> backoffice (prioritaire, même si un targetPath existe)
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->urlGenerator->generate('backoffice'));
        }

        // 2) Après une inscription client, on force le retour catalogue.
        // Cela évite de reprendre un ancien targetPath backoffice (/ouegnewe) mémorisé
        // lorsque le client crée son compte depuis le pilote mobile.
        if ($request->attributes->get('_route') === 'app_register') {
            return new RedirectResponse($this->urlGenerator->generate('product_catalogue'));
        }

        // 3) si on avait une page cible demandée avant login, on y retourne
        $session = $request->getSession();
        if ($session !== null) {
            $targetPath = $this->getTargetPath($session, $firewallName);
            if ($targetPath) {
                return new RedirectResponse($targetPath);
            }
        }

        // 4) client -> catalogue
        return new RedirectResponse($this->urlGenerator->generate('product_catalogue'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
