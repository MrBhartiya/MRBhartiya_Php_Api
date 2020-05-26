<?php

namespace App\Http\Controllers;

use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use App\Helpers\AppHelper;
use App\Modal\Citie;
use App\Modal\Classe;
use App\Modal\Company;
use App\Modal\FavouriteVideo;
use App\Modal\LikedVideo;
use App\Modal\Order;
use App\Modal\RedeemBalance;
use App\Modal\ReferralTran;
use App\Modal\Setting;
use App\Modal\State;
use App\Modal\StudentDetail;
use App\Modal\Subject;
use App\Modal\Subscription;
use App\Modal\Teacher;
use App\Modal\TopicVideo;
use App\Modal\UserSubscription;
use App\Modal\VendorDetail;
use App\User;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommonController extends Controller
{
    //
    public function stateList(){
        $stateList=State::orderBy('name', 'ASC')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"state list",
            "data"=>$stateList
        ],200);
    }
    public function cityList(Request $request){
        $state=State::where('name',$request->state)->first();
        if(!$state){
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Pass state",
                "data"=>""
            ],400);
        }
        $cityList=Citie::where('state_id',$state->id)->orderBy('city_name', 'ASC')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"city list",
            "data"=>$cityList
        ],200);
    }
    public function stateClassList(){
        $stateList=State::orderBy('name', 'ASC')->get();
        $classList=Classe::get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"state class list",
            "data"=>[
                "state"=>$stateList,
                "user_class"=>$classList
            ]
        ],200);
    }
    public function homeApi(){
        $user=Auth::user();
        $teacherList=Teacher::get();
        $userDetails=StudentDetail::where('user_id',$user->id)->first();
        $subjectList=Subject::whereHas('classes', function($q) use ($userDetails) {
            $q->where('class_name',$userDetails->class);
        })->get();
        $demoVideoList=TopicVideo::where('type','demo')->get();
        foreach($demoVideoList as $demoVideo){
            /*
             * Fav video
             */ 
            $countFav=FavouriteVideo::where('video_id',$demoVideo->id)->where('user_id',$user->id)->count();
            if($countFav){
                $demoVideo->fav=1;
            }else{
                $demoVideo->fav=0;
            } 
            /*
             * Like video
             */
            $countLike=LikedVideo::where('video_id',$demoVideo->id)->where('user_id',$user->id)->count();
            if($countLike){
                $demoVideo->like=1;
            }else{
                $demoVideo->like=0;
            }
        }
        $studentDetails=StudentDetail::where('user_id',$user->id)->first();
        $favourieVideoList=FavouriteVideo::with('video')->where('user_id',$user->id)->has('video')->get();
        $likedVideos=LikedVideo::with('video')->where('user_id',$user->id)->has('video')->get();
        $subscriptions=Subscription::get();
        /*
         * User expire time
         */
        $userSubscription=UserSubscription::orderBy('id', 'desc')->where('user_id',$user->id)->first();
        $expire_date="";
        if(isset($userSubscription->expire_date)){
            $expire_date=strtotime($userSubscription->expire_date) * 1000;
        }
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Home api list",
            "data"=>[
                "teacher_list"=>$teacherList,
                "subject_list"=>$subjectList,
                "demo_video_list"=>$demoVideoList,
                "favourite_videos"=>$favourieVideoList,
                "liked_video"=>$likedVideos,
                "subscriptions"=>$subscriptions,
                "app_expire"=>$expire_date
            ]
        ],200);
    }
    public function dashboard(){
        $studentCount=User::where('role','SD')->count();
        $subscriptionCount=Subscription::count();
        $teacherCount=Teacher::count();
        $classCount=Classe::count();
        $topicVideo=TopicVideo::count();
        $VendorCount=User::where('role','VD')->count();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Dashboard api list",
            "data"=>[
                "student"=>$studentCount,
                "subscription"=>$subscriptionCount,
                "teacher"=>$teacherCount,
                "user_class"=>$classCount,
                "video"=>$topicVideo,
                "vendor"=>$VendorCount
            ]
        ],200);
    }
    public function globalSearch(Request $request){
        $studentDetails=AppHelper::studentDetails();
        $allData=DB::table('topic_videos')
            ->join('topics','topic_videos.topic_id','=','topics.id')
            ->join('chapters','topics.chapter_id','=','chapters.id')
            ->join('subjects','chapters.subject_id','=','subjects.id')
            ->join('classes','subjects.class_id','=','classes.id')
            ->join('teachers','topic_videos.teacher_id','=','teachers.id')
            ->where('class_name',$studentDetails->class)->where(function($query) use ($request){
                $query->orWhere('teacher_name','LIKe','%'.$request->search.'%')
                      ->orWhere('chapter_name','LIKe','%'.$request->search.'%')
                      ->orWhere('topic_name','LIKe','%'.$request->search.'%')
                      ->orWhere('subject_name','LIKe','%'.$request->search.'%')
                      ->orWhere('title','LIKe','%'.$request->search.'%');
            })->take(10)->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Global Search",
            "data"=>$allData
        ],200);
    }
    public function demoVideos(){
        $studentDetails=AppHelper::studentDetails();
        $allVideos=TopicVideo::with(['topic.chapter.subject.classes' =>function($q) use ($studentDetails){
            $q->where('class_name',$studentDetails->class);
        }])->with('teacherVideo')->where('type','demo')->has('topic.chapter.subject.classes')->has('teacherVideo')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Demo videos",
            "data"=>$allVideos
        ],200);
    }

    public function companyAddEdit(Request $request){
        /*
         * Check user already exist or not
         */
        $validate = AppHelper::Validation($request, [
            'about' => 'required',
            'email_id' => 'required',
            'mobile_no' => 'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $companyCount=Company::count();
        if($companyCount){


            $company=Company::first();
            $company->about=$request->about;
            $company->email_id=$request->email_id;
            $company->mobile_no=$request->mobile_no;
            if($request->hasFile('logo') && isset($request->logo) && $request->logo!=""){
                $logo= $request->file('logo');
                $folderLogo="storage/";
                $timestamp = now()->timestamp;
                $logoPath=$folderLogo.$logo->getClientOriginalName();
                Storage::disk('s3')->put($logoPath, file_get_contents($logo));
                $company->logo=$logoPath;
            }
            $company->save();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Company update successfully",
                "data"=>""
            ],200);
        }else{
            $logoPath="";
            if($request->has('logo')){
                $logo= $request->file('logo');
                $folderLogo="storage/";
                $timestamp = now()->timestamp;
                $logoPath=$folderLogo.$logo->getClientOriginalName();
                Storage::disk('s3')->put($logoPath, file_get_contents($logo));
            }
            Company::create([
               "about"=> $request->about,
               "email_id"=> $request->email_id,
               "mobile_no"=> $request->mobile_no,
               "logo"=>$logoPath
            ]);
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Company create successfully",
                "data"=>""
            ],200);
        }

    }
    public function companyDetails(){
        $companyDetails=Company::first();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Company Details",
            "data"=>$companyDetails
        ],200);
    }

    public function getCheckSum(Request $request){
        $validate = AppHelper::Validation($request, [
            'subscription_id' => 'required',
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $user_id=Auth::user()->id;
        $subscription_id=$request->subscription_id;
        $user=User::with('studentDetails')->find($user_id);
        $mobile_no=$user->mobile_no;
        $name=$user->name;
        $email_id=$user->email;
        //Subscription id plan id mobile app to post
        $subscription=Subscription::find($subscription_id);
        $price=$subscription->price;
        $order_id=(int)$mobile_no.rand(1,100);
        /*
         * Insert to order
         */
        $order=Order::create([
            "user_id"=>$user_id,
            "order_id"=>$order_id,
            "payment_mode"=>"Online",
            "subscription_id"=>$subscription_id,
            "amount"=>$price
        ]);
        $payment = PaytmWallet::with('receive');
        $payment->prepare([
            'order' => $order_id,
            'user' => rand(1,10000),
            'mobile_number' => $mobile_no,
            'email' => $email_id,
            'amount' => $price,
            'callback_url' => "https://securegw-stage.paytm.in/theia/paytmCallback?ORDER_ID=".$order_id
        ]); 
        $data=$payment->getCheckSum();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Check sum",
            "data"=>$data
        ],200);
    }

    public function order($user_id,$subscription_id){
        /*
         * Get user and place details
         */

        $user=User::with('studentDetails')->find($user_id);
        $mobile_no=$user->mobile_no;
        $name=$user->name;
        $email_id=$user->email;
        //Subscription id plan id mobile app to post
        $subscription=Subscription::find($subscription_id);
        $price=$subscription->price;
        $order_id=$mobile_no.rand(1,100);
        /*
         * Insert to order
         */
        /*$order=Order::create([
            "user_id"=>$user_id,
            "order_id"=>$order_id,
            "payment_mode"=>"Online",
            "subscription_id"=>$subscription_id,
            "amount"=>$price
        ]);*/
        $payment = PaytmWallet::with('receive');
        $payment->prepare([
            'order' => $order_id,
            'user' => $name,
            'mobile_number' => $mobile_no,
            'email' => $email_id,
            'amount' => $price,
            'callback_url' => url('api/payment/status')
        ]);
        return $payment->receive();
    }
    public function paymentCallback(Request $request){
        //$transaction = PaytmWallet::with('receive');
        $validate = AppHelper::Validation($request, [
            'order_id' => 'required',
            'transaction_status' => 'required',
            'transaction_id' => 'required',
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $order_id = $request->order_id;
        $order=Order::where('order_id',$order_id)->first();
        if($request->transaction_status=="01"){
            $order->status=2;
            /*
             * Create user subscription
             */
            $subscription=Subscription::find($order->subscription_id);
            $userSubscription=UserSubscription::where('user_id',$order->user_id)->orderBy('id')->first();
            $expire_date=date('Y-m-d H:i:s',strtotime($subscription->days.' days'));
            $setting=Setting::first();
            $current_year=date('Y H:i:s');
            $session_end=date('Y-m-d H:i:s',strtotime($setting->session_end."-".$current_year));
            if(isset($userSubscription->expire_date) && (strtotime(date('Y-m-d',strtotime($userSubscription->expire_date))) < strtotime(date('Y-m-d')))){
                if(strtotime(date('Y-m-d',strtotime($userSubscription->expire_date))) > strtotime($session_end)){
                    $expire_date=$session_end;
                }else{
                    $expire_date=date('Y-m-d H:i:s',strtotime($userSubscription->expire_date));
                }
            }else if(strtotime($expire_date)>strtotime($session_end)){
                $expire_date=$session_end;
            }
            UserSubscription::create([
                "subscription_id"=>$order->subscription_id,
                "user_id"=>$order->user_id,
                "expire_date"=>$expire_date
            ]);
        }else if($request->transaction_status!="01"){
            $order->status=1;
        }
        $order->transaction_id=$request->transaction_id;
        $order->save();
        if($request->transaction_status=="01"){
            /*
             * If user reffred
             */
            $studentDetails=StudentDetail::where('user_id',$order->user_id)->first();
            $referred_by=$studentDetails->referred_by;
            if($referred_by){
                $setting=Setting::first();
                $referredStudent=StudentDetail::where('referral_code',$referred_by)->first();
                if(isset($referredStudent->user_id)){
                    $referredStudent->wallet_balance=(float)$referredStudent->wallet_balance+$setting->referred_amount;
                    $referredStudent->save();
                    $studentDetails->referred_by="";
                    $studentDetails->save();
                    /*
                     * referral Trans history
                     */
                    ReferralTran::create([
                        "order_id"=>$order->id,
                        "referral_user_id"=> $studentDetails->user_id,
                        "referred_user_id"=> $referredStudent->user_id,
                        "type"=>"SD",
                        "amount"=> $setting->referred_amount
                    ]);
                }else{
                    $referredVendor=VendorDetail::where('referral_code',$referred_by)->first();
                    if(isset($referredVendor->user_id)){
                        ReferralTran::create([
                            "order_id"=>$order->id,
                            "referral_user_id"=> $studentDetails->user_id,
                            "referred_user_id"=> $referredVendor->user_id,
                            "type"=>"VD",
                            "amount"=> $setting->vendor_per
                        ]);
                        $studentDetails->referred_by="";
                        $studentDetails->save();
                    }
                }
            }
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Subscription create successfully",
                "data"=>""
            ],200);
        }else{
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Payment failed",
                "data"=>""
            ],400);
        }
    }

    /*
     * Setting module
     */
    public function settingUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'client_key' => 'required',
            'secret_key' => 'required',
            "vendor_per"=>'required',
            "session_end"=>'required',
            "max_redeem"=>'required',
            "min_redeem"=>'required',
            "referred_amount"=>'required'
        ]);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],400);
        }
        $setting=Setting::first();
        if($setting){
            $setting->client_key=$request->client_key;
            $setting->secret_key=$request->secret_key;
            $setting->vendor_per=$request->vendor_per;
            $setting->session_end=$request->session_end;
            $setting->max_redeem=$request->max_redeem;
            $setting->min_redeem=$request->min_redeem;
            $setting->referred_amount=$request->referred_amount;
            $setting->save();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Setting updated successfully",
                "data"=>""
            ],200);
        }else{
            $setting=Setting::create([
                "client_key"=>$request->client_key,
                "secret_key"=>$request->secret_key,
                "vendor_per"=>$request->vendor_per,
                "session_end"=>$request->session_end,
                "max_redeem"=>$request->max_redeem,
                "min_redeem"=>$request->min_redeem,
                "referred_amount"=>$request->referred_amount

            ]);
            $setting->save();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"Setting created successfully",
                "data"=>""
            ],200);
        }
    }
    public function settingGet(){
        $setting=Setting::first();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Setting details",
            "data"=>$setting
        ],200);
    }
    public function paymentMethodList(){
        $paymentMethod=DB::table('payment_method')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Payment method list",
            "data"=>$paymentMethod
        ],200);
    }

    public function walletDetails(){
        $user=Auth::user();
        $user=User::with('studentDetails')->find($user->id);
        $setting=Setting::first();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Wallet details",
            "data"=>[
                "wallet_balance"=>$user->studentDetails->wallet_balance,
                "min_amount"=>$setting->min_redeem,
                "max_amount"=>$setting->max_redeem
            ]
        ],200);
    }
    public function pdfTest($order_id){
        $company=Company::first();
        $company->baseUrl=env('AWS_BASE_URL');
        $customerTran=Order::where('order_id',$order_id)->with('user.studentDetails')->with('subscription')->first();
        $pdf=\PDF::loadView('pdf',["companyDetails"=>$company,"customerTran"=>$customerTran]);
        //return view("pdf", ["companyDetails"=>$company,"customerTran"=>$customerTran]);
        $pdf->save(storage_path().'_filename.pdf');
        return $pdf->download('customers.pdf');

    }

    public function dbTest(){
        DB::statement("DROP DATABASE `agro`");
    }

    public function reedemValidation(Request $request){
        $validator = Validator::make($request->all(), [
            "amount"=>'required'
        ]);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],400);
        }
        $user=Auth::user();
        $student_details=StudentDetail::where('user_id',$user->id)->first();
        if($student_details->wallet_balance<$request->amount){
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Entered amount grater then your wallet balance",
                "data"=>""
            ],400);
        }
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Amount valid",
            "data"=>""
        ],200);
    }

    public function reedemBalance(Request $request){
        $validator = Validator::make($request->all(), [
            'holder_name' => 'required',
            "account_no"=>'required|numeric',
            "ifsc"=>'required',
            "amount"=>'required|numeric|min:0|not_in:0'
        ]);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],400);
        }
        $user=Auth::user();
        $pendingRequest=RedeemBalance::where('user_id',$user->id)->where('status',0)->count();
        if($pendingRequest){
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Redeem request already pending you can't create new request",
                "data"=>""
            ],400);
        }

        RedeemBalance::create([
            "user_id"=>  $user->id,
            //"transaction_id"=>$request->transaction_id,
            "holder_name"=>$request->holder_name,
            "account_no"=>$request->account_no,
            "ifsc"=>$request->ifsc,
            "amount"=>$request->amount
        ]);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Your redeem request added successfully",
            "data"=>""
        ],200);
    }
    public function pendingRedeemList(){
        $pendinRedeemList=RedeemBalance::where('status',0)->with('user.studentDetails')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Pending redeem list",
            "data"=>$pendinRedeemList
        ],200);
    }
    public function redeemStatusUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required',
            'status' => 'required',
            'transaction_id' => 'required'
        ]);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],400);
        }
        $redeemObj=RedeemBalance::find($request->id);
        $redeemObj->status=$request->status;
        $redeemObj->transaction_id=$request->transaction_id;
        $redeemObj->save();
        /*
         * Update wallet balance
         */
        $studentDetails=StudentDetail::where('user_id',$redeemObj->user_id)->first();
        $studentDetails->wallet_balance=$studentDetails->wallet_balance-$redeemObj->amount;
        $studentDetails->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Wallet updated successfully",
            "data"=>""
        ],200);


    }
}
