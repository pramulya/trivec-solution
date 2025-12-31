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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('phishing_label')->nullable(); // safe | warning | phishing | unknown
            $table->json('phishing_reasons')->nullable(); // alasan kenapa dianggap phishing
            $table->float('phishing_score')->nullable(); // 0.0 – 1.0
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            //
        });
    }
};
