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
        Schema::create('robot_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('robot_id')->constrained('robots')->onDelete('cascade');
            $table->string('key', 80); // ex: ptsGain, ptsLoss
            $table->string('label', 120); // ex: "Pontos Gain"
            $table->string('type', 20); // number/string/boolean/select
            $table->json('value'); // guarda qualquer tipo
            $table->json('default_value')->nullable();
            $table->boolean('required')->default(false);
            $table->json('options')->nullable(); // quando type=select
            $table->json('validation_rules')->nullable(); // min, max, regex, etc
            $table->string('group')->nullable(); // para agrupar na UI
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            // Índices e constraints
            $table->unique(['robot_id', 'key']);
            $table->index('robot_id');
            $table->index(['robot_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robot_parameters');
    }
};
