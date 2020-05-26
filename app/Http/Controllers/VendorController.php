<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\Role;
use App\Modal\VendorDetail;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class VendorController extends Controller
{
    //
    public function create(Request $request){
        $validate = AppHelper::Validation($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            "mobile_no" => 'required|unique:users|numeric|phone_number',
            "city" => 'required',
            "type" => 'required',
            "state" => 'required',
            "address"=>'required',
            "password" => 'required|min:6'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $user=User::create([
           "name"=>$request->name,
           "email"=>$request->email,
           "mobile_no"=>$request->mobile_no,
           "password"=>bcrypt($request->password),
           "role"=>"VD",
           "status"=>1
        ]);
        VendorDetail::create([
            "user_id"=>$user->id,
            "type"=>$request->type,
            "address"=>$request->address,
            "city"=>$request->city,
            "state"=>$request->state,
            "referral_code"=>AppHelper::random_strings(6)
        ]);
        /*
         *  Role assign
         */
        $role = Role::where('name', 'Vendor')->first();
        DB::table('role_user')->insert(
            ['user_id' => $user->id, 'role_id' => $role->id]
        );
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Vendor create successfully",
            "data"=>""
        ],200);
    }
    public function edit(Request $request){
        $validate = AppHelper::Validation($request, [
            'name' => 'required|max:255',
            "city" => 'required',
            "email" => 'required|max:255|unique:users,name,'."$request->user_id",
            "type" => 'required',
            "state" => 'required',
            "address"=>'required',
            "id"=>'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        /*
         * Vendor update
         */
        $vendor=VendorDetail::where('user_id',$request->id)->first();
        $vendor->type=$request->type;
        $vendor->address=$request->address;
        $vendor->city=$request->city;
        $vendor->state=$request->state;
        $vendor->save();
        /*
         * User details update
         */
        $user=User::find($request->id);
        $user->name=$request->name;
        $user->email=$request->email;
        $user->mobile_no=$request->mobile_no;
        if(isset($request->password)){
            $user->password=bcrypt($request->password);
        }
        $user->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Vendor updated successfully",
            "data"=>""
        ],200);
    }
    public function delete(Request $request){
        $validate = AppHelper::Validation($request, [
            "id"=>'required'
        ]);
        if (isset($validate['status'])) {
            return Response::json($validate,400);
        }
        $vendor=VendorDetail::where('user_id',$request->id)->first();
        $user=User::find($request->id);
        if(!$user){
            return Response::json([
                "status_code"=>400,
                "status"=>true,
                "message"=>"Vendor not exist",
                "data"=>""
            ],400);
        }
        $user->delete();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Vendor deleted successfully",
            "data"=>""
        ],200);
    }
    public function objectById($id){
        $vendor=User::with('vendorDetails')->has('vendorDetails')->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Vendor",
            "data"=>$vendor
        ],200);
    }
    public function list(){
        $vendor=User::with('vendorDetails')->has('vendorDetails')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Vendor List",
            "data"=>$vendor
        ],200);
    }
    public function vendorDashboard(){
        $vendor_id=Auth::user()->id;
        $referralUsers=User::with('referral.order')->whereHas('referral',function($query) use ($vendor_id){
            $query->where('referred_user_id',$vendor_id);
        })->has('referral.order')->where('role','SD')->get();
        $vendorReferral=[];
        foreach ($referralUsers as $referralUser){
            $tempObj = new \stdClass();
            $tempObj->id=$referralUser->id;
            $tempObj->name=$referralUser->name;
            $tempObj->mobile_no=$referralUser->mobile_no;
            $tempObj->email=$referralUser->email;
            $amount=$referralUser->referral->order->amount;
            $vendor_per=$referralUser->referral->amount;
            $commision=($amount*$vendor_per)/100;
            $tempObj->amount=$amount;
            $tempObj->vendor_percentage=$vendor_per;
            $tempObj->commision=$commision;
            array_push($vendorReferral,$tempObj);
        }
        $vendor_details=User::with('vendorDetails')->where('role','VD')->find($vendor_id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Student commision",
            "data"=>["commision_details"=>$vendorReferral,"vendor_details"=>$vendor_details]
        ],200);
        
    }
    public function vendorReport(Request $request){
        if($request->vendor=="All"){
            //->has('vendorDetails.referralTran.order.user')->has('vendorDetails.referralTran.order.subscription')
            $vendor=User::with('vendorDetails.referralTran.order.user')->with('vendorDetails.referralTran.order.subscription')
                ->where('role','VD')->get();
        }else{
            $vendor=User::with('vendorDetails.referralTran.order.user')
                ->with('vendorDetails.referralTran.order.subscription')
                //->has('vendorDetails.referralTran.order.user')->has('vendorDetails.referralTran.order.subscription')
                ->find($request->vendor);
        }
        if($vendor==null){
            $vendor=[];
        }
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Student commision",
            "data"=>$vendor
        ],200);

    }
}
