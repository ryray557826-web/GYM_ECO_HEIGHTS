@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-4">
        <div class="flex justify-between items-center gap-3">
            <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">MEMBER ATTENDANCE LOG SHEET</h3>
            <form method="GET" class="flex gap-2">
                <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" 
                    class="bg-[#0b1120] border border-[#1e293b] rounded-lg px-3 py-1.5 text-xs text-white font-mono">
                <button type="submit" class="bg-[#1e293b] hover:bg-[#334155] border border-gray-700 text-white font-bold px-3 py-1.5 rounded text-xs">
                    Filter
                </button>
            </form>
        </div>

        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl overflow-x-auto shadow">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[11px]">
                    <tr>
                        <th class="p-3">DATE</th>
                        <th class="p-3">TIME</th>
                        <th class="p-3">MEMBER ID</th>
                        <th class="p-3">NAME</th>
                        <th class="p-3">PLAN TYPE</th>
                        <th class="p-3">ENTRY TYPE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1e293b] text-gray-300">
                    @forelse($attendances as $att)
                    <tr>
                        <td class="p-3">{{ $att->attendance_date->format('Y-m-d') }}</td>
                        <td class="p-3 text-gray-400">{{ $att->check_in_time }}</td>
                        <td class="p-3 text-[#76c800] font-bold">{{ $att->member ? $att->member->member_code : 'WALK-IN' }}</td>
                        <td class="p-3 font-sans font-bold text-white">{{ $att->customer ? $att->customer->full_name : 'Visitor' }}</td>
                        <td class="p-3 font-sans text-gray-400">
                            {{ $att->member && $att->member->latestSubscription && $att->member->latestSubscription->package ? $att->member->latestSubscription->package->name : 'Single Session' }}
                        </td>
                        <td class="p-3 font-sans">
                            @if($att->entry_type === 'membership')
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-emerald-950 text-[#76c800]">MONTHLY ACTIVE PASS (₱0)</span>
                            @else
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-yellow-950 text-yellow-400">PAID ENTRY (₱50)</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500 font-sans">No attendance records for this date.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection