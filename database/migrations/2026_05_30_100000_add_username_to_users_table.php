<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('email')->nullable()->change();
        });

        // Set default username for existing users based on their email prefix
        DB::table('users')->whereNull('username')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                if ($user->email) {
                    $baseUsername = explode('@', $user->email)[0];
                    $username = $baseUsername;
                    $counter = 1;

                    // Ensure unique username
                    while (DB::table('users')->where('username', $username)->exists()) {
                        $username = $baseUsername . $counter;
                        $counter++;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $username]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->change();
        });
    }
};
