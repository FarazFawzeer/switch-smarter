<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $query = Site::with('contract:id,project_name,client_name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('site_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->input('contract_id'));
        }

        $sites = $query->latest()->paginate(10)->withQueryString();
        $contracts = Contract::select('id', 'project_name', 'client_name')->orderBy('project_name')->get();

        return view('admin.sites.index', compact('sites', 'contracts'));
    }

    public function create()
    {
        $contracts = Contract::select('id', 'project_name', 'client_name')
            ->where('status', 'active')
            ->orderBy('project_name')
            ->get();

        return view('admin.sites.create', compact('contracts'));
    }

    public function store(Request $request)
    {
        $validator = $this->validateSite($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $site = Site::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Site created successfully!',
            'site'    => $site,
        ]);
    }

    public function edit(Site $site)
    {
        $contracts = Contract::select('id', 'project_name', 'client_name')
            ->orderBy('project_name')
            ->get();

        return view('admin.sites.edit', compact('site', 'contracts'));
    }

    public function update(Request $request, Site $site)
    {
        $validator = $this->validateSite($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $site->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Site updated successfully!',
            'site'    => $site,
        ]);
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return response()->json([
            'success' => true,
            'message' => 'Site deleted successfully!'
        ]);
    }

    private function validateSite(Request $request)
    {
        return Validator::make($request->all(), [
            'contract_id'     => ['required', 'exists:contracts,id'],
            'site_name'       => ['required', 'string', 'max:255'],
            'address'         => ['nullable', 'string', 'max:500'],
            'latitude'        => ['required', 'numeric', 'between:-90,90'],
            'longitude'       => ['required', 'numeric', 'between:-180,180'],
            'radius_meters'   => ['required', 'integer', 'min:50', 'max:5000'],
            'elevator_count'  => ['required', 'integer', 'min:1', 'max:50'],
        ], [
            'latitude.between'  => 'Latitude must be a valid coordinate between -90 and 90.',
            'longitude.between' => 'Longitude must be a valid coordinate between -180 and 180.',
            'radius_meters.min' => 'Radius must be at least 50 meters (GPS accuracy limits make anything smaller unreliable).',
            'radius_meters.max' => 'Radius cannot exceed 5000 meters.',
        ]);
    }
}