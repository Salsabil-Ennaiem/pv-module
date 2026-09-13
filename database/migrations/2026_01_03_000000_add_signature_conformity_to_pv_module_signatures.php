<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pv_module_signatures', function (Blueprint $table) {
            $table->string('signed_mechanism', 50)->default('simple_image')->after('mime');
            $table->timestamp('signed_at')->nullable()->after('signed_mechanism');
        });
    }

    public function down(): void
    {
        Schema::table('pv_module_signatures', function (Blueprint $table) {
            $table->dropColumn(['signed_mechanism', 'signed_at']);
        });
    }
};