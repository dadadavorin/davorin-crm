<?php

declare(strict_types=1);

use App\Enums\DealStage;
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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->bigInteger('value_minor')->nullable();
            $table->string('stage')->default(DealStage::New->value);
            $table->decimal('position', 20, 10)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('primary_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('company_id');
            $table->index('primary_contact_id');
            $table->index('owner_id');
        });

        // Matches the board's own WHERE clause — see
        // companies_status_position_index for why the predicate is here.
        DB::statement(<<<'SQL'
            CREATE INDEX deals_stage_position_index
            ON deals (stage, position)
            WHERE deleted_at IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS deals_stage_position_index');

        Schema::dropIfExists('deals');
    }
};
