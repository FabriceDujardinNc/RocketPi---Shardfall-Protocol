<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug', 60)->nullable()->unique()->after('display_name');
        });

        // Backfill existing users
        $taken = [];
        DB::table('users')->orderBy('id')->each(function ($u) use (&$taken) {
            $base = Str::slug($u->display_name ?? $u->name) ?: 'user';
            $slug = $base;
            $i    = 2;
            while (in_array($slug, $taken, true)
                || DB::table('users')->where('slug', $slug)->where('id', '!=', $u->id)->exists()
            ) {
                $slug = $base.'-'.$i;
                $i++;
            }
            $taken[] = $slug;
            DB::table('users')->where('id', $u->id)->update(['slug' => $slug]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
