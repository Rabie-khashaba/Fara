<?php

use App\Enums\SocialAuthProvider;
use App\Http\Controllers\Api\AppUserActivityController;
use App\Http\Controllers\Api\AppUserAuthController;
use App\Http\Controllers\Api\AppUserChatController;
use App\Http\Controllers\Api\AppUserCheckInController;
use App\Http\Controllers\Api\AppUserFollowController;
use App\Http\Controllers\Api\AppUserPostCommentController;
use App\Http\Controllers\Api\AppUserPostController;
use App\Http\Controllers\Api\AppUserPostLikeController;
use App\Http\Controllers\Api\AppUserProfileController;
use App\Http\Controllers\Api\AppUserSharedPostController;
use App\Http\Controllers\Api\AppUserSocialAuthController;
use App\Http\Controllers\Api\FirebaseNotificationController;

use Illuminate\Support\Facades\Route;

Route::prefix('app-user/auth')->group(function (): void {
    Route::post('register', [AppUserAuthController::class, 'register']);
    Route::post('verify-register-otp', [AppUserAuthController::class, 'verifyRegisterOtp']);
    Route::post('resend-register-otp', [AppUserAuthController::class, 'resendRegisterOtp']);
    Route::post('login', [AppUserAuthController::class, 'login']);


    Route::post('social-login', [AppUserAuthController::class, 'socialLogin']);
    Route::get('{provider}/redirect', [AppUserSocialAuthController::class, 'redirect'])
        ->whereIn('provider', SocialAuthProvider::values());
    Route::get('{provider}/callback', [AppUserSocialAuthController::class, 'callback'])
        ->whereIn('provider', SocialAuthProvider::values());


    Route::post('forgot-password', [AppUserAuthController::class, 'forgotPassword']);
    Route::post('forgot-password-verify-otp', [AppUserAuthController::class, 'forgotPasswordVerifyOtp']);
    Route::post('reset-password', [AppUserAuthController::class, 'resetPassword']);
});


Route::middleware('auth:sanctum')->prefix('app-user/auth')->group(function (): void {
    Route::post('logout', [AppUserAuthController::class, 'logout']);
});

Route::get('app-user/profile/{appUserId}', [AppUserProfileController::class, 'show']);
Route::get('app-user/followers/{appUserId}', [AppUserFollowController::class, 'followers']);
Route::get('app-user/following/{appUserId}', [AppUserFollowController::class, 'followingList']);
Route::get('app-user/posts/all', [AppUserPostController::class, 'allPosts']);
Route::get('app-user/shared-posts', [AppUserSharedPostController::class, 'all']);
Route::get('app-user/shared-posts/{id}', [AppUserSharedPostController::class, 'show']);

Route::middleware('auth:sanctum')->prefix('app-user')->group(function (): void {
    Route::get('profile', [AppUserProfileController::class, 'me']);
    Route::post('profile', [AppUserProfileController::class, 'update']);
    Route::post('profile/{appUserId}', [AppUserProfileController::class, 'updateById']);
    Route::delete('profile', [AppUserProfileController::class, 'destroy']);
    Route::delete('profile/{appUserId}', [AppUserProfileController::class, 'destroyById']);

    Route::get('posts', [AppUserPostController::class, 'index']);
    Route::get('my-posts', [AppUserPostController::class, 'myPosts']);
    Route::get('my-reposts', [AppUserPostController::class, 'myReposts']);
    Route::get('following-posts', [AppUserPostController::class, 'followingPosts']);
    Route::post('posts', [AppUserPostController::class, 'store']);
    Route::get('posts/{id}', [AppUserPostController::class, 'show']);
    Route::post('posts/{id}', [AppUserPostController::class, 'update']);
    Route::delete('posts/{id}', [AppUserPostController::class, 'destroy']);
    Route::post('posts/{id}/repost', [AppUserPostController::class, 'repost']);
    Route::post('posts/{id}/share', [AppUserSharedPostController::class, 'store']);
    Route::delete('posts/{id}/share', [AppUserSharedPostController::class, 'destroy']);
    Route::post('posts/{id}/likes', [AppUserPostLikeController::class, 'store']);
    Route::delete('posts/{id}/likes', [AppUserPostLikeController::class, 'destroy']);

    Route::get('posts/{id}/comments', [AppUserPostCommentController::class, 'index']);
    Route::post('posts/{id}/comments', [AppUserPostCommentController::class, 'store']);
    Route::patch('comments/{commentId}', [AppUserPostCommentController::class, 'update']);
    Route::delete('comments/{commentId}', [AppUserPostCommentController::class, 'destroy']);

    Route::post('follow/{appUserId}', [AppUserFollowController::class, 'store']);
    Route::delete('follow/{appUserId}', [AppUserFollowController::class, 'destroy']);

    Route::get('activities', [AppUserActivityController::class, 'index']);

    //chat
    Route::get('chats', [AppUserChatController::class, 'index']);
    Route::post('chats/direct', [AppUserChatController::class, 'startDirectConversation']);
    Route::get('chats/{conversationId}', [AppUserChatController::class, 'show']);
    Route::get('chats/{conversationId}/messages', [AppUserChatController::class, 'messages']);
    Route::post('chats/{conversationId}/messages', [AppUserChatController::class, 'storeMessage']);
    Route::patch('chats/{conversationId}/read', [AppUserChatController::class, 'markAsRead']);

    //check-in
    Route::get('check-in-cities', [AppUserCheckInController::class, 'cities']);
    Route::get('check-ins', [AppUserCheckInController::class, 'index']);
    Route::post('check-ins', [AppUserCheckInController::class, 'store']);
    Route::post('check-in-cities/{city}/check-in', [AppUserCheckInController::class, 'storeByCity']);




    //firebase notification
    Route::post('users/fcm-token', [FirebaseNotificationController::class, 'updateToken']);
    Route::get('notifications', [FirebaseNotificationController::class, 'myNotifications']);
    Route::patch('notifications/{notification}/read', [FirebaseNotificationController::class, 'markAsRead']);
    Route::post('notifications/firebase/send', [FirebaseNotificationController::class, 'send']);
    Route::post('notifications/firebase/send-bulk', [FirebaseNotificationController::class, 'sendBulk']);

});