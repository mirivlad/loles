<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // These tables already exist since the 2024 migrations. Upgrade them
        // in place instead of attempting to create them a second time.
        Schema::table('course_requests', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
        });

        DB::table('course_requests')->update([
            'approved_by' => DB::raw('approved_by_user_id'),
            'note' => DB::raw('comment'),
        ]);

        Schema::table('course_request_items', function (Blueprint $table) {
            $table->renameColumn('request_id', 'course_request_id');
        });

        Schema::table('course_request_items', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->cascadeOnDelete();
            // Existing request items may predate scheduling information.
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('course_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['start_date', 'end_date']);
        });

        Schema::table('course_request_items', function (Blueprint $table) {
            $table->renameColumn('course_request_id', 'request_id');
        });

        Schema::table('course_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('note');
        });
    }
};
