<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Study notes / documents. Files live on-server at storage/app/notes
     * (budget-friendly: no S3 for v1). Served via signed, auth-gated routes.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            $table->string('chapter');
            $table->string('title');
            $table->string('file_path');
            $table->enum('visibility', ['public', 'enrolled'])->default('enrolled');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['subject_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
