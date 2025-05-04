<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{

    public function listAppointmentTypes(Request $request)
    {
        $appointment_type=AppointmentType::all();
        return response(['appointmentTtpe'=>$appointment_type],200);
    
    }

    public function getAvailableSlots(Request $request)
    {
        // 1. Validate request inputs
        $validator = Validator::make($request->all(), [
            'doctor_profile_id' => 'required|exists:doctor_profiles,id',
            'clinic_id'          => 'required|exists:clinics,id',
            'appointment_type_id' => 'required|exists:appointment_types,id',
            'date'                => 'required|date|after_or_equal:today', // تاريخ الموعد
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doctorId= $request->doctor_profile_id;
        $clinicId= $request->clinic_id;
        $appointment_typeId=$request->appointment_type_id;
        $date = Carbon::parse($request->date); // تاريخ اليوم المطلوب
        $dayOfWeek = $date->format('l'); // Monday, Tuesday, etc.

        //1 تاكدنا انو الدكتور عندو جدول اعمال بهاليوم وبهالمركز
        $schedule=DoctorSchedule::where('doctor_profile_id',$doctorId)
        ->where('clinic_id',$clinicId)
        ->where('day_of_week',$dayOfWeek)
        ->first();
        if(!$schedule)
        {
            return response(['message'=>'doctor dosnt have schedule in this day'],400);
        }

      //2 نجيب مدة المعاينة ونقسم اوقات دوام الطبيب بناء عليها
      $slotStart= Carbon::parse($schedule->start_time); // متغير خزنت فيه بداية دوام الدكتور بهالمركز
      $slotEnd= Carbon::parse($schedule->end_time);
      // منجيب الكائن من قاعدة البيانات بناء على الهوية وبعدها منطلع منو المدة
      $appointmentType= AppointmentType::find($appointment_typeId);
      if (!$appointmentType) {
        return response()->json([
            'status' => false,
            'message' => 'Appointment type not found',
        ], 404);
    }
    $duration = $appointmentType->duration_minutes;
    $gap=5;
    $timeSlots=[];
    //   مشان ناخد نسخة مؤقتة من الوقت ونعدل عليه دون تغيير الوقت الاصليcopy منستخدم 
    // lte = Less Than or Equal 
    while($slotStart->copy()->addMinutes($duration)->lte($slotEnd))
    {
        // طالما الوقت داخل الدوام اضفه الى مصفوفة المواعيد
        $timeSlots[]= [
            'start'=> $slotStart->copy(),
            'end'=>$slotStart->copy()->addMinutes($duration),
        ];
        $slotStart->addMinutes($duration + $gap);
    }
    // عداد عم يضيف وقت المعاينة ليفوت ينفحص كل مرة 
    

    // 3 هلق رح جيب المواعيد المحجوزة للطبيب بهاليوم وبهالمركز 

  $appointments= Appointment::where('doctor_profile_id',$doctorId)
  ->where('clinic_id', $clinicId)
  ->whereDate('start_datetime', $date->toDateString())
  ->get();

  //4 فلترة الفترات المحجوزة
  $availableSlots=[];
  // حلقة على الفترات كلها لصفي الاوقات المحجوزة منها
  foreach($timeSlots as $slot)
  {
    $isAvailable=true;
    // حلقة الاوقات المحجوزة لشيلها من الاوقات المتاحة
      foreach($appointments as $appointment)
      {
        // جبلي وقت بداية الحجز وقت نهاية الحجز
        $existingStart= Carbon::parse($appointment->start_datetime);
        $existingEnd= Carbon::parse($appointment->end_datetime);
        if($slot['start']< $existingEnd && $slot['end'] > $existingStart)
        {
            $isAvailable=false;
            break;
        }
    }
      // إذا كانت الفترة متاحة أضفها للمصفوفة
      if ($isAvailable) {
        // إضافة استراحة 5 دقائق للطبيب بعد كل موعد
        $availableSlots[] = [
            'start_time' => $slot['start']->format('H:i'),
            'end_time' => $slot['end']->format('H:i'),
            'note' => 'Available'
        ];

      
     
    }
}

// 5. إذا لم توجد فترات متاحة
if (empty($availableSlots)) {
    return response()->json([
        'status' => false,
        'message' => 'No available slots for the selected date.',
    ], 404);
}

return response()->json([
    'status' => true,
    'message' => 'Available slots fetched successfully.',
    'data' => $availableSlots,
], 200);
}

public function confirmAppointment(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id'             => 'required|exists:users,id',
        'doctor_profile_id'   => 'required|exists:doctor_profiles,id',
        'clinic_id'           => 'required|exists:clinics,id',
        'appointment_type_id' => 'required|exists:appointment_types,id',
        'date'                => 'required|date|after_or_equal:today',
        'start_time'          => 'required|date_format:H:i',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $userId     = $request->user_id;
    $doctorId   = $request->doctor_profile_id;
    $clinicId   = $request->clinic_id;
    $typeId     = $request->appointment_type_id;
    $date       = Carbon::parse($request->date);
    $appointmentType = AppointmentType::find($typeId);

    if (!$appointmentType) {
        return response()->json(['message' => 'Appointment type not found'], 404);
    }

    // اصلاح خطأ الكتابة هنا
    $startTime = Carbon::parse($request->date . ' ' . $request->start_time);
    $endTime = $startTime->copy()->addMinutes($appointmentType->duration_minutes);

    // البحث عن جدول الطبيب في هذا اليوم
    $dayOfWeek = $date->format('l'); // مثل Monday
    $schedule = DoctorSchedule::where('doctor_profile_id', $doctorId)
        ->where('clinic_id', $clinicId)
        ->where('day_of_week', $dayOfWeek)
        ->first();

    if (!$schedule) {
        return response()->json(['message' => 'Doctor has no schedule on this day in this clinic'], 400);
    }

    // التأكد من عدم وجود تضارب
    $conflict = Appointment::where('doctor_profile_id', $doctorId)
        ->where('clinic_id', $clinicId)
        ->whereDate('start_datetime', $date->toDateString())
        ->where(function ($query) use ($startTime, $endTime) {
            $query->whereBetween('start_datetime', [$startTime, $endTime])
                ->orWhereBetween('end_datetime', [$startTime, $endTime])
                ->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_datetime', '<=', $startTime)
                      ->where('end_datetime', '>=', $endTime);
                });
        })->exists();

    if ($conflict) {
        return response()->json([
            'status' => false,
            'message' => 'This time slot is no longer available.',
        ], 409);
    }

    // إنشاء الحجز
    $appointment = Appointment::create([
        'user_id'             => $userId,
        'doctor_profile_id'   => $doctorId,
        'clinic_id'           => $clinicId,
        'doctor_schedule_id'  => $schedule->id,
        'appointment_type_id' => $typeId,
        'start_datetime'      => $startTime,
        'end_datetime'        => $endTime,
        'status'              => 'pending',
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Appointment confirmed successfully.',
        'data' => $appointment
    ], 201);
}





    }
    


