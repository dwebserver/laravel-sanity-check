<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanity_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('sanity_check_runs')->cascadeOnDelete();
            $table->string('route_name')->nullable();
            $table->string('method', 16);
            $table->string('uri', 2048);
            $table->string('resolved_uri', 2048)->nullable();
            $table->string('action', 512)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('classification', 32);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_ignored')->default(false);
            $table->json('parameters')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('run_id');
            $table->index(['run_id', 'classification']);
            $table->index(['run_id', 'is_ignored']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanity_check_items');
    }
};
