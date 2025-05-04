<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    public function DoctorProfile(Request $request,$user_id)
    {
      $user=User::find($user_id);
      if(!$user)
      {
        return response(['message'=>'user is not found'],400);
      }

      if($user->role !== 'doctor')
      {
        return response(['message'=>'doctors only can access this page']);
      }

    $validator=Validator::make($request->all(),[
    'specialization_id' => 'required|integer|exists:specializations,id',
    'bio'=>'required|string',
    'experience_years'=>'required|integer|min:1'
    ]);

    if($validator->fails())
    {
        return response(['errors'=>$validator->errors()],422);
    }

    if ($user->doctorProfile) {
      return response(['message' => 'Doctor profile already exists.'], 409);
  }
  

    $user->doctorProfile()->create([
     'user_id'=>$user->id,
     'specialization_id'=>$request->specialization_id,
     'bio'=>$request->bio,
     'experience_years'=>$request->experience_years,
    ]);

    return response([
        'message'=>'doctor information added successfully',
        'doctor_profile'=>$user,
    ],200);

  }
    public function addClinic(Request $request)

    {
      $validator=Validator::make($request->all(),[
        'name'=>'required|string|unique:clinics,name',
        'address'=>'required|string',
        'phone' => [
          'required',
          'unique:clinics,phone',
          'string',
          'regex:/^\d{7}$/'
           ],
    ]);

    if($validator->fails())
    {
        return response(['errors'=>$validator->errors()],422);
    }
    $clinic=Clinic::create([
      'name'=>$request->name,
      'address'=>$request->address,
      'phone'=>$request->phone
    ]);
    return response(['message'=>'clinic added successfully',
    'clinic '=>$clinic ],200);


    }

    public function addSpecialization(Request $request)
    {
      $validator=Validator::make($request->all(),[
        'name'=>'required|string|unique:Specializations,name',
      
    ]);

    if($validator->fails())
    {
        return response(['errors'=>$validator->errors()],422);
    }
    $Specialization=Specialization::create([
      'name'=>$request->name,
     
    ]);
    return response(['message'=>'Specialization added successfully',
    'Specialization '=>$Specialization],200);

    }
    public function index()
    {
        // جلب جميع الأطباء مع بيانات الاختصاص (Eager Loading)
        $doctors = DoctorProfile::with(['specialization', 'user'])->get();

        // تحضير البيانات المراد إرجاعها
        $formattedDoctors = $doctors->map(function ($doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->user?->name ?? 'غير محدد', // اسم الطبيب من جدول المستخدمين
                'specialization' => $doctor->specialization?->name ?? 'غير محدد', // اسم التخصص
            ];
        });

        return response()->json(['data' => $formattedDoctors], 200);
    }

            public function show($id)
            {
                // جلب بيانات الطبيب مع الاختصاص وساعات العمل
                $doctor = DoctorProfile::with(['specialization', 'schedules.clinic'])->find($id);
        
                if (!$doctor) {
                    return response()->json(['error' => 'الطبيب غير موجود.'], 404);
                }
        
                // تحضير البيانات المراد إرجاعها
                $formattedData = [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name, // اسم الطبيب من جدول المستخدمين
                    'specialization' => $doctor->specialization?->name ?? 'غير محدد', // اسم التخصص
                    'schedules' => $doctor->schedules->map(function ($schedule) {
                        return [
                            'clinic_name' => $schedule->clinic->name, // اسم العيادة
                            'day_of_week' => $schedule->day_of_week, // اليوم
                            'start_time' => $schedule->start_time, // وقت البدء
                            'end_time' => $schedule->end_time, // وقت الانتهاء
                            'is_approved' => $schedule->is_approved ? 'مقبول' : 'قيد المراجعة', // حالة الموافقة
                        ];
                    }),
                ];
        
                return response()->json(['data' => $formattedData], 200);
            }
                public function getAvailableTimes(Request $request)
                {
                    // التحقق من صحة البيانات المدخلة
                    $validated = $request->validate([
                        'doctor_profile_id' => 'required|exists:doctor_profiles,id',
                        'day_of_week' => 'required|string', // اليوم (Monday, Tuesday, ...)
                    ]);
            
                    // جلب جدول عمل الطبيب في اليوم المطلوب
                    $schedule = DoctorSchedule::where('doctor_profile_id', $request->doctor_profile_id)
                        ->where('day_of_week', $request->day_of_week)
                        ->first();
            
                    if (!$schedule) {
                        return response()->json(['error' => 'لا يوجد جدول عمل للطبيب في هذا اليوم.'], 404);
                    }
            
                    // تحويل الأوقات إلى نطاق زمني متاح
                    $availableTimes = [];
                    $startTime = strtotime($schedule->start_time);
                    $endTime = strtotime($schedule->end_time);
            
                    // افتراض أن كل حجز يستغرق ساعة واحدة (يمكن تعديل هذا حسب الحاجة)
                    $interval = 60 * 60; // ساعة واحدة بالثواني
            
                    for ($time = $startTime; $time < $endTime; $time += $interval) {
                        $formattedTime = date('H:i', $time);
                        $availableTimes[] = $formattedTime;
                    }
            
                    return response()->json(['data' => $availableTimes], 200);
                }

              public function showAppointments(Request $request)
              {
                $validator= Validator::make($request->all(),[
                    'doctor_profile_id'=>'required|exists:doctor_profiles,id',
                    'status'=>'required|string|in:all,pending,confirmed,cancelled,completed',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                 $status=$request->status;
                 $doctorId=$request->doctor_profile_id;

                 $query = Appointment::where('doctor_profile_id', $doctorId);

    if ($status !== 'all') {
        $query->where('status', $status);
    }

    $appointments = $query->get();

    if ($appointments->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No appointments found for the selected status.'
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => "Appointments fetched successfully.",
        'data' => $appointments
    ]);
}
            }