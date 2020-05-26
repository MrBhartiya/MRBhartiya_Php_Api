<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Modal\AppVerification;
use App\Modal\StudentDetail;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Helper\Helper;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function login(Request $request){
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        if ($this->attemptLogin($request)) {
            $user = Auth::user();
            if($user=='SD'){
                return Response::json([
                    "status_code"=>401,
                    "status"=>false,
                    "message"=>"Unauthorized login",
                    "data"=>$user
                ],401);
            }
            $user->access_token = $user->createToken("MyToken")->accessToken;
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Login successfully",
                "data"=>$user
            ],200);
        }
        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);
        $errors = "Username password incorrect try again";
        return  Response::json([
            "status_code"=>400,
            "status"=>false,
            "message"=>$errors,
            "data"=>""
        ],400);
    }
    public function studentLogin(Request $request){
        $validate=AppHelper::Validation($request,[
            'username' => 'required|numeric',
            'password' => 'required',
            'fcm_token'=> 'required',
            'device_id'=>'required',
        ]);
        if(isset($validate['status'])){
            return Response::json($validate,400);
        }
        /*
         * Mobile number to get email id
         */
        $tempUser=User::where('mobile_no',$request->username)->first();
        if($tempUser==null){
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Your username invalid",
                "data"=>""
            ],400);
        }
        $request->merge([
            'email' => $tempUser->email,
        ]);

        $this->validateLogin($request);
        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        if ($this->attemptLogin($request)) {
            $user = Auth::user();
            $studentDetails=StudentDetail::where('user_id',$user->id)->first();
            $user->access_token = $user->createToken("MyToken")->accessToken;
            $user->user_class=$studentDetails->class;
            $user->city=$studentDetails->city;
            $user->state=$studentDetails->state;
            $user->gender=$studentDetails->gender;
            $user->dob=$studentDetails->dob;
            $user->referral_code=$studentDetails->referral_code;
            $user->isVideo_purchase=false;
            $user->bucket_url=env('AWS_BASE_URL');
            if($studentDetails->image){
                $user->profile_image=$studentDetails->image;
            }else{
                $user->profile_image="/storage/profile.png";
            }
            $appVerification=AppHelper::checkAppVerification($request->device_id);
            if($appVerification){
                $appVerification->fcm_token=$request->fcm_token;
                $appVerification->save();
            }else{
                $appVerification=AppVerification::create([
                    "user_id"=> $user->id,
                    "fcm_token"=>$request->fcm_token,
                    "device_id"=>$request->device_id,
                ]);
            }
            if($appVerification){
                $user->device_id=$appVerification->device_id;
                $user->fcm_token=$appVerification->fcm_token;
            }
            $user->created=strtotime($user->created_at)*1000;
            $user->updated=strtotime($user->updated_at)*1000;
            $user=AppHelper::unsetObject(['created_at','updated_at'],$user);
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Login successfully",
                "data"=>$user
            ],200);
        }
        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);
        $errors = "Username password incorrect try again";
        return  Response::json([
            "status_code"=>400,
            "status"=>false,
            "message"=>$errors,
            "data"=>""
        ],400);
    }

}
