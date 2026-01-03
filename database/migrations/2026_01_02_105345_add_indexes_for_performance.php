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
            $table->index('email_date');
            $table->index('is_starred');
            $table->index('created_at'); // Often used for sorting
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->index('received_at');
            $table->index('sender');
            $table->index('user_id'); // Ensure foreign key is indexed
        });
    }
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['email_date']);
            $table->dropIndex(['is_starred']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropIndex(['received_at']);
            $table->dropIndex(['sender']);
            $table->dropIndex(['user_id']);
        });
    }
};
