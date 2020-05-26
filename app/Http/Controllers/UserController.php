<?php
namespace App\Http\Controllers;
use App\Helpers\AppHelper;
use App\Modal\AppVerification;
use App\Modal\Order;
use App\Modal\OtpVerification;
use App\Modal\Role;
use App\Modal\Setting;
use App\Modal\StudentDetail;
use App\Modal\Subscription;
use App\Modal\TempUser;
use App\Modal\UserSubscription;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //
    public function tempRegistration(Request $request)
    {
        /*
         * Check user already exist or not
         */
        $validate = AppHelper::Validation($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            "mobile_no" => 'required|unique:users|numeric|phone_number',
            "city" => 'required',
            "state" => 'required',
            "gender" => 'required',
            "class" => 'required',
            "password" => 'required|min:6',
            'fcm_token' => 'required',
            'device_id' => 'required',
            'dob' => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }

        $userExist = User::where('mobile_no', $request->mobile_no)->count();
        if ($userExist) {
            Response::json([
                "status_code" => 400,
                'status' => false,
                'message' => "User already register",
                'data' => ""
            ], 400);
        }
        $tempUser = TempUser::where('mobile_no', $request->mobile_no)->first();
        if ($tempUser) {
            $tempUser->delete();
        }
        /*
         * Create temp user
         */
        TempUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'city' => $request->city,
            'state' => $request->state,
            'gender' => $request->gender,
            'class' => $request->class,
            'password' => $request->password,
            'referred_by' => $request->referred_by,
            'device_id' => $request->device_id,
            'fcm_token' => $request->fcm_token,
            'dob' => date('Y-m-d', strtotime($request->dob))
        ]);
        /*
         * Create otp
         */
        $otpNumber = mt_rand(100000, 999999);
        AppHelper::otpMessage($request->mobile_no, $otpNumber);
        $otpRows = OtpVerification::where('mobile_no', $request->mobile_no)->get();
        if ($otpRows) {
            foreach ($otpRows as $row) {
                $row->status = 0;
                $row->save();
            }
        }

        OtpVerification::create([
            "mobile_no" => $request->mobile_no,
            "otp" => $otpNumber
        ]);
        //otp send to user number
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Otp validate for 10 minutes",
            "data" => ""
        ], 200);
    }

    public function otpVerify(Request $request)
    {
        $validate = AppHelper::Validation($request, [
            "mobile_no" => 'required|unique:users|numeric',
            "otp" => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }

        $otpVerify = OtpVerification::where('mobile_no', $request->mobile_no)->where('status', 1)->where('otp', $request->otp)->count();
        if ($otpVerify == 0) {
            return Response::json([
                "status" => false,
                "message" => "Otp not matched",
                "data" => ""
            ]);
        }
        $tempUser = TempUser::where('mobile_no', $request->mobile_no)->first();
        if (!$tempUser) {
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "Try to register again",
                "data" => ""
            ]);
        }

        /*
         * Create user
         */
        $user = User::create([
            "name" => $tempUser->name,
            "email" => $tempUser->email,
            "mobile_no" => $tempUser->mobile_no,
            "password" => bcrypt($tempUser->password),
            "role" => "SD"
        ]);
        // student details
        $stdentDetails = StudentDetail::create([
            "user_id" => $user->id,
            "class" => $tempUser->class,
            "city" => $tempUser->city,
            "state" => $tempUser->state,
            "dob" => $tempUser->dob,
            "gender" => $tempUser->gender,
            "referral_code" => AppHelper::random_strings(6),
            "referred_by" => $tempUser->referred_by,
        ]);
        $appVerification = AppHelper::checkAppVerification($tempUser->device_id);
        if ($appVerification) {
            $appVerification->fcm_token = $tempUser->fcm_token;
            $appVerification->save();
        } else {
            $appVerification = AppVerification::create([
                "user_id" => $user->id,
                "fcm_token" => $tempUser->fcm_token,
                "device_id" => $tempUser->device_id,
            ]);
        }

        // Create access token
        $user->access_token = $user->createToken("MyToken")->accessToken;
        $user->user_class = $stdentDetails->class;
        $user->city = $stdentDetails->city;
        $user->state = $stdentDetails->state;
        $user->dob = date('d-m-Y', strtotime($stdentDetails->dob));
        $user->gender = $stdentDetails->gender;
        $user->isVideo_purchase = false;
        if ($stdentDetails->image) {
            $user->profile_image = $stdentDetails->image;
        } else {
            $user->profile_image = "storage/profile.png";
        }
        $user->referral_code = $stdentDetails->referral_code;
        $user->device_id = $appVerification->device_id;
        $user->fcm_token = $appVerification->fcm_token;
        $user->referral_code = $stdentDetails->referral_code;
        //$user->wallet_balance=$stdentDetails->wallet_balance;
        //$user->wallet_balance=0;
        $user->created = strtotime($user->created_at) * 1000;
        $user->updated = strtotime($user->updated_at) * 1000;
        $user->bucket_url = env('AWS_BASE_URL');

        $user = AppHelper::unsetObject(['created_at', 'updated_at'], $user);
        $tempUser->delete();//delete temp user
        /*
         * Otp status update
         */
        $otpObj = OtpVerification::where('mobile_no', $tempUser->mobile_no)->where('status', 1)->first();
        if ($otpObj) {
            $otpObj->status = 0;
            $otpObj->save();
        }
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "true",
            "data" => $user
        ], 200);
    }

    public function profileUpdate(Request $request)
    {
        $validate = AppHelper::Validation($request, [
            "image" => 'required',
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $path = "";
        if ($request->has('image')) {
            $image = $request->file('image');
            $timestamp = now()->timestamp;
            $extension = $image->getClientOriginalExtension();
            if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
                return Response::json([
                    "status_code" => 500,
                    "status" => false,
                    "message" => "Image jpg and png required",
                    "data" => ""
                ], 500);
            }
            $path = "storage/student/" . $timestamp . $image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
        }
        $user_id= Auth::user()->id;
        $student = StudentDetail::where('user_id', $user_id)->first();
        $student->image = $path;
        $student->save();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "profile update successfully",
            "data" =>  ["profile_image"=>$path]
        ], 200);
    }

    public function updateFcmToken(Request $request)
    {
        $validate = AppHelper::Validation($request, [
            "fcm_token" => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $user = Auth::user();
        $appObj = AppVerification::where('user_id', $user->id)->first();
        if (!$appObj) {
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "User not exist",
                "data" => ""
            ], 200);
        }
        $appObj->fcm_token = $request->fcm_token;
        $appObj->save();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "fcm token update successfully",
            "data" => ""
        ], 200);
    }

    public function studentList()
    {
        $studentList=DB::table('users')
            ->join('student_details','student_details.user_id','=','users.id')
            ->where('role', 'SD')->get();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Student List",
            "data" => $studentList
        ], 200);
    }

    public function studentDelete(Request $request)
    {
        if (!isset($request->id)) {
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "Id required",
                "data" => ""
            ], 400);
        }
        $student = User::where('role', 'SD')->where('id', $request->id)->first();
        if ($student) {
            $student->delete();
            return Response::json([
                "status_code" => 200,
                "status" => true,
                "message" => "Student Delete successfully",
                "data" => ""
            ], 200);
        }
    }

    public function studentStatusUpdate(Request $request)
    {
        if (!isset($request->status)) {
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "Status required",
                "data" => ""
            ], 400);
        }
        $user_id = $request->id;
        $student = User::find($user_id);
        $student->status = $request->status;
        $student->save();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "status updated successfully",
            "data" => ""
        ], 200);
    }

    public function createUser(Request $request)
    {
        $validate = AppHelper::Validation($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate);
        }
        /*
         * Create user
         */
        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => bcrypt($request->password),
            "role" => "AD"
        ]);
        /*
         *  Role assign
         */
        $role = Role::where('name', 'Admin')->first();
        DB::table('role_user')->insert(
            ['user_id' => $user->id, 'role_id' => $role->id]
        );

        return Response::json([
            "status_code" => 201,
            "status" => true,
            "message" => "User create successfully",
            "data" => ""
        ], 201);
    }

    public function changePassword(Request $request)
    {
        $validate = AppHelper::Validation($request, [
            'password' => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate);
        }
        $user_id = Auth::user()->id;
        $user = User::find($user_id);
        $user->password = bcrypt($request->password);
        $user->save();
        $user = Auth::user()->token();
        $user->revoke();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Password update successfully",
            "data" => ""
        ], 200);
    }

    public function userUpdate(Request $request)
    {
        $validate = AppHelper::Validation($request, [
            'name' => 'required',
            'user_id' => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate);
        }
        $user = User::find($request->user_id);
        $user->name = $request->name;
        if (isset($request->password) && $request->password != "") {
            $user->password = bcrypt($request->password);
        }
        $user->save();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "updated successfully",
            "data" => ""
        ], 200);
    }

    public function userList()
    {
        $user = User::where('role', 'AD')->get();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "User List",
            "data" => $user
        ], 200);
    }

    public function userDelete(Request $request)
    {
        $user = User::where('role', 'AD')->where('id', $request->id)->first();
        if (!$user) {
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "User not exist",
                "data" => ""
            ], 400);
        }
        $user->delete();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "User delete successfully",
            "data" => ""
        ], 200);
    }
    public function userObjectById($id){
        $user = User::where('role', 'AD')->where('id', $id)->first();
        if (!$user) {
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "User not exist",
                "data" => ""
            ], 400);
        }
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "User",
            "data" => $user
        ], 200);
    }
    public function studentObjectById($id){
        $user = User::with('studentDetails')->where('role', 'SD')->where('id', $id)->first();
        if (!$user) {
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "Student not exist",
                "data" => ""
            ], 400);
        }
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Student",
            "data" => $user
        ], 200);
    }

    public function otpResend(Request $request){
        if(!isset($request->mobile_no)){
            return Response::json([
                "status_code" => 400,
                "status" => false,
                "message" => "Mobile no required",
                "data" => ""
            ], 400);
        }
        $otp=OtpVerification::where('mobile_no',$request->mobile_no)->where('status',1)->first();
        if($otp){
            AppHelper::otpMessage($request->mobile_no, $otp->otp);
            return Response::json([
                "status_code" => 200,
                "status" => true,
                "message" => "Otp resent successfully",
                "data" => ""
            ], 200);
        }
        return Response::json([
            "status_code" => 400,
            "status" => false,
            "message" => "You need to register again",
            "data" => ""
        ], 400);

    }
    public function forgetPasswordValidate(Request $request){
        $validate = AppHelper::Validation($request, [
            'mobile_no' => 'required',
            'otp' => 'required',
            'password' => 'required|min:6'

        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $otpVerify = OtpVerification::where('mobile_no', $request->mobile_no)->where('status', 1)->where('otp', $request->otp)->count();
        if ($otpVerify == 0) {
            return Response::json([
                "status" => false,
                "message" => "Otp not matched",
                "data" => ""
            ]);
        }
        $user = User::where('mobile_no',$request->mobile_no)->first();
        $user->password = bcrypt($request->password);
        $user->save();
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Change password successfully",
            "data" => ""
        ], 200);


    }
    public function forgetPassword(Request $request){
        $validate = AppHelper::Validation($request, [
            'mobile_no' => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        /*
        * Create otp
        */
        $otpNumber = mt_rand(100000, 999999);
        AppHelper::otpMessage($request->mobile_no, $otpNumber);
        $otpRows = OtpVerification::where('mobile_no', $request->mobile_no)->get();
        if ($otpRows) {
            foreach ($otpRows as $row) {
                $row->status = 0;
                $row->save();
            }
        }

        OtpVerification::create([
            "mobile_no" => $request->mobile_no,
            "otp" => $otpNumber
        ]);
        //otp send to user number
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Otp sent on registered mobile no",
            "data" => ""
        ], 200);

    }
    public function updateStudentByAdmin(Request $request){
        $validate = AppHelper::Validation($request, [
            'name'=>'required',
            //required|max:255|unique:classes,class_name,'."$request->id
            //'email'=>'required|email|unique:users,email,'.$request->id,
            //'mobile_no' => 'required|numeric|phone_number|unique:users,mobile_no,'.$request->id,
            'class'=>'required',
            'dob'=>'required',
            'state'=>'required',
            'gender'=>'required',
            'id'=>'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        /*
         * User Update
         */
        $user=User::find($request->id);
        $user->name=$request->name;
        //$user->email=$request->email;
        //$user->mobile_no=$request->mobile_no;
        $user->password=bcrypt($request->password);
        $user->save();
        /*
         * student details update
         */
        $studentDetails=StudentDetail::where('user_id',$request->id)->first();
        $studentDetails->class=$request->class;
        $studentDetails->city=$request->city;
        $studentDetails->state=$request->state;
        $studentDetails->gender=$request->gender;
        $studentDetails->dob=date('Y-m-d',strtotime($request->dob));
        $studentDetails->save();

        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Student updated succssfully",
            "data" => ""
        ], 200);
    }
    public function createStudentByAdmin(Request $request){
        $validate = AppHelper::Validation($request, [
            'name'=>'required',
            'email'=>'required|unique:users',
            'password'=>'required|min:6',
            'mobile_no' => 'required|unique:users|numeric|phone_number',
            'class'=>'required',
            'dob'=>'required',
            'state'=>'required',
            'gender'=>'required',
            'payment_method'=>'required',
            'subscription_type'=>'required',
            'amount'=>'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        /*
         * User crate
         */
        $user=User::create([
            "name"=>$request->name,
            "email"=>$request->email,
            "mobile_no"=>$request->mobile_no,
            "password"=>bcrypt($request->password),
            "role"=>"SD",
            "status"=>1
        ]);
        /*
         * Student details
         */
        $studentDetails=StudentDetail::create([
            "user_id"=>$user->id,
            "class"=>$request->class,
            "city"=>$request->city,
            "state"=>$request->state,
            "gender"=>$request->gender,
            "dob"=>date('Y-m-d',strtotime($request->dob)),
            "referral_code"=>AppHelper::random_strings(6)
        ]);
        /*
         * Order details
         */
        $order=Order::create([
            "user_id"=>$user->id,
            "payment_mode"=>$request->payment_method,
            "subscription_id"=>$request->subscription_type,
            "amount"=>$request->amount,
            "transaction_id"=>$request->transaction_id,
            "order_id"=>$request->order_id
        ]);
        /*
         * Create user subscription
         */
        $subscription=Subscription::find($request->subscription_type);
        $expire_date=date('Y-m-d H:i:s',strtotime($subscription->days.' days'));
        $setting=Setting::first();
        $current_year=date('Y H:i:s');
        $session_end=date('Y-m-d H:i:s',strtotime($setting->session_end."-".$current_year));
        if(strtotime($expire_date)>strtotime($session_end)){
            $expire_date=$session_end;
        }

        UserSubscription::create([
            "subscription_id"=>$request->subscription_type,
            "user_id"=>$user->id,
            "expire_date"=>$expire_date
        ]);
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Student created succssfully",
            "data" => ""
        ], 200);
    }
    public function studentHistory($id){
        $studentDetails=User::with('studentDetails')->with('order_hist.subscription')->with('userSubscription')->with('redeemHistory')->find($id);
        return Response::json([
            "status_code" => 200,
            "status" => true,
            "message" => "Student Details",
            "data" => $studentDetails
        ], 200);
    }

}
