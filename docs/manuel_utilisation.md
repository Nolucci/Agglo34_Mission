# Manuel d'Utilisation - Application de Gestion Agglo34

## Table des matières

1. [Introduction](#introduction)
2. [Connexion à l'application](#connexion-à-lapplication)
3. [Tableau de bord](#tableau-de-bord)
4. [Gestion des lignes téléphoniques](#gestion-des-lignes-téléphoniques)
5. [Gestion du parc informatique](#gestion-du-parc-informatique)
6. [Gestion des boxs](#gestion-des-boxs)
7. [Gestion des archives](#gestion-des-archives)
8. [Gestion des agents](#gestion-des-agents)
9. [Calendrier](#calendrier)
10. [Gestion des documents](#gestion-des-documents)
11. [Carte](#carte)
12. [Paramètres administrateur](#paramètres-administrateur)
13. [Déconnexion](#déconnexion)

## 1. Introduction <a name="introduction"></a>

L'application de gestion Agglo34 est un outil de gestion interne destiné à l'administration de l'agglomération. Elle permet de gérer les lignes téléphoniques, le parc informatique, les archives, les agents, et d'autres ressources de l'agglomération.

Cette application est accessible uniquement aux utilisateurs authentifiés via le système LDAP de l'organisation.

## 2. Connexion à l'application <a name="connexion-à-lapplication"></a>

### Accès à la page de connexion

Pour accéder à l'application, ouvrez votre navigateur web et saisissez l'URL de l'application. Vous serez automatiquement redirigé vers la page de connexion si vous n'êtes pas déjà authentifié.

### Authentification

Sur la page de connexion :
1. Saisissez votre nom d'utilisateur LDAP (généralement votre adresse email professionnelle)
2. Saisissez votre mot de passe LDAP
3. Cliquez sur le bouton "Se connecter"

Si vous souhaitez rester connecté, vous pouvez cocher la case "Se souvenir de moi" avant de vous connecter.

### Erreurs de connexion

En cas d'échec de l'authentification, un message d'erreur s'affichera. Vérifiez que :
- Votre nom d'utilisateur est correct
- Votre mot de passe est correct
- Votre compte LDAP est actif
- Le serveur LDAP est accessible

Si le problème persiste, contactez votre administrateur système.

## 3. Tableau de bord <a name="tableau-de-bord"></a>

Après une connexion réussie, vous serez redirigé vers le tableau de bord principal de l'application.

### Vue d'ensemble

Le tableau de bord présente une vue d'ensemble des principales informations de l'agglomération :

- **Carte de l'Agglomération** : Affiche une carte interactive des communes de l'agglomération.
- **Statistiques des Lignes** : Présente des statistiques sur les lignes téléphoniques :
  - Nombre total de lignes
  - Nombre de services uniques
  - Nombre d'opérateurs
  - Nombre de lignes globales
- **Statistiques du Matériel** : Présente des statistiques sur le parc informatique :
  - Nombre total d'équipements
  - Nombre de services uniques
  - Nombre de communes
  - Nombre d'équipements actifs

### Navigation

Le menu de navigation principal se trouve sur le côté gauche de l'écran et permet d'accéder aux différentes fonctionnalités de l'application.

## 4. Gestion des lignes téléphoniques <a name="gestion-des-lignes-téléphoniques"></a>

La section "Lignes téléphoniques" permet de gérer l'ensemble des lignes téléphoniques de l'agglomération.

### Accès à la gestion des lignes

Cliquez sur "Lignes téléphoniques" dans le menu de navigation pour accéder à cette section.

### Vue d'ensemble des lignes

La page principale affiche :
- Un tableau récapitulatif des statistiques des lignes
- Des graphiques de répartition des lignes par commune et par opérateur
- La liste complète des lignes téléphoniques

### Recherche et filtrage des lignes

L'application offre deux méthodes pour trouver rapidement des informations dans la liste des lignes téléphoniques :

1. **Recherche par attribut** : Un champ de recherche en haut du tableau vous permet de rechercher instantanément dans tous les attributs des lignes téléphoniques. Cette recherche est exécutée en temps réel sur la base de données PostgreSQL et filtre les résultats à mesure que vous tapez.

2. **Filtrage avancé** : En cliquant sur le bouton "Filtres", vous ouvrez une fenêtre modale permettant de filtrer les lignes selon des critères précis :
   - Commune
   - Service
   - Opérateur
   - Type de ligne
   - Statut (Global/Local)

Ces fonctionnalités de recherche et de filtrage utilisent des requêtes SQL optimisées sur la base de données PostgreSQL, ce qui permet d'obtenir des résultats rapides même avec un grand volume de données.

### Détails d'une ligne

Pour voir les détails d'une ligne, cliquez sur la ligne correspondante dans le tableau. Une fenêtre modale s'ouvrira avec les informations détaillées :
- Lieu (emplacement)
- Service
- Attribution (personne assignée)
- Marque du téléphone
- Modèle
- Opérateur
- Type de ligne
- Commune

## 5. Gestion du parc informatique <a name="gestion-du-parc-informatique"></a>

La section "Parc Informatique" permet de gérer l'ensemble des équipements informatiques de l'agglomération.

### Accès au parc informatique

Cliquez sur "Parc Informatique" dans le menu de navigation pour accéder à cette section.

### Vue d'ensemble du parc

La page principale affiche :
- Un tableau récapitulatif des statistiques du parc
- Des graphiques de répartition des équipements par type et par statut
- La liste complète des équipements

### Recherche et filtrage des équipements

Comme pour les lignes téléphoniques, l'application offre des fonctionnalités avancées pour rechercher et filtrer les équipements du parc informatique :

1. **Recherche globale** : Un champ de recherche vous permet de trouver rapidement des équipements en recherchant dans tous leurs attributs. Cette recherche utilise les capacités de recherche texte intégral de PostgreSQL pour des résultats rapides et pertinents.

2. **Filtrage avancé** : En cliquant sur le bouton "Filtres", vous ouvrez une fenêtre modale permettant de filtrer les équipements selon des critères précis :
   - Type d'équipement
   - Statut
   - Service
   - Commune
   - Date d'acquisition
   - État

Ces fonctionnalités s'appuient sur des requêtes SQL optimisées pour PostgreSQL, garantissant des performances élevées même avec un grand nombre d'équipements.

### Détails d'un équipement

Pour voir les détails d'un équipement, cliquez sur l'équipement correspondant dans le tableau. Une fenêtre modale s'ouvrira avec les informations détaillées.

## 6. Gestion des boxs <a name="gestion-des-boxs"></a>

La section "boxs" permet de gérer les boxs associées aux communes.

### Liste des boxs

Pour accéder à la liste des boxs, cliquez sur "boxs" dans le menu de navigation.

La page affiche la liste de toutes les boxs avec leur nom et description.

### Détails d'une box

Pour accéder aux détails d'une box spécifique, cliquez sur son identifiant ou son nom dans la liste.

La page de détails affiche :
- Les informations de la box
- Les lignes téléphoniques associées à la commune de la box

## 7. Gestion des archives <a name="gestion-des-archives"></a>

La section "Archives" permet de consulter et gérer les archives de l'agglomération.

### Accès aux archives

Cliquez sur "Archives" dans le menu de navigation pour accéder à cette section.

### Liste des archives

La page affiche la liste de toutes les archives disponibles.

## 8. Gestion des agents <a name="gestion-des-agents"></a>

La section "Agents" permet de gérer les utilisateurs de l'application.

### Accès à la liste des agents

Cliquez sur "Agents" dans le menu de navigation pour accéder à cette section.

### Liste des agents

La page affiche la liste de tous les agents avec leurs informations principales :
- Nom
- Email
- Rôles

## 9. Calendrier <a name="calendrier"></a>

La section "Calendrier" permet de visualiser et gérer les événements de l'agglomération.

### Accès au calendrier

Cliquez sur "Calendrier" dans le menu de navigation pour accéder à cette section.

### Visualisation du calendrier

Le calendrier affiche les événements organisés par jour, semaine ou mois selon la vue sélectionnée.

## 10. Gestion des documents <a name="gestion-des-documents"></a>

La section "Documents" permet d'importer et de gérer des fichiers.

### Accès aux documents

Cliquez sur "Documents" dans le menu de navigation pour accéder à cette section.

### Importation de fichiers

La page permet d'importer des fichiers en les faisant glisser dans la zone prévue à cet effet ou en cliquant sur le bouton "Parcourir". Les fichiers importés sont stockés dans la base de données PostgreSQL, ce qui permet une gestion efficace et des recherches rapides.

## 11. Carte <a name="carte"></a>

La section "Carte" permet de visualiser une carte interactive de l'agglomération.

### Accès à la carte

Cliquez sur "Carte" dans le menu de navigation pour accéder à cette section.

### Utilisation de la carte

La carte affiche les différentes communes de l'agglomération. Vous pouvez :
- Zoomer/dézoomer avec la molette de la souris
- Déplacer la carte en maintenant le clic gauche et en déplaçant la souris
- Cliquer sur une commune pour afficher ses informations

## 12. Paramètres administrateur <a name="paramètres-administrateur"></a>

La section "Paramètres" permet aux administrateurs de configurer l'application.

### Accès aux paramètres

Cliquez sur "Paramètres" dans le menu de navigation pour accéder à cette section. Cette option n'est visible que pour les utilisateurs ayant les droits d'administration.

### Configuration de l'application

La page permet de configurer différents aspects de l'application :
- Activation/désactivation des fonctionnalités CRUD
- Mode d'affichage
- Nombre d'éléments par page
- Nom de l'application
- Message de bienvenue
- Seuil d'alerte
- Activation/désactivation de fonctionnalités spécifiques

### Enregistrement des paramètres

Après avoir modifié les paramètres, cliquez sur le bouton "Enregistrer" pour appliquer les changements.

## 13. Déconnexion <a name="déconnexion"></a>

Pour vous déconnecter de l'application, cliquez sur votre nom d'utilisateur en haut à droite de l'écran, puis sur "Déconnexion" dans le menu déroulant.

Vous serez redirigé vers la page de connexion et votre session sera terminée.
