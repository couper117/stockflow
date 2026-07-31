<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A shop is a tenant-owned selling location. Runs BEFORE users so the users
// table can reference it. Shop-owned inventory & movements arrive in a later
// session; this table plus the ShopScope concept are the foundation for the
// shop-level visibility rule (a seller sees only their own shop).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Shop names are unique WITHIN a company, not globally.
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
