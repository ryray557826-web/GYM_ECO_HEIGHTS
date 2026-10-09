@php
    $navPending = \App\Models\Payment::where('status', 'pending')->count() + \App\Models\Member::where('membership_status', 'pending')->count();
@endphp
<div class="border-b border-slate-800 -mx-4 px-4 sm:mx-0 sm:px-0">
    <nav class="flex space-x-6 text-xs font-heading font-bold uppercase tracking-wider overflow-x-auto no-scrollbar whitespace-nowrap">
        <a href="{{ route('owner.checkin') }}" 
           class="py-3 {{ request()->routeIs('owner.checkin*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }}">
            Check-In
        </a>
        <a href="{{ route('owner.attendance.index') }}" 
           class="py-3 {{ request()->routeIs('owner.attendance*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }}">
            Attendance
        </a>
        <a href="{{ route('owner.members.index') }}" 
           class="py-3 {{ request()->routeIs('owner.members*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }}">
            Members
        </a>
        <a href="{{ route('owner.promos.index') }}" 
           class="py-3 {{ request()->routeIs('owner.promos*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }} flex items-center space-x-1.5">
            <span>Promos & Rates</span>
            <span class="bg-emerald-950 text-emerald-400 text-[10px] px-1.5 py-0.2 rounded border border-emerald-500/30">New</span>
        </a>
        <a href="{{ route('owner.payments.index') }}" 
           class="py-3 {{ request()->routeIs('owner.payments*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }}">
            Payments ({{ $navPending }})
        </a>
        <a href="{{ route('owner.audit.index') }}" 
           class="py-3 {{ request()->routeIs('owner.audit*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }}">
            Audit Logs
        </a>
        <a href="{{ route('owner.finance.index') }}" 
           class="py-3 {{ request()->routeIs('owner.finance*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }}">
            Revenue & Expenses
        </a>
        <a href="{{ route('owner.equipment.index') }}" 
           class="py-3 {{ request()->routeIs('owner.equipment*') ? 'text-emerald-400 border-b-2 border-emerald-400' : 'text-slate-400 hover:text-slate-200 border-b-2 border-transparent' }}">
            Equipment
        </a>
    </nav>
</div>