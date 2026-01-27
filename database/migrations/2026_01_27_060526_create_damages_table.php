<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('damages', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('borrowing_id')->nullable()->constrained('borrowings')->nullOnDelete()->cascadeOnUpdate();

            $table->enum('damage_level', ['minor', 'moderate', 'heavy']);
            $table->text('description');

            $table->date('reported_date');

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->text('solution')->nullable();
            $table->date('completion_date')->nullable();

            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();

            $table->timestamps();

            $table->index(['item_id', 'status']);
            $table->index('damage_level');
            $table->index('reported_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damages');
    }
};
