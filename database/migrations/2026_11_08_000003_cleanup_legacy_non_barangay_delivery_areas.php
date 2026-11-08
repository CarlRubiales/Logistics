<?php

use App\Services\LagunaHubProvisioner;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        LagunaHubProvisioner::ensureProvisioned(true);
    }

    public function down(): void
    {
        // Keep barangay delivery areas intact on rollback
    }
};
