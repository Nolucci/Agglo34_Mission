# Gestion de la Whitelist et des Rôles

## Vue d'ensemble

Ce document décrit le système de gestion de la whitelist et des rôles pour l'authentification LDAP dans l'application Agglo34.

## Système de Whitelist

### Principe

La whitelist permet de contrôler quels utilisateurs LDAP peuvent accéder à l'application. Seuls les utilisateurs présents dans la whitelist active peuvent se connecter.

### Fonctionnalités

- **Ajout d'utilisateurs** : Ajouter des utilisateurs LDAP à la whitelist
- **Désactivation temporaire** : Désactiver un utilisateur sans le supprimer définitivement
- **Réactivation** : Réactiver un utilisateur précédemment désactivé
- **Synchronisation LDAP** : Récupération automatique des informations depuis LDAP

### Gestion via l'interface web

Accédez à `/admin/whitelist` pour gérer la whitelist via l'interface d'administration.

#### Ajouter un utilisateur

1. Cliquez sur "Ajouter un utilisateur"
2. Saisissez le nom d'utilisateur LDAP
3. Optionnel : Cliquez sur "Tester" pour vérifier l'existence dans LDAP
4. Les informations (nom, email) sont récupérées automatiquement depuis LDAP
5. Cliquez sur "Ajouter"

#### Désactiver/Réactiver un utilisateur

- **Désactiver** : Cliquez sur le bouton "Désactiver" pour empêcher la connexion
- **Réactiver** : Cliquez sur le bouton "Réactiver" pour autoriser à nouveau la connexion

### Gestion en ligne de commande

```bash
# Lister tous les utilisateurs de la whitelist
php bin/console app:whitelist:manage list

# Lister uniquement les utilisateurs actifs
php bin/console app:whitelist:manage list --all

# Ajouter un utilisateur
php bin/console app:whitelist:manage add jdupont --name="Jean Dupont" --email="jean.dupont@example.com"

# Désactiver un utilisateur
php bin/console app:whitelist:manage remove jdupont

# Réactiver un utilisateur
php bin/console app:whitelist:manage activate jdupont

# Tester l'existence d'un utilisateur dans LDAP
php bin/console app:whitelist:manage test jdupont
```

## Système de Rôles

### Rôles disponibles

1. **ROLE_ADMIN** : Administrateur complet
   - Accès à toutes les fonctionnalités
   - Gestion des utilisateurs et paramètres
   - Gestion de la whitelist

2. **ROLE_MODIFIEUR** : Utilisateur avec droits de modification
   - Peut modifier les données
   - Accès à toutes les sections en lecture/écriture

3. **ROLE_VISITEUR_TOUT** : Visiteur avec accès complet en lecture
   - Accès à toutes les sections en lecture seule

4. **ROLE_VISITEUR_LIGNES** : Visiteur lignes téléphoniques
   - Accès uniquement aux lignes téléphoniques

5. **ROLE_VISITEUR_PARC** : Visiteur parc informatique
   - Accès uniquement au parc informatique

6. **ROLE_VISITEUR_BOXS** : Visiteur boxs
   - Accès uniquement aux boxs

7. **ROLE_DISABLED** : Utilisateur désactivé
   - Aucun accès à l'application
   - Connexion refusée

### Hiérarchie des rôles

```
ROLE_ADMIN
├── ROLE_MODIFIEUR
│   ├── ROLE_VISITEUR_TOUT
│   │   ├── ROLE_VISITEUR_LIGNES
│   │   ├── ROLE_VISITEUR_PARC
│   │   └── ROLE_VISITEUR_BOXS
```

### Gestion des rôles

Les rôles sont automatiquement normalisés selon la hiérarchie :
- Un utilisateur avec `ROLE_ADMIN` hérite de tous les autres rôles
- Un utilisateur avec `ROLE_MODIFIEUR` hérite de tous les rôles visiteur
- Un utilisateur avec `ROLE_VISITEUR_TOUT` hérite des rôles visiteur spécifiques
- Un utilisateur avec `ROLE_DISABLED` ne peut avoir aucun autre rôle

## Authentification LDAP

### Connexion par identifiant ou email

Les utilisateurs peuvent se connecter avec :
- **Identifiant LDAP** : `jdupont`
- **Email complet** : `jdupont@domain.com`

Le système extrait automatiquement l'identifiant de l'email pour la recherche LDAP.

### Processus d'authentification

1. **Vérification whitelist** : L'utilisateur doit être dans la whitelist active
2. **Recherche LDAP** : Recherche de l'utilisateur dans l'annuaire LDAP
3. **Authentification LDAP** : Vérification du mot de passe via LDAP
4. **Vérification rôle** : L'utilisateur ne doit pas avoir le rôle `ROLE_DISABLED`
5. **Création/Mise à jour** : Création ou mise à jour de l'utilisateur local
6. **Connexion** : Attribution du token d'authentification

### Gestion des utilisateurs désactivés

Pour désactiver temporairement un utilisateur :

1. **Via l'interface** : Aller dans la gestion des utilisateurs et changer le rôle vers "Désactivé"
2. **Via la whitelist** : Désactiver l'utilisateur dans la whitelist
3. **Effet** : L'utilisateur ne peut plus se connecter même avec des identifiants valides

## Configuration

### Paramètres LDAP requis

- **Host** : Serveur LDAP
- **Port** : Port de connexion (389 ou 636)
- **Search DN** : DN du compte de service
- **Search Password** : Mot de passe du compte de service
- **Base DN** : DN de base pour la recherche
- **UID Key** : Attribut utilisé pour l'identifiant (ex: `samaccountname`)
- **Encryption** : Type de chiffrement (none, ssl, tls)

### Attributs LDAP utilisés

- `displayname` ou `cn` : Nom complet
- `mail` : Adresse email
- `samaccountname` : Nom de compte
- `givenname` : Prénom
- `sn` : Nom de famille

## Sécurité

### Bonnes pratiques

1. **Whitelist restrictive** : N'ajouter que les utilisateurs nécessaires
2. **Rôles minimaux** : Attribuer le rôle minimum requis
3. **Désactivation temporaire** : Utiliser `ROLE_DISABLED` plutôt que la suppression
4. **Audit régulier** : Vérifier régulièrement la whitelist et les rôles
5. **Compte de service** : Utiliser un compte dédié avec droits minimaux pour LDAP

### Logs et monitoring

- Toutes les actions de whitelist sont loggées
- Les tentatives de connexion sont tracées
- Les erreurs LDAP sont enregistrées avec détails

## Dépannage

### Problèmes courants

1. **Utilisateur non trouvé dans LDAP**
   - Vérifier l'orthographe du nom d'utilisateur
   - Vérifier la configuration LDAP (Base DN, UID Key)
   - Tester avec la commande : `php bin/console app:whitelist:manage test username`

2. **Utilisateur non autorisé**
   - Vérifier que l'utilisateur est dans la whitelist active
   - Vérifier que l'utilisateur n'a pas le rôle `ROLE_DISABLED`

3. **Erreur de connexion LDAP**
   - Vérifier les paramètres de connexion (host, port, encryption)
   - Vérifier les identifiants du compte de service
   - Tester la connexion : `php bin/console app:ldap:test`

### Commandes de diagnostic

```bash
# Tester la configuration LDAP
php bin/console app:ldap:test

# Vérifier un utilisateur spécifique
php bin/console app:whitelist:manage test username

# Lister tous les utilisateurs de la whitelist
php bin/console app:whitelist:manage list --all

# Vérifier les logs
tail -f var/log/dev.log | grep -i ldap
```

## API

### Endpoints disponibles

- `GET /admin/whitelist` : Interface de gestion
- `POST /admin/whitelist/add` : Ajouter un utilisateur
- `POST /admin/whitelist/remove/{username}` : Désactiver un utilisateur
- `POST /admin/whitelist/activate/{username}` : Réactiver un utilisateur
- `POST /admin/whitelist/test-ldap-user` : Tester un utilisateur LDAP

### Réponses JSON

```json
{
  "success": true|false,
  "message": "Message descriptif",
  "user": {
    "id": 1,
    "ldap_username": "jdupont",
    "name": "Jean Dupont",
    "email": "jean.dupont@example.com",
    "is_active": true
  }
}