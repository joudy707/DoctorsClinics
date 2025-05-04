<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_profile_id'); // تحديث الاسم هنا
            $table->unsignedBigInteger('clinic_id');
            $table->string('day_of_week'); 
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            // تحديث العلاقات الخارجية
            $table->foreign('doctor_profile_id')->references('id')->on('doctor_profiles')->onDelete('cascade');
            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
 
 