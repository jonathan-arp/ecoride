# Configuration des Sessions en Base de Données

## ✅ Mise en place terminée

Les sessions sont maintenant stockées en base de données au lieu du système de fichiers par défaut.

## 🚀 Avantages du stockage des sessions en BDD

### 1. **Stabilité renforcée**

- **Résistance aux accès concurrents** : La base de données gère automatiquement les accès simultanés avec les transactions ACID
- **Pas de corruption de fichiers** : Élimination des problèmes de verrouillage de fichiers lors de navigation rapide
- **Atomicité des opérations** : Chaque lecture/écriture de session est atomique

### 2. **Performances améliorées**

- **Clustering support** : Partage de sessions entre plusieurs serveurs web
- **Indexation optimisée** : Recherche rapide des sessions par ID avec index de base de données
- **Nettoyage automatique** : Suppression efficace des sessions expirées

### 3. **Monitoring et debugging**

- **Visibilité complète** : Possibilité d'inspecter les sessions actives
- **Statistiques** : Comptage des sessions actives/expirées
- **Troubleshooting** : Diagnostic facilité en cas de problèmes

### 4. **Sécurité renforcée**

- **Contrôle d'accès** : Permissions de base de données
- **Audit trail** : Traçabilité des accès aux sessions
- **Isolation** : Sessions isolées par transaction

## 📋 Configuration appliquée

### Framework (config/packages/framework.yaml)

```yaml
session:
  handler_id: Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
  cookie_lifetime: 28800 # 8 heures
  gc_maxlifetime: 28800
  # ... autres paramètres de sécurité
```

### Services (config/services.yaml)

```yaml
# Service intermédiaire pour obtenir l'objet PDO depuis Doctrine
pdo_for_sessions:
  class: PDO
  factory: ["@doctrine.dbal.default_connection", "getNativeConnection"]

# Configuration du gestionnaire de sessions
Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler:
  arguments:
    - "@pdo_for_sessions"
    - { table_name: "sessions" }
```

> **Note technique :** Le `PdoSessionHandler` nécessite un objet PDO natif, pas une connexion Doctrine DBAL.
> C'est pourquoi nous utilisons une factory pour extraire l'objet PDO sous-jacent via `getNativeConnection()`.

### Table de sessions créée

```sql
CREATE TABLE sessions (
    sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
    sess_data LONGTEXT NOT NULL,
    sess_lifetime INTEGER UNSIGNED NOT NULL,
    sess_time INTEGER UNSIGNED NOT NULL,
    INDEX sessions_sess_lifetime_idx (sess_lifetime)
)
```

## 🛠️ Commandes disponibles

### Vérifier le statut des sessions

```bash
php bin/console app:session:status
```

### Nettoyer les sessions expirées

```bash
php bin/console app:session:cleanup
```

### Tester le stockage en BDD

```
Accéder à : /test-session-db
```

## 🔄 Maintenance automatique

### Sessions expirées

Pour nettoyer automatiquement les sessions expirées, ajoutez cette tâche cron :

```bash
# Nettoyer les sessions expirées toutes les heures
0 * * * * cd /path/to/ecoride && php bin/console app:session:cleanup
```

### Carshares expirés

Pour éviter les problèmes de performance lors de navigation rapide, la vérification des carshares expirés a été déplacée vers une tâche cron :

```bash
# Vérifier les carshares expirés toutes les 15 minutes
*/15 * * * * cd /path/to/ecoride && php bin/console app:carshare:check-expired
```

> **Important :** La vérification automatique des carshares expirés dans les contrôleurs a été temporairement désactivée pour éviter les conflits lors de navigation rapide. Utilisez la commande cron ci-dessus pour maintenir la cohérence des données.

## 🎯 Résolution du problème de navigation rapide

Cette configuration résout spécifiquement le problème de déconnexion lors de navigation rapide en :

1. **Éliminant les race conditions** sur les fichiers de session
2. **Garantissant la cohérence** des données de session
3. **Fournissant un accès transactionnel** aux sessions
4. **Supportant l'accès concurrent** sans corruption

Combiné avec la stratégie de detach() des entités dans les contrôleurs, cela offre une solution complète pour la stabilité des sessions lors de navigation rapide.
