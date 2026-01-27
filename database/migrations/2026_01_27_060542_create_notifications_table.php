<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['stock_low', 'overdue', 'damage', 'opname']);
            $table->string('title', 255);
            $table->text('message');

            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable();

            $table->boolean('is_read')->default(false);

            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();

            $table->timestamps();

            $table->index(['admin_id', 'is_read']);
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
