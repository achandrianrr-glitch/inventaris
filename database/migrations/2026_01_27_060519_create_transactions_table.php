<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->enum('type', ['in', 'out']);

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete()->cascadeOnUpdate();
            $table->integer('qty');

            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();

            $table->date('transaction_date');

            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();

            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('completed');

            $table->timestamps();

            $table->index(['type', 'transaction_date']);
            $table->index('status');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
