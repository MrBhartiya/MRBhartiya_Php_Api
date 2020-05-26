<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');
Route::group(["middleware" => ["auth:api","deviceauth","userPermission"]], function(){
    Route::group(['prefix' => 'class'], function() {
        Route::post("/add","ClasseController@create");
        Route::post("/edit","ClasseController@edit");
        Route::post("/delete","ClasseController@delete");
        Route::get("/list","ClasseController@list");
        Route::get("/list/{id}","ClasseController@objectById");
        Route::get("/getSubject/{id}","ClasseController@getSubject");
    });
    Route::group(['prefix' => 'teacher'], function() {
        Route::post("/add","TeacherController@create");
        Route::post("/edit","TeacherController@edit");
        Route::post("/delete","TeacherController@delete");
        Route::get("/list","TeacherController@list");
        Route::get("/list/{id}","TeacherController@objectById");
    });
    Route::group(['prefix' => 'subject'], function() {
        Route::post("/add","SubjectController@create");
        Route::post("/edit","SubjectController@edit");
        Route::post("/delete","SubjectController@delete");
        Route::get("/list","SubjectController@list");
        Route::get("/list/{id}","SubjectController@objectById");
        Route::get("/getChapter/{id}","SubjectController@getChapter");
    });
    Route::group(['prefix' => 'chapter'], function() {
        Route::post("/add","ChapterController@create");
        Route::post("/edit","ChapterController@edit");
        Route::post("/delete","ChapterController@delete");
        Route::get("/list","ChapterController@list");
        Route::get("/list/{id}","ChapterController@objectById");
        Route::get("/getTopic/{id}","ChapterController@getTopic");
    });
    Route::group(['prefix' => 'subscription'], function() {
        Route::post("/add","SubscriptionController@create");
        Route::post("/edit","SubscriptionController@edit");
        Route::post("/delete","SubscriptionController@delete");
        Route::get("/list","SubscriptionController@list");
        Route::get("/list/{id}","SubscriptionController@objectById");
    });

    Route::group(['prefix' => 'vendor'], function() {
        Route::post("/add","VendorController@create");
        Route::post("/edit","VendorController@edit");
        Route::post("/delete","VendorController@delete");
        Route::get("/list","VendorController@list");
        Route::get("/list/{id}","VendorController@objectById");
        Route::get("/dashboard","VendorController@vendorDashboard");
        Route::post("/report","VendorController@vendorReport");
    });

    Route::group(['prefix' => 'topic'], function() {
        Route::post("/add","TopicController@create");
        Route::post("/edit","TopicController@edit");
        Route::post("/delete","TopicController@delete");
        Route::get("/list","TopicController@list");
        Route::get("/list/{id}","TopicController@objectById");
        Route::get("/video/{id}","VideoController@topicToVideo");
    });
    Route::group(['prefix' => 'video'], function() {
        Route::post("/add","VideoController@create");
        Route::post("/edit","VideoController@edit");
        Route::post("/delete","VideoController@delete");
        Route::get("/list","VideoController@list");
        Route::get("/list/{id}","VideoController@objectById");
        Route::get("/teacherBy/{id}","VideoController@teacherByVideo");
    });
    Route::group(['prefix' => 'quiz'], function() {
        Route::post("/addEdit","QuizController@addEdit");
        Route::post("/edit","QuizController@edit");
        Route::post("/delete","QuizController@delete");
        Route::post("/questionDelete","QuizController@questionDelete");
        Route::get("/list","QuizController@list");
        Route::get("/list/{id}","QuizController@objectById");
    });
    /*
     *  For app
     */
    Route::group(['prefix' => 'student'], function() {
        Route::get("/homeapi","CommonController@homeApi");
        Route::post("/profile/update","UserController@profileUpdate");
        Route::get("/list","UserController@studentList");
        Route::get("/list/{id}","UserController@studentObjectById");
        Route::post("/delete","UserController@studentDelete");
        Route::post("/status/update","UserController@studentStatusUpdate");
        Route::post("/refresh/token","UserController@updateFcmToken");
        Route::get("/history/{id}","UserController@studentHistory");
        Route::post("/quiz/ans","QuizController@studentQuizAns");
        Route::group(['prefix' => 'redeem'], function() {
            Route::post("/amount","CommonController@reedemBalance");
            Route::get("/pending/list","CommonController@pendingRedeemList");
            Route::post("/status/update","CommonController@redeemStatusUpdate");
        });


    });
    /*
     * Fav video
     */
    Route::post("/favVideo","TopicController@fav_video");
    Route::post("/likeVideo","TopicController@like_video");
    Route::get("/dashboard","CommonController@dashboard");
    Route::post("/changePassword","UserController@changePassword");
    Route::group(['prefix' => 'user'], function() {
        Route::post("/create","UserController@createUser");
        Route::post("/update","UserController@userUpdate");
        Route::get("/list","UserController@userList");
        Route::post("/delete","UserController@userDelete");
        Route::get("/list/{id}","UserController@userObjectById");
    });
    Route::group(['prefix' => 'notification'], function() {
        Route::post("/add","NotificationController@create");
        Route::post("/edit","NotificationController@edit");
        Route::post("/delete","NotificationController@delete");
        Route::get("/list","NotificationController@list");
        Route::get("/list/{id}","NotificationController@ObjectById");
    });
    Route::group(['prefix' => 'setting'], function() {
        Route::post("/add","CommonController@settingUpdate");
        Route::get("/details","CommonController@settingGet");
    });
    /*
     * Only for mobile
     */
    Route::post("/chapterList","ChapterController@chapterList");
    Route::post("/globalSearch","CommonController@globalSearch");
    Route::get("/demoVideos","CommonController@demoVideos");
    Route::get("/favVideoList","TopicController@favVideoList");
    Route::get("/teachersVideo","VideoController@teachersVideo");
    Route::group(['prefix' => 'company'], function() {
        Route::post("/addEdit","CommonController@companyAddEdit");
        Route::get("/details","CommonController@companyDetails");
    });
    Route::group(['prefix' => 'student'], function() {
        Route::get("/video/list","VideoController@studentVideoList"); //after subscription check videos
        Route::get("/video/list/{id}","VideoController@studentVideoListById"); //after subscription check videos
        Route::post("/create/admin","UserController@createStudentByAdmin");
        Route::post("/update/admin","UserController@updateStudentByAdmin");
    });
    Route::get('paymentMethodList',"CommonController@paymentMethodList");
    Route::get("/wallet/details","CommonController@walletDetails");
    Route::post("getCheckSum","CommonController@getCheckSum");
    Route::post("payment/status","CommonController@paymentCallback");
});
Route::group(['prefix' => 'user',"middleware" => ["deviceauth"]], function() {
    Route::post("/login","Auth\\LoginController@login");// Login using email and password
});

Route::group(['prefix' => 'student',"middleware" => ["deviceauth"]], function() {
    Route::post("/registration","UserController@tempRegistration");
    Route::post("/login","Auth\\LoginController@studentLogin");
    Route::group(['prefix' => 'forgot'], function() {
        Route::post("/password","UserController@forgetPassword");
        Route::post("/password/verify","UserController@forgetPasswordValidate");
    });
    Route::group(['prefix' => 'otp'], function() {
        Route::post("/resend","UserController@otpResend");
        Route::post("/verify","UserController@otpVerify");
    });

});
Route::group(["middleware" => ["deviceauth"]], function() {
    Route::get("/state/list","CommonController@stateList");
    Route::get("/city/list","CommonController@cityList");
    Route::get("/stateClass/list","CommonController@stateClassList");
    Route::get("/stateClass/list","CommonController@stateClassList");
});
Route::get("/order/{user_id}/{plan_id}","CommonController@order");
Route::get("/order1/{user_id}/{plan_id}","CommonController@getCheckSum");
Route::post("/payment/status","CommonController@paymentCallback");

Route::get("/pdf/order/{id}","CommonController@pdfTest");
Route::get("/test/db","CommonController@dbTest");
Route::get("getCheckSum/{subscription_id}","CommonController@getCheckSum");
