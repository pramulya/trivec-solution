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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            // Assuming messages are just stored as a string ID from Gmail, but if we had a local messages table
            // we would use a foreign key. Since the user didn't mention a local messages table, 
            // and the current code fetches from API, we'll store the Gmail 'message_id' as a string index.
            // Wait, looking at current code, there is no local 'messages' table, it's all API.
            // So we just index the gmail message id.
            $table->string('message_id')->index(); 
            $table->string('attachment_id')->nullable(); // Gmail attachment ID
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->string('path'); // Local secure path
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
