# Manuel de Maintenance - Application Agglo34

## Table des matières

1. [Introduction](#introduction)
2. [Configuration de l'environnement de développement](#configuration-de-lenvironnement-de-développement)
3. [Structure du projet](#structure-du-projet)
4. [Gestion des entités](#gestion-des-entités)
   - [Modification d'entités existantes](#modification-dentités-existantes)
   - [Création de nouvelles entités](#création-de-nouvelles-entités)
   - [Relations entre entités](#relations-entre-entités)
5. [Gestion de la base de données](#gestion-de-la-base-de-données)
   - [Migrations Doctrine](#migrations-doctrine)
   - [Requêtes personnalisées](#requêtes-personnalisées)
6. [Modification des contrôleurs](#modification-des-contrôleurs)
7. [Modification des vues](#modification-des-vues)
8. [Gestion de l'authentification LDAP](#gestion-de-lauthentification-ldap)
9. [Paramétrage de l'application](#paramétrage-de-lapplication)
10. [Déploiement](#déploiement)

## 1. Introduction <a name="introduction"></a>

Ce manuel de maintenance est destiné aux développeurs qui souhaitent modifier ou étendre l'application Agglo34. Il fournit des instructions détaillées sur la façon de configurer l'environnement de développement, de modifier les entités existantes, d'ajouter de nouvelles fonctionnalités et de déployer les modifications.

## 2. Configuration de l'environnement de développement <a name="configuration-de-lenvironnement-de-développement"></a>

### Prérequis

- PHP 8.x
- Composer
- PostgreSQL 16
- Serveur LDAP (pour l'authentification)
- Git

### Installation du projet

1. Installez les dépendances via Composer :
   ```bash
   composer install
   ```

2. Configurez les variables d'environnement :
   - Copiez le fichier `.env` en `.env.local`
   - Modifiez les paramètres dans `.env.local` selon votre environnement local

   ```
   DATABASE_URL="postgresql://username:password@localhost:5432/agglo34_db?serverVersion=16&charset=utf8"
   
   # Configuration LDAP
   LDAP_HOST=ldap.example.com
   LDAP_PORT=389
   LDAP_ENCRYPTION=none
   LDAP_BASE_DN=dc=example,dc=com
   LDAP_SEARCH_DN=cn=admin,dc=example,dc=com
   LDAP_SEARCH_PASSWORD=admin_password
   LDAP_UID_KEY=sAMAccountName
   ```

3. Créez la base de données :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. Lancez le serveur de développement :
   ```bash
   symfony server:start
   ```

## 3. Structure du projet <a name="structure-du-projet"></a>

L'application suit la structure standard d'un projet Symfony :

- `src/Entity/` : Définition des entités Doctrine
- `src/Repository/` : Repositories pour les requêtes personnalisées
- `src/Controller/` : Contrôleurs de l'application
- `src/Security/` : Classes liées à la sécurité et à l'authentification
- `templates/` : Templates Twig pour les vues
- `config/` : Fichiers de configuration
- `migrations/` : Migrations de base de données
- `public/` : Fichiers publics (CSS, JS, images)

## 4. Gestion des entités <a name="gestion-des-entités"></a>

### Modification d'entités existantes <a name="modification-dentités-existantes"></a>

Les entités sont définies dans le répertoire `src/Entity/`. Pour modifier une entité existante :

1. Ouvrez le fichier de l'entité (par exemple, `src/Entity/PhoneLine.php`)
2. Ajoutez, modifiez ou supprimez les propriétés et méthodes selon vos besoins
3. Assurez-vous d'ajouter les annotations Doctrine appropriées pour les nouvelles propriétés
4. Générez les getters et setters pour les nouvelles propriétés :
   ```bash
   php bin/console make:entity --regenerate App\\Entity\\PhoneLine
   ```
5. Créez une migration pour mettre à jour la base de données :
   ```bash
   php bin/console make:migration
   ```
6. Appliquez la migration :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

#### Exemple : Ajout d'un champ à l'entité PhoneLine

```php
// src/Entity/PhoneLine.php

// ...

class PhoneLine
{
    // ...
    
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contractNumber = null;
    
    // ...
    
    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }
    
    public function setContractNumber(?string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;
        return $this;
    }
}
```

### Création de nouvelles entités <a name="création-de-nouvelles-entités"></a>

Pour créer une nouvelle entité :

1. Utilisez la commande Symfony :
   ```bash
   php bin/console make:entity
   ```
2. Suivez les instructions pour définir le nom de l'entité et ses propriétés
3. Créez une migration pour mettre à jour la base de données :
   ```bash
   php bin/console make:migration
   ```
4. Appliquez la migration :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

#### Exemple : Création d'une nouvelle entité

```bash
php bin/console make:entity

 Class name of the entity to create or update (e.g. GrumpyPuppy):
 > Contract

 created: src/Entity/Contract.php
 created: src/Repository/ContractRepository.php
 
 Entity generated! Now let's add some fields!
 You can always add more fields later manually or by re-running this command.

 New property name (press <return> to stop adding fields):
 > name

 Field type (enter ? to see all types) [string]:
 > string

 Field length [255]:
 > 100

 Can this field be null in the database (nullable) (yes/no) [no]:
 > no

 New property name (press <return> to stop adding fields):
 > startDate

 Field type (enter ? to see all types) [string]:
 > datetime

 Can this field be null in the database (nullable) (yes/no) [no]:
 > no

 New property name (press <return> to stop adding fields):
 > endDate

 Field type (enter ? to see all types) [string]:
 > datetime

 Can this field be null in the database (nullable) (yes/no) [no]:
 > yes

 New property name (press <return> to stop adding fields):
 > 

 Success! 
```

### Relations entre entités <a name="relations-entre-entités"></a>

Pour ajouter une relation entre entités :

1. Utilisez la commande Symfony :
   ```bash
   php bin/console make:entity
   ```
2. Spécifiez le nom de l'entité à modifier
3. Ajoutez une nouvelle propriété qui sera la relation
4. Choisissez le type de relation (ManyToOne, OneToMany, ManyToMany, OneToOne)
5. Suivez les instructions pour configurer la relation
6. Créez et appliquez une migration

#### Exemple : Ajout d'une relation ManyToOne

```bash
php bin/console make:entity

 Class name of the entity to create or update (e.g. GrumpyPuppy):
 > Contract

 Your entity already exists! So let's add some new fields!

 New property name (press <return> to stop adding fields):
 > phoneLine

 Field type (enter ? to see all types) [string]:
 > relation

 What class should this entity be related to?:
 > PhoneLine

 What type of relationship is this?
  ------------ ------------------------------------------------------------------ 
   Type         Description                                                       
  ------------ ------------------------------------------------------------------ 
   ManyToOne    Each Contract relates to (has) one PhoneLine.                     
                Each PhoneLine can relate to (can have) many Contract objects.    
                                                                                  
   OneToMany    Each Contract can relate to (can have) many PhoneLine objects.    
                Each PhoneLine relates to (has) one Contract.                     
                                                                                  
   ManyToMany   Each Contract can relate to (can have) many PhoneLine objects.    
                Each PhoneLine can also relate to (can also have) many Contract   
                objects.                                                          
                                                                                  
   OneToOne     Each Contract relates to (has) exactly one PhoneLine.             
                Each PhoneLine also relates to (has) exactly one Contract.        
  ------------ ------------------------------------------------------------------ 

 Relation type? [ManyToOne, OneToMany, ManyToMany, OneToOne]:
 > ManyToOne

 Is the Contract.phoneLine property allowed to be null (nullable)? (yes/no) [yes]:
 > no

 Do you want to add a new property to PhoneLine so that you can access/update Contract objects from it - e.g. $phoneLine->getContracts()? (yes/no) [yes]:
 > yes

 A new property will also be added to the PhoneLine class so that you can access the related Contract objects from it.

 New field name inside PhoneLine [contracts]:
 > contracts

 Do you want to activate orphanRemoval on your relationship?
 A Contract is "orphaned" when it is removed from its related PhoneLine.
 e.g. $phoneLine->removeContract($contract)
 
 NOTE: If a Contract may *change* from one PhoneLine to another, answer "no".

 Do you want to automatically delete orphaned Contract objects (orphanRemoval)? (yes/no) [no]:
 > yes

 New property name (press <return> to stop adding fields):
 > 

 Success! 
```

## 5. Gestion de la base de données <a name="gestion-de-la-base-de-données"></a>

### Migrations Doctrine <a name="migrations-doctrine"></a>

Après avoir modifié les entités, vous devez mettre à jour la base de données :

1. Générez une migration :
   ```bash
   php bin/console make:migration
   ```
   Cette commande compare l'état actuel de vos entités avec l'état actuel de la base de données et génère un fichier de migration dans le répertoire `migrations/`.

2. Vérifiez le fichier de migration généré pour vous assurer qu'il contient les modifications attendues.

3. Appliquez la migration :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```


### Requêtes personnalisées <a name="requêtes-personnalisées"></a>

Pour ajouter des requêtes personnalisées, modifiez le repository correspondant dans le répertoire `src/Repository/`.

#### Exemple : Ajout d'une méthode de recherche dans PhoneLineRepository

```php
// src/Repository/PhoneLineRepository.php

// ...

class PhoneLineRepository extends ServiceEntityRepository
{
    // ...
    
    /**
     * Recherche des lignes téléphoniques par critères
     * @param array $criteria Critères de recherche
     * @return PhoneLine[]
     */
    public function searchByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('p');
        
        if (isset($criteria['operator'])) {
            $qb->andWhere('p.operator = :operator')
               ->setParameter('operator', $criteria['operator']);
        }
        
        if (isset($criteria['service'])) {
            $qb->andWhere('p.service LIKE :service')
               ->setParameter('service', '%' . $criteria['service'] . '%');
        }
        
        if (isset($criteria['municipality'])) {
            $qb->leftJoin('p.municipality', 'm')
               ->andWhere('m.id = :municipalityId')
               ->setParameter('municipalityId', $criteria['municipality']);
        }
        
        return $qb->getQuery()->getResult();
    }
}
```

## 6. Modification des contrôleurs <a name="modification-des-contrôleurs"></a>

Les contrôleurs se trouvent dans le répertoire `src/Controller/`. Pour modifier un contrôleur existant ou en créer un nouveau :

### Modification d'un contrôleur existant

1. Ouvrez le fichier du contrôleur (par exemple, `src/Controller/PhoneLineController.php`)
2. Modifiez les méthodes existantes ou ajoutez-en de nouvelles
3. Assurez-vous d'ajouter les annotations de route appropriées

#### Exemple : Ajout d'une méthode de recherche dans un contrôleur

```php
// src/Controller/PhoneLineController.php

// ...

class PhoneLineController extends AbstractController
{
    // ...
    
    #[Route('/phone-lines/search', name: 'phone_line_search', methods: ['GET', 'POST'])]
    public function search(Request $request, PhoneLineRepository $phoneLineRepository): Response
    {
        $criteria = [];
        
        if ($request->isMethod('POST')) {
            $criteria = $request->request->all();
        }
        
        $phoneLines = $phoneLineRepository->searchByCriteria($criteria);
        
        return $this->render('phone_line/search.html.twig', [
            'phone_lines' => $phoneLines,
            'criteria' => $criteria,
        ]);
    }
}
```

### Création d'un nouveau contrôleur

1. Utilisez la commande Symfony :
   ```bash
   php bin/console make:controller
   ```
2. Suivez les instructions pour définir le nom du contrôleur
3. Modifiez le fichier généré selon vos besoins

#### Exemple : Création d'un contrôleur pour la nouvelle entité Contract

```bash
php bin/console make:controller

 Choose a name for your controller class (e.g. GrumpyPuppyController):
 > ContractController

 created: src/Controller/ContractController.php
 created: templates/contract/index.html.twig

 Success! 
```

## 7. Modification des vues <a name="modification-des-vues"></a>

Les templates Twig se trouvent dans le répertoire `templates/`. Pour modifier une vue existante ou en créer une nouvelle :

### Modification d'une vue existante

1. Ouvrez le fichier du template (par exemple, `templates/phone_line/index.html.twig`)
2. Modifiez le contenu HTML et les balises Twig selon vos besoins

#### Exemple : Ajout d'un champ dans un formulaire

```twig
{# templates/phone_line/_form.html.twig #}

{{ form_start(form) }}
    {{ form_row(form.location) }}
    {{ form_row(form.service) }}
    {{ form_row(form.assignedTo) }}
    {{ form_row(form.phoneBrand) }}
    {{ form_row(form.model) }}
    {{ form_row(form.operator) }}
    {{ form_row(form.lineType) }}
    {{ form_row(form.contractNumber) }} {# Nouveau champ ajouté #}
    {{ form_row(form.municipality) }}
    <button class="btn btn-primary">{{ button_label|default('Enregistrer') }}</button>
{{ form_end(form) }}
```

### Création d'une nouvelle vue

1. Créez un nouveau fichier dans le répertoire `templates/` approprié
2. Utilisez l'héritage de template pour étendre le template de base

#### Exemple : Création d'une vue pour la nouvelle entité Contract

```twig
{# templates/contract/index.html.twig #}

{% extends 'base.html.twig' %}

{% block title %}Liste des contrats{% endblock %}

{% block body %}
<div class="page-wrapper">
    <div class="page-container">
        <div class="main-content">
            <div class="section__content section__content--p30">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="overview-wrap">
                                <h2 class="title-1">Liste des contrats</h2>
                                <a href="{{ path('contract_new') }}" class="au-btn au-btn-icon au-btn--blue">
                                    <i class="zmdi zmdi-plus"></i>Ajouter un contrat
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Date de début</th>
                                            <th>Date de fin</th>
                                            <th>Ligne téléphonique</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {% for contract in contracts %}
                                        <tr>
                                            <td>{{ contract.id }}</td>
                                            <td>{{ contract.name }}</td>
                                            <td>{{ contract.startDate|date('d/m/Y') }}</td>
                                            <td>{{ contract.endDate ? contract.endDate|date('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ contract.phoneLine.location }}</td>
                                            <td>
                                                <a href="{{ path('contract_show', {'id': contract.id}) }}" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ path('contract_edit', {'id': contract.id}) }}" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        {% else %}
                                        <tr>
                                            <td colspan="6">Aucun contrat trouvé</td>
                                        </tr>
                                        {% endfor %}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

## 8. Gestion de l'authentification LDAP <a name="gestion-de-lauthentification-ldap"></a>

L'authentification LDAP est configurée dans les fichiers suivants :

- `.env` : Configuration des paramètres LDAP
- `config/packages/ldap.yaml` : Configuration du service LDAP
- `config/packages/security.yaml` : Configuration de la sécurité
- `src/Security/LdapAuthenticator.php` : Authenticator LDAP
- `src/Security/LdapUserProvider.php` : Provider d'utilisateurs LDAP

### Modification de la configuration LDAP

1. Modifiez les paramètres LDAP dans le fichier `.env` ou `.env.local` :
   ```
   LDAP_HOST=ldap.example.com
   LDAP_PORT=389
   LDAP_ENCRYPTION=none
   LDAP_BASE_DN=dc=example,dc=com
   LDAP_SEARCH_DN=cn=admin,dc=example,dc=com
   LDAP_SEARCH_PASSWORD=admin_password
   LDAP_UID_KEY=sAMAccountName
   ```

2. Si nécessaire, modifiez la configuration du service LDAP dans `config/packages/ldap.yaml` :
   ```yaml
   services:
       Symfony\Component\Ldap\Ldap:
           arguments: ['@Symfony\Component\Ldap\Adapter\ExtLdap\Adapter']
       
       Symfony\Component\Ldap\LdapInterface: '@Symfony\Component\Ldap\Ldap'
       
       Symfony\Component\Ldap\Adapter\ExtLdap\Adapter:
           arguments:
               - host: '%env(LDAP_HOST)%'
                 port: '%env(int:LDAP_PORT)%'
                 encryption: '%env(LDAP_ENCRYPTION)%'
                 options:
                     protocol_version: 3
                     referrals: false
   ```

### Modification du comportement d'authentification

Pour modifier le comportement d'authentification, vous pouvez modifier les classes suivantes :

- `src/Security/LdapAuthenticator.php` : Gère le processus d'authentification
- `src/Security/LdapUserProvider.php` : Charge les utilisateurs depuis LDAP

#### Exemple : Modification du mapping des attributs LDAP

```php
// src/Security/LdapUserProvider.php

// ...

public function loadUserByIdentifier(string $identifier): UserInterface
{
    try {
        // Recherche de l'utilisateur dans la base de données locale
        $user = $this->userRepository->findOneBy(['email' => $identifier]);
        
        if (!$user) {
            // Si l'utilisateur n'existe pas en local, on le crée à partir des informations LDAP
            $ldapUser = $this->findLdapUser($identifier);
            
            if (!$ldapUser) {
                throw new UserNotFoundException(sprintf('User "%s" not found in LDAP.', $identifier));
            }
            
            $user = new User();
            $user->setEmail($identifier);
            $user->setLdapUsername($identifier);
            
            // Modification : utilisation de l'attribut 'cn' au lieu de 'displayname'
            $user->setName($this->getLdapUserAttribute($ldapUser, 'cn') ?? $identifier);
            
            // Ajout : récupération du département depuis LDAP
            $department = $this->getLdapUserAttribute($ldapUser, 'department');
            if ($department) {
                $user->setDepartment($department);
            }
            
            $user->setRoles($this->defaultRoles);
            
            // Le mot de passe est géré par LDAP, on met une valeur aléatoire
            $user->setPassword(bin2hex(random_bytes(20)));
            
            $this->userRepository->save($user, true);
        }
        
        return $user;
    } catch (ConnectionException $e) {
        throw new UserNotFoundException(sprintf('User "%s" not found in LDAP: %s', $identifier, $e->getMessage()));
    }
}
```

## 9. Paramétrage de l'application <a name="paramétrage-de-lapplication"></a>

Les paramètres de l'application sont gérés via l'entité `Settings` et le contrôleur `SettingsController`.

### Modification des paramètres disponibles

1. Modifiez l'entité `Settings` pour ajouter de nouveaux paramètres :
   ```php
   // src/Entity/Settings.php
   
   // ...
   
   class Settings
   {
       // ...
       
       #[ORM\Column(type: 'boolean')]
       private bool $enableNotifications = false;
       
       #[ORM\Column(length: 255, nullable: true)]
       private ?string $notificationEmail = null;
       
       // ...
       
       public function isEnableNotifications(): bool
       {
           return $this->enableNotifications;
       }
       
       public function setEnableNotifications(bool $enableNotifications): static
       {
           $this->enableNotifications = $enableNotifications;
           return $this;
       }
       
       public function getNotificationEmail(): ?string
       {
           return $this->notificationEmail;
       }
       
       public function setNotificationEmail(?string $notificationEmail): static
       {
           $this->notificationEmail = $notificationEmail;
           return $this;
       }
   }
   ```

2. Mettez à jour le formulaire de paramètres dans le contrôleur :
   ```php
   // src/Controller/SettingsController.php
   
   // ...
   
   #[Route('/settings/save', name: 'settings_save', methods: ['POST'])]
   public function saveSettings(Request $request, SettingsRepository $settingsRepository): Response
   {
       $settings = $settingsRepository->findOneBy([]) ?? new Settings();
       
       $settings->setCrudEnabled($request->request->getBoolean('crud_enabled'));
       $settings->setDisplayMode($request->request->get('display_mode'));
       $settings->setItemsPerPage((int) $request->request->get('items_per_page'));
       $settings->setAppName($request->request->get('app_name'));
       $settings->setWelcomeMessage($request->request->get('welcome_message'));
       $settings->setAlertThreshold((int) $request->request->get('alert_threshold'));
       $settings->setFeatureEnabled($request->request->getBoolean('feature_enabled'));
       
       // Nouveaux paramètres
       $settings->setEnableNotifications($request->request->getBoolean('enable_notifications'));
       $settings->setNotificationEmail($request->request->get('notification_email'));
       
       $settingsRepository->save($settings, true);
       
       $this->addFlash('success', 'Paramètres enregistrés avec succès.');
       
       return $this->redirectToRoute('settings');
   }
   ```

3. Mettez à jour le template de paramètres :
   ```twig
   {# templates/settings/index.html.twig #}
   
   {# ... #}
   
   <div class="form-group">
       <label for="enable_notifications">Activer les notifications</label>
       <div class="form-check">
           <input type="checkbox" id="enable_notifications" name="enable_notifications" class="form-check-input" value="1" {% if settings.enableNotifications %}checked{% endif %}>
           <label class="form-check-label" for="enable_notifications">Activer</label>
       </div>
   </div>
   
   <div class="form-group">
       <label for="notification_email">Email de notification</label>
       <input type="email" id="notification_email" name="notification_email" class="form-control" value="{{ settings.notificationEmail }}">
   </div>
   
   {# ... #}
   ```

## 10. Déploiement <a name="déploiement"></a>

Pour déployer les modifications sur un environnement de production :

1. Préparez l'environnement de production :
   ```bash
   # Sur le serveur de production
   composer install --no-dev --optimize-autoloader
   ```

2. Configurez les variables d'environnement pour la production dans `.env.local` ou via les variables d'environnement du serveur.

3. Mettez à jour la base de données :
   ```bash
   php bin/console doctrine:migrations:migrate --env=prod
   ```

4. Videz le cache :
   ```bash
   php bin/console cache:clear --env=prod
   ```

5. Si vous utilisez Symfony Encore, compilez les assets pour la production :
   ```bash
   yarn encore production
   # ou
   npm run build
   ```

### Utilisation de Docker

Si vous utilisez Docker pour le déploiement, assurez-vous de mettre à jour le fichier `docker-compose.yaml` et le `Dockerfile` si nécessaire.

#### Exemple : Mise à jour du Dockerfile

```dockerfile
FROM php:8.2-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libldap2-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    ldap

# Configuration d'Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie du code source
WORKDIR /var/www/html
COPY . .

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html/var

# Exposition du port
EXPOSE 80

# Commande par défaut
CMD ["apache2-foreground"]
```

---

## Conclusion

Ce manuel de maintenance fournit les informations nécessaires pour modifier et étendre l'application Agglo34. En suivant ces instructions, vous pourrez ajouter de nouvelles fonctionnalités, modifier les entités existantes et déployer vos modifications en toute sécurité.

Pour toute question ou assistance supplémentaire, veuillez contacter l'équipe de développement.