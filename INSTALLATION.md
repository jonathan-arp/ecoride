# Installation du projet EcoRide

## Prérequis

Avant d'installer le projet, assurez-vous d'avoir les éléments suivants installés sur votre machine :

### 1. Serveur local

- **Laragon** (recommandé pour Windows) ou **XAMPP/WAMP**
- **PHP >= 8.2**
- **MySQL 8.0+** ou **MariaDB 10.11+**
- **Apache** ou **Nginx**

### 2. Outils de développement

- **Composer** (gestionnaire de dépendances PHP)
- **Node.js >= 18** (pour la compilation des assets)
- **npm** ou **yarn** (gestionnaire de paquets JavaScript)
- **Git** (optionnel, pour le versioning)

## Installation pas à pas

### Étape 1 : Téléchargement du projet

```bash
# Si vous avez Git
git clone [URL_DU_DEPOT] ecoride
cd ecoride

# Ou téléchargez et décompressez le projet dans votre dossier www
```

### Étape 2 : Installation des dépendances PHP

```bash
# Dans le dossier racine du projet
composer install
```

### Étape 3 : Installation des dépendances JavaScript

```bash
# Installation des packages Node.js
npm install

# Ou avec Yarn
yarn install
```

### Étape 4 : Configuration de l'environnement

1. **Copiez le fichier d'environnement :**

```bash
cp .env .env.local
```

2. **Modifiez le fichier `.env.local` avec vos paramètres :**

```
# Configuration de la base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/ecoride?serverVersion=8.0.32&charset=utf8mb4"

# Remplacez par vos paramètres :
# - root : votre nom d'utilisateur MySQL
# - @ : votre mot de passe MySQL (vide par défaut avec Laragon)
# - 127.0.0.1:3306 : host et port de votre serveur MySQL
# - ecoride : nom de la base de données

# Configuration du mailer (optionnel pour le développement)
MAILER_DSN=null://null
```

### Étape 5 : Création de la base de données

1. **Créez la base de données :**

```bash
php bin/console doctrine:database:create
```

2. **Exécutez les migrations :**

```bash
php bin/console doctrine:migrations:migrate
```

### Étape 6 : Compilation des assets

1. **Compilation pour le développement :**

```bash
npm run dev
```

2. **Ou compilation en mode surveillance (recommandé pour le développement) :**

```bash
npm run watch
```

3. **Pour la production :**

```bash
npm run build
```

### Étape 7 : Création d'un utilisateur administrateur

```bash
php bin/console app:create-admin-user
```

Suivez les instructions pour créer votre compte administrateur.

## Lancement du projet

### Option 1 : Serveur Symfony (recommandé pour le développement)

```bash
symfony serve
# ou
php -S localhost:8000 -t public/
```

### Option 2 : Avec Laragon

1. Démarrez Laragon
2. Accédez à `http://ecoride.test` (le domaine virtuel sera créé automatiquement)

### Option 3 : Avec Apache/Nginx

Configurez un virtual host pointant vers le dossier `public/` du projet.

## Accès à l'application

- **Frontend :** `http://localhost:8000` ou `http://ecoride.test`
- **Administration :** `http://localhost:8000/admin`

## Fonctionnalités principales

### Pour les utilisateurs

- ✅ Inscription et connexion
- ✅ Création et gestion de profil
- ✅ Upload de photos de profil
- ✅ Gestion des véhicules
- ✅ Publication de trajets en covoiturage
- ✅ Recherche et réservation de trajets
- ✅ Système de crédits et paiements
- ✅ Système d'évaluation et commentaires
- ✅ Validation des trajets par conducteur et passager

### Interface d'administration

- ✅ Gestion des utilisateurs et employés
- ✅ Modération des avis et commentaires
- ✅ Statistiques de la plateforme
- ✅ Graphiques de revenus et d'activité
- ✅ Suspension/activation des comptes

## Structure des données

Le système utilise les entités principales suivantes :

- **User** : Utilisateurs de la plateforme
- **Car** : Véhicules des conducteurs
- **Carshare** : Trajets proposés
- **Reservation** : Réservations des passagers
- **Review** : Évaluations et commentaires
- **Credit** : Système de crédits
- **PlatformTransaction** : Transactions financières
- **PlatformStats** : Statistiques de la plateforme

## Développement

### Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Mettre à jour la base de données
php bin/console doctrine:schema:update --force

# Créer une nouvelle migration
php bin/console make:migration

# Lancer les tests
php bin/phpunit

# Mode surveillance des assets
npm run watch

# Linter PHP (si configuré)
php bin/console lint:twig templates/
```

### Variables d'environnement importantes

```
APP_ENV=dev                    # Environnement (dev/prod)
APP_SECRET=votre_secret_key    # Clé secrète de l'application
DATABASE_URL=...               # URL de connexion à la base de données
MAILER_DSN=...                # Configuration email
```

## Dépannage

### Problèmes courants

1. **Erreur de permissions :**

```bash
# Sur Linux/Mac
chmod -R 755 var/
chmod -R 755 public/uploads/
```

2. **Erreur de base de données :**

- Vérifiez que MySQL est démarré
- Vérifiez les paramètres dans `.env.local`
- Recréez la base : `php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create`

3. **Assets non compilés :**

```bash
npm run dev
# ou
npm run build
```

4. **Sessions qui se déconnectent :**

- Le projet utilise un système de session basé sur les fichiers
- Vérifiez les permissions du dossier `var/sessions/`

## Support

Pour toute question ou problème d'installation, consultez :

- La documentation Symfony : https://symfony.com/doc
- La documentation Doctrine : https://www.doctrine-project.org/
- Les logs d'erreur dans `var/log/`

---

**Version du projet :** Symfony 7.1
**Date de mise à jour :** Juillet 2025
