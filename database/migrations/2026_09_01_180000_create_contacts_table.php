<?php

declare(strict_types=1);

use App\Enums\ContactStatus;
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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->string('status')->default(ContactStatus::New->value);
            $table->decimal('position', 20, 10)->default(0);
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('company_id');
            $table->index('owner_id');
        });

        // A plain unique index would permanently lock a soft-deleted
        // contact's address; the partial predicate lets it be reused once
        // that contact is gone, while a live duplicate is still rejected.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX contacts_email_unique
            ON contacts (email)
            WHERE deleted_at IS NULL
        SQL);

        // Matches the board's own WHERE clause — see
        // companies_status_position_index for why the predicate is here.
        DB::statement(<<<'SQL'
            CREATE INDEX contacts_status_position_index
            ON contacts (status, position)
            WHERE deleted_at IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS contacts_status_position_index');
        DB::statement('DROP INDEX IF EXISTS contacts_email_unique');

        Schema::dropIfExists('contacts');
    }
};
