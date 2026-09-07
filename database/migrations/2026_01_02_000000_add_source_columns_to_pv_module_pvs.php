<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pv_module_pvs', function (Blueprint $table) {
            $table->string('source_type')->nullable()->index()->after('type');
            $table->unsignedBigInteger('source_id')->nullable()->index()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('pv_module_pvs', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropIndex(['source_type']);
                $table->dropIndex(['source_id']);
            }

            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};