<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('position', 20, 10)->default(0);
        });

        // Existing rows all default to 0; space them out per status so the
        // board doesn't open with every card stacked at the same position.
        DB::statement(<<<'SQL'
            UPDATE companies
            SET position = ranked.rank * 1024
            FROM (
                SELECT id, row_number() OVER (PARTITION BY status ORDER BY id) AS rank
                FROM companies
            ) AS ranked
            WHERE companies.id = ranked.id
        SQL);

        // A plain composite index would happily index trashed rows too;
        // the board never reads them, so the partial predicate keeps the
        // index smaller and matching the board's own WHERE clause.
        DB::statement(<<<'SQL'
            CREATE INDEX companies_status_position_index
            ON companies (status, position)
            WHERE deleted_at IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS companies_status_position_index');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
