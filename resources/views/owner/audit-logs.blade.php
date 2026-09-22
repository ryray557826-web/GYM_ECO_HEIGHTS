@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-4">
        <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">SYSTEM & MEMBERSHIP AUDIT LOG</h3>
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl overflow-x-auto shadow">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[11px]">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">ACTION</th>
                        <th class="p-3">TARGET</th>
                        <th class="p-3">VALIDITY</th>
                        <th class="p-3">PERFORMED BY</th>
                        <th class="p-3">DATE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1e293b] text-gray-300 font-mono">
                    @forelse($auditLogs as $a)
                    <tr>
                        <td class="p-3 text-gray-400">{{ $a->log_code }}</td>
                        <td class="p-3 font-sans font-bold text-white">{{ $a->action }}</td>
                        <td class="p-3 font-sans text-gray-300">{{ class_basename($a->entity_type) }} #{{ $a->entity_id }}</td>
                        <td class="p-3 font-sans">{{ $a->validity_period ?? '—' }}</td>
                        <td class="p-3 font-sans">{{ $a->performed_by }}</td>
                        <td class="p-3">{{ $a->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500 font-sans">No audit events recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection