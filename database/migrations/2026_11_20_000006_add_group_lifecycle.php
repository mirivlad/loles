<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('status', 16)->default('active')->index();
            $table->date('started_on')->nullable();
            $table->date('finished_on')->nullable();
        });

        // The only existing state was active/inactive. Do not mislabel
        // previously disabled groups as ongoing training.
        DB::table('groups')->where('is_active', false)->update(['status' => 'archived']);
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'started_on', 'finished_on']);
        });
    }
};
