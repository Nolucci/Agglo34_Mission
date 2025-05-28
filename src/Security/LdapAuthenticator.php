<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LdapAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private LdapInterface $ldap;
    private string $baseDn;
    private string $searchDn;
    private string $searchPassword;
    private string $uidKey;
    private LdapUserProvider $userProvider;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        LdapInterface $ldap,
        string $baseDn,
        string $searchDn,
        string $searchPassword,
        string $uidKey,
        LdapUserProvider $userProvider
    ) {
        $this->ldap = $ldap;
        $this->baseDn = $baseDn;
        $this->searchDn = $searchDn;
        $this->searchPassword = $searchPassword;
        $this->uidKey = $uidKey;
        $this->userProvider = $userProvider;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username, function ($userIdentifier) use ($password) {
                // Vérification des identifiants LDAP
                try {
                    $this->ldap->bind($this->searchDn, $this->searchPassword);
                    
                    $username = $this->ldap->escape($userIdentifier, '', LdapInterface::ESCAPE_FILTER);
                    $query = sprintf('(&(objectClass=person)(%s=%s))', $this->uidKey, $username);
                    
                    $search = $this->ldap->query($this->baseDn, $query);
                    $results = $search->execute();
                    
                    if (1 !== count($results)) {
                        throw new BadCredentialsException('Bad credentials.');
                    }
                    
                    $user = $results[0];
                    $userDn = $user->getDn();
                    
                    try {
                        $this->ldap->bind($userDn, $password);
                    } catch (ConnectionException $e) {
                        throw new BadCredentialsException('Bad credentials.');
                    }
                    
                    // Déléguer le chargement de l'utilisateur au LdapUserProvider
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                } catch (ConnectionException $e) {
                    throw new BadCredentialsException('LDAP connection failed: ' . $e->getMessage());
                }
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Redirection vers la page d'accueil après connexion réussie
        return new RedirectResponse($this->urlGenerator->generate('dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}