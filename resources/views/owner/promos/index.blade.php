@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">PROMOTIONS & MEMBERSHIP PACKAGES</h3>
            <p class="text-xs text-slate-400">Configure rates, discounts, and custom promos visible to gym members.</p>
        </div>
        <button onclick="document.getElementById('addPromoModal').showModal()" 
            class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold px-4 py-2 rounded-xl text-xs uppercase font-heading transition shadow-lg shadow-emerald-500/20">
            + Create New Promo / Package
        </button>
    </div>

    <!-- Packages Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-x-auto shadow-md">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950 text-slate-400 uppercase font-heading text-[10px]">
                <tr>
                    <th class="p-3.5">PACKAGE CODE</th>
                    <th class="p-3.5">NAME & BADGE</th>
                    <th class="p-3.5">PLAN TYPE</th>
                    <th class="p-3.5">RATE (₱)</th>
                    <th class="p-3.5">VALIDITY</th>
                    <th class="p-3.5">STATUS</th>
                    <th class="p-3.5 text-right">ACTIONS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300">
                @forelse($packages as $pkg)
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="p-3.5 font-mono text-slate-400 font-semibold">{{ $pkg->package_code }}</td>
                    <td class="p-3.5">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-white text-sm">{{ $pkg->name }}</span>
                            @if($pkg->promo_badge)
                                <span class="bg-emerald-950 text-emerald-400 border border-emerald-500/30 text-[9px] px-2 py-0.5 rounded font-bold uppercase">
                                    {{ $pkg->promo_badge }}
                                </span>
                            @endif
                        </div>
                        @if($pkg->description)
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $pkg->description }}</p>
                        @endif
                    </td>
                    <td class="p-3.5 uppercase font-mono text-xs">{{ $pkg->plan_type }}</td>
                    <td class="p-3.5 font-mono text-emerald-400 font-bold text-sm">₱{{ number_format($pkg->price, 2) }}</td>
                    <td class="p-3.5 font-mono">{{ $pkg->duration_in_days }} Days</td>
                    <td class="p-3.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $pkg->is_active ? 'bg-emerald-950 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                            {{ $pkg->is_active ? 'ACTIVE' : 'INACTIVE' }}
                        </span>
                    </td>
                    <td class="p-3.5 text-right space-x-2">
                        <form action="{{ route('owner.promos.toggle', $pkg->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-xs {{ $pkg->is_active ? 'text-amber-400 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }}">
                                {{ $pkg->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <form action="{{ route('owner.promos.destroy', $pkg->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this package?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-4 text-center text-slate-500 italic">No packages or promotional deals registered yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Promo -->
<dialog id="addPromoModal" class="bg-slate-900 border border-slate-800 text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">+ Create Gym Promo / Package</h3>
        <button onclick="document.getElementById('addPromoModal').close()" class="text-slate-400 hover:text-white">✕</button>
    </div>
    <form action="{{ route('owner.promos.store') }}" method="POST" class="space-y-3.5 text-xs">
        @csrf
        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Package / Promo Name *</label>
            <input type="text" name="name" placeholder="e.g. Summer Fitness Promo (3 Months)" required 
                class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Plan Category *</label>
                <select name="plan_type" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                    <option value="daily">Daily Walk-In</option>
                    <option value="special_promo">Special Promo</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Rate (₱) *</label>
                <input type="number" step="0.01" name="price" placeholder="750.00" required 
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-mono">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Duration (in Days) *</label>
                <input type="number" name="duration_in_days" value="30" min="1" required 
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-mono">
            </div>
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Promo Badge Tag (Optional)</label>
                <input type="text" name="promo_badge" placeholder="e.g. 15% OFF" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Features / Perks (Comma separated)</label>
            <input type="text" name="features" placeholder="Free Locker, Shower Access, Unlimited Floor Access" 
                class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
        </div>
        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Description</label>
            <textarea name="description" rows="2" placeholder="Promo terms or description..." 
                class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white"></textarea>
        </div>
        <div class="flex items-center space-x-2 pt-1">
            <input type="checkbox" name="is_promo" id="is_promo" value="1" class="rounded bg-slate-950 border-slate-800 text-emerald-500">
            <label for="is_promo" class="text-slate-300">Highlight as active promotional offer</label>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('addPromoModal').close()" class="px-3.5 py-2 border border-slate-800 text-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold rounded-lg uppercase font-heading">Save Promo</button>
        </div>
    </form>
</dialog>
@endsection