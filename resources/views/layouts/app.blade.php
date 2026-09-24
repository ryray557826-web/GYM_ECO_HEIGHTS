<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Eco Heights Fitness Gym</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #080d1a; color: #f3f4f6; font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Chakra Petch', sans-serif; letter-spacing: 0.04em; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- Top Navigation Bar -->
    <nav class="bg-[#0b1120] border-b border-[#1e293b] px-6 py-3.5 flex justify-between items-center">
        <div class="flex items-center space-x-2">
            <span class="text-orange-400 font-bold text-lg">⚡</span>
            <span class="font-heading font-extrabold text-white text-base tracking-wider">
                ECO HEIGHTS <span class="text-gray-500 font-normal">·</span> 
                <span class="text-gray-300 font-semibold text-xs tracking-normal uppercase">
                    {{ auth()->check() && auth()->user()->isOwner() ? 'OWNER' : 'GYM' }}
                </span>
            </span>
        </div>

        @auth
        <div class="flex items-center space-x-4">
            <span class="text-xs text-gray-300 font-medium">
                {{ auth()->user()->customer ? auth()->user()->customer->full_name : auth()->user()->name ?? 'Owner' }}
            </span>
            @if(auth()->user()->customer && auth()->user()->customer->member)
                <span class="bg-emerald-950/80 border border-[#76c800]/40 text-[#76c800] text-[11px] font-mono font-bold px-2 py-0.5 rounded">
                    {{ auth()->user()->customer->member->member_code }}
                </span>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-xs font-semibold text-gray-400 hover:text-white transition">
                    Logout
                </button>
            </form>
        </div>
        @endauth
    </nav>

    <!-- Main Content Yield -->
<main class="flex-1 px-4 sm:px-8 lg:px-12 py-8 max-w-[96%] xl:max-w-[1600px] 2xl:max-w-[1850px] w-full mx-auto">
        @yield('content')
    </main>

    <!-- Floating Help Button -->
    <div class="fixed bottom-4 right-4">
        <button class="w-7 h-7 rounded-full bg-white text-black font-bold flex items-center justify-center shadow-lg text-xs">
            ?
        </button>
    </div>

    @stack('scripts')
</body>
</html>