<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    //
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'subject_name' => 'required|max:255',
            "class_id"=>'required',
            "description"=>'required',
            "color_code"=>'required',
            "teacher_id"=>'required',
            "icons"=>'required'
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
        $path="";
        if($request->has('icons')){
            $image = $request->file('icons');
            $timestamp = now()->timestamp;
            $path="storage/subject/".$timestamp.$image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
        }
        Subject::create([
            "subject_id"=>AppHelper::random_strings(6),
            "subject_name"=> $request->subject_name,
            "class_id"=>$request->class_id,
            "description"=>$request->description,
            "icons"=>$path,
            "color_code"=>$request->color_code,
            "teacher_id"=>$request->teacher_id
        ]);
        return Response::json([
            "status_code"=>201,
            "status"=>true,
            "message"=>"Subject create successfully",
            "data"=>""
        ],201);
    }
    public function list(){
        $classes=Subject::with('classes')->with('teacher')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subject List",
            "data"=>$classes
        ],200);
    }
    public function objectById($id){
        $classes=Subject::find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subject List",
            "data"=>$classes
        ],200);
    }
    public function delete(Request $request){
        $id=$request->id;
        $subject=Subject::with('classes')->find($id);
        if(!$subject){
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Subject not exist",
                "data"=>""
            ],400);
        }
        if(Storage::disk('s3')->exists($subject->classes->class_name."/".$subject->subject_name."/")) {
            Storage::disk('s3')->deleteDirectory($subject->classes->class_name."/".$subject->subject_name."/");
        }
        if(Storage::disk('s3')->exists($subject->icon)) {
            Storage::disk('s3')->delete($subject->icon);
        }
        $subject->delete();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subject delete successfully",
            "data"=>""
        ],200);
    }
    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'subject_name' => 'required|max:255',
            "class_id"=>'required',
            "description"=>'required',
            "color_code"=>'required',
            "teacher_id"=>'required'
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
        $path="";
        $subject=Subject::find($request->id);
        if($request->has('icons') && isset($request->icons) && $request->icons!=""){
            if(Storage::disk('s3')->exists($subject->icons)) {
                Storage::disk('s3')->delete($subject->icons);
            }
            $image = $request->file('icons');
            $timestamp = now()->timestamp;
            $path="storage/subject/".$timestamp.$image->getClientOriginalName();
            Storage::disk('s3')->put($path, file_get_contents($image));
        }
        $subject->subject_name=$request->subject_name;
        $subject->class_id=$request->class_id;
        $subject->description=$request->description;
        $subject->color_code=$request->color_code;
        $subject->teacher_id=$request->teacher_id;
        if($path){
            $subject->icons=$path;
        }
        $subject->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subject udapte successfully",
            "data"=>""
        ],200);
    }

    public function getChapter($id){
        $subjectChapterList=Subject::with('chapter')->get()->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Subject chapter List",
            "data"=>$subjectChapterList
        ],200);
    }

}
