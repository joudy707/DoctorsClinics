<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table='appointments';
    protected $fillable=['user_id','doctor_profile_id','clinic_id','doctor_schedule_id','appointment_type_id','start_datetime','end_datetime','status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doctor()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function schedule()
    {
        return $this->belongsTo(DoctorSchedule::class);
    }

    public function appointment_type()
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
