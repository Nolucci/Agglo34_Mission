FROM php:8.4-fpm-alpine

# Installation des dépendances système
RUN apk update --no-cache && apk add --no-cache \
    libpq-dev \
    openldap-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql ldap

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers de configuration Composer en premier pour optimiser le cache Docker
COPY composer.json composer.lock ./

# Copie du script d'initialisation en premier
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Installation des dépendances PHP
RUN composer install --no-scripts --no-autoloader --optimize-autoloader

# Copie du reste des fichiers de l'application
COPY . .

# Configuration Git pour éviter l'erreur de propriété douteuse
RUN git config --global --add safe.directory /var/www/html

# Finalisation de l'installation Composer
RUN composer dump-autoload --optimize

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposition du port PHP-FPM
EXPOSE 9000

# Commande de démarrage avec script d'initialisation
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]