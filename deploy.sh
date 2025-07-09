#!/bin/bash


echo "Début du déploiement de l'application Agglo34_Mission..."

# Vérification et installation des outils essentiels (sudo, curl)
echo "Vérification et installation des outils essentiels (sudo, curl)..."
if ! command -v sudo &> /dev/null
then
    echo "sudo n'est pas installé. Tentative d'installation..."
    apt update && apt install -y sudo
fi

if ! command -v curl &> /dev/null
then
    echo "curl n'est pas installé. Tentative d'installation..."
    sudo apt update && sudo apt install -y curl
fi

# 1. Construction et démarrage des conteneurs Docker
echo "Construction et démarrage des conteneurs Docker..."
sudo docker-compose up -d --build

# Le dépôt est déjà cloné, le script s'exécute depuis la racine du dépôt.
# Assurez-vous que le script est exécuté depuis le répertoire racine du projet cloné.

# 2. Installation des dépendances Composer dans le conteneur
echo "Installation des dépendances Composer dans le conteneur sru_app..."
sudo docker exec -w /var/www/html sru_app composer install --no-interaction --optimize-autoloader

# 3. Mise en place de la base de données dans le conteneur
echo "Mise en place de la base de données dans le conteneur sru_app..."
sudo docker exec -w /var/www/html sru_app php bin/console doctrine:database:create --if-not-exists
sudo docker exec -w /var/www/html sru_app php bin/console make:migration --no-interaction
sudo docker exec -w /var/www/html sru_app php bin/console doctrine:migrations:migrate --no-interaction

# 4. Ajout de l'admin dans le conteneur
echo "Création de l'utilisateur administrateur dans le conteneur sru_app..."
sudo docker exec -it -w /var/www/html sru_app php bin/console app:create-admin-user

# 7. Terminé
echo "Déploiement terminé !"
echo "L'application est accessible à l'adresse : http://$(hostname -I | awk '{print $1}')"