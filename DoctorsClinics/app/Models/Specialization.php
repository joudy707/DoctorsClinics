<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $table='specializations';
    protected $fillable=['name'];

    public function doctors()
    {
        return $this->hasMany(DoctorProfile::class , 'specialization_id');
    }
}