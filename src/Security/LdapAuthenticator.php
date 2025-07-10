<?php

namespace App\Security;

use App\Service\SettingsService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LdapAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private LdapUserProvider $userProvider;
    private LoggerInterface $logger;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private SettingsService $settingsService,
        LdapUserProvider $userProvider,
        LoggerInterface $logger
    ) {
        $this->userProvider = $userProvider;
        $this->logger = $logger;
    }

    public function authenticate(Request $request): Passport
    {
        $this->logger->info("LdapAuthenticator::authenticate appelé.");
        $username = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');
        $this->logger->info("Username: " . $username . ", Password (partiel): " . substr($password, 0, 3) . '...');

        $request->getSession()->set('_security.last_username', $username);

        return new SelfValidatingPassport(
            new UserBadge($username, function ($userIdentifier) use ($password) {
                // Si l'identifiant est une adresse email, extraire le samaccountname
                if (str_contains($userIdentifier, '@')) {
                    $userIdentifier = explode('@', $userIdentifier)[0];
                }

                // Récupérer les paramètres LDAP depuis la base de données
                $settings = $this->settingsService->getSettings();
                if (!$settings || !$settings->isLdapEnabled()) {
                    throw new BadCredentialsException('LDAP is not enabled');
                }

                // Créer la connexion LDAP avec les paramètres de la base de données
                $ldap = Ldap::create('ext_ldap', [
                    'host' => $settings->getLdapHost(),
                    'port' => $settings->getLdapPort(),
                    'encryption' => $settings->getLdapEncryption(),
                    'options' => [
                        'protocol_version' => 3,
                        'referrals' => false
                    ]
                ]);

                // Vérification des identifiants LDAP
                try {
                    $this->logger->info("Tentative d'authentification pour l'utilisateur: " . $userIdentifier);

                    // Première connexion avec le compte de service
                    try {
                        $searchDn = $settings->getLdapSearchDn();
                        $searchPassword = $settings->getLdapSearchPassword();
                        $this->logger->info("Tentative de connexion avec le compte de service DN: " . $searchDn);
                        $ldap->bind($searchDn, $searchPassword);
                        $this->logger->info("Connexion avec le compte de service réussie");
                    } catch (\Exception $e) {
                        $this->logger->error("Erreur de connexion avec le compte de service: " . $e->getMessage());
                        throw $e;
                    }

                    // Recherche de l'utilisateur avec le filtre exact
                    $uidKey = $settings->getLdapUidKey();
                    $baseDn = $settings->getLdapBaseDn();
                    $username = $ldap->escape($userIdentifier, '', LdapInterface::ESCAPE_FILTER);
                    $query = sprintf('(&(objectClass=user)(objectCategory=person)(%s=%s))', $uidKey, $username);

                    $this->logger->info("Recherche LDAP - Base DN: " . $baseDn);
                    $this->logger->info("Recherche LDAP - Filtre: " . $query);

                    $search = $ldap->query($baseDn, $query);
                    $results = $search->execute();

                    $this->logger->info(sprintf("Nombre de résultats trouvés: %d", count($results)));

                    if (0 === count($results)) {
                        throw new BadCredentialsException(sprintf('Aucun utilisateur trouvé avec %s=%s', $uidKey, $username));
                    }

                    if (count($results) > 1) {
                        throw new BadCredentialsException(sprintf('Plusieurs utilisateurs trouvés avec %s=%s', $uidKey, $username));
                    }

                    $user = $results[0];
                    $userDn = $user->getDn();

                    // Affichage des informations de l'utilisateur pour le débogage
                    $this->logger->info(sprintf('DN de l\'utilisateur pour la liaison: %s', $userDn));
                    $this->logger->info('Attributs de l\'utilisateur récupérés:');
                    foreach ($user->getAttributes() as $key => $value) {
                        if (!in_array($key, ['thumbnailPhoto', 'jpegPhoto', 'userPassword'])) {
                            $this->logger->info(sprintf('- %s: %s', $key, implode(', ', $value)));
                        }
                    }

                    // Tentative de connexion avec les identifiants de l'utilisateur
                    $this->logger->info(sprintf("Tentative de liaison avec le DN utilisateur: %s", $userDn));
                    $this->logger->info(sprintf("Mot de passe fourni (partiel): %s...", substr($password, 0, 3)));
                    try {
                        $ldap->bind($userDn, $password);
                        $this->logger->info(sprintf("Authentification réussie pour l'utilisateur: %s", $userIdentifier));
                    } catch (ConnectionException $e) {
                        $this->logger->error(sprintf("Échec de l'authentification pour %s. Message complet: %s", $userIdentifier, $e->getMessage()));
                        throw new BadCredentialsException('Mot de passe incorrect ou problème de liaison LDAP: ' . $e->getMessage());
                    }

                    // Déléguer le chargement de l'utilisateur au LdapUserProvider
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                } catch (ConnectionException $e) {
                    throw new BadCredentialsException('LDAP connection failed: ' . $e->getMessage());
                }
            }),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ],
        );
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $this->getLoginUrl($request) === $request->getPathInfo();
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