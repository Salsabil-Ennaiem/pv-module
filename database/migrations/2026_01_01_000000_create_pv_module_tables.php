<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pv_module_pvs', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->json('contenu')->nullable();
            $table->string('statut')->default('brouillon')->index();
            $table->json('template_data')->nullable();
            $table->timestamp('date_generation')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->timestamp('signature_deadline')->nullable();
            $table->json('receivers')->nullable();
            $table->json('versions')->nullable();
            $table->string('type')->default('pv')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('pv_module_pv_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pv_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('statut')->default('en_attente')->index();
            $table->text('commentaire')->nullable();
            $table->timestamp('date_reponse')->nullable();
            $table->timestamps();

            $table->index(['pv_id', 'user_id', 'version']);
        });

        Schema::create('pv_module_pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type');
            $table->json('config')->nullable();
            $table->string('orientation')->default('portrait');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });

        Schema::create('pv_module_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('path');
            $table->string('mime')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pv_module_pv_validations');
        Schema::dropIfExists('pv_module_signatures');
        Schema::dropIfExists('pv_module_pdf_templates');
        Schema::dropIfExists('pv_module_pvs');
    }
};