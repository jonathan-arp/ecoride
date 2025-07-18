# Configuration des environnements - EcoRide

## 📁 Organisation des fichiers d'environnement

Symfony charge automatiquement les fichiers dans cet ordre :

1. **`.env`** - Valeurs par défaut (commitées sur GitHub)
2. **`.env.dev`** - Configuration développement (commitée sur GitHub)
3. **`.env.prod`** - Configuration production (commitée sur GitHub)
4. **`.env.local`** - Overrides locaux (non commitées - dans .gitignore)
5. **`.env.dev.local`** - Overrides développement locaux (non commitées)
6. **`.env.prod.local`** - Overrides production locaux (non commitées)

## 🚀 Configuration rapide pour développeur

### Option 1 : Utilisation directe (recommandée)

Les fichiers `.env` et `.env.dev` sont déjà configurés avec des valeurs sûres.

### Option 2 : Personnalisation locale

```bash
# Créer un fichier local (non commité)
cp .env.local.example .env.local

# Modifier selon vos besoins
# DATABASE_URL="mysql://votre_user:votre_pass@127.0.0.1:3306/votre_db"
# ENABLE_EMAILS=true
```

## 📧 Configuration des emails

### En développement (par défaut)

```bash
ENABLE_EMAILS=false
MAILER_DSN=null://null
```

### Pour tester les emails

```bash
# Dans .env.local
ENABLE_EMAILS=true
MAILER_DSN=smtp://localhost:1025  # MailHog
# ou
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525  # Mailtrap
```

## 🔧 Comment Symfony sait quel fichier utiliser

### Variables d'environnement

- `APP_ENV=dev` → Symfony charge `.env.dev`
- `APP_ENV=prod` → Symfony charge `.env.prod`
- `APP_ENV=test` → Symfony charge `.env.test`

### Ordre de priorité

```
.env (base)
↓
.env.dev (si APP_ENV=dev)
↓
.env.local (vos overrides)
↓
.env.dev.local (vos overrides dev)
```

## 🛡️ Sécurité

### Fichiers committes sur GitHub

- ✅ `.env` (valeurs par défaut sûres)
- ✅ `.env.dev` (configuration développement)
- ✅ `.env.prod` (configuration production SANS secrets)

### Fichiers locaux (non committes)

- ❌ `.env.local` (vos configurations personnelles)
- ❌ `.env.dev.local` (vos overrides développement)
- ❌ `.env.prod.local` (vrais secrets production)

## 📋 Exemple d'utilisation

### Développeur nouveau sur le projet

```bash
git clone https://github.com/jonathan-arp/ecoride.git
cd ecoride
composer install
# Rien d'autre à faire ! APP_ENV=dev charge automatiquement .env.dev
```

### Déploiement production

```bash
# Créer .env.prod.local avec les vrais secrets
echo "DATABASE_URL=mysql://real_user:real_pass@127.0.0.1:3306/ecoride" > .env.prod.local
echo "MAILER_DSN=smtp://real_email:real_pass@smtp.ionos.fr:587" >> .env.prod.local
export APP_ENV=prod
```

## 🔍 Vérification

Pour voir quelle configuration est chargée :

```bash
php bin/console debug:config
php bin/console debug:dotenv
```
