<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('order_issue_reports', function (Blueprint $table) { $table->string('evidence_path')->nullable()->after('description'); $table->string('evidence_name')->nullable()->after('evidence_path'); }); } public function down(): void { Schema::table('order_issue_reports', function (Blueprint $table) { $table->dropColumn(['evidence_path', 'evidence_name']); }); } };
