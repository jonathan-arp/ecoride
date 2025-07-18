# Configuration Email pour le développement

## 🚀 Configuration rapide

1. **Copiez le fichier de configuration :**

   ```bash
   cp .env.local.example .env.local
   ```

2. **Désactiver les emails en développement :**
   ```bash
   # Dans .env.local
   ENABLE_EMAILS=false
   ```

## 📧 Options d'envoi d'emails

### Option 1 : Désactiver les emails (recommandé pour le développement)

```bash
ENABLE_EMAILS=false
MAILER_DSN=null://null
```

### Option 2 : Tester avec MailHog (Docker)

```bash
ENABLE_EMAILS=true
MAILER_DSN=smtp://localhost:1025
```

### Option 3 : Tester avec Mailtrap

```bash
ENABLE_EMAILS=true
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
```

### Option 4 : Production (Ionos)

```bash
ENABLE_EMAILS=true
MAILER_DSN=smtp://votre-email@votre-domaine.com:votre-mot-de-passe@smtp.ionos.fr:587
```

## 🛡️ Protection contre les crashes

Le code est protégé contre les erreurs d'email :

- ✅ Vérification de la configuration `ENABLE_EMAILS`
- ✅ Gestion des erreurs avec `try/catch`
- ✅ Logs d'erreur sans interruption du processus
- ✅ L'application continue de fonctionner même si l'email échoue

## 📝 Emails envoyés par l'application

1. **Annulation de covoiturage** : Notification aux participants
2. **Validation de trajet** : Demande de validation aux passagers
3. **Autres notifications** : Selon les fonctionnalités

## 🔧 Dépannage

### Problème : "Could not send email"

**Solution :** Vérifiez votre configuration MAILER_DSN ou désactivez les emails.

### Problème : "ENABLE_EMAILS not found"

**Solution :** Ajoutez `ENABLE_EMAILS=false` dans votre `.env.local`.

### Problème : Emails non reçus

**Solution :** Vérifiez vos logs et votre configuration SMTP.
