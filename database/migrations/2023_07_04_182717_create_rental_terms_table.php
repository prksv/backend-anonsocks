<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("rental_terms", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("category_id");
            $table
                ->foreign("category_id")
                ->references("id")
                ->on("categories");
            $table->integer("days");
            $table->float("price");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("rental_terms");
    }
};
