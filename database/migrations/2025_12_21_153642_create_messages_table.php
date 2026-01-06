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
            // Body email (opsional, bisa null)
            $table->longText('body')->nullable();
            
            // New Features (Consolidated)
            $table->timestamp('email_date')->nullable()->index();
            $table->boolean('is_html')->default(false);
            $table->string('folder')->default('inbox')->index();
            $table->boolean('is_starred')->default(false)->index();

            // Status
            $table->boolean('is_analyzed')->default(false);

            $table->timestamps();
            $table->index('created_at');
            
            // AI Analysis Results
            $table->string('phishing_label')->nullable(); // safe, suspicious, phishing
            $table->unsignedTinyInteger('phishing_score')->nullable(); // 0–100
            $table->json('phishing_reasons')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
