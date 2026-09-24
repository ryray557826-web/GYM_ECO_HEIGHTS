@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow-2xl">
    <h2 class="text-xl font-heading font-bold text-white uppercase mb-4">Member Registration</h2>
    
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-950/60 border border-red-800 text-red-300 text-xs rounded-lg">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-3 text-xs">
        @csrf
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-gray-400 mb-1">First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Last Name *</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Email Address *</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Password (At least 6 characters) *</label>
            <input type="password" name="password" required minlength="6" class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-gray-400 mb-1">Date of Birth *</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white font-mono">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Contact Number *</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" required class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Emergency Contact Phone</label>
            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white font-mono">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Home Address *</label>
            <textarea name="address" rows="2" required class="w-full bg-[#080d1a] border border-gray-800 rounded p-2 text-white">{{ old('address') }}</textarea>
        </div>
        <button type="submit" class="w-full py-2.5 bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold text-sm rounded font-heading uppercase mt-3">
            Register Member
        </button>
        <div class="text-center pt-2">
            <span class="text-xs text-gray-400">Already a member? </span>
            <a href="{{ route('login') }}" class="text-xs text-[#76c800] font-semibold hover:underline">Sign in here</a>
        </div>
    </form>
</div>
@endsection