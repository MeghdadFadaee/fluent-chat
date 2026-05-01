<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->timestamp('pinned_at')->nullable()->after('muted_until');
            $table->index(['user_id', 'pinned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'pinned_at']);
            $table->dropColumn('pinned_at');
        });
    }
};
