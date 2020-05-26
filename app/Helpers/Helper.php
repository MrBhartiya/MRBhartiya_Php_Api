<?php
namespace App\Helpers;

use App\Modal\AppVerification;
use App\Modal\StudentDetail;
use App\Modal\UserSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class AppHelper
{
    static public function otpMessage($mobile_no,$otp){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://control.msg91.com/api/sendotp.php?authkey=299428AhClMkoUH5da860b0&mobile=$mobile_no&message=$otp Otp validate for 10 minutes&sender=OTPSMS&country=91&otp=$otp",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
    }

    static public function Validation($request,$validation){
        $validate=Validator::make($request->all(), $validation);
        if($validate->fails()){
            $validateMessage=$validate->errors()->messages();
            $fistMessage="";
            $errorMessage=[];
            foreach($validateMessage as $key=>$message){
                array_push($errorMessage,$message[0]);
            }
            return [
                "status_code"=>400,
                "status"=>false,
                "message"=>$errorMessage[0],
                "data"=>""
            ];
        }
    }
    static public function unsetObject($arr,$object){
            foreach ($arr as $key){
                unset($object[$key]);
            }
            return $object;
    }
    static public function random_strings($length_of_string)
    {

        // String of all alphanumeric character
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        // Shufle the $str_result and returns substring
        // of specified length
        return substr(str_shuffle($str_result),
            0, $length_of_string);
    }
    static public function checkAppVerification($id){
        $appRow=AppVerification::where('device_id',$id)->first();
        return $appRow;
    }
    static public function studentDetails(){
        $user_id=Auth::user()->id;
        $studentDetails=StudentDetail::where('user_id',$user_id)->first();
        return $studentDetails;
    }
    static public function SubscriptionCheck(){
        $user=Auth::user();
        $userSubscription=UserSubscription::where('user_id',$user->user_id)->orderBy('id','DESC')->first();
        if($userSubscription){
            $current_date=date('Y-m-d H:i:s');
            if(strtotime($current_date)<=strtotime($userSubscription->expire_date)){
                return true;
            }
        }
        return false;

    }

}