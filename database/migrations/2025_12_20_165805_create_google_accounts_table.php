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
        Schema::create('google_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();

    $table->string('google_id')->nullable();
    $table->text('access_token');
    $table->text('refresh_token')->nullable();
    $table->integer('expires_in')->nullable();
    $table->string('token_type')->nullable();
    $table->string('scope')->nullable();

    $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_accounts');
    }
};
