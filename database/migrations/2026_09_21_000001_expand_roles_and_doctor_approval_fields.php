<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expandir roles en users
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','operator','supervisor_doctor','doctor','patient') NOT NULL DEFAULT 'patient'");

        // Agregar campos de aprobación en doctors
        Schema::table('doctors', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('user_id');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->enum('membership_status', ['none', 'active', 'expired'])->default('none')->after('rejection_reason');
            $table->timestamp('membership_expires_at')->nullable()->after('membership_status');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'approved_by', 'approved_at', 'rejection_reason', 'membership_status', 'membership_expires_at']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','doctor','patient') NOT NULL DEFAULT 'patient'");
    }
};
