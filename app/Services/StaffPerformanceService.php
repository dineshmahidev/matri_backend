<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\StaffAttendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class StaffPerformanceService
{
    public function formatStaffSummary(User $staff): array
    {
        $todayAttendance = StaffAttendance::where('user_id', $staff->id)
            ->where('date', today())
            ->first();

        $conversions = Lead::where('assigned_to', $staff->id)->where('status', 'Converted')->count();

        return [
            'id' => 'S' . (100 + $staff->id),
            'staffId' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->getRoleNames()->first() ?? 'staff',
            'leads' => $staff->assigned_leads_count ?? Lead::where('assigned_to', $staff->id)->count(),
            'conversions' => $conversions,
            'status' => $todayAttendance && $todayAttendance->login_at && !$todayAttendance->logout_at
                ? 'Active'
                : ($todayAttendance && $todayAttendance->login_at ? 'Offline' : 'Inactive'),
            'loginTime' => $todayAttendance?->login_at?->format('Y-m-d H:i:s'),
            'logoutTime' => $todayAttendance?->logout_at
                ? $todayAttendance->logout_at->format('Y-m-d H:i:s')
                : ($todayAttendance?->login_at ? 'Still Logged In' : 'N/A'),
            'mobile' => $staff->phone,
            'companyMobile' => $staff->company_mobile,
            'dob' => $staff->dob?->format('Y-m-d'),
            'salary' => $staff->salary ? '₹' . number_format((float) $staff->salary, 0) : null,
        ];
    }

    public function formatStaffDetail(User $staff): array
    {
        $summary = $this->formatStaffSummary($staff);
        $summary['assignedLeadsList'] = Lead::where('assigned_to', $staff->id)
            ->latest()
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'phone' => $l->phone,
                'status' => $l->status,
                'createdAt' => $l->created_at?->format('Y-m-d'),
            ]);

        $summary['todayReport'] = $this->dayMetricsBulk($staff, today(), today())[today()->toDateString()] ?? [
            'leads_handled' => 0, 'new_leads' => 0, 'conversions' => 0, 'contacted' => 0,
        ];

        return $summary;
    }

    public function performanceReport(User $staff, string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $attendance = StaffAttendance::where('user_id', $staff->id)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->format('Y-m-d'));

        $metricsByDate = $this->dayMetricsBulk($staff, $fromDate, $toDate);

        $days = [];
        $presentDays = 0;
        $absentDays = 0;
        $totals = [
            'leads_handled' => 0,
            'new_leads' => 0,
            'conversions' => 0,
            'contacted' => 0,
        ];

        foreach (CarbonPeriod::create($fromDate, $toDate) as $date) {
            $dateStr = $date->format('Y-m-d');
            $record = $attendance->get($dateStr);
            $metrics = $metricsByDate[$dateStr] ?? [
                'leads_handled' => 0, 'new_leads' => 0, 'conversions' => 0, 'contacted' => 0,
            ];

            $present = (bool) ($record?->login_at);
            $isFuture = $date->isFuture();

            if ($present) {
                $presentDays++;
            } elseif (!$isFuture) {
                $absentDays++;
            }

            foreach (['leads_handled', 'new_leads', 'conversions', 'contacted'] as $key) {
                $totals[$key] += $metrics[$key];
            }

            $days[] = [
                'date' => $dateStr,
                'present' => $present,
                'absent' => !$present && !$isFuture,
                'login_at' => $record?->login_at?->format('H:i:s'),
                'logout_at' => $record?->logout_at?->format('H:i:s'),
                'leads_handled' => $metrics['leads_handled'],
                'new_leads' => $metrics['new_leads'],
                'conversions' => $metrics['conversions'],
                'contacted' => $metrics['contacted'],
                'calls_made' => $metrics['contacted'],
                'notes_added' => $metrics['leads_handled'],
            ];
        }

        return [
            'staff' => $this->formatStaffSummary($staff),
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'summary' => [
                'total_days' => count($days),
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                ...$totals,
            ],
            'days' => $days,
        ];
    }

    private function dayMetricsBulk(User $staff, Carbon $fromDate, Carbon $toDate): array
    {
        $dateStrFrom = $fromDate->toDateString();
        $dateStrTo = $toDate->toDateString();

        $leadsHandled = Lead::select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('assigned_to', $staff->id)
            ->whereBetween(DB::raw('DATE(updated_at)'), [$dateStrFrom, $dateStrTo])
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->pluck('count', 'date');

        $newLeads = Lead::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('assigned_to', $staff->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateStrFrom, $dateStrTo])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date');

        $conversions = Lead::select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('assigned_to', $staff->id)
            ->where('status', 'Converted')
            ->whereBetween(DB::raw('DATE(updated_at)'), [$dateStrFrom, $dateStrTo])
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->pluck('count', 'date');

        $contacted = Lead::select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('assigned_to', $staff->id)
            ->whereIn('status', ['Contacted', 'Qualified', 'Converted'])
            ->whereBetween(DB::raw('DATE(updated_at)'), [$dateStrFrom, $dateStrTo])
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->pluck('count', 'date');

        $result = [];
        foreach (CarbonPeriod::create($fromDate, $toDate) as $date) {
            $d = $date->format('Y-m-d');
            $result[$d] = [
                'leads_handled' => (int) ($leadsHandled[$d] ?? 0),
                'new_leads' => (int) ($newLeads[$d] ?? 0),
                'conversions' => (int) ($conversions[$d] ?? 0),
                'contacted' => (int) ($contacted[$d] ?? 0),
            ];
        }

        return $result;
    }
}
