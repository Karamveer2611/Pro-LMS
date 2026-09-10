<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type');
            $table->text('content_body')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_required')->default(true);
            $table->string('release_type')->default('immediate');
            $table->unsignedInteger('release_days')->nullable();
            $table->date('release_date')->nullable();
            $table->timestamps();

            $table->index(['section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
