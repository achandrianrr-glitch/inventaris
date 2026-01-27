<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();

            $table->foreignId('borrower_id')->constrained('borrowers')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete()->cascadeOnUpdate();

            $table->integer('qty');

            $table->enum('borrow_type', ['lesson', 'daily']);

            $table->integer('lesson_hour')->nullable();
            $table->string('subject', 100)->nullable();
            $table->string('teacher', 100)->nullable();

            $table->date('borrow_date');
            $table->time('borrow_time')->nullable();

            $table->dateTime('return_due');
            $table->dateTime('return_date')->nullable();

            $table->enum('return_condition', ['normal', 'damaged', 'lost'])->nullable();
            $table->enum('status', ['borrowed', 'returned', 'late', 'damaged', 'lost'])->default('borrowed');

            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['borrower_id', 'status']);
            $table->index(['item_id', 'status']);
            $table->index('borrow_date');
            $table->index('return_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
