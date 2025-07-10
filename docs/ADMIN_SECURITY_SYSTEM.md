# Nouveau Système de Sécurité - Agglo34 Mission

## Vue d'ensemble

Le système de sécurité a été entièrement refondu pour éliminer les vulnérabilités liées au système de "premier utilisateur" et de whitelist. Le nouveau système utilise un compte administrateur par défaut sécurisé.

## Changements principaux

### Ancien système (SUPPRIMÉ)
- ❌ Le premier utilisateur à se connecter obtenait automatiquement les droits admin
- ❌ Système de whitelist géré par le premier utilisateur
- ❌ Vulnérabilité de sécurité majeure

### Nouveau système (ACTUEL)
- ✅ Compte administrateur par défaut créé lors de l'installation
- ✅ Authentification sécurisée par mot de passe
- ✅ Mode maintenance avec accès admin uniquement
- ✅ Pas de droits automatiques accordés

## Compte Administrateur par Défaut

### Identifiants
- **Email**: `admin@beziers-mediterranee.fr`
- **Nom d'utilisateur LDAP**: `admin`
- **Nom**: `Administrateur`
- **Rôles**: `ROLE_ADMIN`

### Création du compte
```bash
# Créer l'utilisateur admin avec un mot de passe sécurisé
docker exec sru_app php bin/console app:create-admin-user VotreMotDePasseSecurise
```

## Mode Maintenance

### Activation
Le mode maintenance peut être activé depuis les paramètres de l'application. Quand il est activé :
- Seul l'utilisateur admin peut se connecter
- Tous les autres utilisateurs sont redirigés vers la page de connexion
- Un message de maintenance est affiché

### Utilisation
1. Activez le mode maintenance dans les paramètres
2. Connectez-vous avec le compte admin (`admin@beziers-mediterranee.fr`)
3. Effectuez les opérations de maintenance nécessaires
4. Désactivez le mode maintenance

## Modes d'Authentification

### 1. Mode LDAP Activé
- Les utilisateurs se connectent avec leurs identifiants LDAP
- Pas de mot de passe requis (géré par LDAP)
- Rôles par défaut assignés aux nouveaux utilisateurs

### 2. Mode LDAP Désactivé
- Seul le compte admin peut se connecter
- Authentification par email/mot de passe
- Idéal pour les environnements de test ou maintenance

### 3. Mode Maintenance
- Seul le compte admin peut se connecter
- Fonctionne indépendamment du statut LDAP
- Message de maintenance affiché aux autres utilisateurs

## Sécurité

### Bonnes pratiques
1. **Mot de passe fort** : Utilisez un mot de passe complexe pour le compte admin
2. **Accès restreint** : Ne partagez pas les identifiants admin
3. **Mode maintenance** : Utilisez-le pour les opérations sensibles
4. **Surveillance** : Surveillez les connexions admin dans les logs

### Protection contre les vulnérabilités
- ✅ Pas de droits automatiques accordés
- ✅ Authentification obligatoire pour tous les accès
- ✅ Isolation du compte admin
- ✅ Contrôle d'accès en mode maintenance

## Migration depuis l'ancien système

### Étapes effectuées automatiquement
1. Suppression du champ `isFirstUser` de la table `user`
2. Suppression de la table `whitelist`
3. Suppression des contrôleurs et services liés à la whitelist
4. Mise à jour des providers de sécurité

### Actions manuelles requises
1. Créer le compte admin avec la commande fournie
2. Informer les utilisateurs du nouveau système
3. Configurer les rôles des utilisateurs existants si nécessaire

## Commandes utiles

```bash
# Créer l'utilisateur admin
docker exec sru_app php bin/console app:create-admin-user [mot-de-passe]

# Vider le cache après modifications
docker exec sru_app php bin/console cache:clear

# Vérifier les migrations
docker exec sru_app php bin/console doctrine:migrations:status
```

## Dépannage

### Problème : Impossible de se connecter
1. Vérifiez que l'utilisateur admin a été créé
2. Vérifiez le mot de passe
3. Vérifiez les logs d'authentification

### Problème : Mode maintenance ne fonctionne pas
1. Vérifiez que le MaintenanceListener est actif
2. Vérifiez la configuration de sécurité
3. Videz le cache

### Problème : Erreurs LDAP
1. Vérifiez la configuration LDAP dans les paramètres
2. Testez la connexion LDAP
3. Vérifiez les logs d'erreur

## Support

Pour toute question ou problème, consultez :
1. Les logs de l'application (`var/log/`)
2. Les logs du serveur web
3. La documentation Symfony Security