<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('preferred_route', 20)->default('fastest');
            $table->jsonb('transport_modes')->nullable();
            $table->jsonb('accessibility_needs')->default(DB::raw("'[]'::jsonb"));
        });

        DB::statement(<<<'SQL'
            ALTER TABLE users
                ADD CONSTRAINT users_preferred_route
                    CHECK (preferred_route IN ('fastest', 'cheapest', 'transfers', 'sustainable')),
                ADD CONSTRAINT users_accessibility_needs
                    CHECK (jsonb_typeof(accessibility_needs) = 'array'
                           AND accessibility_needs <@ '["wheelchair","reduced_mobility","visual","hearing"]'::jsonb)
        SQL);

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
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
