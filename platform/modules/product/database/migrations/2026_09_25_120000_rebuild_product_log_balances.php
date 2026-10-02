<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Disabled after the 2026-10-02 audit: the historical ledger is
        // incomplete and cannot be replayed from a zero opening balance.
        // Recovery requires verified baselines and an explicit backup/plan.
    }

    public function down(): void
    {
        // Never reintroduce corrupted stock balances.
    }
};
