<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Relasi ke user
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // ID email dari Gmail
            $table->string('gmail_message_id')->index();

            // Metadata email
            $table->string('from')->nullable();
            $table->string('subject')->nullable();
            $table->text('snippet')->nullable();

            // Body email (opsional, bisa null)
            $table->longText('body')->nullable();

            // Status
            $table->boolean('is_analyzed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
