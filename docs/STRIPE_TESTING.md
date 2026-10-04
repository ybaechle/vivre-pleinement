# Tester les paiements Stripe

Procédure pour valider de bout en bout, en **mode test** Stripe, les trois tunnels
de paiement (rendez-vous, livre, formations), avant la mise en production. Tous
passent par un PaymentIntent affiché dans le Payment Element, sur une page du site.

## 1. Récupérer les clés de test

1. Crée/ouvre ton compte sur [dashboard.stripe.com](https://dashboard.stripe.com).
2. Active le **mode Test** (interrupteur en haut à droite).
3. Dans **Développeurs → Clés API**, copie :
   - **Clé publiable** `pk_test_...`
   - **Clé secrète** `sk_test_...`

Renseigne-les dans `.env` :

```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
CASHIER_CURRENCY=eur
CASHIER_CURRENCY_LOCALE=fr_FR
```

Puis : `vendor/bin/sail artisan config:clear`

## 2. Brancher le webhook en local (Stripe CLI)

Le webhook confirme l'achat après paiement. En local, on utilise la Stripe CLI.

```bash
# Installer la CLI : https://stripe.com/docs/stripe-cli
stripe login
stripe listen --events payment_intent.succeeded,charge.refunded --forward-to localhost/stripe/webhook
```

La commande affiche un secret `whsec_...` **propre à cette session**. Copie-le dans `.env` :

```env
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

Puis `vendor/bin/sail artisan config:clear` et **laisse `stripe listen` tourner** dans un terminal.

> ⚠️ Ce `whsec_` de la CLI est différent de celui d'un endpoint créé dans le Dashboard.
> Pour la prod, voir §5.

## 3. Tester un RDV payant

1. Assure-toi qu'une prestation **payante** existe (ex. « Séance individuelle », 70 €).
   Sinon, dans `/espace-pro → Rendez-vous → Prestations`, mets un prix > 0.
2. Va sur `/reservation`, choisis la séance payante, un créneau, remplis le formulaire.
3. Tu arrives sur la page de paiement du site (Payment Element). Paie avec la carte de test :
   - Numéro : `4242 4242 4242 4242`
   - Date : n'importe quelle date future · CVC : 3 chiffres · Code postal : 5 chiffres
4. Après paiement → redirection vers la page de **confirmation**.
5. Vérifie :
   - Terminal `stripe listen` : event `payment_intent.succeeded` reçu, réponse `200`.
   - `/espace-pro → Rendez-vous` : le RDV est **Confirmé** + **Payé**.
   - Emails (Mailpit, `localhost:8025` avec Sail) : confirmation client + notification Laura.

### Cartes de test utiles
| Scénario | Carte |
|---|---|
| Paiement réussi | `4242 4242 4242 4242` |
| 3D Secure requis | `4000 0027 6000 3184` |
| Paiement refusé | `4000 0000 0000 0002` |

## 4. Tester les cas limites

- **Abandon du paiement** : quitte la page de paiement sans payer. Le RDV reste
  `unpaid`/`Pending`, puis est annulé automatiquement au bout de 30 minutes
  (`appointments:send-reminders`) pour libérer le créneau.
- **RDV gratuit** : réserver le « RDV découverte » (0 €) ne passe pas par Stripe – confirmation directe.
- **Double-booking au paiement** (rare) : si le créneau est pris pendant le paiement, le
  webhook rembourse automatiquement et envoie l'email « créneau plus disponible ».

## 4 bis. Activer PayPal (via Stripe)

Le Payment Element affiche les moyens de paiement activés dans le dashboard
(`automatic_payment_methods`). Pour proposer PayPal :

1. Dashboard Stripe → **Paramètres → Moyens de paiement** (Settings → Payment methods).
2. Active **PayPal** (en mode Test d'abord, puis Live).
3. C'est tout : le client verra « Carte » + « PayPal » sur l'écran de paiement.

> ⚠️ Conditions Stripe pour PayPal : compte Stripe éligible, **devise EUR** (OK ici),
> et selon le pays du compte. Si PayPal n'apparaît pas sur la page de paiement, c'est presque
> toujours qu'il n'est pas activé dans le dashboard ou non disponible pour ta devise/pays.
>
> L'argent PayPal arrive sur ton **compte Stripe** (pas ton compte PayPal Business) –
> c'est le compromis de cette approche, choisi pour sa simplicité.

En test, PayPal propose un compte sandbox pour simuler le paiement. Le webhook
`payment_intent.succeeded` est identique quel que soit le moyen de paiement :
le reste du flux (confirmation, emails, remboursement) ne change pas.

## 5. Passage en production

1. Dans le Dashboard Stripe (**mode Live**), crée un endpoint webhook :
   - URL : `https://TON-DOMAINE/stripe/webhook`
   - Événements : `payment_intent.succeeded`, `charge.refunded`
   - (ou via `php artisan cashier:webhook`, qui crée l'endpoint avec ces événements, lus dans `config/cashier.php`)
2. Copie le **signing secret** de cet endpoint dans le `.env` de prod (`STRIPE_WEBHOOK_SECRET`).
3. Mets les clés **Live** (`pk_live_`, `sk_live_`).
4. Vérifie que le **worker de queue** tourne (les emails sont en file `database`) :
   `php artisan queue:work` (ou Supervisor/Horizon).
5. Vérifie que le **cron** est actif pour les rappels :
   `* * * * * cd /chemin && php artisan schedule:run >> /dev/null 2>&1`

## Dépannage

- **`api_key cannot be the empty string`** : clés Stripe absentes du `.env` (config à vider).
- **Signature webhook invalide** : mauvais `STRIPE_WEBHOOK_SECRET` (celui de la CLI ≠ celui du Dashboard).
- **Achat resté en attente après paiement** : le webhook n'arrive pas → vérifier que `stripe listen`
  tourne (local) ou que l'endpoint est bien configuré (prod), et regarder `storage/logs/laravel.log`.
  En production, `payments:reconcile` rattrape ces paiements toutes les 15 minutes.
- **Emails non reçus** : le worker de queue ne tourne pas (`queue:work`).
