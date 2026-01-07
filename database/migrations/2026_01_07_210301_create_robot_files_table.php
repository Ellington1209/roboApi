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
        Schema::create('robot_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('robot_id')->constrained('robots')->onDelete('cascade');
            $table->string('name', 255)->nullable(); // Nome original do arquivo
            $table->string('disk', 30)->default('public'); // public/s3/minio
            $table->string('path', 500); // caminho no storage
            $table->string('url', 700)->nullable(); // URL pública
            $table->string('mime_type', 100)->nullable();
            $table->string('file_type', 20)->nullable(); // psf, mq5
            $table->bigInteger('size_bytes')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            // Índices
            $table->index('robot_id');
            $table->index(['robot_id', 'file_type']);
            $table->index(['robot_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robot_files');
    }
};
