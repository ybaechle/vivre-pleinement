<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le webhook charge.refunded et le rattrapage des paiements retrouvent le
     * rendez-vous par son PaymentIntent, comme pour les inscriptions et les
     * commandes du livre, déjà indexées.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['stripe_payment_intent_id']);
        });
    }
};
