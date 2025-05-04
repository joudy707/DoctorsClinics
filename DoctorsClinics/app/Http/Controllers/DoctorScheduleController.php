<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DoctorScheduleController extends Controller
{
    public function store(Request $request)
    {
        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'doctor_profile_id' => 'required|exists:doctor_profiles,id',
            'clinic_id' => 'required|exists:clinics,id',
            'day_of_week' => 'required|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
    
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }
    
        // جلب الدكتور والعيادة
        $doctor = DoctorProfile::find($request->doctor_profile_id);
        $clinic = Clinic::find($request->clinic_id);
    
        if (!$doctor || !$clinic) {
            return response(['message' => 'Doctor or Clinic not found'], 404);
        }
    
        $day = $request->day_of_week;
        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        $duration = $start->diffInMinutes($end);
    
        if ($duration < 30) {
            return response([
                'error' => 'The shift duration must be at least 30 minutes.'
            ], 422);
        }
    
        // التحقق من عدم تعارض مع جداول الدكتور
        foreach ($doctor->schedules()->where('day_of_week', $day)->get() as $schedule) {
            if ($start < Carbon::parse($schedule->end_time) && $end > Carbon::parse($schedule->start_time)) {
                return response(['error' => 'Doctor has another schedule at this time.'], 409);
            }
        }
    
        // التحقق من عدم تعارض مع أطباء آخرين في العيادة
        foreach ($clinic->doctor_schedules()->where('day_of_week', $day)->get() as $schedule) {
            if ($start < Carbon::parse($schedule->end_time) && $end > Carbon::parse($schedule->start_time)) {
                return response(['error' => 'Another doctor is working at this time in the clinic.'], 409);
            }
        }
    
        // إنشاء الجدول
        $schedule = $doctor->schedules()->create([
            'clinic_id' => $clinic->id,
            'day_of_week' => $day,
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
        ]);
    
        return response()->json([
            'message' => 'Schedule added successfully',
            'schedule' => $schedule
        ], 201);
    }
    
    
    }