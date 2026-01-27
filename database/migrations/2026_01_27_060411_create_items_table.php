<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name', 255);

            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete()->cascadeOnUpdate();

            $table->text('specification')->nullable();
            $table->year('purchase_year')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();

            $table->integer('stock_total')->default(0);
            $table->integer('stock_available')->default(0);
            $table->integer('stock_borrowed')->default(0);
            $table->integer('stock_damaged')->default(0);

            $table->enum('condition', ['good', 'minor', 'heavy'])->default('good');
            $table->enum('status', ['active', 'service', 'inactive'])->default('active');

            $table->timestamps();

            $table->index(['name', 'code']);
            $table->index(['category_id', 'brand_id', 'location_id']);
            $table->index('status');
            $table->index('condition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
