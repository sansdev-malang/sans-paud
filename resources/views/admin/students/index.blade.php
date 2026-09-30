<x-admin-layout>
    <div class="p-6 space-y-6" x-data="studentApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2.5 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-500/20 shadow-xs">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Daftar Murid
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-800">
                                PG - TK - DAYCARE
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Database komprehensif murid, penempatan kelompok, daycare, dan TPQ Anak Saleh.</p>
                    </div>
                </div>
            </div>

            <!-- ACTION CONTROLS -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <!-- Info Badge Tahun Ajaran Aktif -->
                <div class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 rounded-xl text-xs shadow-xs">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">T.A. Aktif:</span>
                    <span class="font-bold text-indigo-700 dark:text-indigo-300">
                        {{ $activeAcademicYear ? $activeAcademicYear->name : '2026/2027' }} ({{ $activeAcademicYear->semester ?? 'Ganjil' }})
                    </span>
                </div>

                <!-- Impor Excel Button -->
                <button type="button" @click="importModalOpen = true"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-slate-500"></i>
                    Impor Excel
                </button>

                <!-- Ekspor Excel Button -->
                <a href="{{ route('students.export.excel', request()->query()) }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    Ekspor Excel
                </a>

                <!-- Tambah Murid Button -->
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Murid
                </button>
            </div>
        </section>

        <!-- IMPORT ERRORS ALERT -->
        @if(session('import_errors'))
            <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-400 p-4 rounded-xl flex items-start gap-3 text-left w-full">
                <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 shrink-0 text-rose-500 dark:text-rose-400"></i>
                <div class="space-y-1">
                    <h5 class="text-xs font-bold">Beberapa baris data murid gagal diimpor:</h5>
                    <ul class="list-disc list-inside text-[11px] leading-relaxed opacity-90 max-h-40 overflow-y-auto">
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat Card 1: Total Murid Aktif -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Murid Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total_active']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Total terdaftar: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($stats['total_all']) }}</span> anak
                </div>
            </div>

            <!-- Stat Card 2: Laki-laki -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Laki-laki (Putra)</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['male']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Murid aktif putra
                </div>
            </div>

            <!-- Stat Card 3: Perempuan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Perempuan (Putri)</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['female']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl border border-rose-100 dark:border-rose-900/50">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Murid aktif putri
                </div>
            </div>

            <!-- Stat Card 4: Distribusi Sub-Unit -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Distribusi Layanan</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="text-xs font-bold text-amber-600">PG: {{ $stats['pg'] }}</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-xs font-bold text-indigo-600">TK: {{ $stats['tk'] }}</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-xs font-bold text-purple-600">TPA: {{ $stats['daycare'] }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Total {{ $stats['classrooms'] }} kelompok belajar aktif
                </div>
            </div>
        </section>

        <!-- SUB-UNIT FILTER TABS -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <a href="{{ route('students.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'ALL'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $selectedSubUnit === 'ALL' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                <span>🌟</span> Semua Murid
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedSubUnit === 'ALL' ? 'bg-indigo-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $stats['total_all'] }}</span>
            </a>
            <a href="{{ route('students.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'PG'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $selectedSubUnit === 'PG' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                <span>🧸</span> Playgroup (PG)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedSubUnit === 'PG' ? 'bg-amber-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $stats['pg'] }}</span>
            </a>
            <a href="{{ route('students.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'TK'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $selectedSubUnit === 'TK' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                <span>🎒</span> TK (Taman Kanak-Kanak)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedSubUnit === 'TK' ? 'bg-indigo-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $stats['tk'] }}</span>
            </a>
            <a href="{{ route('students.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'DAYCARE'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $selectedSubUnit === 'DAYCARE' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                <span>👶</span> Daycare (TPA)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedSubUnit === 'DAYCARE' ? 'bg-purple-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $stats['daycare'] }}</span>
            </a>
            <a href="{{ route('students.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'TPQ'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $selectedSubUnit === 'TPQ' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                <span>📖</span> TPQ
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedSubUnit === 'TPQ' ? 'bg-emerald-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $stats['tpq'] }}</span>
            </a>
        </div>

        <!-- SEARCH & FILTERS -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs w-full">
            <form method="GET" action="{{ route('students.index') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <input type="hidden" name="sub_unit" value="{{ $selectedSubUnit }}">

                <!-- Search Box -->
                <div class="relative w-full md:max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 dark:text-slate-500"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama ananda, panggilan, NIS, No. Ortu..."
                        style="padding-left: 2.25rem;"
                        class="w-full h-9 pr-4 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 placeholder-slate-400 dark:placeholder-slate-500 transition-colors shadow-inner">
                </div>

                <!-- Filter Select Toolbar -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Filter Tahun Ajaran -->
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                                T.A. {{ $year->name }} {{ $year->is_active ? '★ (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Jenjang -->
                    <select name="class_level_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Jenjang</option>
                        @foreach($classLevels as $lvl)
                            <option value="{{ $lvl->id }}" {{ request('class_level_id') == $lvl->id ? 'selected' : '' }}>
                                [{{ $lvl->sub_unit }}] {{ $lvl->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Kelompok -->
                    <select name="classroom_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Kelompok</option>
                        @foreach($classrooms as $rombel)
                            <option value="{{ $rombel->id }}" {{ request('classroom_id') == $rombel->id ? 'selected' : '' }}>
                                {{ $rombel->name }} ({{ $rombel->sub_unit }})
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Status -->
                    <select name="status" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Status</option>
                        <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="lulus" {{ request('status') == 'lulus' ? 'selected' : '' }}>Lulus</option>
                        <option value="mutasi" {{ request('status') == 'mutasi' ? 'selected' : '' }}>Mutasi</option>
                        <option value="keluar" {{ request('status') == 'keluar' ? 'selected' : '' }}>Keluar</option>
                        <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    @if(request()->hasAny(['search', 'academic_year_id', 'class_level_id', 'classroom_id', 'status', 'gender']))
                        <a href="{{ route('students.index', ['sub_unit' => $selectedSubUnit]) }}" 
                            class="h-9 px-3 inline-flex items-center justify-center text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors"
                            title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- TABLE LIST MURID -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12">No</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">NIS & PIN</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Ananda</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Program & Kelompok</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">L/P & Usia</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-44">Orang Tua & WA</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-20">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($students as $index => $s)
                            @php
                                $unitBadgeBg = match($s->sub_unit) {
                                    'PG' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                    'DAYCARE' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-400 border-purple-200 dark:border-purple-800',
                                    'TPQ' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                                    default => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                <td class="px-5 py-3.5 text-slate-400 font-mono text-[11px]">
                                    {{ $students->firstItem() + $index }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                                {{ $s->nis }}
                                            </span>
                                            <button type="button" @click="copyText('{{ $s->nis }}')" class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-indigo-600 transition-opacity p-0.5" title="Salin NIS">
                                                <i data-lucide="copy" class="w-3 h-3"></i>
                                            </button>
                                        </div>
                                        @if($s->pin_access)
                                            <span class="font-mono text-[10px] text-slate-400">
                                                PIN: <span class="font-bold text-slate-600 dark:text-slate-300">{{ $s->pin_access }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full border flex items-center justify-center font-bold text-xs shrink-0 shadow-xs {{ $unitBadgeBg }}">
                                            {{ $s->avatar_initials }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition-colors" @click="openDetailModal({{ $s->id }})">
                                                {{ $s->full_name }}
                                            </span>
                                            <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                                @if($s->nickname)
                                                    <span class="text-indigo-600 dark:text-indigo-400 font-medium">({{ $s->nickname }})</span>
                                                @endif
                                                @if($s->spmb_candidate_id)
                                                    <span class="px-1.5 py-0.2 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-medium border border-emerald-200/50">SPMB</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <!-- Primary Classroom Badge -->
                                        @if($s->classroom)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $s->sub_unit === 'PG' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800' : 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800' }}">
                                                {{ $s->sub_unit === 'PG' ? '🧸' : '🎒' }} {{ $s->classroom->name }}
                                            </span>
                                        @endif

                                        <!-- Daycare Badge -->
                                        @if($s->daycareClassroom || ($s->sub_unit === 'DAYCARE' && $s->classroom))
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800">
                                                👶 {{ $s->daycareClassroom?->name ?? $s->classroom?->name }}
                                            </span>
                                        @endif

                                        <!-- TPQ Badge -->
                                        @if($s->is_tpq || $s->tpqClassroom || $s->sub_unit === 'TPQ')
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800">
                                                📖 TPQ
                                            </span>
                                        @endif

                                        @if(!$s->classroom && !$s->daycareClassroom && !$s->is_tpq)
                                            <span class="text-[11px] text-slate-400 italic">Belum Ditempatkan</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col text-slate-600 dark:text-slate-300">
                                        <span class="font-medium text-xs">{{ $s->formatted_gender }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $s->age ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[160px]">
                                            {{ $s->father_name ?: ($s->mother_name ?: ($s->guardian_name ?: '-')) }}
                                        </span>
                                        @if($s->clean_parent_phone)
                                            <a href="{{ $s->whatsapp_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400 hover:underline font-mono mt-0.5">
                                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                                {{ $s->parent_phone }}
                                            </a>
                                        @else
                                            <span class="text-[11px] text-slate-400">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($s->status === 'aktif')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                                            Aktif
                                        </span>
                                    @elseif($s->status === 'lulus')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40">
                                            Lulus
                                        </span>
                                    @elseif($s->status === 'mutasi')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                                            Mutasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            {{ ucfirst($s->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openDetailModal({{ $s->id }})"
                                            class="p-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg transition-colors cursor-pointer"
                                            title="Lihat Detail Murid">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="openEditModal({{ $s->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Data Murid">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="deleteStudent({{ $s->id }}, '{{ addslashes($s->full_name) }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Murid">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="max-w-sm mx-auto flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-500 mb-3 border border-indigo-100 dark:border-indigo-900/50">
                                            <i data-lucide="users" class="w-6 h-6"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Data Murid</h4>
                                        <p class="text-xs text-slate-400 mt-1 mb-4 text-center">
                                            Data murid tidak ditemukan pada filter ini. Anda dapat menarik calon siswa dari SPMB atau menambah data secara manual.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($students->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $students->firstItem() }}-{{ $students->lastItem() }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $students->total() }}</span> murid
                    </span>
                    <div>
                        {{ $students->links() }}
                    </div>
                </div>
            @endif
        </section>

        <!-- MODAL DETAIL MURID (ENHANCED TABBED LAYOUT) -->
        <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="detailModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col text-left">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white/95 dark:bg-slate-900/95">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-lg flex items-center justify-center shadow-xs" x-text="selectedStudent?.avatar_initials || 'A'">
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="selectedStudent?.full_name"></h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/50 dark:border-emerald-800" x-text="selectedStudent?.status || 'Aktif'"></span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-400 mt-0.5">
                                <span>NIS: <strong class="font-mono text-indigo-600 dark:text-indigo-400" x-text="selectedStudent?.nis"></strong></span>
                                <span>PIN: <strong class="font-mono text-slate-700 dark:text-slate-300" x-text="selectedStudent?.pin_access || '-'"></strong></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Sub-Tabs -->
                <div class="flex items-center gap-2 px-5 py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 text-xs overflow-x-auto no-scrollbar">
                    <button type="button" @click="detailTab = 'program'"
                        :class="detailTab === 'program' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="shapes" class="w-3.5 h-3.5"></i>
                        <span>Program & Kelompok</span>
                    </button>
                    <button type="button" @click="detailTab = 'biodata'"
                        :class="detailTab === 'biodata' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        <span>Biodata Ananda</span>
                    </button>
                    <button type="button" @click="detailTab = 'ortu'"
                        :class="detailTab === 'ortu' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                        <span>Orang Tua & Kontak</span>
                    </button>
                    <button type="button" @click="detailTab = 'history'"
                        :class="detailTab === 'history' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="milestone" class="w-3.5 h-3.5"></i>
                        <span>Riwayat Belajar</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 text-xs overflow-y-auto max-h-[60vh]" x-show="selectedStudent">
                    
                    <!-- TAB 1: Program & Layanan Terdaftar -->
                    <div x-show="detailTab === 'program'" class="space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Sub Unit Utama</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 text-sm" x-text="selectedStudent?.sub_unit || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Kelompok Utama</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.classroom?.name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Wali Kelas / Pendamping</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.classroom?.homeroom_teacher?.name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Layanan Daycare</span>
                                <span class="font-semibold text-purple-600 dark:text-purple-400" x-text="selectedStudent?.daycare_classroom?.name ? '👶 ' + selectedStudent.daycare_classroom.name : (selectedStudent?.sub_unit === 'DAYCARE' ? '👶 Daycare Utama' : 'Tidak Mengambil')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Layanan TPQ</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400" x-text="selectedStudent?.is_tpq || selectedStudent?.sub_unit === 'TPQ' ? '📖 Mengikuti TPQ' : 'Tidak Mengambil'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Tahun Ajaran</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.academic_year?.name ? 'T.A. ' + selectedStudent.academic_year.name : '-'"></span>
                            </div>
                        </div>

                        <!-- Shortcut Akses Rapor -->
                        <div class="p-3.5 bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 rounded-xl flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-indigo-900 dark:text-indigo-300">
                                <i data-lucide="book-open-check" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                <span>Kelola penilaian naratif & rapor ananda</span>
                            </div>
                            <a :href="`/reports/cards?classroom_id=${selectedStudent?.classroom_id}&academic_year_id=${selectedStudent?.academic_year_id}`"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold flex items-center gap-1.5 transition-colors shadow-xs">
                                <span>Buka E-Rapor</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>

                    <!-- TAB 2: Biodata Ananda -->
                    <div x-show="detailTab === 'biodata'" class="space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Lengkap</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.full_name"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Panggilan</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.nickname || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Jenis Kelamin</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.formatted_gender"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Tempat, Tgl Lahir</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(selectedStudent?.birth_place ? selectedStudent.birth_place + ', ' : '') + (selectedStudent?.birth_date || '-')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Usia Saat Ini</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.age || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Agama</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.religion || 'Islam'"></span>
                            </div>
                            <div class="col-span-2 sm:col-span-3">
                                <span class="text-slate-400 block text-[11px]">Alamat Domisili</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedStudent?.address || '-'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Orang Tua & Kontak -->
                    <div x-show="detailTab === 'ortu'" class="space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ayah</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.father_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ibu</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.mother_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">WhatsApp Utama</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.parent_phone || '-'"></span>
                            </div>
                        </div>

                        <!-- Action Chat WA -->
                        <template x-if="selectedStudent?.whatsapp_url">
                            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-start">
                                <a :href="selectedStudent.whatsapp_url" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    Hubungi Orang Tua via WhatsApp
                                </a>
                            </div>
                        </template>
                    </div>

                    <!-- TAB 4: Riwayat Perjalanan Belajar (Timeline) -->
                    <div x-show="detailTab === 'history'" class="space-y-4">
                        <div class="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                            <template x-if="studentHistories.length === 0">
                                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/40 text-slate-500 text-center text-xs">
                                    Belum ada histori rombel tercatat untuk ananda ini.
                                </div>
                            </template>

                            <template x-for="h in studentHistories" :key="h.id">
                                <div class="relative group">
                                    <div class="absolute -left-6 top-1 w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center shadow-xs"
                                        :class="h.status === 'lulus' ? 'bg-amber-500 text-white' : (h.status === 'aktif' ? 'bg-emerald-500 text-white' : 'bg-indigo-500 text-white')">
                                        <i data-lucide="check" class="w-2.5 h-2.5" x-show="h.status === 'lulus'"></i>
                                    </div>

                                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-1.5">
                                        <div class="flex flex-wrap items-center justify-between gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                    :class="h.sub_unit === 'PG' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400'"
                                                    x-text="h.sub_unit || 'TK'">
                                                </span>
                                                <h5 class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="h.classroom_name || 'Kelompok Belajar'"></h5>
                                                <span class="text-[10px] text-slate-400" x-text="h.grade_level ? '(' + h.grade_level + ')' : ''"></span>
                                            </div>

                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                                :class="{
                                                    'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800': h.status === 'lulus',
                                                    'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800': h.status === 'aktif',
                                                    'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800': h.status === 'mutasi'
                                                }"
                                                x-text="h.status ? h.status.toUpperCase() : 'AKTIF'">
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap items-center justify-between text-[11px] pt-1 border-t border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400">
                                            <div class="flex items-center gap-1">
                                                <i data-lucide="user-check" class="w-3 h-3 text-indigo-500"></i>
                                                <span>Wali Kelas: <strong class="text-slate-800 dark:text-slate-200" x-text="h.homeroom_teacher_name || '-'"></strong></span>
                                            </div>
                                            <span class="text-[11px] font-mono font-bold text-slate-700 dark:text-slate-300" x-text="h.academic_year?.name ? 'T.A. ' + h.academic_year.name : ''"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center">
                    <button type="button" @click="copyStudentSummary()" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        Salin Info Ananda
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="detailModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                            Tutup
                        </button>
                        <button type="button" @click="openEditModal(selectedStudent.id)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs flex items-center gap-1.5">
                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            Edit Data Murid
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH / EDIT MURID (STEPPED FORM LAYOUT) -->
        <div x-show="formModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="formModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col text-left">
                
                <form @submit.prevent="submitForm">
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white/95 dark:bg-slate-900/95">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Data Murid' : 'Tambah Murid Baru'"></h3>
                            <p class="text-xs text-slate-400 mt-0.5">Konfigurasi identitas ananda, kelompok terdaftar, dan kontak ortu.</p>
                        </div>
                        <button type="button" @click="formModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Step Navigation Tabs -->
                    <div class="flex items-center border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 text-xs px-5 py-2 gap-2 overflow-x-auto no-scrollbar">
                        <button type="button" @click="formTab = 'program'"
                            :class="formTab === 'program' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                            <span>1. Program & Rombel</span>
                        </button>
                        <button type="button" @click="formTab = 'ananda'"
                            :class="formTab === 'ananda' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                            <span>2. Data Ananda</span>
                        </button>
                        <button type="button" @click="formTab = 'ortu'"
                            :class="formTab === 'ortu' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                            class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                            <span>3. Orang Tua & Kontak</span>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5 text-xs overflow-y-auto max-h-[58vh]">
                        
                        <!-- TAB 1: Penempatan Program & Kelompok -->
                        <div x-show="formTab === 'program'" class="space-y-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600">
                                    <i data-lucide="shapes" class="w-3.5 h-3.5"></i>
                                    Program Utama
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Sub Unit Utama <span class="text-rose-500">*</span></label>
                                        <select x-model="formData.sub_unit" required
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                            <option value="TK">🎒 TK (Taman Kanak-Kanak)</option>
                                            <option value="PG">🧸 Playgroup (PG)</option>
                                            <option value="DAYCARE">👶 Daycare (TPA Saja)</option>
                                            <option value="TPQ">📖 TPQ Saja</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelompok Utama</label>
                                        <select x-model="formData.classroom_id"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                            <option value="">-- Pilih Kelompok --</option>
                                            @foreach($allClassrooms as $rombel)
                                                <option value="{{ $rombel->id }}">
                                                    [{{ $rombel->sub_unit }}] {{ $rombel->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                        <select x-model="formData.academic_year_id" required
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                            @foreach($academicYears as $year)
                                                <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Layanan Tambahan (Daycare & TPQ) -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-purple-600">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Layanan Tambahan Terpadu
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Layanan Daycare (TPA)</label>
                                        <select x-model="formData.daycare_classroom_id"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                            <option value="">- Tidak Mengambil Daycare -</option>
                                            @foreach($daycareClassrooms as $dc)
                                                <option value="{{ $dc->id }}">👶 {{ $dc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Layanan TPQ</label>
                                        <label class="flex items-center gap-2 h-9 px-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg cursor-pointer">
                                            <input type="checkbox" x-model="formData.is_tpq" class="rounded text-indigo-600 focus:ring-indigo-500">
                                            <span class="font-medium text-slate-700 dark:text-slate-300">📖 Mengikuti Mengaji TPQ</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Biodata Ananda -->
                        <div x-show="formTab === 'ananda'" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIS <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="formData.nis" required placeholder="Contoh: 27.PAUD.001"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">PIN Akses Ortu</label>
                                    <input type="text" x-model="formData.pin_access" placeholder="Contoh: 1234"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Murid</label>
                                    <select x-model="formData.status" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        <option value="aktif">Aktif</option>
                                        <option value="lulus">Lulus</option>
                                        <option value="mutasi">Mutasi</option>
                                        <option value="keluar">Keluar</option>
                                        <option value="nonaktif">Nonaktif</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ananda <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="formData.full_name" required placeholder="Nama lengkap sesuai akta"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Panggilan</label>
                                    <input type="text" x-model="formData.nickname" placeholder="Panggilan"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.gender" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        <option value="L">Laki-laki (Putra)</option>
                                        <option value="P">Perempuan (Putri)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                                    <input type="text" x-model="formData.birth_place" placeholder="Kota lahir"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                                    <input type="date" x-model="formData.birth_date"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: Data Orang Tua & Kontak -->
                        <div x-show="formTab === 'ortu'" class="space-y-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-emerald-600">
                                    <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                                    Kontak Keluarga
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ayah</label>
                                        <input type="text" x-model="formData.father_name" placeholder="Nama ayah"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ibu</label>
                                        <input type="text" x-model="formData.mother_name" placeholder="Nama ibu"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. WhatsApp Utama</label>
                                        <input type="text" x-model="formData.parent_phone" placeholder="08xxxxxxxxxx"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Domisili</label>
                                        <input type="text" x-model="formData.address" placeholder="Alamat rumah / domisili"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="formTab !== 'program'" @click="formTab = (formTab === 'ortu' ? 'ananda' : 'program')"
                                class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1">
                                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                                Kembali
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="formModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                                Batal
                            </button>
                            <button type="button" x-show="formTab !== 'ortu'" @click="formTab = (formTab === 'program' ? 'ananda' : 'ortu')"
                                class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1">
                                <span>Selanjutnya</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Murid')"></span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        <!-- MODAL: IMPOR EXCEL MURID -->
        <div x-show="importModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.outside="importModalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden animate-card">
                
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/70">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-200/50 dark:border-indigo-900/50">
                            <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Impor Data Murid (Excel)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Unggah file spreadsheet .xlsx/.xls untuk menambahkan murid secara massal.</p>
                        </div>
                    </div>
                    <button type="button" @click="importModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 text-left">
                    @csrf

                    <!-- Download Template Alert Banner -->
                    <div class="p-3.5 bg-indigo-50/80 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/60 rounded-xl flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 text-indigo-900 dark:text-indigo-300">
                            <i data-lucide="info" class="w-4 h-4 shrink-0 text-indigo-600 dark:text-indigo-400"></i>
                            <span>Belum memiliki template standar?</span>
                        </div>
                        <a href="{{ route('students.download-template') }}"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shrink-0 flex items-center gap-1.5 transition-colors shadow-xs">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            Unduh Template
                        </a>
                    </div>

                    <!-- Default Academic Year -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tahun Ajaran Pendaftaran</label>
                        <select name="default_academic_year_id" class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }} {{ $ay->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Default Classroom (Optional) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Default Kelompok / Rombel (Opsional)</label>
                        <select name="default_classroom_id" class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                            <option value="">-- Sesuaikan dengan Kolom di File Excel --</option>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}">[{{ $c->sub_unit }}] {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- File Input -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pilih File Excel (.xlsx, .xls, .csv) <span class="text-rose-500">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                            class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300 border border-slate-200 dark:border-slate-800 rounded-lg cursor-pointer bg-slate-50/50 dark:bg-slate-900/50">
                    </div>

                    <!-- Modal Footer Buttons -->
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
                        <button type="button" @click="importModalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            Unggah & Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Alpine.js Application Logic for Data Murid -->
    <script>
        function studentApp() {
            return {
                detailModalOpen: false,
                formModalOpen: false,
                importModalOpen: false,
                isEdit: false,
                saving: false,
                detailTab: 'program',
                formTab: 'program',
                selectedStudent: null,
                studentHistories: [],
                formData: {
                    id: null,
                    academic_year_id: '{{ $selectedYearId && $selectedYearId !== "all" ? $selectedYearId : ($academicYears->firstWhere("is_active", true)?->id ?? "") }}',
                    sub_unit: 'TK',
                    classroom_id: '',
                    daycare_classroom_id: '',
                    is_tpq: false,
                    nis: '',
                    pin_access: '',
                    full_name: '',
                    nickname: '',
                    gender: 'L',
                    birth_place: '',
                    birth_date: '',
                    religion: 'Islam',
                    address: '',
                    father_name: '',
                    mother_name: '',
                    parent_phone: '',
                    status: 'aktif',
                },

                copyText(text) {
                    navigator.clipboard.writeText(text);
                    if (typeof showToast === 'function') {
                        showToast('Berhasil!', 'Teks berhasil disalin ke papan klip.', 'success');
                    } else {
                        alert('Tersalin: ' + text);
                    }
                },

                copyStudentSummary() {
                    if (!this.selectedStudent) return;
                    const s = this.selectedStudent;
                    const summary = `*DATA MURID PAUD ANAK SALEH*\nNama: ${s.full_name} (${s.nickname || '-'})\nNIS: ${s.nis}\nPIN Ortu: ${s.pin_access || '-'}\nProgram: ${s.sub_unit} - ${s.classroom?.name || '-'}\nLayanan: Daycare: ${s.daycare_classroom?.name || '-'}, TPQ: ${s.is_tpq ? 'Ya' : 'Tidak'}\nOrtu: ${s.father_name || s.mother_name || '-'} (WA: ${s.parent_phone || '-'})`;
                    this.copyText(summary);
                },

                openDetailModal(id) {
                    this.detailTab = 'program';
                    fetch(`/students/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.selectedStudent = res.student;
                            this.studentHistories = res.histories || [];
                            this.detailModalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => alert("Gagal memuat detail murid: " + err.message));
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formTab = 'program';
                    this.formData = {
                        id: null,
                        academic_year_id: '{{ $selectedYearId && $selectedYearId !== "all" ? $selectedYearId : ($academicYears->firstWhere("is_active", true)?->id ?? "") }}',
                        sub_unit: '{{ $selectedSubUnit !== "ALL" ? $selectedSubUnit : "TK" }}',
                        classroom_id: '',
                        daycare_classroom_id: '',
                        is_tpq: false,
                        nis: '',
                        pin_access: '',
                        full_name: '',
                        nickname: '',
                        gender: 'L',
                        birth_place: '',
                        birth_date: '',
                        religion: 'Islam',
                        address: '',
                        father_name: '',
                        mother_name: '',
                        parent_phone: '',
                        status: 'aktif',
                    };
                    this.formModalOpen = true;
                },

                openEditModal(id) {
                    this.formTab = 'program';
                    fetch(`/students/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const s = res.student;
                            this.isEdit = true;
                            this.formData = {
                                id: s.id,
                                academic_year_id: s.academic_year_id || '',
                                sub_unit: s.sub_unit || 'TK',
                                classroom_id: s.classroom_id || '',
                                daycare_classroom_id: s.daycare_classroom_id || '',
                                is_tpq: !!s.is_tpq,
                                nis: s.nis,
                                pin_access: s.pin_access || '',
                                full_name: s.full_name,
                                nickname: s.nickname || '',
                                gender: s.gender || 'L',
                                birth_place: s.birth_place || '',
                                birth_date: s.birth_date ? s.birth_date.substring(0, 10) : '',
                                religion: s.religion || 'Islam',
                                address: s.address || '',
                                father_name: s.father_name || '',
                                mother_name: s.mother_name || '',
                                parent_phone: s.parent_phone || '',
                                status: s.status || 'aktif',
                            };
                            this.detailModalOpen = false;
                            this.formModalOpen = true;
                        }
                    })
                    .catch(err => alert("Gagal mengambil data murid: " + err.message));
                },

                submitForm() {
                    if (this.saving) return;
                    this.saving = true;

                    const url = this.isEdit ? `/students/${this.formData.id}` : '/students';
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
                            this.formModalOpen = false;
                            alert(res.message || 'Data murid berhasil disimpan!');
                            window.location.reload();
                        } else {
                            alert(res.message || 'Terjadi kesalahan saat menyimpan.');
                        }
                    })
                    .catch(err => {
                        this.saving = false;
                        alert('Error: ' + err.message);
                    });
                },

                deleteStudent(id, name) {
                    if (!confirm(`Apakah Anda yakin ingin menghapus data murid "${name}"?`)) return;

                    fetch(`/students/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            alert(res.message || 'Murid berhasil dihapus!');
                            window.location.reload();
                        } else {
                            alert(res.message || 'Gagal menghapus murid.');
                        }
                    })
                    .catch(err => alert('Error: ' + err.message));
                }
            }
        }
    </script>
</x-admin-layout>
