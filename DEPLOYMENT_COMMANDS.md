# 🚀 COMMANDES DE DÉPLOIEMENT EN PRODUCTION - AGGLO34 MISSION

## Étapes à exécuter dans l'ordre pour déployer l'application

### 📁 ÉTAPE 0 : PRÉPARATION DE L'ENVIRONNEMENT
```bash
# 🖥️ ENVIRONNEMENT : Terminal système (n'importe quel dossier)
# Créer le dossier de déploiement sur le serveur
mkdir -p /var/www/agglo34-mission
cd /var/www/agglo34-mission

# Cloner ou copier les fichiers du projet dans ce dossier
# git clone [URL_DU_REPO] .
# OU copier tous les fichiers du projet dans /var/www/agglo34-mission/
```

### 1. INSTALLATION DES DÉPENDANCES
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Installation des packages PHP
composer install --no-dev --optimize-autoloader

# Installation des assets JavaScript
php bin/console importmap:install
```

### 2. CONFIGURATION DE L'ENVIRONNEMENT
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Copier le fichier d'environnement
cp .env .env.local

# 📝 ÉDITER LE FICHIER : .env.local (avec nano, vim, ou éditeur de texte)
# Modifier les valeurs suivantes :
# APP_ENV=prod
# APP_SECRET=[générer_une_clé_secrète_32_caractères]
# DATABASE_URL="postgresql://admin:admin@localhost:5432/agglo34_db?serverVersion=16&charset=utf8"
# LDAP_HOST=votre-serveur-ldap.com
# LDAP_BASE_DN=dc=votre-domaine,dc=com
# LDAP_SEARCH_DN=cn=admin,dc=votre-domaine,dc=com
# LDAP_SEARCH_PASSWORD=votre_mot_de_passe_ldap

# Exemple d'édition :
nano .env.local
```

### 3. CRÉATION DE LA BASE DE DONNÉES
```bash
# 🖥️ ENVIRONNEMENT : Terminal système (PostgreSQL doit être installé)
# 📂 DOSSIER : N'importe quel dossier (commandes système PostgreSQL)

# Créer la base de données PostgreSQL
createdb -U postgres agglo34_db

# Créer l'utilisateur de base de données
psql -U postgres -c "CREATE USER admin WITH PASSWORD 'admin';"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE agglo34_db TO admin;"
```

### 4. MIGRATION DE LA BASE DE DONNÉES
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Vérifier que le schéma est correct
php bin/console doctrine:schema:validate
```

### 5. CRÉATION DE L'UTILISATEUR ADMINISTRATEUR
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Créer l'utilisateur admin (remplacer MOT_DE_PASSE par un mot de passe sécurisé)
php bin/console app:create-admin-user MOT_DE_PASSE_ADMIN_SECURISE
```

### 6. CONFIGURATION LDAP (SI UTILISÉ)
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Initialiser les paramètres LDAP
php bin/console app:init-ldap-settings

# Tester la connexion LDAP (optionnel)
php bin/console app:test-ldap nom_utilisateur_test
```

### 7. OPTIMISATION POUR LA PRODUCTION
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Compiler les variables d'environnement
composer dump-env prod

# Vider et optimiser le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Compiler les assets
php bin/console asset-map:compile
```

### 8. PERMISSIONS DES FICHIERS
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal système avec privilèges sudo

# Linux/Mac
sudo chown -R www-data:www-data var/ public/
sudo chmod -R 755 var/ public/

# Windows - Depuis l'explorateur de fichiers ou PowerShell en tant qu'administrateur
# S'assurer que IIS/Apache a les permissions sur var/ et public/
```

### 9. VÉRIFICATIONS FINALES
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Vérifier la configuration
php bin/console about

# Vérifier les routes
php bin/console debug:router

# Tester la base de données
php bin/console doctrine:schema:validate
```

### 10. CONFIGURATION SERVEUR WEB
```bash
# 🖥️ ENVIRONNEMENT : Configuration serveur web

# Pour Apache - Créer/modifier le VirtualHost
# 📝 FICHIER : /etc/apache2/sites-available/agglo34-mission.conf
# DocumentRoot /var/www/agglo34-mission/public

# Pour Nginx - Créer/modifier la configuration
# 📝 FICHIER : /etc/nginx/sites-available/agglo34-mission
# root /var/www/agglo34-mission/public;

# Activer le site (Apache)
sudo a2ensite agglo34-mission.conf
sudo systemctl reload apache2

# Activer le site (Nginx)
sudo ln -s /etc/nginx/sites-available/agglo34-mission /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

---

## 🔐 CONNEXION INITIALE
- URL : `http://votre-domaine.com/login`
- Email : `admin@agglo34.local`
- Mot de passe : `[MOT_DE_PASSE_ADMIN_SECURISE]`

---

## 🛠️ COMMANDES DE MAINTENANCE UTILES

### Mode maintenance
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Activer le mode maintenance
php bin/console app:maintenance on

# Activer avec message personnalisé
php bin/console app:maintenance on "Maintenance en cours - Retour prévu à 14h00"

# Désactiver le mode maintenance
php bin/console app:maintenance off

# Vérifier le statut
php bin/console app:maintenance status
```

### Gestion des utilisateurs LDAP
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Ajouter un utilisateur à la whitelist
php bin/console app:whitelist:add nom_utilisateur email@domain.com "Nom Complet"

# Lister les utilisateurs autorisés
php bin/console app:whitelist:list

# Activer/désactiver un utilisateur
php bin/console app:whitelist:toggle nom_utilisateur

# Supprimer un utilisateur
php bin/console app:whitelist:remove nom_utilisateur
```

### Maintenance de la base de données
```bash
# 🖥️ ENVIRONNEMENT : Terminal système (PostgreSQL)
# 📂 DOSSIER : N'importe quel dossier

# Sauvegarde
pg_dump -U admin -h localhost agglo34_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Optimisation PostgreSQL
psql -U admin -d agglo34_db -c "VACUUM ANALYZE;"
```

### Nettoyage et optimisation
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Nettoyer le cache
php bin/console cache:clear --env=prod

# Nettoyer les logs anciens
find var/log -name "*.log" -mtime +30 -delete

# Optimiser l'autoloader
composer dump-autoload --optimize --no-dev
```

---

## ⚠️ NOTES IMPORTANTES

1. **Sécurité** : Changez tous les mots de passe par défaut
2. **Sauvegarde** : Effectuez une sauvegarde avant chaque mise à jour
3. **Logs** : Surveillez les fichiers de logs dans `var/log/`
4. **HTTPS** : Configurez SSL/TLS pour la production
5. **Firewall** : Limitez l'accès aux ports nécessaires uniquement

---

## 🆘 DÉPANNAGE RAPIDE

### En cas de problème
```bash
# 📂 DOSSIER : /var/www/agglo34-mission/ (racine du projet)
# 🖥️ ENVIRONNEMENT : Terminal dans le dossier du projet

# Réinitialiser le cache complètement
rm -rf var/cache/*
php bin/console cache:warmup --env=prod

# Vérifier les permissions
ls -la var/ public/

# Désactiver le mode maintenance d'urgence
php bin/console app:maintenance off

# Recréer l'utilisateur admin si nécessaire
php bin/console app:create-admin-user NouveauMotDePasse123!
```

### Vérification des logs
```bash
# Voir les erreurs récentes
tail -f var/log/prod.log

# Voir les erreurs PHP
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx

---

## 📍 RÉSUMÉ DES EMPLACEMENTS

### Dossiers de travail :
- **Racine du projet** : `/var/www/agglo34-mission/` (toutes les commandes Symfony)
- **Système** : N'importe quel dossier (commandes PostgreSQL, serveur web)

### Fichiers à éditer :
- **Configuration app** : `/var/www/agglo34-mission/.env.local`
- **Apache VirtualHost** : `/etc/apache2/sites-available/agglo34-mission.conf`
- **Nginx config** : `/etc/nginx/sites-available/agglo34-mission`

### Logs à surveiller :
- **Application** : `/var/www/agglo34-mission/var/log/prod.log`
- **Apache** : `/var/log/apache2/error.log`
- **Nginx** : `/var/log/nginx/error.log`