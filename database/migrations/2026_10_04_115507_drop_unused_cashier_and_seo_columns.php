<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire ce qu'aucun code ne lit plus : les tables d'abonnement et les
 * colonnes client de Cashier sur `users` (le client Stripe est l'élève, et le
 * site ne vend aucun abonnement), le canonical et le schéma JSON-LD hérités de
 * WordPress (le front les ignore), et le lien de paiement des produits
 * (remplacé par les PaymentIntents).
 *
 * Les gardes d'existence couvrent une base créée après la suppression des
 * migrations Cashier, qui n'a jamais eu ces tables ni ces colonnes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');

        if (Schema::hasColumn('users', 'stripe_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['stripe_id']);
                $table->dropColumn(['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at']);
            });
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['seo_canonical', 'seo_schema_json']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stripe_payment_link');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('stripe_payment_link')->nullable();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('seo_canonical')->nullable();
            $table->json('seo_schema_json')->nullable();
        });
    }
};
