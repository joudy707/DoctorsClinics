<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
   
        protected $fillable = [
            'doctor_profile_id',
            'clinic_id',
            'day_of_week',
            'start_time',
            'end_time',
            'is_approved'
        ];
    
        public function doctorProfile()
        {
            return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
        }
    
        public function clinic()
        {
            return $this->belongsTo(Clinic::class, 'clinic_id');
        }

        public function appointments()
        {
             return $this->hasMany(Appointment::class);
        }
    }
