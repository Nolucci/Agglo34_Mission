#!/bin/bash

echo "Début du déploiement de l'application Agglo34_Mission..."

# 1. Installation de Git et Apache (si non installés)
echo "Vérification et installation des dépendances système (git, apache2, php et ses modules)..."
sudo apt update
sudo apt install -y git apache2 php libapache2-mod-php php-cli php-mysql php-mbstring php-xml php-zip php-gd php-curl php-intl

# Le dépôt est déjà cloné, le script s'exécute depuis la racine du dépôt.
# Assurez-vous que le script est exécuté depuis le répertoire racine du projet cloné.

# 3. Installation des dépendances Composer
echo "Installation des dépendances Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer install --no-interaction --optimize-autoloader

# 4. Configuration d'Apache
echo "Configuration d'Apache..."
sudo chown -R www-data:www-data .
sudo a2enmod rewrite

# Création du fichier de configuration Apache pour l'application
APACHE_CONF="/etc/apache2/sites-available/agglomission.conf"
echo "<VirtualHost *:80>" | sudo tee $APACHE_CONF
echo "    ServerAdmin webmaster@localhost" | sudo tee -a $APACHE_CONF
echo "    DocumentRoot $(pwd)/public" | sudo tee -a $APACHE_CONF
echo "    <Directory $(pwd)/public>" | sudo tee -a $APACHE_CONF
echo "        AllowOverride All" | sudo tee -a $APACHE_CONF
echo "        Require all granted" | sudo tee -a $APACHE_CONF
echo "    </Directory>" | sudo tee -a $APACHE_CONF
echo "    ErrorLog ${APACHE_LOG_DIR}/error.log" | sudo tee -a $APACHE_CONF
echo "    CustomLog ${APACHE_LOG_DIR}/access.log combined" | sudo tee -a $APACHE_CONF
echo "</VirtualHost>" | sudo tee -a $APACHE_CONF

sudo a2ensite agglomission.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2

# 5. Mise en place de la base de données
echo "Mise en place de la base de données..."
php bin/console doctrine:database:create --if-not-exists
php bin/console make:migration --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Ajout de l'admin
echo "Création de l'utilisateur administrateur..."
php bin/console app:create-admin-user

# 7. Terminé
echo "Déploiement terminé !"
echo "L'application est accessible à l'adresse : http://$(hostname -I | awk '{print $1}')"