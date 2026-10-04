<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE videos SET youtube_published_at = published_at WHERE youtube_published_at IS NULL');
    }

    /**
     * Simple reprise de données : les valeurs d'origine ne sont pas
     * récupérables, il n'y a rien à défaire.
     */
    public function down(): void {}
};
