<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);

            $table->enum('type', ['student', 'teacher']);
            $table->string('class', 50)->nullable();
            $table->string('major', 100)->nullable();

            $table->string('id_number', 50)->nullable();
            $table->string('contact', 20)->nullable();

            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->timestamps();

            $table->index('name');
            $table->index('type');
            $table->index('status');
            $table->index('id_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowers');
    }
};
