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
        Schema::create('robot_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('robot_id')->constrained('robots')->onDelete('cascade');
            $table->integer('version');
            $table->longText('code');
            $table->text('changelog')->nullable();
            $table->boolean('is_current')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices e constraints
            $table->unique(['robot_id', 'version']);
            $table->index('robot_id');
            $table->index(['robot_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robot_versions');
    }
};
