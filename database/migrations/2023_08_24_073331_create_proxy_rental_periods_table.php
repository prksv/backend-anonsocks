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
        Schema::create('proxy_rental_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("order_proxy_id");
            $table
                ->foreign("order_proxy_id")
                ->references("id")
                ->on("order_proxy");
            $table->unsignedBigInteger("rental_term_id");
            $table
                ->foreign("rental_term_id")
                ->references("id")
                ->on("rental_terms");
            $table->float("amount")->default(0);
            $table->timestamp("expires_at");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxy_rental_periods');
    }
};
