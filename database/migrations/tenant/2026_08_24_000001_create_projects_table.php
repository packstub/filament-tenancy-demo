<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Runs against EVERY tenant database (database/migrations/tenant/ is the
 * plugin's tenant migration path). Note there is no tenant_id column —
 * isolation happens at the connection level, one database per tenant.
 *
 * Never put users/sessions here: identity and auth stay in the central DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->date('due_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
