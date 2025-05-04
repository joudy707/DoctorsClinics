<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorScheduleController;
use App\Models\Appointment;
use Illuminate\Support\Facades\Route;

Route::post('regist',[AuthenticationController::class,'regist']);
Route::post('login',[AuthenticationController::class,'login']);
Route::post('doctorInformation/{user_id}',[DoctorController::class,'DoctorProfile']);
Route::post('addClinic',[DoctorController::class,'addClinic']);
Route::post('addSpecialization',[DoctorController::class,'addSpecialization']);
Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/{id}', [DoctorController::class, 'show']);
Route::post('/doctors/available-times', [DoctorController::class, 'getAvailableTimes']);
Route::post('/doctor/appointments',[DoctorController::class,'showAppointments']);
Route::post();

Route::post('/schedules', [DoctorScheduleController::class, 'store']);



Route::get('/appointment-types', [AppointmentController::class, 'listAppointmentTypes']);
Route::post('/doctor-schedules', [AppointmentController::class, 'getAvailableSlots']);
Route::post('/appointments/confirm', [AppointmentController::class, 'confirmAppointment']);
