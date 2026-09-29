<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal E-Rapor Wali Murid | SANS PAUD Anak Saleh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-900 via-slate-900 to-indigo-950 min-h-screen flex items-center justify-center p-4 text-slate-100">

    <div class="w-full max-w-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-white/20 dark:border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6 text-slate-800 dark:text-slate-100">
        
        <!-- BRANDING HEADER -->
        <div class="text-center space-y-2">
            @if (setting('app_logo'))
                <img src="{{ asset('storage/' . setting('app_logo')) }}" alt="Logo" class="w-16 h-16 mx-auto rounded-2xl object-contain shadow-md">
            @else
                <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-2xl shadow-md">
                    AS
                </div>
            @endif
            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-slate-50">
                    Portal E-Rapor Ananda
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    KB - TK - DAYCARE - TPQ ANAK SALEH MALANG
                </p>
            </div>
        </div>

        <!-- ALERTS -->
        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-400 text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 text-amber-800 dark:text-amber-400 text-xs font-medium">
                {{ session('warning') }}
            </div>
        @endif

        <!-- LOGIN FORM -->
        <form method="POST" action="{{ route('portal.report-cards.check') }}" class="space-y-4">
            @csrf

            <!-- Input NIS -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Nomor Induk Siswa (NIS)
                </label>
                <input type="text" name="nis" value="{{ old('nis') }}" required placeholder="Contoh: 26.PAUD.001"
                    class="w-full h-11 px-4 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-mono text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <!-- Input PIN Akses -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    PIN Akses Rapor (4 Digit)
                </label>
                <input type="password" name="pin" maxlength="10" required placeholder="••••"
                    class="w-full h-11 px-4 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-mono tracking-widest text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <span class="text-[10px] text-slate-400 mt-1 block">PIN akses dapat diperoleh dari Wali Kelas / Ustadzah kelompok ananda.</span>
            </div>

            <!-- Pilih Tahun & Semester -->
            <div class="grid grid-cols-2 gap-3 pt-1">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Tahun Ajaran</label>
                    <select name="academic_year_id" class="w-full h-9 px-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-800 dark:text-slate-200">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $year->is_active ? 'selected' : '' }}>
                                {{ $year->name }} {{ $year->is_active ? '★' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Semester</label>
                    <select name="semester" class="w-full h-9 px-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-800 dark:text-slate-200">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full h-11 mt-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition-all cursor-pointer flex items-center justify-center gap-2">
                <span>Buka Rapor Ananda</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>

        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                &larr; Masuk sebagai Guru / Staf
            </a>
        </div>
    </div>

</body>
</html>
