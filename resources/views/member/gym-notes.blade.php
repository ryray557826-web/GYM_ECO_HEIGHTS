@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Member Navigation Tabs -->
    <div class="border-b border-[#1e293b]">
        <nav class="flex space-x-8 text-xs font-heading font-bold uppercase tracking-wider">
            <a href="{{ route('member.overview') }}" class="py-3 text-gray-400 hover:text-gray-200 border-b-2 border-transparent">
                Overview
            </a>
            <a href="{{ route('member.notes.index') }}" class="py-3 text-[#76c800] border-b-2 border-[#76c800] flex items-center space-x-1.5">
                <span>Gym Notes</span>
                <span class="bg-[#1e293b] text-white text-[10px] px-1.5 py-0.2 rounded font-mono">{{ $gymNotes->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- Log New Workout Box -->
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
        <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white mb-3">+ Log New Workout Routine</h3>
        <form action="{{ route('member.notes.store') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-gray-400 mb-1">Workout Date</label>
                    <input type="date" name="workout_date" value="{{ date('Y-m-d') }}" required 
                        class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">Routine Title</label>
                    <input type="text" name="routine_title" placeholder="e.g. Chest & Triceps Day" required 
                        class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                </div>
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Exercises & Sets Notes</label>
                <textarea name="notes" rows="3" placeholder="Bench Press 4x10 @ 60kg, Incline Dumbbell Press 3x12 @ 20kg..." required 
                    class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white"></textarea>
            </div>
            <button type="submit" class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold px-4 py-2 rounded uppercase font-heading text-xs">
                Save Workout Note
            </button>
        </form>
    </div>

    <!-- Saved Notes Grid -->
    <div class="space-y-3">
        <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">SAVED WORKOUT NOTES</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($gymNotes as $note)
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-4 space-y-2 shadow">
                <div class="flex justify-between items-start">
                    <h4 class="font-bold text-white text-sm">{{ $note->routine_title }}</h4>
                    <span class="text-xs font-mono text-gray-500">{{ $note->workout_date->format('Y-m-d') }}</span>
                </div>
                <p class="text-xs text-gray-300 whitespace-pre-line">{{ $note->notes }}</p>
                <div class="pt-2 text-right">
                    <form action="{{ route('member.notes.destroy', $note->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-400 hover:text-red-300">Delete</button>
                    </form>
                </div>
            </div>
            @empty
            <p class="text-gray-500 text-xs italic">No workout notes logged yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection