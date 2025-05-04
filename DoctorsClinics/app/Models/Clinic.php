<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $table='clinics';
    protected $fillable=['name','address','phone'];

    public function doctor_schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'clinic_id');
    }

    public function appointments()
    {
         return $this->hasMany(Appointment::class);
    }
}    
