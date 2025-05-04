<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentType extends Model
{
     protected $table='appointment_types';
     protected $fillable=['name','duration_minutes','price'];

     public function appointments()
     {
          return $this->hasMany(Appointment::class);
     }
}
