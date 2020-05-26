<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Modal\Quiz;
use App\Modal\QuizQuestion;
use App\Modal\StudentQuizAns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
class QuizController extends Controller
{
    //
    public function addEdit(Request $request){
        $validator = $this->validation($request);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ]);
        }
        $quiz=Quiz::where('video_id',$request->video_id)->first();
        if($quiz){
            $quiz->title=$request->title;
            $quiz->save();

        }else{
            $quiz=Quiz::create([
                "quiz_id"=>AppHelper::random_strings(6),
                "video_id"=>$request->video_id,
                "title"=>$request->title
            ]);
        }
        foreach ($request->questions as $question){
            $question=(object)$question;
            if(isset($question->id) && $question->id){
             $questionExist=QuizQuestion::find($question->id);
             $questionExist->question= $question->question;
             $questionExist->a=$question->a;
             $questionExist->b=$question->b;
             $questionExist->c=$question->c;
             $questionExist->d=$question->d;
             $questionExist->ans=$question->ans;
             $questionExist->description=$question->description;
             $questionExist->save();
            }else{
                QuizQuestion::create([
                    "quiz_id"=> $quiz->id,
                    "question"=> $question->question,
                    "a"=>$question->a,
                    "b"=>$question->b,
                    "c"=>$question->c,
                    "d"=>$question->d,
                    "ans"=>$question->ans,
                    "description"=>$question->description
                ]);
            }
        }

        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"quiz create successfully",
            "data"=>""
        ],200);
    }
    public function list(){
        $quiz=Quiz::with('quiz_question')->with('TopicVideo.topic.chapter.subject.classes')->with('TopicVideo.topic.chapter.subject.teacher')->get();
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Quiz List",
            "data"=>$quiz
        ],200);
    }
    public function objectById($id){
        $quizObj=Quiz::with('quiz_question')->with('TopicVideo.topic.chapter.subject.classes')->with('TopicVideo.topic.chapter.subject.teacher')->find($id);
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Quiz Object",
            "data"=>$quizObj
        ],200);
    }

    public function delete(Request $request){
        $quiz=Quiz::find($request->id);
        if($quiz){
            $quiz->delete();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"quiz delete successfully",
                "data"=>""
            ],200);
        }
        return Response::json([
            "status_code"=>400,
            "status"=>true,
            "message"=>"quiz not exist",
            "data"=>""
        ],400);
    }

    public function questionDelete(Request $request){
        $question=QuizQuestion::find($request->id);
        if($question){
            $question->delete();
            return Response::json([
                "status_code"=>200,
                "status"=>true,
                "message"=>"question delete successfully",
                "data"=>""
            ],200);
        }
        return Response::json([
            "status_code"=>400,
            "status"=>true,
            "message"=>"question not exist",
            "data"=>""
        ],400);
    }
    public function update(Request $request){
        $validator = $this->validation($request);
        if($validator->fails()){
            $validateMessage=$validator->errors()->messages();
            return Response::json([
                "status_code"=>400,
                "status"=>false,
                "message"=>"Required field missing",
                "data"=>$validateMessage
            ]);
        }
        $quiz=Quiz::find($request->id);
        $quiz->title=$request->title;
        $quiz->save();
        foreach ($request->questions as $question){
            $question=(object)$question;
            if(isset($question->id)){
                $questionUpdate=QuizQuestion::find($question->id);
                $questionUpdate->question=$question->question;
                $questionUpdate->a=$question->a;
                $questionUpdate->b=$question->b;
                $questionUpdate->c=$question->c;
                $questionUpdate->d=$question->d;
                $questionUpdate->ans=$question->ans;
                $questionUpdate->description=$question->description;
                $questionUpdate->save();
            }else{
                QuizQuestion::create([
                    "quiz_id"=> $quiz->id,
                    "question"=> $question->question,
                    "a"=>$question->a,
                    "b"=>$question->b,
                    "c"=>$question->c,
                    "d"=>$question->d,
                    "ans"=>$question->ans,
                    "description"=>$question->description
                ]);
            }
        }

        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"quiz create successfully",
            "data"=>""
        ],200);
    }

    public function studentQuizAns(Request $request){
        /*
         * ans :-  {1:"a",2:"b",3:"c"}
         */
        $quiz_id=$request->quiz_id;
        $questionCount=QuizQuestion::where('quiz_id',$quiz_id)->count();
        $user=Auth::user();
        $correctAns=0;
        foreach ($request->ans as $key=>$value){
            $correctAnsObj=QuizQuestion::where('id',$key)->first();
            if(isset($correctAnsObj->ans)){
                StudentQuizAns::create([
                    "user_id"=>$user->id,
                    "question_id"=>$key,
                    "ans"=>$value,
                    "correct_ans"=>$correctAnsObj->ans
                ]);
                if($correctAnsObj->ans==$value){
                    $correctAns++;
                }
            }
        }
        return Response::json([
            "status_code"=>200,
            "status"=>true,
            "message"=>"Quiz result",
            "data"=>[
                "questions"=>$questionCount,
                "correct"=>$correctAns
            ]
        ],200);
    }


    public function validation($request){
        $validate=Validator::make($request->all(), [
            'video_id' => 'required',
            'title'=>'required'
        ]);
        return $validate;
    }
}
