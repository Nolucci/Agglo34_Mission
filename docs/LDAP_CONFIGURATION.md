# Configuration LDAP Conditionnelle

## Vue d'ensemble

Ce système permet de gérer l'authentification LDAP de manière conditionnelle dans l'application Agglo34 Mission. Par défaut, LDAP est **désactivé**, permettant un accès libre à l'application. Une fois activé depuis les paramètres, l'authentification LDAP devient obligatoire.

## Fonctionnalités

### 1. Accès sans authentification (LDAP désactivé)
- **État par défaut** : LDAP est désactivé
- **Comportement** : Accès libre à toutes les fonctionnalités
- **Utilisateur automatique** : Un utilisateur anonyme avec droits administrateur est créé automatiquement
- **Aucune page de connexion** requise

### 2. Authentification LDAP (LDAP activé)
- **Activation** : Via les paramètres administrateur
- **Comportement** : Authentification LDAP obligatoire pour accéder à l'application
- **Redirection** : Les utilisateurs non authentifiés sont redirigés vers la page de connexion
- **Gestion des utilisateurs** : Création automatique des utilisateurs LDAP en base locale

### 3. Test de connexion LDAP
- **Bouton de test** : Disponible dans les paramètres administrateur
- **Test de connexion** : Vérifie la connectivité au serveur LDAP
- **Test d'authentification** : Permet de tester l'authentification d'un utilisateur spécifique
- **Feedback en temps réel** : Affichage des résultats et erreurs

## Architecture technique

### Composants principaux

#### 1. Services
- **`SettingsService`** : Gestion des paramètres LDAP
- **`LdapTestService`** : Tests de connexion et d'authentification LDAP

#### 2. Sécurité
- **`ConditionalLdapAuthenticator`** : Authenticator qui délègue à LDAP si activé
- **`ConditionalUserProvider`** : Provider qui gère l'utilisateur anonyme ou LDAP
- **`LdapAuthenticator`** : Authenticator LDAP standard
- **`LdapUserProvider`** : Provider LDAP standard

#### 3. Event Listeners
- **`ConditionalAuthenticationListener`** : Authentifie automatiquement un utilisateur anonyme quand LDAP est désactivé
- **`MaintenanceListener`** : Gestion du mode maintenance

#### 4. Entités
- **`Settings`** : Stockage des paramètres LDAP en base de données
- **`User`** : Entité utilisateur avec support LDAP

### Configuration

#### Paramètres LDAP disponibles
- **ldap_enabled** : Active/désactive LDAP (false par défaut)
- **ldap_host** : Serveur LDAP
- **ldap_port** : Port LDAP (389 par défaut)
- **ldap_encryption** : Type de chiffrement (none, ssl, tls)
- **ldap_base_dn** : Base DN pour les recherches
- **ldap_search_dn** : DN du compte de service
- **ldap_search_password** : Mot de passe du compte de service
- **ldap_uid_key** : Attribut utilisé comme identifiant (nomcompte par défaut)

## Utilisation

### 1. Installation initiale
```bash
# Initialiser les paramètres LDAP par défaut
php bin/console app:init-ldap-settings
```

### 2. Configuration LDAP
1. Accéder aux **Paramètres administrateur**
2. Remplir la section **Configuration LDAP**
3. Utiliser le bouton **"Tester la connexion LDAP"** pour valider la configuration
4. Optionnellement, tester l'authentification d'un utilisateur spécifique
5. **Activer LDAP** une fois les tests réussis
6. **Enregistrer les paramètres**

### 3. Test de connexion
- **Test de connectivité** : Vérifie la connexion au serveur LDAP et la recherche dans le Base DN
- **Test d'authentification** : Vérifie qu'un utilisateur peut s'authentifier avec ses identifiants

### 4. Basculement LDAP
- **Désactivation** : Retour immédiat à l'accès libre
- **Activation** : Authentification LDAP obligatoire pour tous les accès

## Sécurité

### Mode LDAP désactivé
- Utilisateur anonyme avec droits **ROLE_ADMIN**
- Accès complet à toutes les fonctionnalités
- Aucune authentification requise

### Mode LDAP activé
- Authentification obligatoire via LDAP
- Création automatique des utilisateurs en base locale
- Gestion des rôles basée sur la whitelist et les permissions
- Premier utilisateur obtient automatiquement les droits administrateur

## Dépannage

### Problèmes courants

#### 1. Erreur de connexion LDAP
- Vérifier l'adresse du serveur et le port
- Contrôler les paramètres de chiffrement
- Tester la connectivité réseau

#### 2. Erreur d'authentification
- Vérifier le Search DN et le mot de passe du compte de service
- Contrôler le Base DN
- Vérifier l'attribut UID utilisé

#### 3. Utilisateur non trouvé
- Vérifier que l'utilisateur existe dans le Base DN spécifié
- Contrôler l'attribut UID (nomcompte par défaut)
- Vérifier les filtres LDAP

### Logs
Les logs d'authentification LDAP sont disponibles dans les logs Symfony standard.

## Migration depuis un système LDAP existant

Si vous migrez depuis un système où LDAP était toujours activé :

1. Les paramètres LDAP existants sont préservés
2. LDAP reste activé si déjà configuré
3. Aucune interruption de service pour les utilisateurs existants

## Commandes utiles

```bash
# Initialiser les paramètres LDAP
php bin/console app:init-ldap-settings

# Tester la configuration LDAP (si disponible)
php bin/console app:test-ldap

# Vider le cache après modification des paramètres
php bin/console cache:clear