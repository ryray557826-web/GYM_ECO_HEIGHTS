<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromoController extends Controller
{
    public function index()
    {
        $packages = Package::with('features')->orderBy('is_active', 'desc')->get();
        return view('owner.promos.index', compact('packages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:150',
            'plan_type'        => 'required|string|max:50',
            'price'            => 'required|numeric|min:0',
            'duration_in_days' => 'required|integer|min:1',
            'promo_badge'      => 'nullable|string|max:50',
            'description'      => 'nullable|string|max:255',
            'features'         => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $code = 'PKG-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $request->name), 0, 4)) . '-' . (int)$request->price;

            $package = Package::create([
                'package_code'     => $code,
                'name'             => $request->name,
                'plan_type'        => strtolower($request->plan_type),
                'price'            => $request->price,
                'duration_in_days' => $request->duration_in_days,
                'is_promo'         => $request->has('is_promo'),
                'promo_badge'      => $request->promo_badge,
                'description'      => $request->description,
                'is_active'        => true,
            ]);

            if ($request->filled('features')) {
                $features = array_map('trim', explode(',', $request->features));
                foreach ($features as $f) {
                    if (!empty($f)) {
                        PackageFeature::create([
                            'package_id'          => $package->id,
                            'feature_description' => $f
                        ]);
                    }
                }
            }

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "Promo / Plan Created: {$package->name} (₱{$package->price})",
                'performed_by'    => 'Owner'
            ]);
        });

        return back()->with('success', 'New package / promotion added successfully.');
    }

    public function update(Request $request, Package $package)
    {
        $request->validate([
            'name'             => 'required|string|max:150',
            'price'            => 'required|numeric|min:0',
            'duration_in_days' => 'required|integer|min:1',
            'promo_badge'      => 'nullable|string|max:50',
            'description'      => 'nullable|string|max:255',
        ]);

        $package->update([
            'name'             => $request->name,
            'price'            => $request->price,
            'duration_in_days' => $request->duration_in_days,
            'is_promo'         => $request->has('is_promo'),
            'promo_badge'      => $request->promo_badge,
            'description'      => $request->description,
        ]);

        return back()->with('success', "Package {$package->name} updated.");
    }

    public function toggle(Package $package)
    {
        $package->update(['is_active' => !$package->is_active]);
        $status = $package->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Package {$package->name} is now {$status}.");
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return back()->with('success', "Package removed.");
    }
}