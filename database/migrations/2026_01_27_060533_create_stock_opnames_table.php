<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->date('opname_date');

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete()->cascadeOnUpdate();

            $table->integer('system_stock');
            $table->integer('physical_stock');
            $table->integer('difference');

            $table->enum('status', ['normal', 'discrepancy']);
            $table->enum('validation', ['matched', 'review', 'approved'])->default('matched');

            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('opname_date');
            $table->index('status');
            $table->index('validation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
