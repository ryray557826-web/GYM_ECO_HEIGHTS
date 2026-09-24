@php
    $navPending = \App\Models\Payment::where('status', 'pending')->count() + \App\Models\Member::where('membership_status', 'pending')->count();
@endphp
<div class="border-b border-[#1e293b]">
    <nav class="flex space-x-6 text-xs font-heading font-bold uppercase tracking-wider">
        <a href="{{ route('owner.checkin') }}" 
           class="py-3 {{ request()->routeIs('owner.checkin*') ? 'text-[#76c800] border-b-2 border-[#76c800]' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent' }}">
            Check-In
        </a>
        <a href="{{ route('owner.attendance.index') }}" 
           class="py-3 {{ request()->routeIs('owner.attendance*') ? 'text-[#76c800] border-b-2 border-[#76c800]' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent' }}">
            Attendance
        </a>
        <a href="{{ route('owner.members.index') }}" 
           class="py-3 {{ request()->routeIs('owner.members*') ? 'text-[#76c800] border-b-2 border-[#76c800]' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent' }}">
            Members
        </a>
        <a href="{{ route('owner.payments.index') }}" 
           class="py-3 {{ request()->routeIs('owner.payments*') ? 'text-[#76c800] border-b-2 border-[#76c800]' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent' }}">
            Payments ({{ $navPending }})
        </a>
        <a href="{{ route('owner.audit.index') }}" 
           class="py-3 {{ request()->routeIs('owner.audit*') ? 'text-[#76c800] border-b-2 border-[#76c800]' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent' }}">
            Audit Logs
        </a>
        <a href="{{ route('owner.finance.index') }}" 
           class="py-3 {{ request()->routeIs('owner.finance*') ? 'text-[#76c800] border-b-2 border-[#76c800]' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent' }}">
            Revenue & Expenses
        </a>
        <a href="{{ route('owner.equipment.index') }}" 
           class="py-3 {{ request()->routeIs('owner.equipment*') ? 'text-[#76c800] border-b-2 border-[#76c800]' : 'text-gray-400 hover:text-gray-200 border-b-2 border-transparent' }}">
            Equipment
        </a>
    </nav>
</div>