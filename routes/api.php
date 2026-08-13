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
Route::get('/auth/check-email', [AuthController::class, 'checkEmail']);
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
Route::get('/payment-gateways', [PublicController::class, 'paymentGateways']);

Route::get('/reference/religions', [ReferenceDataController::class, 'religions']);
Route::get('/reference/castes', [ReferenceDataController::class, 'castes']);
Route::get('/reference/states', [ReferenceDataController::class, 'states']);
Route::get('/reference/cities', [ReferenceDataController::class, 'cities']);
Route::get('/reference/blood-groups', [ReferenceDataController::class, 'bloodGroups']);

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
    Route::get('/profile/privacy', [ProfileController::class, 'privacy']);
    Route::put('/profile/privacy', [ProfileController::class, 'updatePrivacy']);
    Route::put('/profile/onboarding-step', [ProfileController::class, 'updateOnboardingStep']);
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
    
    Route::get('/blocks', [\App\Http\Controllers\UserBlockController::class, 'index']);
    Route::post('/blocks', [\App\Http\Controllers\UserBlockController::class, 'block']);
    Route::delete('/blocks/{blocked_id}', [\App\Http\Controllers\UserBlockController::class, 'unblock']);
    
    Route::get('/search', [SearchController::class, 'search']);

    Route::get('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'index']);
    Route::get('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'show']);
    Route::post('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'store']);

    // Contact request system
    Route::get('/contact-requests/sent', [\App\Http\Controllers\Api\ContactRequestController::class, 'sent']);
    Route::get('/contact-requests/received', [\App\Http\Controllers\Api\ContactRequestController::class, 'received']);
    Route::post('/contact-requests', [\App\Http\Controllers\Api\ContactRequestController::class, 'send']);
    Route::put('/contact-requests/{id}/respond', [\App\Http\Controllers\Api\ContactRequestController::class, 'respond']);
    Route::get('/contact-requests/check/{targetId}', [\App\Http\Controllers\Api\ContactRequestController::class, 'check']);

    // ─── Admin panel routes: admin, manager, staff all share this base ───
    Route::middleware('role:admin,manager,staff')->prefix('admin')->group(function () {
        Route::get('/profile', [AdminController::class, 'profile']);
        Route::put('/profile', [AdminController::class, 'updateProfile']);
        Route::post('/profile/photo', [AdminController::class, 'uploadProfilePhoto']);
        Route::post('/profile/change-password', [AdminController::class, 'changePasswordSelf']);

        // Staff notes — admin & staff only (manager.permission:staff_notes)
        Route::middleware('manager.permission:staff_notes')->group(function () {
            Route::get('/staff-notes', [StaffNoteController::class, 'index']);
            Route::post('/staff-notes', [StaffNoteController::class, 'store']);
            Route::put('/staff-notes/{staffNote}', [StaffNoteController::class, 'update']);
            Route::delete('/staff-notes/{staffNote}', [StaffNoteController::class, 'destroy']);
        });

        // Support tickets — manager.permission:support_tickets
        Route::middleware('manager.permission:support_tickets')->group(function () {
            Route::get('/support-tickets', [\App\Http\Controllers\Api\SupportTicketController::class, 'adminIndex']);
            Route::get('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'adminShow']);
            Route::put('/support-tickets/{id}', [\App\Http\Controllers\Api\SupportTicketController::class, 'adminReply']);
        });

        // Settings + Dashboard (no permission needed)
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);
        Route::post('/verify-password', [AdminController::class, 'verifyPassword']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // Manager permissions endpoint (to drive frontend sidebar)
        Route::get('/manager-permissions', function () {
            $raw = \App\Models\SiteSetting::where('key', 'manager_permissions')->value('value');
            return response()->json($raw ? json_decode($raw, true) : []);
        });

        // Current user's effective permissions (from their role)
        Route::get('/my-permissions', function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            $permissions = $user->getAllPermissions()->pluck('name');
            $modules = [];
            $permList = [];
            foreach ($permissions as $perm) {
                $permList[] = $perm;
                $key = str_contains($perm, '.') ? explode('.', $perm)[0] : $perm;
                $modules[$key] = true;
            }
            $modules['_role'] = $user->getRoleNames()->first() ?? 'staff';
            $modules['_perms'] = $permList;
            return response()->json($modules);
        });

        // ─── Users & Members — manager.permission:users ───
        Route::middleware('manager.permission:users')->group(function () {
            Route::get('/users', [AdminController::class, 'users']);
            Route::put('/users/{id}', [AdminController::class, 'updateUser']);
            Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
            Route::post('/users/bulk-delete', [AdminController::class, 'bulkDeleteUsers']);
            Route::post('/users/bulk-premium', [AdminController::class, 'bulkPremiumUsers']);
            Route::post('/users/bulk-verify', [AdminController::class, 'bulkVerifyUsers']);
            Route::post('/users/{id}/change-password', [AdminController::class, 'changePassword']);
            Route::post('/users', [AdminController::class, 'createUser']);
            Route::post('/upload', [AdminController::class, 'uploadFile']);
        });

        // ─── Plans / Packages — manager.permission:packages ───
        Route::middleware('manager.permission:packages')->group(function () {
            Route::get('/plans', [AdminController::class, 'getPlans']);
            Route::post('/plans', [AdminController::class, 'createPlan']);
            Route::put('/plans/{id}', [AdminController::class, 'updatePlan']);
            Route::delete('/plans/{id}', [AdminController::class, 'deletePlan']);
        });

        // ─── CMS — manager.permission:cms ───
        Route::middleware('manager.permission:cms')->group(function () {
            Route::get('/faqs', [AdminController::class, 'getFaqs']);
            Route::post('/faqs', [AdminController::class, 'createFaq']);
            Route::put('/faqs/{id}', [AdminController::class, 'updateFaq']);
            Route::delete('/faqs/{id}', [AdminController::class, 'deleteFaq']);
            Route::get('/success-stories', [AdminController::class, 'getStories']);
            Route::post('/success-stories', [AdminController::class, 'createStory']);
            Route::put('/success-stories/{id}', [AdminController::class, 'updateStory']);
            Route::delete('/success-stories/{id}', [AdminController::class, 'deleteStory']);
            Route::get('/blog', [AdminController::class, 'getBlogs']);
            Route::post('/blog', [AdminController::class, 'createBlog']);
            Route::put('/blog/{id}', [AdminController::class, 'updateBlog']);
            Route::delete('/blog/{id}', [AdminController::class, 'deleteBlog']);
            Route::get('/pages', [AdminController::class, 'getPages']);
            Route::post('/pages', [AdminController::class, 'createPage']);
            Route::put('/pages/{id}', [AdminController::class, 'updatePage']);
            Route::delete('/pages/{id}', [AdminController::class, 'deletePage']);
        });

        // ─── Leads — manager.permission:leads ───
        Route::middleware('manager.permission:leads')->group(function () {
            Route::get('/leads', [AdminController::class, 'leads']);
            Route::post('/leads', [AdminController::class, 'createLead']);
            Route::put('/leads/{id}', [AdminController::class, 'updateLead']);
            Route::delete('/leads/{id}', [AdminController::class, 'deleteLead']);
            Route::post('/leads/bulk-assign', [AdminController::class, 'bulkAssign']);
            Route::post('/leads/bulk-delete', [AdminController::class, 'bulkDeleteLeads']);
            Route::post('/leads/import', [AdminController::class, 'importLeads']);
            Route::get('/leads/export', [AdminController::class, 'exportLeads']);
        });

        // ─── Reference Data — manager.permission:reference_data ───
        Route::middleware('manager.permission:reference_data')->group(function () {
            Route::get('/reference', [AdminController::class, 'getAllReferenceData']);
            Route::post('/reference/religions', [AdminController::class, 'createReligion']);
            Route::put('/reference/religions/{id}', [AdminController::class, 'updateReligion']);
            Route::post('/reference/religions/{id}/toggle', [AdminController::class, 'toggleReligion']);
            Route::delete('/reference/religions/{id}', [AdminController::class, 'deleteReligion']);
            Route::post('/reference/castes', [AdminController::class, 'createCaste']);
            Route::put('/reference/castes/{id}', [AdminController::class, 'updateCaste']);
            Route::post('/reference/castes/{id}/toggle', [AdminController::class, 'toggleCaste']);
            Route::delete('/reference/castes/{id}', [AdminController::class, 'deleteCaste']);
            Route::post('/reference/states', [AdminController::class, 'createState']);
            Route::put('/reference/states/{id}', [AdminController::class, 'updateState']);
            Route::post('/reference/states/{id}/toggle', [AdminController::class, 'toggleState']);
            Route::delete('/reference/states/{id}', [AdminController::class, 'deleteState']);
            Route::post('/reference/cities', [AdminController::class, 'createCity']);
            Route::put('/reference/cities/{id}', [AdminController::class, 'updateCity']);
            Route::post('/reference/cities/{id}/toggle', [AdminController::class, 'toggleCity']);
            Route::delete('/reference/cities/{id}', [AdminController::class, 'deleteCity']);
            Route::get('/reference/blood-groups', [AdminController::class, 'getBloodGroups']);
            Route::post('/reference/blood-groups', [AdminController::class, 'createBloodGroup']);
            Route::put('/reference/blood-groups/{id}', [AdminController::class, 'updateBloodGroup']);
            Route::post('/reference/blood-groups/{id}/toggle', [AdminController::class, 'toggleBloodGroup']);
            Route::delete('/reference/blood-groups/{id}', [AdminController::class, 'deleteBloodGroup']);
            // Legacy routes
            Route::get('/religions', [AdminController::class, 'getReligions']);
            Route::post('/religions', [AdminController::class, 'createReligion']);
            Route::put('/religions/{id}', [AdminController::class, 'updateReligion']);
            Route::delete('/religions/{id}', [AdminController::class, 'deleteReligion']);
            Route::get('/castes', [AdminController::class, 'getCastes']);
            Route::post('/castes', [AdminController::class, 'createCaste']);
            Route::put('/castes/{id}', [AdminController::class, 'updateCaste']);
            Route::delete('/castes/{id}', [AdminController::class, 'deleteCaste']);
        });

        // ─── Payments — manager.permission:payments ───
        Route::middleware('manager.permission:payments')->group(function () {
            Route::get('/payments', [AdminController::class, 'payments']);
            Route::put('/payments/{id}', [AdminController::class, 'updatePayment']);
        });

        // ─── Reports — manager.permission:reports ───
        Route::middleware('manager.permission:reports')->group(function () {
            Route::get('/reports', [AdminController::class, 'reports']);
        });

        // ─── Bulk Upload — manager.permission:users_bulk ───
        Route::middleware('manager.permission:users_bulk')->group(function () {
            Route::post('bulk-upload', [\App\Http\Controllers\Api\BulkUploadController::class, 'upload']);
            Route::get('bulk-upload/status/{id}', [\App\Http\Controllers\Api\BulkUploadController::class, 'status']);
            Route::post('/bulk-upload-users', [AdminController::class, 'bulkUploadUsers']);
        });

        // ─── Image upload (generic) — manager.permission:users ───
        Route::middleware('manager.permission:users')->group(function () {
            Route::post('upload-image/{id}', [AdminController::class, 'uploadImage']);
            Route::delete('delete-image/{id}', [AdminController::class, 'deleteImage']);
            Route::post('/users/{id}/add-credits', [AdminController::class, 'addUserCredits']);
        });

        // ─── Staff management — manager.permission:staff ───
        Route::middleware('manager.permission:staff')->group(function () {
            Route::get('/staff', [AdminController::class, 'staff']);
            Route::get('/staff/{id}', [AdminController::class, 'showStaff']);
            Route::get('/staff/{id}/performance', [AdminController::class, 'staffPerformance']);
            Route::post('/staff', [AdminController::class, 'createStaff']);
            Route::put('/staff/{id}', [AdminController::class, 'updateStaff']);
            Route::delete('/staff/{id}', [AdminController::class, 'deleteStaff']);
            Route::post('/staff/{id}/upload-leads', [AdminController::class, 'uploadStaffLeads']);
        });

        // ─── Roles & Permissions — manager.permission:roles ───
        Route::middleware('manager.permission:roles')->group(function () {
            Route::get('/roles', [AdminController::class, 'getRoles']);
            Route::get('/permissions', [AdminController::class, 'getPermissions']);
            Route::post('/roles', [AdminController::class, 'createRole']);
            Route::put('/roles/{id}', [AdminController::class, 'updateRole']);
            Route::delete('/roles/{id}', [AdminController::class, 'deleteRole']);
        });

        // ─── Impersonate & logout — admin only ───
        Route::post('users/{id}/impersonate', [AdminController::class, 'impersonate'])->middleware('manager.permission:none');
        Route::post('logout', [AdminController::class, 'logout']);
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
        Route::post('/leads', [StaffController::class, 'createLead']);
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
