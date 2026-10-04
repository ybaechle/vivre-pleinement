# Vivre Pleinement

Site de Laura Baechlé, praticienne ACT spécialisée dans les troubles anxieux :
blog, vidéos YouTube, prise de rendez-vous payants, vente d'un livre numérique
et formations en ligne avec espace élève.

## Stack

- PHP 8.5, Laravel 13, Livewire 3, Filament 4 (back-office sur `/espace-pro`)
- MariaDB, files d'attente et cache en base
- Stripe via Laravel Cashier 16 (PaymentIntents et Payment Element)
- Tailwind CSS 4 et Vite
- Tests : Pest 4

## Installation locale

Le projet tourne dans Laravel Sail ; toutes les commandes passent par `vendor/bin/sail`.

```bash
cp .env.example .env
composer install
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail npm install
vendor/bin/sail npm run dev
```

`migrate --seed` crée le compte administrateur (variables `ADMIN_*`, obligatoires),
importe le contenu du blog depuis `database/seed.sql` et pose les réglages par défaut.

Les mails partent dans Mailpit : <http://localhost:8025>.

## Commandes utiles

| Commande | Rôle |
|---|---|
| `vendor/bin/sail artisan test --compact` | Lance la suite de tests |
| `vendor/bin/sail bin pint --dirty` | Formate le code PHP modifié |
| `vendor/bin/sail npm run build` | Compile les assets |
| `vendor/bin/sail artisan youtube:oauth-setup` | Configure l'accès OAuth à la chaîne YouTube (sous-titres) |
| `vendor/bin/sail artisan videos:export-transcripts` / `videos:import-transcripts` | Mise en forme manuelle des transcriptions, en secours du pipeline n8n |

## Tâches planifiées (production)

Le cron appelle `schedule:run` chaque minute (`scripts/supervisor/crontab`), et un
worker Supervisor traite la file (`scripts/supervisor/vivre-pleinement-worker.conf`).

| Tâche | Fréquence |
|---|---|
| `appointments:send-reminders` : rappels, suivi 24 h après la séance, paniers abandonnés | 15 min |
| `payments:reconcile` : rattrapage des paiements dont le webhook s'est perdu | 15 min |
| `youtube:sync` : synchronisation des vidéos de la chaîne | toutes les heures |
| `youtube:fetch-transcripts --since=14` : récupération des sous-titres | toutes les heures |
| `auth:clear-resets students` : purge des jetons de réinitialisation expirés | quotidienne |
| `comments:purge-ips` : effacement des adresses IP des commentaires de plus d'un an | quotidienne |

## Vidéos et transcriptions

1. `youtube:sync` crée les vidéos publiées sur la chaîne.
2. `youtube:fetch-transcripts` récupère leurs sous-titres bruts.
3. n8n reformate les transcriptions en paragraphes et produit l'enrichissement
   éditorial (intro, résumé, points clés, chapitres) via les endpoints
   `/api/automation/videos`, protégés par `AUTOMATION_TOKEN`.

## Paiements

Voir [docs/STRIPE_TESTING.md](docs/STRIPE_TESTING.md) pour tester les trois tunnels
(rendez-vous, livre, formations) et configurer le webhook Stripe en production.
