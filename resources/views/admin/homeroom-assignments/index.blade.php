<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-3.5 sm:space-y-4" x-data="homeroomApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Penugasan Guru</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Penugasan pendidik dan guru per kelompok belajar tersimpan per tahun ajaran.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('teachers.index') }}"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Data Guru
                </a>
                <form action="{{ route('homeroom-assignments.sync-classrooms') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer"
                        title="Sinkronkan wali kelas dari rombel">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-500"></i>
                        Sinkronkan Rombel
                    </button>
                </form>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tugaskan Guru
                </button>
            </div>
        </section>

        <!-- COMPACT KPI STATS (MINIMALIS & LANGSUNG FOKUS KE TABEL) -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
            <!-- Stat 1: Total Penugasan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Penugasan</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 font-mono">
                            {{ number_format($stats['total_assignments'] ?? $assignments->total()) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Wali Kelas</span>
                    </div>
                </div>
            </div>

            <!-- Stat 2: Total Kelompok Terisi -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                    <i data-lucide="shapes" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Kelompok Terisi</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ number_format($stats['assigned_classrooms'] ?? 0) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">/ {{ number_format($stats['total_classrooms'] ?? $classrooms->count()) }} Kelompok</span>
                    </div>
                </div>
            </div>

            <!-- Stat 3: Guru Siap Ditugaskan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-900/40">
                    <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Guru Tersedia</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                            {{ number_format($stats['total_teachers'] ?? $teachers->count()) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Guru</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- FILTERS TOOLBAR (KIRI: TAHUN AJARAN, JENJANG, KELAS, STATUS, BARIS | KANAN: SEARCH MANDIRI) -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
            <form method="GET" action="{{ route('homeroom-assignments.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                
                <!-- Left: Dropdown Tahun Ajaran, Jenjang, Kelas & Status -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- 1. Tahun Ajaran -->
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- 2. Jenjang -->
                    <select name="jenjang_id" onchange="handleJenjangFilterChange(this)"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="all" {{ $selectedJenjangId === 'all' || empty($selectedJenjangId) ? 'selected' : '' }}>Semua Jenjang</option>
                        @foreach($jenjangs as $j)
                            <option value="{{ $j->id }}" {{ $selectedJenjangId == $j->id ? 'selected' : '' }}>
                                {{ $j->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- 3. Kelas (Sesuai Jenjang yang Sedang Dipilih) -->
                    <select name="class_level_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="all" {{ $selectedClassLevelId === 'all' || empty($selectedClassLevelId) ? 'selected' : '' }}>Semua Kelas</option>
                        @foreach($availableClassLevels as $lvl)
                            <option value="{{ $lvl->id }}" {{ $selectedClassLevelId == $lvl->id ? 'selected' : '' }}>
                                {{ $lvl->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- 4. Peran Penugasan -->
                    <select name="role" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="all" {{ ($selectedRole ?? 'all') === 'all' ? 'selected' : '' }}>Semua Peran</option>
                        <option value="wali_kelas" {{ ($selectedRole ?? '') === 'wali_kelas' ? 'selected' : '' }}>Wali Kelas</option>
                        <option value="guru_kelas" {{ ($selectedRole ?? '') === 'guru_kelas' ? 'selected' : '' }}>Guru Kelas</option>
                        <option value="gpk" {{ ($selectedRole ?? '') === 'gpk' ? 'selected' : '' }}>GPK (Inklusi)</option>
                        <option value="gpq" {{ ($selectedRole ?? '') === 'gpq' ? 'selected' : '' }}>GPQ (Al-Qur'an)</option>
                        <option value="guru_mapel" {{ ($selectedRole ?? '') === 'guru_mapel' ? 'selected' : '' }}>Guru Sentra / Mapel</option>
                        <option value="koordinator_tingkat" {{ ($selectedRole ?? '') === 'koordinator_tingkat' ? 'selected' : '' }}>Koordinator Tingkat</option>
                    </select>

                    <!-- 5. Status Penugasan -->
                    <select name="status" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="all" {{ ($selectedStatus ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="active" {{ ($selectedStatus ?? '') === 'active' || ($selectedStatus ?? '') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ ($selectedStatus ?? '') === 'inactive' || ($selectedStatus ?? '') === '0' ? 'selected' : '' }}>Non-aktif</option>
                    </select>

                    <!-- 6. Jumlah Baris -->
                    <select name="per_page" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10 Data</option>
                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 Data</option>
                        <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25 Data</option>
                        <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50 Data</option>
                        <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100 Data</option>
                        <option value="all" {{ request('per_page') == 'all' || request('per_page') == '99999' ? 'selected' : '' }}>Semua</option>
                    </select>

                    @if(request()->filled('search') || (request('jenjang_id') && request('jenjang_id') !== 'all') || (request('class_level_id') && request('class_level_id') !== 'all') || (request('role') && request('role') !== 'all') || (request('status') && request('status') !== 'all') || (request()->filled('per_page') && request('per_page') != 15))
                        <a href="{{ route('homeroom-assignments.index', ['academic_year_id' => $selectedYearId]) }}" class="h-9 px-2.5 flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 rounded-lg transition-colors" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>

                <!-- Right: Search Bar (Live Search) -->
                <div x-data="liveSearchBox('assignments-table-container', '{{ request('search') }}')" class="relative w-full sm:w-72 md:w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg x-show="isSearching" x-cloak class="animate-spin w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg x-show="!isSearching" class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </span>

                    <input type="text" name="search" x-model="searchVal" @input="onInput()" @keydown.enter.prevent="performSearch()" placeholder="Cari..."
                        class="w-full h-9 pl-9 pr-8 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors">

                    <button type="button" x-show="searchVal && searchVal.trim() !== ''" @click="clear()"
                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer bg-transparent border-0"
                        title="Bersihkan pencarian">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                </div>

            </form>
        </section>

        <!-- TABLE LIST PENUGASAN GURU -->
        <section id="assignments-table-container" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/30">
                <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Daftar Penugasan Guru
                </h3>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                    Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $assignments->count() }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $assignments->total() }}</span> data
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Jenjang</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Kelas</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Kelompok Belajar</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-36">Peran Guru</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider min-w-[200px]">Guru Ditugaskan</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-24">Status</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($assignments as $a)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">

                                <!-- 2. Jenjang -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $jenjangName = $a->jenjang?->name ?? $a->classroom?->jenjang?->name ?? $a->classLevel?->jenjang?->name;
                                    @endphp
                                    @if($jenjangName)
                                        <span class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 whitespace-nowrap">
                                            {{ $jenjangName }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <!-- 3. Kelas -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                        {{ $a->classLevel?->name ?? $a->classroom?->classLevel?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 4. Kelompok Belajar -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($a->classroom)
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40 font-bold text-xs">
                                                <i data-lucide="shapes" class="w-3.5 h-3.5"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                                    {{ $a->classroom->name }}
                                                </p>
                                                <p class="text-[10.5px] text-slate-500 dark:text-slate-400 flex items-center gap-1 font-medium mt-0.5">
                                                    <i data-lucide="users" class="w-3 h-3 text-slate-400"></i>
                                                    <span>{{ $a->classroom->active_students_count }} Murid</span>
                                                </p>
                                            </div>
                                        </div>
                                    @elseif($a->classLevel)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            <i data-lucide="layers" class="w-3 h-3 text-slate-400"></i>
                                            Tingkat {{ $a->classLevel->name }} (Seluruh Kelompok)
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-xs">-</span>
                                    @endif
                                </td>

                                <!-- 5. Peran Guru -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold w-fit
                                            @if($a->role === 'wali_kelas') bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800
                                            @elseif($a->role === 'guru_kelas') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800
                                            @elseif($a->role === 'gpk') bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200 dark:border-purple-800
                                            @elseif($a->role === 'gpq') bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800
                                            @elseif($a->role === 'guru_mapel') bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-400 border border-teal-200 dark:border-teal-800
                                            @else bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200 dark:border-sky-800
                                            @endif">
                                            {{ $a->role_label }}
                                        </span>
                                        @if($a->role === 'guru_mapel' && $a->subject_name)
                                            <span class="text-[10.5px] text-slate-500 dark:text-slate-400 font-medium">{{ $a->subject_name }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 6. Guru Ditugaskan -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5 min-w-[200px]">
                                        @if($a->teacher?->photo)
                                            <img src="{{ asset('storage/' . $a->teacher->photo) }}" alt="{{ $a->teacher->name }}" class="w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ substr($a->teacher?->name ?? 'G', 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-slate-900 dark:text-slate-100 leading-snug truncate">
                                                {{ $a->teacher?->name ?? 'Belum Ditugaskan' }}
                                            </p>
                                            
                                            @php
                                                $teacherEmail = $a->teacher?->email ?? $a->teacher?->user?->email;
                                            @endphp
                                            
                                            @if($teacherEmail)
                                                <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium truncate flex items-center gap-1 mt-0.5" title="{{ $teacherEmail }}">
                                                    <i data-lucide="mail" class="w-3 h-3 shrink-0 text-slate-400"></i>
                                                    <span class="truncate">{{ $teacherEmail }}</span>
                                                </div>
                                            @endif

                                            <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono mt-0.5">
                                                @if($a->teacher?->nuptk)
                                                    <span>NUPTK: {{ $a->teacher->nuptk }}</span>
                                                @elseif($a->teacher?->niy)
                                                    <span>NIY: {{ $a->teacher->niy }}</span>
                                                @elseif($a->teacher?->nik)
                                                    <span>NIK: {{ $a->teacher->nik }}</span>
                                                @elseif($a->teacher?->phone)
                                                    <span>{{ $a->teacher->phone }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 7. Status (Interactive Toggle) -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <form action="{{ route('homeroom-assignments.toggle-status', $a->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="cursor-pointer group/status" title="Klik untuk mengubah status penugasan aktif/non-aktif">
                                            @if($a->is_active)
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 group-hover/status:bg-emerald-100 transition-colors">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700 group-hover/status:bg-slate-200 transition-colors">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    Non-aktif
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- 8. Aksi -->
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ $a->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Penugasan">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button type="button" @click="deleteAssignment({{ $a->id }}, '{{ addslashes($a->classroom?->name ?? '') }}', '{{ addslashes($a->teacher?->name ?? '') }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Penugasan">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800/60 flex items-center justify-center text-slate-400">
                                            <i data-lucide="users" class="w-6 h-6"></i>
                                        </div>
                                        <p class="font-semibold text-xs text-slate-600 dark:text-slate-400">Belum ada penugasan pendidik pada filter ini</p>
                                        <p class="text-[11px] text-slate-400">Klik tombol "Tugaskan Guru" untuk menambahkan alokasi baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $assignments->links() }}
                </div>
            @endif
        </section>

        <!-- MODAL TAMBAH / EDIT WALI KELAS -->
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="margin: 0px !important; margin-top: 0px !important; top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col text-left">
                    
                    <form @submit.prevent="submitForm">
                        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Penugasan Guru (Formasi)' : 'Tugaskan Guru / Formasi Baru'"></h3>
                                <p class="text-xs text-slate-400 mt-0.5">Pilih peran, tahun ajaran, jenjang, kelas, kelompok, dan guru yang ditugaskan.</p>
                            </div>
                            <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="p-6 space-y-4 text-xs">
                            <!-- 1. Peran Penugasan Guru -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Peran Penugasan Guru <span class="text-rose-500">*</span></label>
                                <select x-model="formData.role" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-bold">
                                    <option value="wali_kelas">Wali Kelas</option>
                                    <option value="guru_kelas">Guru Kelas</option>
                                    <option value="gpk">GPK (Guru Pendamping Khusus Inklusi)</option>
                                    <option value="gpq">GPQ (Guru Pembimbing Al-Qur'an)</option>
                                    <option value="guru_mapel">Guru Sentra / Mapel</option>
                                    <option value="koordinator_tingkat">Koordinator Tingkat</option>
                                    <option value="pendamping_tingkat">Pendamping Tingkat</option>
                                </select>
                            </div>

                            <!-- Input Khusus jika Guru Sentra / Mapel -->
                            <div x-show="formData.role === 'guru_mapel'">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Sentra / Bidang Mapel <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="formData.subject_name" placeholder="Misal: Sentra Seni, Sentra Balok, Sentra Imtaq..."
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>

                            <!-- 2. Tahun Ajaran -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                <select x-model="formData.academic_year_id" @change="onAcademicYearChange()" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                                    <option value="">-- Pilih Tahun Ajaran --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}">{{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- 3. Jenjang -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenjang <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.jenjang_id" @change="onJenjangChange()" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        <option value="">-- Pilih Jenjang --</option>
                                        @foreach($jenjangs as $j)
                                            <option value="{{ $j->id }}">{{ $j->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 4. Kelas -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelas <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.class_level_id" @change="onClassLevelChange()" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        <option value="">-- Pilih Kelas --</option>
                                        <template x-for="lvl in filteredClassLevels" :key="lvl.id">
                                            <option :value="lvl.id" x-text="lvl.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <!-- 5. Kelompok Belajar (Hidden jika Koordinator / Pendamping Tingkat) -->
                            <div x-show="formData.role !== 'koordinator_tingkat' && formData.role !== 'pendamping_tingkat'">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelompok Belajar <span class="text-rose-500" x-show="formData.role !== 'koordinator_tingkat' && formData.role !== 'pendamping_tingkat'">*</span></label>
                                <select x-model="formData.classroom_id" :required="formData.role !== 'koordinator_tingkat' && formData.role !== 'pendamping_tingkat'"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-bold">
                                    <option value="">-- Pilih Kelompok Belajar --</option>
                                    <template x-for="c in filteredClassrooms" :key="c.id">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- 6. Guru Ditugaskan -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Guru Ditugaskan <span class="text-rose-500">*</span></label>
                                <select x-model="formData.employee_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                                    <option value="">-- Pilih Guru --</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} {{ $t->position ? '(' . $t->position . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan / Keterangan</label>
                                <textarea x-model="formData.notes" rows="2" placeholder="Catatan opsional mengenai penugasan ini..."
                                    class="w-full p-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                            </div>

                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-200">Status Penugasan</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Tandai aktif agar otomatis tersambung ke data kelompok belajar dan rapor.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="formData.is_active" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                            <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                                <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tugaskan Guru')"></span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </template>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function homeroomApp() {
            const allLevels = @json($classLevels);
            const allClasses = @json($classrooms);
            const allAssignments = @json($assignments->items());
            const activeAYId = '{{ $selectedYearId ?? ($activeAcademicYear?->id ?? '') }}';

            return {
                modalOpen: false,
                isEdit: false,
                saving: false,
                assignments: allAssignments,

                formData: {
                    id: null,
                    academic_year_id: activeAYId,
                    jenjang_id: '',
                    class_level_id: '',
                    classroom_id: '',
                    employee_id: '',
                    role: 'wali_kelas',
                    subject_name: '',
                    is_active: true,
                    notes: '',
                },

                get filteredClassLevels() {
                    if (!this.formData.jenjang_id) return allLevels;
                    return allLevels.filter(lvl => lvl.jenjang_id == this.formData.jenjang_id);
                },

                get filteredClassrooms() {
                    let res = allClasses;
                    if (this.formData.academic_year_id) {
                        res = res.filter(c => !c.academic_year_id || c.academic_year_id == this.formData.academic_year_id);
                    }
                    if (this.formData.jenjang_id) {
                        res = res.filter(c => c.jenjang_id == this.formData.jenjang_id || (c.class_level && c.class_level.jenjang_id == this.formData.jenjang_id));
                    }
                    if (this.formData.class_level_id) {
                        res = res.filter(c => c.class_level_id == this.formData.class_level_id);
                    }
                    return res;
                },

                onAcademicYearChange() {
                    this.onJenjangChange();
                },

                onJenjangChange() {
                    const validLevel = this.filteredClassLevels.some(l => l.id == this.formData.class_level_id);
                    if (!validLevel) {
                        this.formData.class_level_id = this.filteredClassLevels.length > 0 ? this.filteredClassLevels[0].id : '';
                    }
                    this.onClassLevelChange();
                },

                onClassLevelChange() {
                    const validClass = this.filteredClassrooms.some(c => c.id == this.formData.classroom_id);
                    if (!validClass) {
                        this.formData.classroom_id = this.filteredClassrooms.length > 0 ? this.filteredClassrooms[0].id : '';
                    }
                },

                openCreateModal() {
                    this.isEdit = false;
                    const defaultJenjangId = ('{{ $selectedJenjangId }}' && '{{ $selectedJenjangId }}' !== 'all')
                        ? '{{ $selectedJenjangId }}'
                        : (allLevels.length > 0 ? allLevels[0].jenjang_id : '');
                    this.formData = {
                        id: null,
                        academic_year_id: activeAYId,
                        jenjang_id: defaultJenjangId,
                        class_level_id: '',
                        classroom_id: '',
                        employee_id: '',
                        role: '{{ ($selectedRole && $selectedRole !== "all") ? $selectedRole : "wali_kelas" }}',
                        subject_name: '',
                        is_active: true,
                        notes: '',
                    };
                    this.onJenjangChange();
                    this.modalOpen = true;
                },

                openEditModal(id) {
                    const assignment = this.assignments.find(a => a.id === id);
                    if (!assignment) return;
                    this.isEdit = true;
                    this.formData = {
                        id: assignment.id,
                        academic_year_id: assignment.academic_year_id,
                        jenjang_id: assignment.jenjang_id || (assignment.classroom ? assignment.classroom.jenjang_id : ''),
                        class_level_id: assignment.class_level_id || (assignment.classroom ? assignment.classroom.class_level_id : ''),
                        classroom_id: assignment.classroom_id || '',
                        employee_id: assignment.employee_id,
                        role: assignment.role || 'wali_kelas',
                        subject_name: assignment.subject_name || '',
                        is_active: !!assignment.is_active,
                        notes: assignment.notes || '',
                    };
                    this.modalOpen = true;
                },

                submitForm() {
                    if (this.saving) return;
                    this.saving = true;

                    const url = this.isEdit ? `/homeroom-assignments/${this.formData.id}` : '/homeroom-assignments';
                    const method = this.isEdit ? 'PUT' : 'POST';

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.formData)
                    })
                    .then(res => res.json())
                    .then(res => {
                        this.saving = false;
                        if (res.success) {
                            this.modalOpen = false;
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(res.message || 'Penugasan guru berhasil disimpan!', 'success');
                            }
                            const targetJenjang = res.jenjang_id || this.formData.jenjang_id || '{{ $selectedJenjangId }}';
                            const targetTapel = res.academic_year_id || this.formData.academic_year_id || '{{ $selectedYearId }}';
                            const url = new URL(window.location.origin + window.location.pathname);
                            url.searchParams.set('academic_year_id', targetTapel);
                            url.searchParams.set('jenjang_id', targetJenjang);
                            window.location.href = url.toString();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', res.message || 'Terjadi kesalahan saat menyimpan.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        this.saving = false;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                        }
                    });
                },

                deleteAssignment(id, kelompokName, teacherName) {
                    const action = () => {
                        fetch(`/homeroom-assignments/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                if (typeof window.setPendingToast === 'function') {
                                    window.setPendingToast(res.message || 'Penugasan berhasil dihapus!', 'success');
                                }
                                const targetJenjang = res.jenjang_id || '{{ $selectedJenjangId }}';
                                const targetTapel = res.academic_year_id || '{{ $selectedYearId }}';
                                const url = new URL(window.location.origin + window.location.pathname);
                                url.searchParams.set('academic_year_id', targetTapel);
                                url.searchParams.set('jenjang_id', targetJenjang);
                                window.location.href = url.toString();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal menghapus penugasan.', 'error');
                                }
                            }
                        })
                        .catch(err => {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                            }
                        });
                    };

                    if (typeof showGlobalConfirmModal === 'function') {
                        showGlobalConfirmModal(`Apakah Anda yakin ingin menghapus penugasan guru "${teacherName}" di kelompok "${kelompokName}"?`, action, true);
                    } else if (confirm(`Apakah Anda yakin ingin menghapus penugasan guru "${teacherName}" di kelompok "${kelompokName}"?`)) {
                        action();
                    }
                }
            }
        }

        function handleJenjangFilterChange(selectEl) {
            const form = selectEl.form;
            const classLevelSelect = form.querySelector('select[name="class_level_id"]');
            if (classLevelSelect) {
                classLevelSelect.value = 'all';
            }
            form.submit();
        }
    </script>
</x-admin-layout>
