# Architecture du Projet Agglo34

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture technique](#architecture-technique)
3. [Structure du projet](#structure-du-projet)
4. [Modèle de données](#modèle-de-données)
5. [Base de données PostgreSQL](#base-de-données-postgresql)
6. [Système d'authentification](#système-dauthentification)
7. [Interfaces utilisateur](#interfaces-utilisateur)
8. [Flux de données](#flux-de-données)

## 1. Vue d'ensemble <a name="vue-densemble"></a>

L'application Agglo34 est une application web basée sur le framework Symfony qui permet la gestion des ressources de l'agglomération, notamment les lignes téléphoniques, le parc informatique, les archives et les agents.

### Schéma global de l'application

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                    Application Agglo34                      │
│                                                             │
├─────────────┬─────────────┬─────────────┬─────────────┬─────┴─────┐
│             │             │             │             │           │
│  Gestion    │  Gestion    │  Gestion    │  Gestion    │  Autres   │
│  des lignes │  du parc    │  des        │  des        │  modules  │
│  téléphon.  │  informat.  │  boxs     │  archives   │           │
│             │             │             │             │           │
└─────────────┴─────────────┴─────────────┴─────────────┴───────────┘
```

## 2. Architecture technique <a name="architecture-technique"></a>

L'application est construite selon le modèle MVC (Modèle-Vue-Contrôleur) en utilisant le framework Symfony.

### Pile technologique

- **Backend** : PHP 8.x avec Symfony 6.x
- **Base de données** : PostgreSQL 16 (via Doctrine ORM)
- **Frontend** : HTML, CSS, JavaScript, Twig (moteur de templates)
- **Authentification** : LDAP
- **Bibliothèques JS** : jQuery, Chart.js, Leaflet (pour les cartes), DataTables
- **CSS/Design** : Bootstrap 4

### Schéma d'architecture technique

```
┌─────────────────────────────────────────────────────────────┐
│                      Navigateur Web                         │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     Serveur Web (Apache/Nginx)              │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                     Application Symfony                     │
│                                                             │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────┐   │
│  │             │   │             │   │                 │   │
│  │ Contrôleurs │──▶│   Services  │──▶│  Entités (ORM)  │   │
│  │             │   │             │   │                 │   │
│  └─────────────┘   └─────────────┘   └────────┬────────┘   │
│         │                                     │            │
│         │          ┌─────────────┐            │            │
│         └─────────▶│  Templates  │            │            │
│                    │    (Twig)   │            │            │
│                    └─────────────┘            │            │
│                                               │            │
└───────────────────────────────────────────────┼────────────┘
                                                │
                            ┌──────────────────┐│┌─────────────────┐
                            │                  ││                  │
                            │  PostgreSQL DB   ◀┘│ Serveur LDAP    │
                            │                  │ │                  │
                            └──────────────────┘ └─────────────────┘
```

## 3. Structure du projet <a name="structure-du-projet"></a>

Le projet suit la structure standard d'une application Symfony :

```
Agglo34_Mission/
├── assets/                 # Fichiers frontend (JS, CSS) gérés par Webpack Encore
├── bin/                    # Exécutables (console Symfony)
├── config/                 # Configuration de l'application
│   ├── packages/           # Configuration des bundles
│   │   ├── ldap.yaml       # Configuration LDAP
│   │   ├── security.yaml   # Configuration de sécurité
│   │   └── ...
│   ├── routes.yaml         # Configuration des routes
│   └── services.yaml       # Configuration des services
├── migrations/             # Migrations de base de données
├── public/                 # Fichiers publics accessibles via le web
│   ├── css/                # Feuilles de style
│   ├── js/                 # Scripts JavaScript
│   ├── images/             # Images
│   ├── fonts/              # Polices
│   └── data/               # Données publiques (ex: GeoJSON)
├── src/                    # Code source PHP
│   ├── Controller/         # Contrôleurs
│   ├── Entity/             # Entités Doctrine
│   ├── Repository/         # Repositories Doctrine
│   ├── Security/           # Classes liées à la sécurité
│   └── Command/            # Commandes console
├── templates/              # Templates Twig
│   ├── base.html.twig      # Template de base
│   ├── index.html.twig     # Template du tableau de bord
│   ├── pages/              # Templates des pages principales
│   ├── modals/             # Templates des fenêtres modales
│   └── partials/           # Fragments de templates réutilisables
├── tests/                  # Tests automatisés
├── translations/           # Fichiers de traduction
├── var/                    # Fichiers variables (cache, logs)
├── vendor/                 # Dépendances (gérées par Composer)
├── .env                    # Variables d'environnement
├── composer.json           # Configuration Composer
└── symfony.lock            # Verrouillage des versions Symfony
```

## 4. Modèle de données <a name="modèle-de-données"></a>

Le modèle de données de l'application est composé des entités principales suivantes :

### Diagramme des entités

```
┌───────────────┐       ┌───────────────┐       ┌───────────────┐
│     User      │       │  Municipality  │       │     Box       │
├───────────────┤       ├───────────────┤       ├───────────────┤
│ id            │       │ id            │       │ id            │
│ email         │       │ name          │       │ name          │
│ name          │       │ address       │       │ description   │
│ password      │       │ contactName   │       └───────────────┘
│ roles         │       │ contactPhone  │
│ ldapUsername  │       └───┬───────────┘
└───────────────┘           │
        │                   │
        │                   │
        │                   │
┌───────┴───────┐       ┌───▼───────────┐       ┌───────────────┐
│   Settings    │       │   PhoneLine   │       │    Archive    │
├───────────────┤       ├───────────────┤       ├───────────────┤
│ id            │       │ id            │       │ id            │
│ crudEnabled   │       │ location      │       │ ...           │
│ displayMode   │       │ service       │       └───────────────┘
│ itemsPerPage  │       │ assignedTo    │
│ appName       │       │ phoneBrand    │
│ welcomeMessage│       │ model         │
│ alertThreshold│       │ operator      │
│ featureEnabled│       │ lineType      │
└───────────────┘       └───────────────┘
```

### Relations entre entités

- **User** : Représente un utilisateur de l'application
  - Relation avec Settings (ManyToOne)

- **Municipality** : Représente une commune de l'agglomération
  - Relation avec PhoneLine (OneToMany)

- **PhoneLine** : Représente une ligne téléphonique
  - Relation avec Municipality (ManyToOne)

- **Box** : Représente une box associée à une commune
  - Pas de relation directe dans le modèle actuel

- **Archive** : Représente une archive
  - Structure à compléter

- **Settings** : Représente les paramètres de l'application
  - Relation avec User (OneToMany)

- **Log** : Représente une entrée de journal
  - Structure à compléter

## 5. Base de données PostgreSQL <a name="base-de-données-postgresql"></a>

L'application utilise PostgreSQL comme système de gestion de base de données relationnelle. PostgreSQL a été choisi pour ses performances, sa fiabilité et ses fonctionnalités avancées.

### Configuration

La base de données PostgreSQL est configurée dans le fichier `.env` :

```
DATABASE_URL="postgresql://admin:admin@database:5432/agglo34_db?serverVersion=16&charset=utf8"
```

Et dans le fichier `config/packages/doctrine.yaml` :

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        profiling_collect_backtrace: '%kernel.debug%'
        use_savepoints: true
    orm:
        # ...
        identity_generation_preferences:
            Doctrine\DBAL\Platforms\PostgreSQLPlatform: identity
        # ...
```

### Fonctionnalités PostgreSQL utilisées

L'application tire parti de plusieurs fonctionnalités avancées de PostgreSQL :

1. **Requêtes optimisées** : Les repositories Doctrine utilisent des requêtes SQL optimisées pour PostgreSQL.
2. **Recherche texte intégral** : Pour les fonctionnalités de recherche dans les lignes téléphoniques et le parc informatique.
3. **Transactions** : Pour garantir l'intégrité des données lors des opérations complexes.
4. **Indexation** : Pour améliorer les performances des requêtes fréquentes.

### Exemple de requête PostgreSQL

Voici un exemple de requête SQL native utilisée dans l'application pour obtenir des statistiques sur les lignes téléphoniques :

```sql
SELECT
    COUNT(*) as total_lines,
    AVG(debit_max) as avg_speed,
    COUNT(DISTINCT operateur) as unique_operators,
    SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active_lines
FROM phone_line
```

## 6. Système d'authentification <a name="système-dauthentification"></a>

L'application utilise l'authentification LDAP pour gérer les accès utilisateurs.

### Schéma du processus d'authentification

```
┌──────────────┐     1. Tentative de connexion     ┌──────────────┐
│              │─────────────────────────────────▶│              │
│  Utilisateur │                                   │  Application │
│              │◀────────────────────────────────│              │
└──────────────┘     8. Redirection Dashboard      └──────┬───────┘
                                                          │
                                                          │ 2. Vérification
                                                          ▼
                                                   ┌──────────────┐
                                                   │              │
                                                   │ LdapAuthenti-│
                                                   │    cator     │
                                                   │              │
                                                   └──────┬───────┘
                                                          │
                                                          │ 3. Recherche
                                                          ▼
┌──────────────┐     5. Création/Mise à jour      ┌──────────────┐
│              │◀────────────────────────────────│              │
│  Base de     │                                   │ LdapUserPro- │
│  données     │─────────────────────────────────▶│    vider     │
└──────────────┘     6. Récupération utilisateur   └──────┬───────┘
                                                          │
                                                          │ 4. Authentification
                                                          ▼
                                                   ┌──────────────┐
                                                   │              │
                                                   │    Serveur   │
                                                   │     LDAP     │
                                                   │              │
                                                   └──────────────┘
```

### Processus d'authentification

1. L'utilisateur saisit ses identifiants sur la page de connexion
2. Le `LdapAuthenticator` intercepte la demande d'authentification
3. Le `LdapAuthenticator` recherche l'utilisateur dans le LDAP via le `LdapUserProvider`
4. Le `LdapUserProvider` vérifie les identifiants auprès du serveur LDAP
5. Si l'authentification LDAP réussit, l'utilisateur est créé ou mis à jour dans la base de données locale
6. L'utilisateur est chargé depuis la base de données locale
7. L'authentification est validée
8. L'utilisateur est redirigé vers le tableau de bord

## 7. Interfaces utilisateur <a name="interfaces-utilisateur"></a>

L'application présente plusieurs interfaces utilisateur principales :

### Structure des pages

```
┌─────────────────────────────────────────────────────────────┐
│                       En-tête (Header)                      │
├─────────────┬───────────────────────────────────────────────┤
│             │                                               │
│             │                                               │
│             │                                               │
│             │                                               │
│   Menu      │                                               │
│   latéral   │                Contenu principal              │
│             │                                               │
│             │                                               │
│             │                                               │
│             │                                               │
│             │                                               │
└─────────────┴───────────────────────────────────────────────┘
```

### Pages principales

- **Tableau de bord** : Vue d'ensemble avec statistiques et carte
- **Lignes téléphoniques** : Gestion des lignes téléphoniques
- **Parc informatique** : Gestion des équipements informatiques
- **boxs** : Gestion des boxs par commune
- **Archives** : Gestion des archives
- **Agents** : Gestion des utilisateurs
- **Calendrier** : Visualisation des événements
- **Documents** : Importation et gestion de fichiers
- **Carte** : Visualisation cartographique de l'agglomération
- **Paramètres** : Configuration de l'application

## 8. Flux de données <a name="flux-de-données"></a>

### Flux principal de l'application

```
┌──────────────┐     Requête HTTP      ┌──────────────┐
│              │────────────────────▶│              │
│  Navigateur  │                       │  Contrôleur  │
│  Web         │◀───────────────────│              │
└──────────────┘     Réponse HTML      └──────┬───────┘
                                              │
                                              │ Appel
                                              ▼
                                       ┌──────────────┐
                                       │              │
                                       │  Repository  │
                                       │              │
                                       └──────┬───────┘
                                              │
                                              │ Requête SQL
                                              ▼
                                       ┌──────────────┐
                                       │              │
                                       │  Base de     │
                                       │  données     │
                                       │              │
                                       └──────────────┘
```

### Flux d'authentification LDAP

```
┌──────────────┐                      ┌──────────────┐
│              │  1. Identifiants     │              │
│  Utilisateur │────────────────────▶│  Security    │
│              │                      │  Controller  │
└──────────────┘                      └──────┬───────┘
                                             │
                                             │ 2. Authentification
                                             ▼
┌──────────────┐                      ┌──────────────┐
│              │  5. Création/MAJ     │              │
│  Base de     │◀───────────────────│  LDAP User    │
│  données     │                      │  Provider    │
└──────────────┘                      └──────┬───────┘
                                             │
                                             │ 3. Vérification
                                             ▼
                                      ┌──────────────┐
                                      │              │
                                      │  Serveur     │
                                      │  LDAP        │
                                      │              │
                                      └──────────────┘
```

---

## Conclusion

Cette documentation présente l'architecture globale du projet Agglo34, une application Symfony dédiée à la gestion des ressources de l'agglomération. L'application est structurée selon le modèle MVC et utilise l'authentification LDAP pour la gestion des utilisateurs.

Le modèle de données est centré autour des entités principales comme Municipality, PhoneLine, Box, Archive et User, avec des relations bien définies entre elles.

L'interface utilisateur est organisée autour d'un tableau de bord central et de plusieurs modules spécialisés pour la gestion des différentes ressources de l'agglomération.