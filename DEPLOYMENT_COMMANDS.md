# Commandes de Déploiement - Agglo34_Mission

## Prérequis

### 1. Installation de Docker et Docker Compose

**Sur Ubuntu/Debian :**
```bash
# Mise à jour du système
sudo apt update && sudo apt upgrade -y

# Installation de Docker
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# Installation de Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER
```

**Sur CentOS/RHEL/Fedora :**
```bash
# Installation de Docker
sudo dnf install -y dnf-plugins-core
sudo dnf config-manager --add-repo https://download.docker.com/linux/fedora/docker-ce.repo
sudo dnf install -y docker-ce docker-ce-cli containerd.io

# Installation de Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Démarrer et activer Docker
sudo systemctl start docker
sudo systemctl enable docker

# Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER
```

### 2. Redémarrage de session
```bash
# Redémarrer la session pour appliquer les changements de groupe
newgrp docker
# OU se déconnecter/reconnecter
```

## Déploiement de l'Application

### 1. Cloner le projet (si pas déjà fait)
```bash
git clone <URL_DU_REPO>
cd Agglo34_Mission
```

### 2. Vérifier les fichiers de configuration
```bash
# Vérifier que les fichiers existent
ls -la docker-compose.yaml Dockerfile .env
```

### 3. Construire et démarrer les conteneurs
```bash
# Construire les images et démarrer tous les services
docker-compose up -d --build

# Vérifier que tous les conteneurs sont démarrés
docker-compose ps
```

### 4. Attendre que la base de données soit prête
```bash
# Vérifier que PostgreSQL est prêt
docker-compose exec database pg_isready -U admin -d agglo34_db

# Si la commande échoue, attendre quelques secondes et réessayer
# Répéter jusqu'à ce que la base soit prête
```

### 5. Installation des dépendances Composer
```bash
# Installer les dépendances PHP
docker-compose exec app composer install --no-dev --optimize-autoloader
```

### 6. Configuration de l'application Symfony

```bash
# Exécuter les migrations de base de données
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Charger les fixtures (données de test)
docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction

# Vider le cache
docker-compose exec app php bin/console cache:clear --env=prod
```

### 7. Créer un utilisateur administrateur
```bash
# Créer un utilisateur admin interactivement
docker-compose exec app php bin/console app:create-admin-user

# OU avec des paramètres directs
docker-compose exec app php bin/console app:create-admin-user admin admin@agglo34.local motdepasse
```

### 8. Configurer les permissions
```bash
# Ajuster les permissions des fichiers
docker-compose exec app chown -R www-data:www-data /var/www/html/var
docker-compose exec app chmod -R 775 /var/www/html/var
```

## Vérification du Déploiement

### 1. Vérifier les services
```bash
# Voir l'état de tous les conteneurs
docker-compose ps

# Voir les logs en cas de problème
docker-compose logs app
docker-compose logs database
docker-compose logs webserver
docker-compose logs pgadmin
```

### 2. Tester l'accès aux services
```bash
# Tester l'application web
curl -I http://localhost:4080

# Tester la base de données
docker-compose exec database psql -U admin -d agglo34_db -c "SELECT version();"
```

## Accès aux Services

- **Application Web** : http://localhost:4080
- **PgAdmin** : http://localhost:4081
  - Email : admin@agglo34.local
  - Mot de passe : admin123
- **Base de données PostgreSQL** : localhost:4032
  - Utilisateur : admin
  - Mot de passe : admin123
  - Base de données : agglo34_db

## Commandes de Maintenance

### Arrêter l'application
```bash
docker-compose down
```

### Redémarrer l'application
```bash
docker-compose restart
```

### Voir les logs en temps réel
```bash
docker-compose logs -f
```

### Sauvegarder la base de données
```bash
docker-compose exec database pg_dump -U admin agglo34_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurer la base de données
```bash
docker-compose exec -T database psql -U admin agglo34_db < backup_file.sql
```

### Mettre à jour l'application
```bash
# Arrêter les services
docker-compose down

# Récupérer les dernières modifications
git pull

# Reconstruire et redémarrer
docker-compose up -d --build

# Exécuter les migrations si nécessaire
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Vider le cache
docker-compose exec app php bin/console cache:clear --env=prod
```

## Dépannage

### Problèmes courants

1. **Port déjà utilisé** :
   ```bash
   # Vérifier les ports utilisés
   sudo netstat -tulpn | grep :4080

   # Modifier les ports dans docker-compose.yaml si nécessaire
   ```

2. **Permissions insuffisantes** :
   ```bash
   # Vérifier que l'utilisateur est dans le groupe docker
   groups $USER

   # Si pas dans le groupe, l'ajouter et redémarrer la session
   sudo usermod -aG docker $USER
   newgrp docker
   ```

3. **Conteneur qui ne démarre pas** :
   ```bash
   # Voir les logs détaillés
   docker-compose logs [nom_du_service]

   # Reconstruire l'image
   docker-compose build --no-cache [nom_du_service]
   ```

4. **Base de données non accessible** :
   ```bash
   # Vérifier que le conteneur PostgreSQL est démarré
   docker-compose ps database

   # Vérifier les logs de la base
   docker-compose logs database

   # Tester la connexion
   docker-compose exec database pg_isready -U admin -d agglo34_db
   ```

## Commandes de Déploiement Complètes (Copier-Coller)

```bash
# 1. Installation Docker (Ubuntu/Debian)
sudo apt update && sudo apt upgrade -y
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
sudo usermod -aG docker $USER
newgrp docker

# 2. Déploiement de l'application
cd /chemin/vers/Agglo34_Mission
docker-compose up -d --build
docker-compose exec app composer install --no-dev --optimize-autoloader
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction
docker-compose exec app php bin/console cache:clear --env=prod
docker-compose exec app php bin/console app:create-admin-user
docker-compose exec app chown -R www-data:www-data /var/www/html/var
docker-compose exec app chmod -R 775 /var/www/html/var

# 3. Vérification
docker-compose ps
curl -I http://localhost:4080