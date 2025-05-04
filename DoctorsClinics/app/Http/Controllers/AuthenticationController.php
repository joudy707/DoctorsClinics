<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationController extends Controller
{
    public function regist (Request $request)
    {
        $validator=Validator::make($request->all(),[
            'name'=>'required|string',
            'email'=>'required|string|email|ends_with:gmail.com|unique:users',
            'password' => 'required|string|min:6',
             'role'=>'required|string|in:patient,doctor'
              ]);

              if ($validator->fails())
              {
                  return response(['errors'=>$validator->errors()],422);
              }

              $user=User::create([
                    'name'=>$request->name,
                     'email'=>$request->email,
                     'password' => Hash::make($request->password),
                     'role'=>$request->role
              ]);

              $token = JWTAuth::fromUser($user);

        return response(['message'=>'Account created successfully',
         'user'=>$user,
         'token'=>$token
        ]);
    }

    public function login(Request $request)
    {
        $validator=Validator::make($request->all(),[
            "email"=>"required|string",
            "password"=>"required|string",
           
        ]);

        if($validator->fails())
        {
            return response([
                "errors"=>$validator->errors()
            ],422);
        }

        $cacheKey="LoginAttempts".$request->ip();

        $attempts=Cache::get($cacheKey,0);

        if($attempts>=3)
        {
            return response([
                "message"=>"You have been temporarily blocked due to failed attempts. Try again in 30 seconds."
            ]);
        }
        
        if($token=Auth::guard("user")->attempt(["email"=>$request->input("email"),"password"=>$request->input("password")]))
        {

            $user=Auth::guard("user")->user();

          

            $token=JWTAuth::fromUser($user);

            Cache::forget($cacheKey);

          

            return response([
                "message"=>"Login successfully",
                "user"=>$user,
                "token"=>$token
            ],200);
        }          
        
        $attempts++;

        Cache::put($cacheKey,$attempts,30);

        return response([
            "message"=>"invalid email or password.Try again"
        ],200);

    }



}
