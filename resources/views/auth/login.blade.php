<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Heights - Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0b0f19; font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Chakra Petch', sans-serif; letter-spacing: 0.05em; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <!-- Top Logo & Header -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 rounded-full bg-[#080d1a] border border-[#1e293b] flex items-center justify-center mx-auto mb-3 shadow-inner">
            <div class="w-12 h-12 rounded-full bg-emerald-950/80 border border-[#76c800]/40 flex items-center justify-center">
                <span class="text-orange-400 text-2xl">⚡</span>
            </div>
        </div>
        <h1 class="text-3xl font-heading font-extrabold text-white tracking-widest">ECO HEIGHTS</h1>
        <p class="text-[#76c800] font-heading font-bold text-xs tracking-widest mt-1">FITNESS GYM · MANAGEMENT SYSTEM</p>
    </div>

    <!-- Login Box -->
    <div class="w-full max-w-md bg-[#111827] border border-[#1f293d] rounded-xl p-6 shadow-2xl">
        
        <!-- Toggle Tabs: Member vs Owner -->
        <div class="grid grid-cols-2 bg-[#080d1a] p-1 rounded-lg mb-6 border border-gray-800">
            <button type="button" id="tabMember" onclick="switchLoginRole('member')" 
                class="py-2 text-xs font-bold rounded-md bg-[#76c800] text-black transition">
                Member
            </button>
            <button type="button" id="tabOwner" onclick="switchLoginRole('owner')" 
                class="py-2 text-xs font-bold text-gray-400 hover:text-white transition">
                Owner
            </button>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-950/60 border border-red-800 text-red-300 text-xs rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label id="identifier_label" class="block text-xs font-bold text-sky-400 font-mono tracking-wider mb-1.5">
                    MEMBER ID <span class="text-[#76c800]">*</span>
                </label>
                <input type="text" name="identifier" id="identifier" value="{{ old('identifier') }}" required
                    class="w-full bg-[#080d1a] border border-[#1f293d] rounded-lg px-4 py-2.5 text-gray-100 text-sm focus:outline-none focus:border-[#76c800] font-mono uppercase" 
                    placeholder="e.g. ECO-001">
            </div>

            <div>
                <label class="block text-xs font-bold text-sky-400 font-mono tracking-wider mb-1.5">
                    PASSWORD <span class="text-[#76c800]">*</span>
                </label>
                <input type="password" name="password" id="password" required
                    class="w-full bg-[#080d1a] border border-[#1f293d] rounded-lg px-4 py-2.5 text-gray-100 text-sm focus:outline-none focus:border-[#76c800]"
                    placeholder="Enter your password">
            </div>

            <!-- Remember Me Checkbox -->
            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="remember" id="remember" value="1" checked 
                    class="w-4 h-4 rounded bg-[#080d1a] border border-[#1f293d] text-[#76c800] focus:ring-0 focus:ring-offset-0 cursor-pointer">
                <label for="remember" class="text-xs text-gray-300 cursor-pointer select-none">
                    Remember me on this device
                </label>
            </div>

            <button type="submit" 
                class="w-full py-3 bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold text-sm rounded-lg transition font-heading tracking-wider shadow-lg shadow-[#76c800]/10">
                Sign In
            </button>

            <div class="text-center pt-2">
                <span class="text-xs text-gray-400">New member? </span>
                <a href="{{ route('register') }}" class="text-xs text-[#76c800] font-semibold hover:underline">Register here</a>
            </div>
        </form>
    </div>

    <script>
    function switchLoginRole(role) {
        const tabMember = document.getElementById('tabMember');
        const tabOwner = document.getElementById('tabOwner');
        const label = document.getElementById('identifier_label');
        const input = document.getElementById('identifier');

        if(role === 'member') {
            tabMember.className = "py-2 text-xs font-bold rounded-md bg-[#76c800] text-black transition";
            tabOwner.className = "py-2 text-xs font-bold text-gray-400 hover:text-white transition";
            label.innerHTML = 'MEMBER ID <span class="text-[#76c800]">*</span>';
            input.placeholder = "e.g. ECO-001";
            input.value = "";
        } else {
            tabOwner.className = "py-2 text-xs font-bold rounded-md bg-[#76c800] text-black transition";
            tabMember.className = "py-2 text-xs font-bold text-gray-400 hover:text-white transition";
            label.innerHTML = 'OWNER EMAIL <span class="text-[#76c800]">*</span>';
            input.placeholder = "e.g. owner@ecoheights.com";
            input.value = "";
        }
    }
    </script>
</body>
</html>