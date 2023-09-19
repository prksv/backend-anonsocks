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
        Schema::create("orders", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("rental_term_id");
            $table
                ->foreign("rental_term_id")
                ->references("id")
                ->on("rental_terms");
            $table->unsignedBigInteger("user_id");
            $table
                ->foreign("user_id")
                ->references("id")
                ->on("users");
            $table->unsignedBigInteger("category_id");
            $table
                ->foreign("category_id")
                ->references("id")
                ->on("categories");
            $table->integer("status");
            $table->integer("proxy_count");
            $table->string("country");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("orders");
    }
};
