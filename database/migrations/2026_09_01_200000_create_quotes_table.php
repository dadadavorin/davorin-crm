<?php

declare(strict_types=1);

use App\Enums\QuoteStatus;
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
        // Backs `GenerateQuoteNumber` — a plain auto-increment sequence, not
        // reset per year. `quotes.number`'s unique index is the backstop the
        // action falls back to on a collision; it never checks-then-inserts.
        // `IF NOT EXISTS` because the sequence isn't owned by a table column
        // — `migrate:fresh`'s drop-all-tables step doesn't touch it, so a
        // second fresh migration in the same database must not collide with
        // the sequence the first one already created.
        DB::statement('CREATE SEQUENCE IF NOT EXISTS quote_number_seq');

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('status')->default(QuoteStatus::Draft->value);
            $table->foreignId('deal_id')->constrained('deals')->restrictOnDelete();
            $table->date('issue_date');
            $table->date('valid_until');

            // Snapshotted at write time (T8) — a later change to the rate
            // used elsewhere must never alter an existing quote's totals.
            $table->decimal('tax_rate', 5, 4);
            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor')->default(0);

            // The snapshotted customer block — frozen from the deal's
            // company and primary contact at write time, never joined live.
            $table->string('bill_to_company_name');
            $table->text('bill_to_address')->nullable();
            $table->string('bill_to_contact_name')->nullable();
            $table->string('bill_to_contact_email')->nullable();

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->decimal('position', 20, 10)->default(0);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('deal_id');
            $table->index('owner_id');
        });

        // Matches the board's own WHERE clause — see
        // companies_status_position_index for why the predicate is here.
        DB::statement(<<<'SQL'
            CREATE INDEX quotes_status_position_index
            ON quotes (status, position)
            WHERE deleted_at IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS quotes_status_position_index');

        Schema::dropIfExists('quotes');

        DB::statement('DROP SEQUENCE IF EXISTS quote_number_seq');
    }
};
