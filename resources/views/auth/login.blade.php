<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Heights Fitness Gym - Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #080d1a; font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Chakra Petch', sans-serif; letter-spacing: 0.05em; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <!-- Top Branding -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 rounded-full bg-[#0b1120] border border-[#1e293b] flex items-center justify-center mx-auto mb-3 shadow-inner">
            <div class="w-12 h-12 rounded-full bg-emerald-950/80 border border-[#76c800]/40 flex items-center justify-center">
                <span class="text-orange-400 text-2xl">⚡</span>
            </div>
        </div>
        <h1 class="text-3xl font-heading font-extrabold text-white tracking-widest">ECO HEIGHTS</h1>
        <p class="text-[#76c800] font-heading font-bold text-xs tracking-widest mt-1">FITNESS GYM · MANAGEMENT SYSTEM</p>
    </div>

    <!-- Login Card -->
    <div class="w-full max-w-md bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 sm:p-8 shadow-2xl">
        
        <div class="mb-5 pb-3 border-b border-[#1e293b]">
            <h2 class="text-sm font-heading font-bold uppercase tracking-wider text-white">Account Sign In</h2>
            <p class="text-xs text-slate-400 mt-0.5">Enter your email address or Member ID to continue.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-950/60 border border-red-800 text-red-300 text-xs rounded-xl">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/login" class="space-y-4">
            @csrf
            
            <!-- Universal Identifier Field -->
            <div>
                <label for="identifier" class="block text-xs font-bold text-sky-400 font-mono tracking-wider mb-1.5">
                    EMAIL OR MEMBER ID <span class="text-[#76c800]">*</span>
                </label>
                <input type="text" name="identifier" id="identifier" value="{{ old('identifier') }}" required autofocus
                    class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl px-4 py-2.5 text-gray-100 text-sm focus:outline-none focus:border-[#76c800] font-sans" 
                    placeholder="e.g. ECO-001 or name@example.com">
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-xs font-bold text-sky-400 font-mono tracking-wider mb-1.5">
                    PASSWORD <span class="text-[#76c800]">*</span>
                </label>
                <input type="password" name="password" id="password" required
                    class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl px-4 py-2.5 text-gray-100 text-sm focus:outline-none focus:border-[#76c800]"
                    placeholder="Enter your password">
            </div>

            <!-- Remember Me -->
            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="remember" id="remember" value="1" checked 
                    class="w-4 h-4 rounded bg-[#080d1a] border border-[#1e293b] text-[#76c800] focus:ring-0 focus:ring-offset-0 cursor-pointer">
                <label for="remember" class="text-xs text-slate-300 cursor-pointer select-none">
                    Remember me on this device
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                class="w-full py-3 bg-[#76c800] hover:bg-[#68b000] text-slate-950 font-extrabold text-sm rounded-xl transition font-heading tracking-wider shadow-lg shadow-[#76c800]/10">
                Sign In
            </button>

            <!-- Registration Link -->
            <div class="text-center pt-2">
                <span class="text-xs text-slate-400">Not a registered member yet? </span>
                <a href="{{ route('register') }}" class="text-xs text-[#76c800] font-semibold hover:underline">Register here</a>
            </div>
        </form>
    </div>

</body>
</html>