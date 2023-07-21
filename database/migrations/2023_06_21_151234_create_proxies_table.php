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
        Schema::create("proxies", function (Blueprint $table) {
            $table->id();
            $table->string("ip");
            $table->string("external_id")->index();
            $table->string("port");
            $table->string("username");
            $table->string("password");
            $table->integer("type");
            $table->integer("provider");
            $table->string("country");
            $table->integer("status");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("proxies");
    }
};
