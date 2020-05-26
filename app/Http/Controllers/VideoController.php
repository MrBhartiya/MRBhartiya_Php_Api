<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\StudentDetail;
use App\Modal\Subject;
use App\Modal\Teacher;
use Illuminate\Http\Request;
use App\Modal\FavouriteVideo;
use App\Modal\LikedVideo;
use App\Modal\Topic;
use App\Modal\TopicVideo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; 

class VideoController extends Controller
{
    //
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'topic_id' => 'required',
            'teacher_id' => 'required',
            'title' => 'required',
            'type' => 'required',
            'video' => 'required',
            'document' => 'required',
            'video_description' => 'required',
            'document_description' => 'required'
        ]);
 
        /*
         * Max 3 demo video for subject wise validation
         */
        if($request->type=="demo"){
            $subject_id=$request->subject_id;
            $videoCount=self::demoVideoValidation($subject_id);
            if($videoCount>2){
                return Response::json([
                    "status_code"=>400,
                    "status"=>false,
                    "message"=>"You can't upload more then 3 demo videos",
                    "data"=>""
                ],400);
            }
        }

        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ],400);
        }
        /*
         * Video and pdf extension validation
         */
        $flag=true;
        if($request->has('video') && isset($request->video) && $request->video!=""){
            $video = $request->file('video');
            $extension =$video->getClientOriginalExtension();
            if(!in_array($extension, ['webm','mkv','avi','mp4'])) {
                $flag=false;
            }
        }
        if($request->has('document') && isset($request->document) && $request->document!=""){
            $dcoument = $request->file('document');
            $extension =$dcoument->getClientOriginalExtension();
            if(!in_array($extension, ['pdf'])) {
                $flag=false;
            }
        }
        if($flag==false) {
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Video extension webm,mkv,avi,mp4 required and document required only pdf",
                "data"=>""
            ],400);
        }

        /*
         * Chapter id to get all name list
         */
        $topic=Topic::find($request->topic_id);
        $test=$topic->with('chapter.subject.classes')->orderBy('id','DESC')->first();
        $topicFolder=$test->topic_name;
        $chapterFolder=$test->chapter->chapter_name;
        $subjectFolder=$test->chapter->subject->subject_name;
        $classFolder=$test->chapter->subject->classes->class_name;
        $folderVideo=$classFolder."/".$subjectFolder."/".$chapterFolder."/".$topicFolder."/video/";
        $folderDocument=$classFolder."/".$subjectFolder."/".$chapterFolder."/".$topicFolder."/document/";
        $videoPath="";
        if($request->has('video')){
            $videoObj = $request->file('video');
            $timestamp = now()->timestamp;
            $videoPath=$folderVideo.$timestamp.$videoObj->getClientOriginalName();
            Storage::disk('s3')->put($videoPath, file_get_contents($videoObj));
        }

        $documentPath="";
        if($request->has('document')){
            $documentObj=$request->file('document');
            $timestamp = now()->timestamp;
            $documentPath=$folderDocument.$timestamp.$documentObj->getClientOriginalName();
            Storage::disk('s3')->put($documentPath, file_get_contents($documentObj));
        }
        $videoThumbnailPath="";
        if($request->has('thumbnail')){
            $thumbnailObj=$request->file('thumbnail');
            $timestamp = now()->timestamp;
            $videoThumbnailPath=$folderDocument.$timestamp.$thumbnailObj->getClientOriginalName();
            Storage::disk('s3')->put($videoThumbnailPath, file_get_contents($thumbnailObj));
        }
        TopicVideo::create([
            'video_id'=>AppHelper::random_strings(6),
            'topic_id'=>$request->topic_id,
            'teacher_id'=>$request->teacher_id,
            'title'=>$request->title,
            'type'=>$request->type,
            'video_url'=>$videoPath,
            'document_url'=>$documentPath,
            'thumbnail'=>$videoThumbnailPath,
            'video_description' =>$request->video_description,
            'document_description' =>$request->document_description,
        ]);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Video create successfully",
            "data"=>""
        ],200);
    }


    public function delete(Request $request){
        $id=$request->id;
        $topicVideo=TopicVideo::find($id);
        $topicVideo->delete();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Video delete successfully",
            "data"=>""
        ],200);
    }

    public function list(){
        $videoList=TopicVideo::with('topic.chapter.subject.classes')->with('topic.chapter.subject.teacher')->with('teacherVideo')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Video list",
            "data"=>$videoList
        ],200);
    }
    public function studentVideoList(){
        $studentDetails=AppHelper::studentDetails();
        $userSubscription=AppHelper::SubscriptionCheck();
        if($userSubscription){
            $videoList=TopicVideo::with(['topic.chapter.subject.classes'=>function($q) use ($studentDetails){
                $q->where('class_name',$studentDetails->class);
            }])->with('topic.chapter.subject.teacher')->has('topic.chapter.subject.classes')->get();
        }else{
            $videoList=TopicVideo::with(['topic.chapter.subject.classes'=>function($q) use ($studentDetails){
                $q->where('class_name',$studentDetails->class);
            }])->with('topic.chapter.subject.teacher')->has('topic.chapter.subject.classes')->where('type','demo')->get();
        }

        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Video list",
            "data"=>$videoList
        ],200);
    }
    public function studentVideoListById($id){
        $user=Auth::user();
        $studentDetails=AppHelper::studentDetails();
        $videoList=TopicVideo::with(['topic.chapter.subject.classes'=>function($q) use ($studentDetails){
            $q->where('class_name',$studentDetails->class);
        }])->with('topic.chapter.subject.teacher')->with('quiz.quiz_question')->has('topic.chapter.subject.classes')->find($id);
        if($videoList->quiz==null || $videoList->quiz=='null'){
            unset($videoList->quiz);
$videoList->quiz=(object)[];
        }
        if(!isset($videoList->quiz)){ 
$videoList->quiz=(object)[];
        }
            /*
             * Fav video
             */
            $countFav=FavouriteVideo::where('video_id',$videoList->id)->where('user_id',$user->id)->count();
            if($countFav){
                $videoList->fav=1;
            }else{
                $videoList->fav=0;
            }
            /*
             * Like video
             */
            $countLike=LikedVideo::where('video_id',$videoList->id)->where('user_id',$user->id)->count();
            if($countLike){
                $videoList->like=1;
            }else{
                $videoList->like=0;
            }
        $upComing=TopicVideo::with(['topic.chapter.subject.classes'=>function($q) use ($studentDetails){
            $q->where('class_name',$studentDetails->class);
        }])->with('topic.chapter.subject.teacher')->has('topic.chapter.subject.classes')->where('id','!=',$id)->limit(4)->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Video list",
            "data"=>["video"=>$videoList,"upcoming"=>$upComing]
        ],200);
    }
    public function objectById($id){
        //with('topic.chapter.subject.classes')->with('topic.chapter.subject.teacher')
        $videoList=TopicVideo::with('quiz.quiz_question')->with('topic.chapter.subject.classes')->with('topic.chapter.subject.teacher')->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Video list",
            "data"=>$videoList
        ],200);
    }

    public function edit(Request $request){
        $validator = Validator::make($request->all(), [
            'topic_id' => 'required',
            'teacher_id' => 'required',
            'title' => 'required',
            'type' => 'required',
            'video_description' => 'required',
            'document_description' => 'required'
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





        /*
         * Video and pdf extension validation
         */
        $flag=true;
        if($request->has('video') && isset($request->video) && $request->video!=""){
            $video = $request->file('video');
            $extension =$video->getClientOriginalExtension();
            if(!in_array($extension, ['webm','mkv','avi','mp4'])) {
                $flag=false;
            }
        }
        if($request->has('document') && isset($request->document) && $request->document!=""){
            $dcoument = $request->file('document');
            $extension =$dcoument->getClientOriginalExtension();
            if(!in_array($extension, ['pdf'])) {
                $flag=false;
            }
        }
        if($flag==false) {
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Video extension webm,mkv,avi,mp4 required and document required only pdf",
                "data"=>""
            ],400);
        }
        /*
         * Chapter id to get all name list
         */
        $topic=Topic::find($request->topic_id);

        $test=$topic->with('chapter.subject.classes')->orderBy('id','DESC')->first();
        $topicFolder=$test->topic_name;
        $chapterFolder=$test->chapter->chapter_name;
        $subjectFolder=$test->chapter->subject->subject_name;
        $classFolder=$test->chapter->subject->classes->class_name;
        $folderVideo=$classFolder."/".$subjectFolder."/".$chapterFolder."/".$topicFolder."/video/";
        $folderDocument=$classFolder."/".$subjectFolder."/".$chapterFolder."/".$topicFolder."/document/";
        $topicVideo=TopicVideo::find($request->id);
        if($topicVideo->type!="demo" && $request->type=="demo"){
            $subject_id=$request->subject_id;
            $videoCount=self::demoVideoValidation($subject_id);
            if($videoCount>2){
                return Response::json([
                    "status_code"=>400,
                    "status"=>false,
                    "message"=>"You can't upload more then 3 demo videos",
                    "data"=>""
                ],400);
            }
        }
        $videoPath="";
        if($request->has('video') && $request->video){
            $videoObj = $request->file('video');
            $timestamp = now()->timestamp;
            $videoPath=$folderVideo.$timestamp.$videoObj->getClientOriginalName();
            Storage::disk('s3')->put($videoPath, file_get_contents($videoObj));
            if(Storage::disk('s3')->exists($topicVideo->video_url)) {
                Storage::disk('s3')->delete($topicVideo->video_url);
            }
        }

        $documentPath="";
        if($request->has('document') && $request->document){
            $documentObj=$request->file('document');
            $timestamp = now()->timestamp;
            $documentPath=$folderDocument.$timestamp.$documentObj->getClientOriginalName();
            Storage::disk('s3')->put($documentPath, file_get_contents($documentObj));
            if(Storage::disk('s3')->exists($topicVideo->document_url)) {
                Storage::disk('s3')->delete($topicVideo->document_url);
            }
        }
        $videoThumbnail="";
        if($request->has('thumbnail') && $request->thumbnail){
            $thumbnailObj=$request->file('thumbnail');
            $timestamp = now()->timestamp;
            $videoThumbnail=$folderDocument.$timestamp.$thumbnailObj->getClientOriginalName();
            Storage::disk('s3')->put($videoThumbnail, file_get_contents($thumbnailObj));
            if(Storage::disk('s3')->exists($topicVideo->document_url)) {
                Storage::disk('s3')->delete($topicVideo->document_url);
            }
        }
        $topicVideo->teacher_id=$request->teacher_id;
        $topicVideo->title=$request->title;
        $topicVideo->type=$request->type;
        $topicVideo->video_description=$request->video_description;
        $topicVideo->document_description=$request->document_description;
        if($videoPath){
            $topicVideo->video_url=$videoPath;
        }
        if($documentPath){
            $topicVideo->document_url=$documentPath;
        }
        if($videoThumbnail){
            $topicVideo->thumbnail=$videoThumbnail;
        }
        $topicVideo->save();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Video update successfully",
            "data"=>""
        ],200);
    }
    public function teachersVideo(){
        /*
         * Student wise class check
         */
        $user_id=Auth::user()->id;
        $studentDetails=StudentDetail::where('user_id',$user_id)->first();
        $classStudent=$studentDetails->class;
        $teachersVideo=Teacher::with(['video.topic.chapter.subject.classes'=> function($q) use ($classStudent) {
            $q->where('class_name',$classStudent);
        }])->has('video.topic.chapter.subject.classes')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Teachers video",
            "data"=>$teachersVideo
        ],200);
    }
    public function topicToVideo($topic_id){
        $topicVideos=TopicVideo::where('topic_id',$topic_id)->get();
        $user=Auth::user();
        foreach ($topicVideos as $video){
            /*
                        * Fav video
                        */
            $countFav=FavouriteVideo::where('video_id',$video->id)->where('user_id',$user->id)->count();
            if($countFav){
                $video->fav=1;
            }else{
                $video->fav=0;
            }
            /*
             * Like video
             */
            $countLike=LikedVideo::where('video_id',$video->id)->where('user_id',$user->id)->count();
            if($countLike){
                $video->like=1;
            }else{
                $video->like=0;
            }
        }
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Topic to video",
            "data"=>$topicVideos
        ],200);
    }
    public function teacherByVideo($id){
        $userSubscription=AppHelper::SubscriptionCheck();
        if($userSubscription){
            $videos=TopicVideo::where("teacher_id",$id)->get();
        }else{
            $videos=TopicVideo::where("teacher_id",$id)->where('type','demo')->get();
        }
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Teacher by video",
            "data"=>$videos
        ],200);
    }
    public static function demoVideoValidation($subject_id){
        $videoCount=DB::table('subjects')
            ->join('chapters','chapters.subject_id','subjects.id')
            ->join('topics','topics.chapter_id','chapters.id')
            ->join('topic_videos','topics.id','topic_videos.topic_id')
            ->where('subjects.id',$subject_id)->where('topic_videos.type','demo')->count();
        return $videoCount;
    }
}
