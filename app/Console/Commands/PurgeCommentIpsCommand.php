<?php

namespace App\Console\Commands;

use App\Models\Comment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * L'adresse IP d'un commentateur ne sert qu'à la modération et à répondre à
 * une éventuelle réquisition : elle est effacée au bout d'un an, durée
 * annoncée dans la politique de confidentialité. Le commentaire, lui, reste.
 */
#[Signature('comments:purge-ips')]
#[Description('Efface l\'adresse IP des commentaires publiés il y a plus d\'un an.')]
class PurgeCommentIpsCommand extends Command
{
    private const RETENTION_MONTHS = 12;

    public function handle(): int
    {
        $cutoff = now()->subMonths(self::RETENTION_MONTHS);

        $purged = Comment::withTrashed()
            ->whereNotNull('author_ip')
            ->where(fn ($query) => $query
                ->where('posted_at', '<', $cutoff)
                ->orWhere(fn ($query) => $query->whereNull('posted_at')->where('created_at', '<', $cutoff)))
            ->update(['author_ip' => null]);

        $this->info("Adresses IP effacées : {$purged}.");

        return self::SUCCESS;
    }
}
