#!/bin/bash
set -e

echo "🚀 Démarrage de l'initialisation de l'application Agglo34..."

# Attendre que la base de données soit disponible
echo "⏳ Attente de la disponibilité de la base de données..."
until pg_isready -h database -p 5432 -U admin -d agglo34_db; do
    echo "Base de données non disponible, nouvelle tentative dans 5 secondes..."
    sleep 5
done
echo "✅ Base de données disponible !"

# Vérifier si les migrations ont déjà été exécutées
if [ ! -f "/var/www/html/var/.migrations_done" ]; then
    echo "🔧 Exécution des migrations de base de données..."
    php bin/console doctrine:migrations:migrate --no-interaction

    echo "📊 Chargement des fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction

    # Marquer les migrations comme terminées
    touch /var/www/html/var/.migrations_done
    echo "✅ Migrations et fixtures terminées !"
else
    echo "ℹ️ Migrations déjà exécutées, passage à l'étape suivante..."
fi

# Vérifier si l'utilisateur admin existe déjà
ADMIN_EXISTS=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user WHERE email = 'admin@agglo34.local'" 2>/dev/null | tail -n 1 || echo "0")

if [ "$ADMIN_EXISTS" = "0" ]; then
    echo "👤 Création de l'utilisateur administrateur par défaut..."
    php bin/console app:create-admin-user admin admin@agglo34.local admin123 --no-interaction
    echo "✅ Utilisateur administrateur créé !"
else
    echo "ℹ️ Utilisateur administrateur déjà existant..."
fi

# Vider le cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --env=dev --no-debug

# Configurer les permissions
echo "🔐 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

# Configurer les permissions pour .env.local
chown www-data:www-data /var/www/html/.env.local
chmod 664 /var/www/html/.env.local

echo "🎉 Initialisation terminée avec succès !"

# Exécuter la commande passée en paramètre (php-fpm par défaut)
exec "$@"