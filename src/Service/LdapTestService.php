<?php

namespace App\Service;

use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Psr\Log\LoggerInterface;

/**
 * Service pour tester la connexion LDAP
 */
class LdapTestService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Teste la connexion LDAP avec les paramètres fournis
     */
    public function testConnection(array $config): array
    {
        try {
            $this->logger->info('Test de connexion LDAP démarré', $config);

            // Validation des paramètres requis
            $requiredFields = ['host', 'port', 'search_dn', 'search_password', 'base_dn'];
            foreach ($requiredFields as $field) {
                if (empty($config[$field])) {
                    return [
                        'success' => false,
                        'message' => "Le champ '{$field}' est requis pour tester la connexion LDAP."
                    ];
                }
            }

            // Création de la connexion LDAP
            $ldap = Ldap::create('ext_ldap', [
                'host' => $config['host'],
                'port' => (int)$config['port'],
                'encryption' => $config['encryption'] ?? 'none'
            ]);

            // Test de connexion avec le compte de service
            $ldap->bind($config['search_dn'], $config['search_password']);

            // Test de recherche dans le base DN
            $query = $ldap->query($config['base_dn'], '(objectClass=*)');
            $results = $query->execute();

            $this->logger->info('Test LDAP réussi', [
                'host' => $config['host'],
                'port' => $config['port'],
                'results_count' => count($results)
            ]);

            return [
                'success' => true,
                'message' => 'Connexion LDAP réussie ! ' . count($results) . ' objets trouvés dans le Base DN.'
            ];

        } catch (ConnectionException $e) {
            $this->logger->error('Erreur de connexion LDAP', [
                'error' => $e->getMessage(),
                'config' => $config
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de connexion LDAP : ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur générale lors du test LDAP', [
                'error' => $e->getMessage(),
                'config' => $config
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du test : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Teste l'authentification d'un utilisateur spécifique
     */
    public function testUserAuthentication(array $config, string $username, string $password): array
    {
        try {
            $this->logger->info('Test d\'authentification utilisateur LDAP', ['username' => $username]);

            // Validation des paramètres
            if (empty($username) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Nom d\'utilisateur et mot de passe requis.'
                ];
            }

            // Création de la connexion LDAP
            $ldap = Ldap::create('ext_ldap', [
                'host' => $config['host'],
                'port' => (int)$config['port'],
                'encryption' => $config['encryption'] ?? 'none'
            ]);

            // Connexion avec le compte de service
            $ldap->bind($config['search_dn'], $config['search_password']);

            // Recherche de l'utilisateur
            $uidKey = $config['uid_key'] ?? 'nomcompte';
            $escapedUsername = $ldap->escape($username, '', \Symfony\Component\Ldap\LdapInterface::ESCAPE_FILTER);
            $query = sprintf('(&(objectClass=user)(objectCategory=person)(%s=%s))', $uidKey, $escapedUsername);

            $search = $ldap->query($config['base_dn'], $query);
            $results = $search->execute();

            if (count($results) === 0) {
                return [
                    'success' => false,
                    'message' => "Utilisateur '{$username}' non trouvé dans LDAP."
                ];
            }

            if (count($results) > 1) {
                return [
                    'success' => false,
                    'message' => "Plusieurs utilisateurs trouvés pour '{$username}'."
                ];
            }

            $user = $results[0];
            $userDn = $user->getDn();

            // Test d'authentification avec les identifiants utilisateur
            $ldap->bind($userDn, $password);

            $this->logger->info('Test d\'authentification utilisateur réussi', ['username' => $username]);

            return [
                'success' => true,
                'message' => "Authentification réussie pour l'utilisateur '{$username}'.",
                'user_dn' => $userDn,
                'user_attributes' => $this->extractUserAttributes($user)
            ];

        } catch (ConnectionException $e) {
            $this->logger->error('Erreur d\'authentification LDAP', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur d\'authentification : ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur générale lors du test d\'authentification', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du test : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extrait les attributs utiles de l'utilisateur LDAP
     */
    private function extractUserAttributes($user): array
    {
        $attributes = $user->getAttributes();
        $extracted = [];

        $relevantAttributes = ['displayname', 'mail', 'samaccountname', 'cn', 'givenname', 'sn'];

        foreach ($relevantAttributes as $attr) {
            if (isset($attributes[$attr]) && !empty($attributes[$attr][0])) {
                $extracted[$attr] = $attributes[$attr][0];
            }
        }

        return $extracted;
    }
}