<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanity_check_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('executed_by_id')->nullable();
            $table->string('executed_by_type', 255)->nullable();
            $table->unsignedInteger('total_routes')->default(0);
            $table->unsignedInteger('tested_routes')->default(0);
            $table->unsignedInteger('ignored_routes')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('redirect_count')->default(0);
            $table->unsignedInteger('client_error_count')->default(0);
            $table->unsignedInteger('server_error_count')->default(0);
            $table->decimal('success_rate', 8, 2)->default(0);
            $table->json('config_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['executed_by_type', 'executed_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanity_check_runs');
    }
};
