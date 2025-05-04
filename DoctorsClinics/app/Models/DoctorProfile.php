<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    protected $table='doctor_profiles';
    protected $fillable=['user_id','specialization_id','bio','experience_years'];
  
    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
        
    }

    public function clinicsdoctor()
    {
        return $this->hasMany(ClinicDoctor::class);
    }
    
    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_profile_id');
    }

    public function appointments()
    {
         return $this->hasMany(Appointment::class);
    }
}