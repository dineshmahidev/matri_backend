<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InterestController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SavedProfileController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\StaffNoteController;
use App\Http\Controllers\Api\ReferenceDataController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

Route::get('/plans', [PublicController::class, 'plans']);
Route::get('/success-stories', [PublicController::class, 'successStories']);
Route::get('/blog', [PublicController::class, 'blog']);
Route::get('/blog/{slug}', [PublicController::class, 'showBlog']);
Route::get('/faqs', [PublicController::class, 'faqs']);
Route::get('/pages/{slug}', [PublicController::class, 'showPage']);
Route::get('/settings', [PublicController::class, 'getSettings']);
Route::post('/contact', [PublicController::class, 'contact']);

Route::get('/reference/religions', [ReferenceDataController::class, 'religions']);
Route::get('/reference/castes', [ReferenceDataController::class, 'castes']);
Route::get('/reference/states', [ReferenceDataController::class, 'states']);
Route::get('/reference/cities', [ReferenceDataController::class, 'cities']);

Route::get('/members/browse', [MemberController::class, 'browse']);
Route::get('/members/recently-joined', [MemberController::class, 'recentlyJoined']);
Route::get('/members/premium', [MemberController::class, 'premiumMembers']);
Route::get('/members/featured', [MemberController::class, 'featuredMembers']);
Route::get('/members/{id}', [MemberController::class, 'show'])->where('id', '^(?!browse$|recommended$|recently-joined$|premium$|featured$).+');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/members/recommended', [MemberController::class, 'recommended']);
    
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/onboarding-step', [ProfileController::class, 'updateOnboardingStep']);
    Route::get('/members/{id}/match', [MemberController::class, 'matchPorutham']);
    Route::post('/members/{id}/unlock', [MemberController::class, 'unlock']);
    Route::post('/profile/photo', [ProfileController::class, 'updateProfilePhoto']);
    Route::post('/profile/gallery', [ProfileController::class, 'addGalleryImage']);
    Route::post('/profile/gallery/bulk', [ProfileController::class, 'addGalleryImages']);
    Route::delete('/profile/gallery', [ProfileController::class, 'deleteGalleryImage']);
    
    Route::get('/interests/sent', [InterestController::class, 'sent']);
    Route::get('/interests/received', [InterestController::class, 'received']);
    Route::post('/interests', [InterestController::class, 'send']);
    Route::put('/interests/{interest}', [InterestController::class, 'respond']);
    
    Route::get('/conversations', [MessageController::class, 'conversations']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'messages']);
    Route::post('/conversations/send-to-user', [MessageController::class, 'sendToUser']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'send']);
    
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments/create-order', [PaymentController::class, 'createOrder']);
    Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
    
    Route::get('/saved', [SavedProfileController::class, 'index']);
    Route::post('/saved', [SavedProfileController::class, 'store']);
    Route::delete('/saved/{userId}', [SavedProfileController::class, 'destroy']);
    
    Route::get('/search', [SearchController::class, 'search']);

    Route::get('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'index']);
    Route::get('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'show']);
    Route::post('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'store']);

    Route::middleware('role:admin,staff')->prefix('admin')->group(function () {
        Route::get('/profile', [AdminController::class, 'profile']);
        Route::put('/profile', [AdminController::class, 'updateProfile']);
        Route::post('/profile/photo', [AdminController::class, 'uploadProfilePhoto']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::post('/users/bulk-delete', [AdminController::class, 'bulkDeleteUsers']);
        Route::post('/users/{id}/change-password', [AdminController::class, 'changePassword']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::post('/upload', [AdminController::class, 'uploadFile']);
        Route::get('/staff-notes', [StaffNoteController::class, 'index']);
        Route::post('/staff-notes', [StaffNoteController::class, 'store']);
        Route::put('/staff-notes/{staffNote}', [StaffNoteController::class, 'update']);
        Route::delete('/staff-notes/{staffNote}', [StaffNoteController::class, 'destroy']);
        Route::get('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'adminIndex']);
        Route::get('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'adminShow']);
        Route::put('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'adminReply']);
    });

    // Admin routes (gate role: admin)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // Plans CRUD
        Route::get('/plans', [AdminController::class, 'getPlans']);
        Route::post('/plans', [AdminController::class, 'createPlan']);
        Route::put('/plans/{id}', [AdminController::class, 'updatePlan']);
        Route::delete('/plans/{id}', [AdminController::class, 'deletePlan']);

        // CMS FAQs
        Route::get('/faqs', [AdminController::class, 'getFaqs']);
        Route::post('/faqs', [AdminController::class, 'createFaq']);
        Route::put('/faqs/{id}', [AdminController::class, 'updateFaq']);
        Route::delete('/faqs/{id}', [AdminController::class, 'deleteFaq']);

        // CMS Success Stories
        Route::get('/success-stories', [AdminController::class, 'getStories']);
        Route::post('/success-stories', [AdminController::class, 'createStory']);
        Route::put('/success-stories/{id}', [AdminController::class, 'updateStory']);
        Route::delete('/success-stories/{id}', [AdminController::class, 'deleteStory']);

        // CMS Blog Posts
        Route::get('/blog', [AdminController::class, 'getBlogs']);
        Route::post('/blog', [AdminController::class, 'createBlog']);
        Route::put('/blog/{id}', [AdminController::class, 'updateBlog']);
        Route::delete('/blog/{id}', [AdminController::class, 'deleteBlog']);

        // CMS Pages
        Route::get('/pages', [AdminController::class, 'getPages']);
        Route::post('/pages', [AdminController::class, 'createPage']);
        Route::put('/pages/{id}', [AdminController::class, 'updatePage']);
        Route::delete('/pages/{id}', [AdminController::class, 'deletePage']);

        // Site Settings
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);
        Route::post('/verify-password', [AdminController::class, 'verifyPassword']);

        Route::get('/leads', [AdminController::class, 'leads']);
        Route::put('/leads/{id}', [AdminController::class, 'updateLead']);
        Route::delete('/leads/{id}', [AdminController::class, 'deleteLead']);
        Route::post('/leads/bulk-assign', [AdminController::class, 'bulkAssign']);
        Route::post('/leads/bulk-delete', [AdminController::class, 'bulkDeleteLeads']);
        Route::post('/leads/import', [AdminController::class, 'importLeads']);
        Route::get('/leads/export', [AdminController::class, 'exportLeads']);
        Route::get('/payments', [AdminController::class, 'payments']);
        Route::get('/staff', [AdminController::class, 'staff']);
        Route::get('/staff/{id}', [AdminController::class, 'showStaff']);
        Route::get('/staff/{id}/performance', [AdminController::class, 'staffPerformance']);
        Route::post('/staff', [AdminController::class, 'createStaff']);
        Route::put('/staff/{id}', [AdminController::class, 'updateStaff']);
        Route::delete('/staff/{id}', [AdminController::class, 'deleteStaff']);
        Route::post('/staff/{id}/upload-leads', [AdminController::class, 'uploadStaffLeads']);
        Route::get('/reports', [AdminController::class, 'reports']);
        Route::post('/bulk-upload-users', [AdminController::class, 'bulkUploadUsers']);
        Route::post('/users/{id}/add-credits', [AdminController::class, 'addUserCredits']);
    });

    // Staff routes (gate role: staff)
    Route::middleware('role:staff')->prefix('staff')->group(function () {
        Route::get('/profile', [StaffController::class, 'profile']);
        Route::put('/profile', [StaffController::class, 'updateProfile']);
        Route::post('/profile/photo', [StaffController::class, 'uploadProfilePhoto']);
        Route::get('/dashboard', [StaffController::class, 'dashboard']);
        Route::post('/attendance/check-in', [StaffController::class, 'checkIn']);
        Route::post('/attendance/check-out', [StaffController::class, 'checkOut']);
        Route::get('/attendance', [StaffController::class, 'attendance']);
        Route::get('/leads', [StaffController::class, 'leads']);
        Route::get('/leads/{lead}', [StaffController::class, 'showLead']);
        Route::put('/leads/{lead}', [StaffController::class, 'updateLead']);
        Route::get('/leads/{lead}/notes', [StaffController::class, 'leadNotes']);
        Route::post('/leads/{lead}/notes', [StaffController::class, 'storeLeadNote']);
        Route::post('/create-user', [StaffController::class, 'createUser']);
        Route::get('/monthly-report', [StaffController::class, 'monthlyReport']);
        Route::get('/notes', [StaffNoteController::class, 'index']);
        Route::post('/notes', [StaffNoteController::class, 'store']);
        Route::put('/notes/{staffNote}', [StaffNoteController::class, 'update']);
        Route::delete('/notes/{staffNote}', [StaffNoteController::class, 'destroy']);
    });
});
