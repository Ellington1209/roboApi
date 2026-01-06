<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('robot_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('robot_id')->constrained('robots')->onDelete('cascade');
            $table->string('title', 120)->nullable();
            $table->string('caption', 255)->nullable();
            $table->string('disk', 30)->default('public'); // public/s3/minio
            $table->string('path', 500); // caminho no storage
            $table->string('url', 700)->nullable(); // URL pública (opcional)
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            // Índices
            $table->index('robot_id');
            $table->index(['robot_id', 'sort_order']);
            $table->index(['robot_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robot_images');
    }
};
