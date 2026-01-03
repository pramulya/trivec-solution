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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            
            $table->string('sender'); // Phone number or name
            $table->text('body');
            $table->string('source')->default('manual'); // 'manual' or 'termii'
            $table->timestamp('received_at')->useCurrent();
            
            // AI Analysis
            $table->string('ai_label')->nullable(); 
            $table->float('ai_score')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
