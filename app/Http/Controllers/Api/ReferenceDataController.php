<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caste;
use App\Models\City;
use App\Models\Religion;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReferenceDataController extends Controller
{
    public function religions()
    {
        $data = Cache::remember('ref:religions', 86400, fn() =>
            Religion::orderBy('name')->get(['id', 'name'])
        );
        return response()->json($data);
    }

    public function castes(Request $request)
    {
        $cacheKey = 'ref:castes:' . ($request->religion_id ?? 'all') . ':' . md5($request->religion ?? '');
        $data = Cache::remember($cacheKey, 86400, function () use ($request) {
            $query = Caste::orderBy('name');

            if ($request->filled('religion_id')) {
                $query->where('religion_id', $request->religion_id);
            } elseif ($request->filled('religion')) {
                $religionId = Religion::where('name', $request->religion)->value('id');
                if ($religionId) {
                    $query->where('religion_id', $religionId);
                }
            }

            return $query->get(['id', 'religion_id', 'name']);
        });
        return response()->json($data);
    }

    public function states()
    {
        $data = Cache::remember('ref:states', 86400, fn() =>
            State::orderBy('name')->get(['id', 'name'])
        );
        return response()->json($data);
    }

    public function cities(Request $request)
    {
        $cacheKey = 'ref:cities:' . ($request->state_id ?? 'all') . ':' . md5($request->state ?? '');
        $data = Cache::remember($cacheKey, 86400, function () use ($request) {
            $query = City::orderBy('name');

            if ($request->filled('state_id')) {
                $query->where('state_id', $request->state_id);
            } elseif ($request->filled('state')) {
                $stateId = State::where('name', $request->state)->value('id');
                if ($stateId) {
                    $query->where('state_id', $stateId);
                }
            }

            return $query->limit($request->integer('limit', 500))->get(['id', 'state_id', 'name']);
        });
        return response()->json($data);
    }
}
