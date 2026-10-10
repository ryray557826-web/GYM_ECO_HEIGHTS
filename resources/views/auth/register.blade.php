@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 sm:p-8 shadow-2xl">
    <div class="mb-4 pb-3 border-b border-[#1e293b]">
        <h2 class="text-lg font-heading font-bold text-white uppercase tracking-wider">Member Registration</h2>
        <p class="text-xs text-slate-400 mt-0.5">Register for an account. New accounts begin in Pending status for owner verification.</p>
    </div>
    
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-950/60 border border-red-800 text-red-300 text-xs rounded-xl">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="/register" class="space-y-3.5 text-xs">
        @csrf
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Last Name *</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Email Address *</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Password (At least 6 characters) *</label>
            <input type="password" name="password" required minlength="6" class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">Date of Birth *</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" onclick="this.showPicker()" required class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white font-mono cursor-pointer">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Contact Phone *</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Emergency Contact Phone</label>
            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white font-mono">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Home Address *</label>
            <textarea name="address" rows="2" required class="w-full bg-[#080d1a] border border-gray-800 rounded-xl p-2.5 text-white">{{ old('address') }}</textarea>
        </div>
        <button type="submit" class="w-full py-3 bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold text-sm rounded-xl font-heading uppercase mt-2 shadow-lg shadow-[#76c800]/10 transition">
            Register Member
        </button>
        <div class="text-center pt-2">
            <span class="text-xs text-gray-400">Already have an account? </span>
            <a href="{{ route('login') }}" class="text-xs text-[#76c800] font-semibold hover:underline">Sign in here</a>
        </div>
    </form>
</div>
@endsection