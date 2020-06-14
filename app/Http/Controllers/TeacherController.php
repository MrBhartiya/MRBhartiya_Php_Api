<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\Classe;
use App\Modal\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    //
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'teacher_name' => 'required|max:255',
            "education"=>'required',
            "display"=>'required',
            "teacher_image"=>'required'
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
        if($request->has('teacher_image') && isset($request->teacher_image) && $request->teacher_image!=""){
            $image = $request->file('teacher_image');
            $timestamp = now()->timestamp;
            $path="storage/teacher/".$timestamp.$image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
        }
        if(!isset($path)){
            $path="";
        }
        Teacher::create([
            "teacher_id"=>AppHelper::random_strings(6),
            "teacher_name"=> $request->teacher_name,
            "teacher_image"=>$path,
            "education"=>$request->education,
            "display"=>$request->display
        ]);
        return Response::json([
            "status_code"=>201,
            "status"=>true,
            "message"=>"Teacher create successfully",
            "data"=>""
        ],201);
    }
    public function list(){
        $classes=Teacher::get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"teacher List",
            "data"=>$classes
        ],200);
    }
    public function objectById($id){
        $classes=Teacher::find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"teacher List",
            "data"=>$classes
        ],200);
    }
    public function delete(Request $request){
        $id=$request->id;
        $class=Teacher::find($id);
        $class->delete();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Teacher delete successfully",
            "result"=>""
        ],200);
    }
    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'teacher_name' => 'required|max:255',
            "education"=>'required',
            "display"=>'required'
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

        $teacher=Teacher::find($request->id);
        $teacher->teacher_name=$request->teacher_name;
        
        if($request->has('teacher_image') && isset($request->teacher_image) && $request->teacher_image!=""){
            $image = $request->file('teacher_image');
            $timestamp = now()->timestamp;
            $path="storage/teacher/".$timestamp.$image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
            if(Storage::disk('s3')->exists($teacher->teacher_image)) {
                Storage::disk('s3')->delete($teacher->teacher_image);
            }
        }
        if(isset($path)){
            $teacher->teacher_image=$path;
        }
        $teacher->education=$request->education;
        $teacher->display=$request->display;
        $teacher->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Teacher udapte successfully",
            "data"=>""
        ],200);
    }

}
