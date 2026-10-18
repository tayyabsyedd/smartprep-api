<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('kind', ['practice', 'mock'])->default('practice');
            $table->boolean('timed')->default(true);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_free')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();

            $table->index(['module_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
