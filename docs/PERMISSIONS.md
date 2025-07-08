# Système de Permissions et Whitelist

## Vue d'ensemble

Ce système implémente un contrôle d'accès basé sur les rôles avec une whitelist pour les utilisateurs LDAP.

## Hiérarchie des Rôles

### 1. ROLE_ADMIN (Administrateur)
- **Permissions** : Accès total à toutes les fonctionnalités
- **Peut** :
  - Créer et modifier toutes les données
  - Gérer les utilisateurs et leurs rôles
  - Gérer la whitelist
  - Accéder à toutes les pages
- **Héritage** : Inclut automatiquement tous les autres rôles

### 2. ROLE_MODIFIEUR (Modifieur)
- **Permissions** : Peut créer et modifier les données auxquelles il a accès
- **Peut** :
  - Modifier les données dans les sections autorisées
  - Accéder à toutes les pages de consultation
- **Héritage** : Inclut automatiquement tous les rôles ROLE_VISITEUR_*

### 3. ROLE_VISITEUR_TOUT (Visiteur - Tout)
- **Permissions** : Accès en lecture à toutes les sections
- **Peut** :
  - Consulter toutes les pages
  - Exporter les données
- **Héritage** : Inclut automatiquement tous les rôles ROLE_VISITEUR_* spécifiques

### 4. ROLE_VISITEUR_LIGNES (Visiteur - Lignes Téléphoniques)
- **Permissions** : Accès en lecture uniquement aux lignes téléphoniques
- **Peut** :
  - Consulter la page des lignes téléphoniques
  - Exporter les données des lignes

### 5. ROLE_VISITEUR_PARC (Visiteur - Parc Informatique)
- **Permissions** : Accès en lecture uniquement au parc informatique
- **Peut** :
  - Consulter la page du parc informatique
  - Exporter les données du matériel

### 6. ROLE_VISITEUR_BOXS (Visiteur - Boxs)
- **Permissions** : Accès en lecture uniquement aux boxs
- **Peut** :
  - Consulter la page des boxs
  - Exporter les données des boxs

## Système de Whitelist

### Fonctionnement
1. **Premier utilisateur** : Le premier utilisateur à se connecter devient automatiquement administrateur
2. **Utilisateurs suivants** : Doivent être ajoutés à la whitelist par un administrateur
3. **Contrôle d'accès** : Les utilisateurs non whitelistés ne peuvent pas se connecter

### Gestion de la Whitelist
- **Ajout** : Seuls les administrateurs peuvent ajouter des utilisateurs
- **Suppression** : Seuls les administrateurs peuvent retirer des utilisateurs
- **Informations stockées** :
  - Nom d'utilisateur LDAP (obligatoire)
  - Nom complet (optionnel)
  - Email (optionnel)
  - Utilisateur qui a ajouté l'entrée
  - Date d'ajout

## Configuration des Contrôleurs

### Annotations de Sécurité
```php
#[IsGranted('ROLE_ADMIN')]           // Administrateurs uniquement
#[IsGranted('ROLE_MODIFIEUR')]       // Modifieurs et administrateurs
#[IsGranted('ROLE_VISITEUR_LIGNES')] // Accès aux lignes téléphoniques
```

### Contrôles d'Accès dans security.yaml
```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/lines, roles: ROLE_VISITEUR_LIGNES }
    - { path: ^/equipment, roles: ROLE_VISITEUR_PARC }
    - { path: ^/boxes, roles: ROLE_VISITEUR_BOXS }
```

## Utilisation dans les Templates

### Fonctions Twig Disponibles
```twig
{% if can_access_phone_lines() %}
    <!-- Contenu pour les utilisateurs ayant accès aux lignes -->
{% endif %}

{% if can_modify() %}
    <!-- Boutons de modification -->
{% endif %}

{% if is_admin() %}
    <!-- Fonctionnalités d'administration -->
{% endif %}
```

### Vérifications de Permissions
```twig
{{ can_access_phone_lines() }}    <!-- true/false -->
{{ can_access_equipment() }}      <!-- true/false -->
{{ can_access_boxes() }}          <!-- true/false -->
{{ can_modify() }}                <!-- true/false -->
{{ can_manage_users() }}          <!-- true/false -->
{{ is_admin() }}                  <!-- true/false -->
{{ is_modifieur() }}              <!-- true/false -->
```

## Méthodes de l'Entité User

### Vérifications de Rôles
```php
$user->isAdmin()                  // Vérifie ROLE_ADMIN
$user->isModifieur()              // Vérifie ROLE_MODIFIEUR ou ROLE_ADMIN
$user->canAccessPhoneLines()      // Vérifie l'accès aux lignes
$user->canAccessEquipment()       // Vérifie l'accès au parc informatique
$user->canAccessBoxes()           // Vérifie l'accès aux boxs
$user->canModify()                // Vérifie les droits de modification
$user->canManageUsers()           // Vérifie les droits de gestion des utilisateurs
```

## Service UserPermissionService

### Méthodes Principales
```php
// Gestion du premier utilisateur
$service->isFirstUser()                    // Vérifie si c'est le premier utilisateur
$service->makeFirstUserAdmin($user)        // Fait du premier utilisateur un admin

// Gestion de la whitelist
$service->isUserWhitelisted($username)     // Vérifie si l'utilisateur est whitelisté
$service->addToWhitelist($username, ...)   // Ajoute à la whitelist
$service->removeFromWhitelist($username)   // Retire de la whitelist

// Gestion des rôles
$service->assignRole($user, $role)         // Assigne un rôle
$service->removeRole($user, $role)         // Retire un rôle
$service->getAvailableRoles()              // Liste des rôles disponibles
```

## Messages d'Erreur

### Erreur de Whitelist
Lorsqu'un utilisateur non whitelisté tente de se connecter :
- **Message** : "Votre compte n'est pas autorisé à accéder à cette application"
- **Action** : L'utilisateur reste sur la page de connexion
- **Solution** : Un administrateur doit ajouter l'utilisateur à la whitelist

## Migration et Installation

### Base de Données
La migration `Version20250108084700` ajoute :
- Table `whitelist` pour la gestion des utilisateurs autorisés
- Colonnes `is_first_user`, `created_at`, `last_login_at` à la table `user`

### Configuration LDAP
Les variables d'environnement nécessaires :
- `LDAP_HOST`
- `LDAP_PORT`
- `LDAP_ENCRYPTION`
- `LDAP_BASE_DN`
- `LDAP_SEARCH_DN`
- `LDAP_SEARCH_PASSWORD`
- `LDAP_UID_KEY`

## Interface d'Administration

### Page de Gestion des Utilisateurs
- **URL** : `/admin/users`
- **Accès** : Administrateurs uniquement
- **Fonctionnalités** :
  - Liste des utilisateurs enregistrés
  - Gestion des rôles par utilisateur
  - Gestion de la whitelist
  - Ajout/suppression d'utilisateurs autorisés

### Onglets Disponibles
1. **Utilisateurs** : Liste et gestion des rôles
2. **Whitelist** : Gestion des utilisateurs autorisés