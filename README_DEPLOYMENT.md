# Guide de Déploiement - Agglo34_Mission

## Prérequis

- Système d'exploitation : Distribution Linux (Ubuntu/Debian recommandé)
- Accès root ou sudo
- Connexion Internet

## Déploiement Automatique

### Étape 1 : Cloner le projet

```bash
git clone <url-du-repository>
cd Agglo34_Mission
```

### Étape 2 : Lancer le déploiement

```bash
sudo ./deploy.sh
```

Le script va automatiquement :

1. **Installer les dépendances système** :
   - Docker et Docker Compose
   - curl et autres outils essentiels

2. **Demander la configuration** :
   - Configuration de la base de données (nom, utilisateur, mot de passe, port)
   - Configuration de PgAdmin (email, mot de passe, port)
   - Configuration du serveur web (port)
   - Configuration LDAP (serveur, port, DN, credentials)
   - Configuration de l'application (environnement)

3. **Créer les conteneurs Docker** :
   - Conteneur PHP/Symfony (sru_app)
   - Conteneur Apache (sru_webserver)
   - Conteneur PostgreSQL (sru_postgresql16)
   - Conteneur PgAdmin (sru_pgadmin)

4. **Configurer l'application** :
   - Installation des dépendances Composer
   - Création de la base de données
   - Mise à jour du schéma de base de données
   - Création de l'utilisateur administrateur
   - Configuration des permissions

## Services Déployés

Après le déploiement, les services suivants seront disponibles :

- **Application Web** : `http://[IP-SERVEUR]:[PORT-WEB]` (défaut: 4080)
- **PgAdmin** : `http://[IP-SERVEUR]:[PORT-PGADMIN]` (défaut: 4081)
- **Base de données PostgreSQL** : `[IP-SERVEUR]:[PORT-DB]` (défaut: 4032)

## Gestion des Conteneurs

### Arrêter l'application
```bash
sudo docker-compose down
```

### Redémarrer l'application
```bash
sudo docker-compose up -d
```

### Voir les logs
```bash
sudo docker-compose logs -f
```

### Voir l'état des conteneurs
```bash
sudo docker-compose ps
```

## Configuration

### Variables d'environnement

Le script crée automatiquement un fichier `.env.local` avec toutes les variables de configuration saisies lors du déploiement.

### Modification de la configuration

Pour modifier la configuration après déploiement :

1. Éditer le fichier `.env.local`
2. Redémarrer les conteneurs :
   ```bash
   sudo docker-compose down
   sudo docker-compose up -d
   ```

## Accès aux Conteneurs

### Accès au conteneur PHP/Symfony
```bash
sudo docker-compose exec app bash
```

### Accès au conteneur de base de données
```bash
sudo docker-compose exec database psql -U [DB_USER] -d [DB_NAME]
```

## Commandes Symfony Utiles

### Exécuter des commandes Symfony dans le conteneur
```bash
sudo docker-compose exec app php bin/console [commande]
```

### Exemples :
```bash
# Vider le cache
sudo docker-compose exec app php bin/console cache:clear

# Créer un nouvel utilisateur admin
sudo docker-compose exec app php bin/console app:create-admin-user

# Voir les routes
sudo docker-compose exec app php bin/console debug:router
```

## Dépannage

### Problèmes de permissions
```bash
sudo docker-compose exec app chown -R www-data:www-data /var/www/html/var
sudo docker-compose exec app chmod -R 775 /var/www/html/var
```

### Reconstruire les conteneurs
```bash
sudo docker-compose down
sudo docker-compose up -d --build
```

### Voir les logs détaillés
```bash
sudo docker-compose logs [nom-du-service]
```

### Vérifier l'état de la base de données
```bash
sudo docker-compose exec database pg_isready -U [DB_USER] -d [DB_NAME]
```

## Sécurité

- Tous les mots de passe sont demandés de manière sécurisée (masqués)
- Les variables sensibles sont stockées dans `.env.local` (non versionné)
- Les conteneurs utilisent des réseaux isolés
- Les permissions sont configurées correctement

## Support

En cas de problème :

1. Vérifier les logs : `sudo docker-compose logs -f`
2. Vérifier l'état des conteneurs : `sudo docker-compose ps`
3. Redémarrer les services : `sudo docker-compose restart`

## Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Apache Web    │    │   PHP/Symfony   │    │   PostgreSQL    │
│    Server       │◄──►│   Application   │◄──►│   Database      │
│  (sru_webserver)│    │   (sru_app)     │    │(sru_postgresql16)│
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                                              ▲
         │              ┌─────────────────┐              │
         └──────────────►│    PgAdmin      │──────────────┘
                        │  (sru_pgadmin)  │
                        └─────────────────┘
```

Tous les services communiquent via un réseau Docker privé (`sru_network`).