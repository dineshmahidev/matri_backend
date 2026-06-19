<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Faq;
use App\Models\SuccessStory;
use App\Models\BlogPost;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\MemberResource;
use App\Services\StaffPerformanceService;
use App\Services\MemberCreationService;
use App\Services\MemberUserService;

class AdminController extends Controller
{
    public function __construct(
        private StaffPerformanceService $staffPerformance,
        private MemberUserService $memberUserService,
    ) {}

    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'dob' => $user->dob?->format('Y-m-d'),
            'role' => $user->role,
            'photo' => $user->photo,
            'createdAt' => $user->created_at?->format('Y-m-d'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'gender' => 'sometimes|nullable|string',
            'dob' => 'sometimes|nullable|date',
        ]);
        $user->update($data);
        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function uploadProfilePhoto(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:5120']);
        $path = $request->file('photo')->store('admin-photos', 'public');
        $user = $request->user();
        $user->photo = asset('storage/' . $path);
        $user->save();
        return response()->json(['url' => $user->photo]);
    }

    public function dashboard()
    {
        $totalUsers = User::where('role', 'member')->count();
        $premiumUsers = User::where('role', 'member')->whereHas('profile', fn($q) => $q->where('premium', true))->count();

        return response()->json([
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => User::where('role', 'member')->where('updated_at', '>=', now()->subDays(30))->count(),
                'premiumUsers' => $premiumUsers,
                'revenue' => Payment::where('status', 'paid')->sum('amount'),
                'newSignups' => User::where('role', 'member')->whereDate('created_at', today())->count(),
                'matches' => rand(1500, 2000),
            ],
            'revenueChart' => [
                ['month' => 'Jan', 'revenue' => 820000, 'signups' => 240],
                ['month' => 'Feb', 'revenue' => 940000, 'signups' => 280],
                ['month' => 'Mar', 'revenue' => 1120000, 'signups' => 320],
                ['month' => 'Apr', 'revenue' => 1080000, 'signups' => 305],
                ['month' => 'May', 'revenue' => 1245000, 'signups' => 360],
                ['month' => 'Jun', 'revenue' => 1390000, 'signups' => 412],
            ],
            'recentLeads' => Lead::with('assignedStaff')->latest()->take(6)->get(),
        ]);
    }

    public function users(Request $request)
    {
        $query = User::where('role', 'member')->with('profile.gallery', 'activeSubscription.plan');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('profile', fn($p) => $p->where('display_id', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('has_photo')) {
            if ($request->has_photo === 'yes') {
                $query->whereHas('profile', fn($p) => $p->whereNotNull('photo')->where('photo', '!=', ''));
            } elseif ($request->has_photo === 'no') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('profile')
                        ->orWhereHas('profile', function ($p) {
                            $p->where(function ($inner) {
                                $inner->whereNull('photo')->orWhere('photo', '');
                            });
                        });
                });
            }
        }

        if ($request->filled('has_gallery')) {
            if ($request->has_gallery === 'yes') {
                $query->whereHas('profile.gallery');
            } elseif ($request->has_gallery === 'no') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('profile')
                        ->orWhereHas('profile', fn($p) => $p->whereDoesntHave('gallery'));
                });
            }
        }

        if ($request->filled('premium')) {
            if ($request->premium === 'yes') {
                $query->whereHas('profile', fn($p) => $p->where('premium', true));
            } elseif ($request->premium === 'no') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('profile')
                        ->orWhereHas('profile', fn($p) => $p->where('premium', false));
                });
            }
        }

        if ($request->filled('featured')) {
            if ($request->featured === 'yes') {
                $query->whereHas('profile', fn($p) => $p->where('featured', true));
            } elseif ($request->featured === 'no') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('profile')
                        ->orWhereHas('profile', fn($p) => $p->where('featured', false));
                });
            }
        }

        if ($request->boolean('all')) {
            return MemberResource::collection($query->latest()->get());
        }

        return MemberResource::collection($query->latest()->paginate(20));
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::where('role', 'member')->findOrFail($id);
        $profile = $user->profile;

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'gender' => 'sometimes|required|in:male,female,other',
            'dob' => 'sometimes|nullable|date',
            'tob' => 'sometimes|nullable|string|max:20',
            'bio' => 'sometimes|nullable|string|max:1000',
            'height' => 'sometimes|nullable|string',
            'religion' => 'sometimes|nullable|string',
            'community' => 'sometimes|nullable|string',
            'mother_tongue' => 'sometimes|nullable|string',
            'motherTongue' => 'sometimes|nullable|string',
            'city' => 'sometimes|nullable|string',
            'state' => 'sometimes|nullable|string',
            'profession' => 'sometimes|nullable|string',
            'education' => 'sometimes|nullable|string',
            'income' => 'sometimes|nullable|string',
            'marital_status' => 'sometimes|nullable|string',
            'maritalStatus' => 'sometimes|nullable|string',
            'premium' => 'sometimes|boolean',
            'verified' => 'sometimes|boolean',
            'featured' => 'sometimes|boolean',
            'plan_id' => 'sometimes|nullable|integer',
            'planId' => 'sometimes|nullable|integer',
            'photo' => 'sometimes|nullable|string|max:2048',
            'gallery' => 'sometimes|array',
            'gallery.*' => 'string|max:2048',
        ]);

        $userData = collect($data)->only(['name', 'email', 'phone', 'gender', 'dob', 'tob'])->toArray();
        if (count($userData) > 0) {
            $user->update($userData);
        }

        $profileData = [];
        $mappings = [
            'motherTongue' => 'mother_tongue',
            'maritalStatus' => 'marital_status',
        ];
        foreach (['bio', 'height', 'religion', 'community', 'city', 'state', 'profession', 'education', 'income', 'premium', 'verified', 'featured', 'photo'] as $field) {
            if (array_key_exists($field, $data)) {
                $profileData[$field] = $data[$field];
            }
        }
        foreach ($mappings as $camel => $snake) {
            if (array_key_exists($camel, $data)) {
                $profileData[$snake] = $data[$camel];
            } elseif (array_key_exists($snake, $data)) {
                $profileData[$snake] = $data[$snake];
            }
        }

        if ($profile && count($profileData) > 0) {
            $profile->update($profileData);
        }

        // Handle Plan ID updates
        $planIdField = null;
        if (array_key_exists('planId', $data)) {
            $planIdField = $data['planId'];
        } elseif (array_key_exists('plan_id', $data)) {
            $planIdField = $data['plan_id'];
        }

        if ($planIdField !== null || array_key_exists('planId', $data) || array_key_exists('plan_id', $data)) {
            if (empty($planIdField)) {
                // Degrade to Free
                if ($profile) {
                    $profile->update(['premium' => false]);
                }
                $user->subscriptions()->where('status', 'active')->update(['status' => 'expired']);
            } else {
                $plan = Plan::findOrFail($planIdField);
                if ($profile) {
                    $profile->update(['premium' => true]);
                }
                // Add plan quotas to the user
                $user->contact_quota += $plan->contact_quota ?? 0;
                $user->message_quota += $plan->message_quota ?? 0;
                $user->credits += $plan->credits ?? 0;
                $user->save();
                $user->subscriptions()->where('status', 'active')->where('plan_id', '!=', $planIdField)->update(['status' => 'expired']);
                $sub = $user->subscriptions()->where('status', 'active')->where('plan_id', $planIdField)->first();
                if (!$sub) {
                    \App\Models\Subscription::create([
                        'user_id' => $user->id,
                        'plan_id' => $planIdField,
                        'starts_at' => now(),
                        'ends_at' => now()->addYear(),
                        'status' => 'active',
                    ]);
                }
            }
        }

        if ($profile && isset($data['gallery'])) {
            // Delete old gallery records
            $profile->gallery()->delete();
            // Re-create new gallery records
            foreach ($data['gallery'] as $index => $imageUrl) {
                if ($imageUrl) {
                    \App\Models\ProfileGallery::create([
                        'member_profile_id' => $profile->id,
                        'image_url' => $imageUrl,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new MemberResource($user->fresh())
        ]);
    }

    public function deleteUser($id)
    {
        $user = User::where('role', 'member')->findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function bulkDeleteUsers(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        User::where('role', 'member')->whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected users deleted successfully']);
    }

    public function changePassword(Request $request, $id)
    {
        $user = User::where('role', 'member')->findOrFail($id);

        $data = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'password' => 'required|string|min:8',
            'dob' => 'nullable|date',
            'religion_id' => 'nullable|integer|exists:religions,id',
            'religion' => 'required_without:religion_id|string|max:100',
            'caste_id' => 'nullable|integer|exists:castes,id',
            'community' => 'required_without:caste_id|string|max:100',
            'state_id' => 'nullable|integer|exists:states,id',
            'state' => 'required_without:state_id|string|max:100',
            'city_id' => 'nullable|integer|exists:cities,id',
            'city' => 'required_without:city_id|string|max:100',
            'mother_tongue' => 'nullable|string|max:50',
            'rasi' => 'nullable|string|max:50',
            'nakshatram' => 'nullable|string|max:50',
            'profile_for' => 'nullable|string|max:50',
        ]);

        $user = $this->memberUserService->create($data);

        return response()->json([
            'message' => 'User created successfully',
            'user' => new MemberResource($user),
        ], 201);
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('gallery', 'public');
            $imageUrl = asset('storage/' . $path);
            return response()->json(['url' => $imageUrl], 200);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }

    // FAQs CRUD
    public function getFaqs() { return response()->json(Faq::orderBy('sort_order')->get()); }
    public function createFaq(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:5000',
            'sort_order' => 'nullable|integer',
        ]);
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = Faq::count() + 1;
        }
        $faq = Faq::create($data);
        return response()->json(['message' => 'FAQ created successfully', 'faq' => $faq], 201);
    }
    public function updateFaq(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:5000',
            'sort_order' => 'sometimes|integer',
        ]);
        $faq->update($data);
        return response()->json(['message' => 'FAQ updated successfully', 'faq' => $faq]);
    }
    public function deleteFaq($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
        return response()->json(['message' => 'FAQ deleted successfully']);
    }

    // Success Stories CRUD
    public function getStories() { return response()->json(SuccessStory::latest()->get()); }

    public function createStory(Request $request)
    {
        $data = $request->validate([
            'couple_name' => 'required|string|max:255',
            'date' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'photo' => 'required|url',
            'quote' => 'required|string|max:2000',
        ]);
        $story = SuccessStory::create($data);
        return response()->json(['message' => 'Success story created successfully', 'story' => $story], 201);
    }
    public function updateStory(Request $request, $id)
    {
        $story = SuccessStory::findOrFail($id);
        $data = $request->validate([
            'couple_name' => 'required|string|max:255',
            'date' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'photo' => 'required|url',
            'quote' => 'required|string|max:2000',
        ]);
        $story->update($data);
        return response()->json(['message' => 'Success story updated successfully', 'story' => $story]);
    }
    public function deleteStory($id)
    {
        $story = SuccessStory::findOrFail($id);
        $story->delete();
        return response()->json(['message' => 'Success story deleted successfully']);
    }

    // Blog Posts CRUD
    public function getBlogs() { return response()->json(BlogPost::latest()->get()); }
    public function createBlog(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_posts,slug',
            'category' => 'required|string|max:100',
            'read_time' => 'required|string|max:50',
            'published_at' => 'required|date',
            'image' => 'required|url',
            'excerpt' => 'required|string|max:1000',
            'body' => 'required|string|max:50000',
        ]);
        $blog = BlogPost::create($data);
        return response()->json(['message' => 'Blog post created successfully', 'blog' => $blog], 201);
    }
    public function updateBlog(Request $request, $id)
    {
        $blog = BlogPost::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blog_posts,slug,' . $blog->id,
            'category' => 'required|string|max:100',
            'read_time' => 'required|string|max:50',
            'published_at' => 'required|date',
            'image' => 'required|url',
            'excerpt' => 'required|string|max:1000',
            'body' => 'required|string|max:50000',
        ]);
        $blog->update($data);
        return response()->json(['message' => 'Blog post updated successfully', 'blog' => $blog]);
    }
    public function deleteBlog($id)
    {
        $blog = BlogPost::findOrFail($id);
        $blog->delete();
        return response()->json(['message' => 'Blog post deleted successfully']);
    }
    // Pages CRUD
    public function getPages() { return response()->json(\App\Models\Page::all()); }
    public function createPage(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'body' => 'nullable|string|max:50000',
        ]);
        $page = \App\Models\Page::create($data);
        return response()->json(['message' => 'Page created successfully', 'page' => $page], 201);
    }
    public function updatePage(Request $request, $id)
    {
        $page = \App\Models\Page::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'body' => 'nullable|string|max:50000',
        ]);
        $page->update($data);
        return response()->json(['message' => 'Page updated successfully', 'page' => $page]);
    }
    public function deletePage($id)
    {
        $page = \App\Models\Page::findOrFail($id);
        $page->delete();
        return response()->json(['message' => 'Page deleted successfully']);
    }

    // Site Settings CRUD
    public function getSettings() {
        $settings = \Illuminate\Support\Facades\Cache::remember('site_settings', 3600, fn() =>
            \App\Models\SiteSetting::all()->pluck('value', 'key')
        );

        if (!empty($settings['razorpay_key_secret'])) {
            $settings['razorpay_key_secret_masked'] = true;
            $settings['razorpay_key_secret'] = '••••••••';
        }

        $settings['razorpay_source'] = !empty(env('RAZORPAY_KEY_ID')) ? 'env' : 'database';

        return response()->json($settings);
    }
    public function verifyPassword(Request $request)
    {
        $data = $request->validate(['password' => 'required|string']);
        if (\Illuminate\Support\Facades\Hash::check($data['password'], $request->user()->password)) {
            return response()->json(['verified' => true]);
        }
        return response()->json(['verified' => false, 'message' => 'Incorrect password'], 403);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
        ]);
        foreach ($data['settings'] as $key => $value) {
            if ($key === 'razorpay_key_secret' && ($value === '••••••••' || $value === '')) {
                continue;
            }
            if (in_array($key, ['razorpay_key_secret_masked', 'razorpay_source'], true)) {
                continue;
            }
            \App\Models\SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        \Illuminate\Support\Facades\Cache::forget('site_settings');
        return response()->json(['message' => 'Settings updated successfully']);
    }

    // Plans CRUD
    public function getPlans() { return response()->json(Plan::all()); }
    public function createPlan(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'price' => 'required|integer|min:0',
            'period' => 'required|string|max:30',
            'color' => 'nullable|string|max:255',
            'popular' => 'required|boolean',
            'features' => 'required|array',
            'features.*' => 'string',
            'contact_quota' => 'nullable|integer|min:0',
            'message_quota' => 'nullable|integer|min:0',
            'credits' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = strtolower(str_replace(' ', '-', $data['name']));
        $plan = Plan::create($data);
        return response()->json(['message' => 'Plan created successfully', 'plan' => $plan], 201);
    }
    public function updatePlan(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'price' => 'required|integer|min:0',
            'period' => 'required|string|max:30',
            'color' => 'nullable|string|max:255',
            'popular' => 'required|boolean',
            'features' => 'required|array',
            'features.*' => 'string',
            'contact_quota' => 'nullable|integer|min:0',
            'message_quota' => 'nullable|integer|min:0',
            'credits' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = strtolower(str_replace(' ', '-', $data['name']));
        $plan->update($data);
        return response()->json(['message' => 'Plan updated successfully', 'plan' => $plan]);
    }
    public function deletePlan($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();
        return response()->json(['message' => 'Plan deleted successfully']);
    }

    public function leads(Request $request) {
        $query = Lead::with('assignedStaff')->select('leads.*');
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }
        return response()->json($query->latest()->paginate(20));
    }

    public function updateLead(Request $request, $id) {
        $lead = Lead::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|nullable|string|max:255',
            'source' => 'sometimes|nullable|string|max:100',
            'status' => 'sometimes|in:New,Contacted,Qualified,Converted',
            'assigned_to' => 'sometimes|nullable|exists:users,id,role,staff',
        ]);
        $lead->update($data);
        return response()->json(['message' => 'Lead updated', 'lead' => $lead->load('assignedStaff')]);
    }

    public function bulkAssign(Request $request) {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:leads,id',
            'assigned_to' => 'required|exists:users,id,role,staff',
        ]);
        Lead::whereIn('id', $data['ids'])->update(['assigned_to' => $data['assigned_to']]);
        return response()->json(['message' => count($data['ids']) . ' leads assigned successfully']);
    }

    public function importLeads(Request $request) {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            Lead::create([
                'display_id' => 'L' . strtoupper(uniqid()),
                'name' => $data['name'] ?? $data['Name'] ?? '',
                'phone' => $data['phone'] ?? $data['Phone'] ?? '',
                'email' => $data['email'] ?? $data['Email'] ?? '',
                'source' => $data['source'] ?? $data['Source'] ?? 'Import',
                'status' => 'New',
            ]);
            $count++;
        }
        fclose($handle);
        return response()->json(['message' => "$count leads imported successfully"]);
    }

    public function exportLeads(Request $request) {
        $query = Lead::query();
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }
        $leads = $query->latest()->get();
        $filename = 'leads-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];
        $callback = function () use ($leads) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Phone', 'Email', 'Source', 'Status', 'Assigned To', 'Created At']);
            foreach ($leads as $l) {
                fputcsv($handle, [
                    $l->name, $l->phone, $l->email, $l->source, $l->status,
                    $l->assignedStaff?->name ?? '', $l->created_at?->format('Y-m-d') ?? '',
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function payments() { return response()->json(Payment::with('user')->latest()->paginate(20)); }

    public function staff()
    {
        $staff = User::where('role', 'staff')
            ->withCount('assignedLeads')
            ->get()
            ->map(fn ($s) => $this->staffPerformance->formatStaffSummary($s));

        return response()->json($staff);
    }

    public function showStaff($id)
    {
        $staff = User::where('role', 'staff')
            ->withCount('assignedLeads')
            ->findOrFail($id);

        return response()->json($this->staffPerformance->formatStaffDetail($staff));
    }

    public function staffPerformance(Request $request, $id)
    {
        $staff = User::where('role', 'staff')->findOrFail($id);

        $from = $request->query('from', today()->toDateString());
        $to = $request->query('to', today()->toDateString());

        return response()->json(
            $this->staffPerformance->performanceReport($staff, $from, $to)
        );
    }

    public function reports()
    {
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $totalUsers = User::where('role', 'member')->count();
        $totalLeads = Lead::count();
        $convertedLeads = Lead::where('status', 'Converted')->count();
        $conversionRate = $totalLeads > 0 ? $convertedLeads . '/' . $totalLeads : '0/0';

        // Revenue trend (last 6 months)
        $revenueTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthRev = Payment::where('status', 'paid')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            $monthSignups = User::where('role', 'member')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $revenueTrend[] = [
                'month' => $month->format('M'),
                'revenue' => (int) $monthRev,
                'signups' => $monthSignups,
            ];
        }

        // Plan distribution
        $planDist = \App\Models\Plan::withCount(['subscriptions as subscriber_count' => function ($q) {
            $q->where('status', 'active');
        }])->get()->map(fn($p) => [
            'name' => $p->name,
            'value' => (int) $p->subscriber_count,
            'color' => $p->color ?? 'oklch(0.7 0.02 240)',
        ])->values();

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalUsers' => $totalUsers,
            'totalLeads' => $totalLeads,
            'conversionRate' => $conversionRate,
            'revenueTrend' => $revenueTrend,
            'planDistribution' => $planDist,
        ]);
    }

    public function uploadStaffLeads(Request $request, $id)
    {
        $staff = User::whereIn('role', ['staff', 'admin'])->findOrFail($id);
        
        $data = $request->validate([
            'leads' => 'required|array',
            'leads.*.name' => 'required|string|max:255',
            'leads.*.phone' => 'required|string|max:20',
            'leads.*.email' => 'nullable|string|max:255',
            'leads.*.source' => 'nullable|string|max:255',
        ]);
        
        $createdLeads = [];
        foreach ($data['leads'] as $leadData) {
            $lead = Lead::create([
                'display_id' => 'L' . strtoupper(substr(uniqid(), -6)),
                'name' => $leadData['name'],
                'phone' => $leadData['phone'],
                'email' => $leadData['email'] ?? null,
                'source' => $leadData['source'] ?? 'Uploaded',
                'status' => 'New',
                'assigned_to' => $staff->id,
            ]);
            
            $createdLeads[] = [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'status' => $lead->status,
                'createdAt' => $lead->created_at ? $lead->created_at->format('Y-m-d') : today()->format('Y-m-d'),
            ];
        }
        
        return response()->json([
            'message' => count($createdLeads) . ' leads uploaded and assigned successfully',
            'leads' => $createdLeads
        ]);
    }
}
