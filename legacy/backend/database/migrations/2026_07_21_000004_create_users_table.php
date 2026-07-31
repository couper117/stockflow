<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Runs AFTER companies, roles & shops so the tenant/role/shop foreign keys
// resolve. Every user belongs to exactly one company (the tenant) and one role.
// A seller/shopkeeper is additionally assigned to one shop.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('locale', 5)->default('en');
            // Which shop this user works in (sellers/shopkeepers). Nullable:
            // super admins, stock managers and the boss are not tied to a shop.
            $table->foreignId('assigned_shop_id')->nullable()->constrained('shops')->nullOnDelete();
            // Forward-looking: the central stock arrives in a later session, so
            // this stays nullable with no FK constraint yet.
            $table->unsignedBigInteger('assigned_stock_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Email is unique WITHIN a company, not globally.
            $table->unique(['company_id', 'email']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
