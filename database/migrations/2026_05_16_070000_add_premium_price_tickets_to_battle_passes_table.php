<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battle_passes', function (Blueprint $table) {
            $table->unsignedSmallInteger('premium_price_tickets')
                ->default(5)
                ->after('premium_price_shards');
        });
    }

    public function down(): void
    {
        Schema::table('battle_passes', function (Blueprint $table) {
            $table->dropColumn('premium_price_tickets');
        });
    }
};
