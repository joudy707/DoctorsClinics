<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        Specialization::create(['name' => ' Dentistry']);
        Specialization::create(['name' => ' General Medicine']);
        Specialization::create(['name' => 'Surgery']);
        Specialization::create(['name' => ' Pediatrics']);
    }
}