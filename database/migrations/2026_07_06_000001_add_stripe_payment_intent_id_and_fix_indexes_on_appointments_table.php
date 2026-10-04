<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->after('token');
            $table->dropIndex(['starts_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('stripe_payment_intent_id');
            $table->dropIndex(['status']);
            $table->index('starts_at');
        });
    }
};
