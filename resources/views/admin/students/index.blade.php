<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-3.5 sm:space-y-5 lg:space-y-6" x-data="studentApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Daftar Murid</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Database komprehensif murid, penempatan kelompok, daycare, dan TPQ Anak Saleh.</p>
            </div>

            <!-- ACTION CONTROLS -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 shrink-0">
                <!-- Info Badge Tahun Ajaran Aktif -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 rounded-xl text-[11px] sm:text-xs shadow-xs">
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
                    class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-slate-500"></i>
                    Impor Excel
                </button>

                <!-- Ekspor Dropdown (Excel & PDF) -->
                <div x-data="{ exportOpen: false }" class="relative">
                    <button type="button" @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Ekspor Data</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
                    </button>
                    
                    <div x-show="exportOpen" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-1.5 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1 overflow-hidden">
                        <a href="{{ route('students.export.excel', request()->query()) }}"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                            <span>Excel (.xlsx)</span>
                        </a>
                        <a href="{{ route('students.export.pdf', request()->query()) }}"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-400 transition-colors border-t border-slate-100 dark:border-slate-800">
                            <i data-lucide="file-text" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
                            <span>PDF (.pdf)</span>
                        </a>
                    </div>
                </div>

                <!-- Tambah Murid Button -->
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] sm:text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Murid
                </button>
            </div>
        </section>

        <!-- IMPORT ERRORS ALERT -->
        @if(session('import_errors'))
            <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-400 p-3 sm:p-4 rounded-xl flex items-start gap-3 text-left w-full">
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
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
            <!-- Stat Card 1: Total Murid Aktif -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Murid Aktif</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 font-mono">
                            {{ number_format($stats['total_active']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">dari {{ number_format($stats['total_all']) }} terdaftar</span>
                    </div>
                </div>
            </div>

            <!-- Stat Card 2: Laki-laki -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Laki-laki (Putra)</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400 font-mono">
                            {{ number_format($stats['male']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Murid putra aktif</span>
                    </div>
                </div>
            </div>

            <!-- Stat Card 3: Perempuan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-100 dark:border-rose-900/40">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Perempuan (Putri)</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                            {{ number_format($stats['female']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Murid putri aktif</span>
                    </div>
                </div>
            </div>

            <!-- Stat Card 4: Total Kelompok Belajar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 border border-purple-100 dark:border-purple-900/40">
                    <i data-lucide="shapes" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Kelompok Belajar</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-purple-600 dark:text-purple-400 font-mono">
                            {{ number_format($stats['classrooms']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Kelompok aktif jenjang ini</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- UNIFIED FILTER TOOLBAR (JENJANG TABS, SEARCH & DROPDOWN FILTERS) -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 sm:p-3 shadow-xs space-y-2">
            <form method="GET" action="{{ route('students.index') }}" class="space-y-2">
                <input type="hidden" name="jenjang_id" value="{{ $selectedJenjangId }}">

                <!-- Row 1: Jenjang Tabs on Left & Search Box on Right -->
                <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-2.5">
                    <!-- Jenjang Tabs -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach($jenjangs as $j)
                            <a href="{{ route('students.index', array_merge(request()->except('jenjang_id', 'class_level_id', 'classroom_id'), ['jenjang_id' => $j->id])) }}"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer inline-flex items-center gap-1.5 {{ $selectedJenjangId == $j->id ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700' }}">
                                @if($j->code === 'KB' || $j->code === 'PG')
                                    <span>🧸</span>
                                @elseif($j->code === 'TK')
                                    <span>🎒</span>
                                @elseif($j->code === 'DAYCARE' || $j->code === 'TPA')
                                    <span>👶</span>
                                @elseif($j->code === 'TPQ')
                                    <span>📖</span>
                                @else
                                    <span>✨</span>
                                @endif
                                <span>{{ $j->name }}</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[9.5px] font-bold {{ $selectedJenjangId == $j->id ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                                    {{ $j->students_count ?? 0 }}
                                </span>
                            </a>
                        @endforeach
                    </div>

                    <!-- Search Box -->
                    <div class="w-full lg:w-64 relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, No. Ortu..."
                            style="padding-left: 2rem;"
                            class="w-full h-8 pr-2.5 text-[11px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 placeholder-slate-400 transition-colors">
                    </div>
                </div>

                <!-- Row 2: Secondary Dropdown Filters -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                    <!-- Filter 1: Tahun Ajaran -->
                    <div class="inline-flex items-center bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg h-8 px-2.5 gap-1 shadow-xs">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shrink-0">TA:</span>
                        <select name="academic_year_id" onchange="this.form.submit()"
                            class="h-full py-0 pl-1 pr-6 text-[11px] font-bold bg-transparent text-slate-900 dark:text-slate-100 focus:outline-none cursor-pointer border-0">
                            <option value="all" {{ $selectedYearId === 'all' ? 'selected' : '' }} class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-semibold">Semua TA</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }} class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-semibold">
                                    {{ $year->name }} {{ $year->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter 2: Kelas (Sesuai Jenjang Aktif) -->
                    <select name="class_level_id" id="filter_class_level_id" onchange="handleClassLevelFilterChange(this)"
                        class="h-8 py-0 pl-2.5 pr-7 text-[11px] font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                        <option value="all" class="bg-white dark:bg-slate-900">Semua Kelas</option>
                        @foreach($classLevels as $lvl)
                            <option value="{{ $lvl->id }}" {{ ($selectedClassLevelId ?? request('class_level_id')) == $lvl->id ? 'selected' : '' }} class="bg-white dark:bg-slate-900">
                                Kelas {{ $lvl->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter 3: Kelompok Belajar -->
                    <select name="classroom_id" id="filter_classroom_id" onchange="this.form.submit()"
                        class="h-8 py-0 pl-2.5 pr-7 text-[11px] font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                        <option value="all" class="bg-white dark:bg-slate-900">Semua Kelompok</option>
                        @foreach($classrooms as $rombel)
                            <option value="{{ $rombel->id }}" {{ ($selectedClassroomId ?? request('classroom_id')) == $rombel->id ? 'selected' : '' }} class="bg-white dark:bg-slate-900">
                                Kelompok {{ $rombel->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter 4: Status Murid -->
                    <select name="status" onchange="this.form.submit()"
                        class="h-8 py-0 pl-2.5 pr-7 text-[11px] font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                        <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Semua Status</option>
                        <option value="aktif" {{ $selectedStatus === 'aktif' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Aktif</option>
                        <option value="lulus" {{ $selectedStatus === 'lulus' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Lulus</option>
                        <option value="mutasi" {{ $selectedStatus === 'mutasi' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Mutasi</option>
                        <option value="keluar" {{ $selectedStatus === 'keluar' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Keluar</option>
                        <option value="nonaktif" {{ $selectedStatus === 'nonaktif' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Nonaktif</option>
                    </select>

                    <!-- Filter 5: Gender -->
                    <select name="gender" onchange="this.form.submit()"
                        class="h-8 py-0 pl-2.5 pr-7 text-[11px] font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                        <option value="all" {{ $selectedGender === 'all' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Semua Gender</option>
                        <option value="L" {{ $selectedGender === 'L' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Putra (L)</option>
                        <option value="P" {{ $selectedGender === 'P' ? 'selected' : '' }} class="bg-white dark:bg-slate-900">Putri (P)</option>
                    </select>

                    @if(request()->hasAny(['search', 'academic_year_id', 'class_level_id', 'classroom_id', 'status', 'gender']))
                        <a href="{{ route('students.index', ['jenjang_id' => $selectedJenjangId]) }}" 
                            class="h-8 px-2.5 inline-flex items-center justify-center gap-1 text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-xs transition-colors cursor-pointer"
                            title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                            <span>Reset</span>
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
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36 whitespace-nowrap">Tahun Ajaran</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-40 whitespace-nowrap">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32 whitespace-nowrap">Kelas</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36 whitespace-nowrap">Kelompok</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Murid</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28 whitespace-nowrap">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28 whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($students as $index => $s)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                <!-- 1. Tahun Ajaran -->
                                <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $s->academicYear?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 2. Jenjang -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @php
                                        $jenjangObj = $s->jenjang ?? $s->classroom?->jenjang ?? $s->classLevel?->jenjang;
                                        $jCode = $jenjangObj?->code ?? $s->sub_unit;
                                        $jName = $jenjangObj?->name ?? ($jCode ? strtoupper($jCode) : '-');
                                    @endphp
                                    @if($jName && $jName !== '-')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                                            @if($jCode === 'KB' || $jCode === 'PG')
                                                <span>🧸</span>
                                            @elseif($jCode === 'TK')
                                                <span>🎒</span>
                                            @elseif($jCode === 'DAYCARE' || $jCode === 'TPA')
                                                <span>👶</span>
                                            @elseif($jCode === 'TPQ')
                                                <span>📖</span>
                                            @endif
                                            <span>{{ $jName }}</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- 3. Kelas -->
                                <td class="px-5 py-3.5 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $s->classLevel?->name ?? $s->classroom?->classLevel?->name ?? '-' }}
                                </td>

                                <!-- 4. Kelompok -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $s->classroom?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 5. Nama Murid & NIS -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                            {{ $s->avatar_initials }}
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition-colors" @click="openDetailModal({{ $s->id }})">
                                                {{ $s->full_name }}
                                            </span>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[10.5px] text-slate-400">
                                                @if($s->nis)
                                                    <span class="font-mono text-indigo-600 dark:text-indigo-400 font-bold">NIS: {{ $s->nis }}</span>
                                                    <span>&bull;</span>
                                                @endif
                                                <span>{{ $s->formatted_gender }}</span>
                                                @if($s->nickname)
                                                    <span>&bull;</span>
                                                    <span class="italic">({{ $s->nickname }})</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Status -->
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    @if($s->status === 'aktif')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @elseif($s->status === 'lulus')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                            Lulus
                                        </span>
                                    @elseif($s->status === 'mutasi')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            Mutasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            {{ ucfirst($s->status) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- 7. Aksi -->
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
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
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="max-w-sm mx-auto flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-500 mb-3 border border-indigo-100 dark:border-indigo-900/50">
                                            <i data-lucide="users" class="w-6 h-6"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Data Murid</h4>
                                        <p class="text-xs text-slate-400 mt-1 mb-4 text-center">
                                            Data murid tidak ditemukan pada filter ini. Anda dapat menambah data baru atau mengimpor dari file Excel.
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
        <template x-teleport="body">
            <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-2.5 sm:p-4" style="top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; margin: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="detailModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col text-left my-auto" style="height: 570px; max-height: min(570px, calc(100vh - 32px));">
                
                <!-- Modal Header -->
                <div class="p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-900/80 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-base sm:text-lg flex items-center justify-center shrink-0 shadow-xs" x-text="selectedStudent?.avatar_initials || 'A'">
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-50 truncate" x-text="selectedStudent?.full_name"></h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase shrink-0"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800': selectedStudent?.status === 'aktif',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200 dark:border-blue-800': selectedStudent?.status === 'lulus',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800': selectedStudent?.status === 'mutasi',
                                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700': !['aktif','lulus','mutasi'].includes(selectedStudent?.status)
                                    }"
                                    x-text="selectedStudent?.status ? selectedStudent.status.toUpperCase() : 'AKTIF'">
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-[11px] sm:text-xs text-slate-400 mt-0.5">
                                <span>NIS: <strong class="font-mono text-indigo-600 dark:text-indigo-400" x-text="selectedStudent?.nis || '-'"></strong></span>
                                <span>&bull;</span>
                                <span>PIN Wali: <strong class="font-mono text-slate-700 dark:text-slate-300" x-text="selectedStudent?.pin_access || '-'"></strong></span>
                                <template x-if="selectedStudent?.classroom?.name">
                                    <span class="inline-flex items-center gap-1 font-semibold text-slate-600 dark:text-slate-400">
                                        <span>&bull;</span>
                                        <i data-lucide="shapes" class="w-3 h-3 text-indigo-500"></i>
                                        <span x-text="selectedStudent.classroom.name"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer">
                        <i data-lucide="x" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </button>
                </div>

                <!-- Modal Sub-Tabs -->
                <div class="flex items-center gap-1.5 px-3.5 sm:px-5 py-2 sm:py-2.5 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs overflow-x-auto no-scrollbar shrink-0">
                    <button type="button" @click="detailTab = 'program'"
                        :class="detailTab === 'program' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 text-[11px] sm:text-xs">
                        <i data-lucide="shapes" class="w-3.5 h-3.5"></i>
                        <span>Program & Kelompok</span>
                    </button>
                    <button type="button" @click="detailTab = 'biodata'"
                        :class="detailTab === 'biodata' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 text-[11px] sm:text-xs">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        <span>Biodata Ananda</span>
                    </button>
                    <button type="button" @click="detailTab = 'ortu'"
                        :class="detailTab === 'ortu' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 text-[11px] sm:text-xs">
                        <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                        <span>Orang Tua & Kontak</span>
                    </button>
                    <button type="button" @click="detailTab = 'history'"
                        :class="detailTab === 'history' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 text-[11px] sm:text-xs">
                        <i data-lucide="milestone" class="w-3.5 h-3.5"></i>
                        <span>Riwayat Belajar</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto flex-1 min-h-0 overscroll-contain" style="flex: 1 1 0%; min-height: 0;" x-show="selectedStudent">
                    
                    <!-- TAB 1: Program & Penempatan Akademik -->
                    <div x-show="detailTab === 'program'" class="space-y-4">
                        <!-- Main Academic Details -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                                Penempatan Akademik
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Jenjang Pendidikan</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.jenjang?.name || selectedStudent?.classroom?.jenjang?.name || selectedStudent?.class_level?.jenjang?.name || selectedStudent?.sub_unit || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Tingkat Kelas</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.class_level?.name || selectedStudent?.classroom?.class_level?.name || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Kelompok Belajar</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.classroom?.name || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Wali Kelas / Guru</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.classroom?.homeroom_teacher?.name || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Tahun Ajaran</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.academic_year?.name ? 'T.A. ' + selectedStudent.academic_year.name : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Status Terdaftar</span>
                                    <span class="font-bold uppercase text-xs mt-0.5 block" :class="selectedStudent?.status === 'aktif' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400'" x-text="selectedStudent?.status || 'Aktif'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Biodata Ananda -->
                    <div x-show="detailTab === 'biodata'" class="space-y-4">
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                Identitas Pribadi Ananda
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Nama Lengkap</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.full_name || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Nama Panggilan</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.nickname || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Jenis Kelamin</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.formatted_gender || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Tempat, Tgl Lahir</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.birth_place ? selectedStudent.birth_place + ', ' : '') + (selectedStudent?.birth_date ? selectedStudent.birth_date.substring(0, 10) : '-')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Usia Saat Ini</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.age || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Agama</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.religion || 'Islam'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">NISN</span>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.nisn || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">NIK (KTP/KIA)</span>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.nik || '-'"></span>
                                </div>
                                <div class="col-span-2 sm:col-span-3 pt-2 border-t border-slate-200/80 dark:border-slate-700/80">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Alamat Lengkap & Domisili</span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.address || '-') + (selectedStudent?.city ? ', ' + selectedStudent.city : '')"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Orang Tua & Kontak -->
                    <div x-show="detailTab === 'ortu'" class="space-y-4">
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                                Data Orang Tua / Wali
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Ayah Kandung</span>
                                    <p class="font-bold text-slate-900 dark:text-slate-100 text-xs" x-text="selectedStudent?.father_name || '-'"></p>
                                    <p class="text-[11px] text-slate-500 font-mono" x-text="selectedStudent?.father_phone ? '📞 ' + selectedStudent.father_phone : 'Tidak ada no. HP'"></p>
                                </div>
                                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg space-y-1">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Ibu Kandung</span>
                                    <p class="font-bold text-slate-900 dark:text-slate-100 text-xs" x-text="selectedStudent?.mother_name || '-'"></p>
                                    <p class="text-[11px] text-slate-500 font-mono" x-text="selectedStudent?.mother_phone ? '📞 ' + selectedStudent.mother_phone : 'Tidak ada no. HP'"></p>
                                </div>
                                <div class="col-span-1 sm:col-span-2 p-3 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
                                    <div>
                                        <span class="text-[10.5px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider block">No. WhatsApp Utama</span>
                                        <p class="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm mt-0.5" x-text="selectedStudent?.parent_phone || selectedStudent?.father_phone || selectedStudent?.mother_phone || '-'"></p>
                                    </div>
                                    <template x-if="selectedStudent?.whatsapp_url">
                                        <a :href="selectedStudent.whatsapp_url" target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs shrink-0 cursor-pointer">
                                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                            <span>Chat WhatsApp</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: Riwayat Perjalanan Belajar (Timeline) -->
                    <div x-show="detailTab === 'history'" class="space-y-4">
                        <div class="relative pl-6 space-y-3.5 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                            <template x-if="studentHistories.length === 0">
                                <div class="p-5 rounded-xl bg-slate-50 dark:bg-slate-800/40 text-slate-500 text-center text-xs border border-slate-200/80 dark:border-slate-800">
                                    <i data-lucide="milestone" class="w-6 h-6 mx-auto text-slate-400 mb-1.5"></i>
                                    <p class="font-semibold">Belum ada catatan riwayat kelompok sebelumnya.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Riwayat kelompok akan terakumulasi otomatis saat murid naik kelompok atau ganti tahun ajaran.</p>
                                </div>
                            </template>

                            <template x-for="h in studentHistories" :key="h.id">
                                <div class="relative group">
                                    <div class="absolute -left-6 top-1 w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center shadow-xs text-white"
                                        :class="h.status === 'lulus' ? 'bg-amber-500' : (h.status === 'aktif' ? 'bg-emerald-500' : 'bg-indigo-500')">
                                        <i data-lucide="check" class="w-2.5 h-2.5" x-show="h.status === 'lulus'"></i>
                                    </div>

                                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-2">
                                        <div class="flex flex-wrap items-center justify-between gap-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800"
                                                    x-text="h.sub_unit || 'TK'">
                                                </span>
                                                <h5 class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="h.classroom_name || 'Kelompok Belajar'"></h5>
                                                <span class="text-[11px] text-slate-400 font-semibold" x-text="h.grade_level ? '(' + h.grade_level + ')' : ''"></span>
                                            </div>

                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                :class="{
                                                    'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800': h.status === 'lulus',
                                                    'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800': h.status === 'aktif',
                                                    'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800': h.status === 'mutasi'
                                                }"
                                                x-text="h.status ? h.status.toUpperCase() : 'AKTIF'">
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap items-center justify-between text-[11px] pt-1.5 border-t border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400">
                                            <div class="flex items-center gap-1.5">
                                                <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-500"></i>
                                                <span>Wali Kelas: <strong class="text-slate-800 dark:text-slate-200" x-text="h.homeroom_teacher_name || '-'"></strong></span>
                                            </div>
                                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="h.academic_year?.name ? 'T.A. ' + h.academic_year.name : ''"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-3 sm:p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/70 flex flex-wrap justify-between items-center gap-2 shrink-0">
                    <button type="button" @click="copyStudentSummary()" class="px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 cursor-pointer shadow-xs">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        Salin Info Ananda
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="detailModalOpen = false" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                            Tutup
                        </button>
                        <button type="button" @click="openEditModal(selectedStudent.id)" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            Edit Data Murid
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- MODAL TAMBAH / EDIT MURID (STEPPED FORM LAYOUT) -->
        <template x-teleport="body">
            <div x-show="formModalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-2.5 sm:p-4" style="top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; margin: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="formModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col text-left my-auto" style="height: 570px; max-height: min(570px, calc(100vh - 32px));">
                
                <form novalidate @submit.prevent="submitForm" class="flex flex-col h-full w-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-900/80 shrink-0">
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Data Murid' : 'Tambah Murid Baru'"></h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">Konfigurasi data penempatan kelompok, identitas ananda, dan kontak keluarga.</p>
                        </div>
                        <button type="button" @click="formModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer">
                            <i data-lucide="x" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                        </button>
                    </div>

                    <!-- Stepper Step Navigation -->
                    <div class="flex items-center border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs px-3.5 sm:px-5 py-2 sm:py-2.5 gap-2 overflow-x-auto no-scrollbar shrink-0">
                        <button type="button" @click="formTab = 'program'"
                            :class="formTab === 'program' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">1</span>
                            <span>Penempatan Akademik</span>
                            <span x-show="formErrors.academic_year_id || formErrors.jenjang_id" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                        <button type="button" @click="formTab = 'ananda'"
                            :class="formTab === 'ananda' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">2</span>
                            <span>Biodata Ananda</span>
                            <span x-show="formErrors.full_name || formErrors.gender || formErrors.status" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                        <button type="button" @click="formTab = 'ortu'"
                            :class="formTab === 'ortu' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">3</span>
                            <span>Orang Tua & Kontak</span>
                            <span x-show="formErrors.parent_phone" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                    </div>

                    <!-- Modal Body (Fixed flex-1 container ensuring identical height across all tabs) -->
                    <div class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto flex-1 min-h-0 overscroll-contain" style="flex: 1 1 0%; min-height: 0;">
                        
                        <!-- TAB 1: Penempatan Akademik & Program -->
                        <div x-show="formTab === 'program'" class="space-y-4">
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                    <i data-lucide="shapes" class="w-3.5 h-3.5"></i>
                                    Penempatan Akademik & Kelompok
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                        <select x-model="formData.academic_year_id" @change="onAcademicYearChange()"
                                            :class="formErrors.academic_year_id ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold cursor-pointer">
                                            @foreach($academicYears as $year)
                                                <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="formErrors.academic_year_id" x-text="formErrors.academic_year_id" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenjang Pendidikan <span class="text-rose-500">*</span></label>
                                        <select x-model="formData.jenjang_id" @change="onJenjangChange()"
                                            :class="formErrors.jenjang_id ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="">-- Pilih Jenjang --</option>
                                            @foreach($jenjangs as $j)
                                                <option value="{{ $j->id }}">{{ $j->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="formErrors.jenjang_id" x-text="formErrors.jenjang_id" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tingkat Kelas</label>
                                        <select x-model="formData.class_level_id" @change="onClassLevelChange()"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="">-- Pilih Kelas --</option>
                                            <template x-for="lvl in availableClassLevels" :key="lvl.id">
                                                <option :value="lvl.id" x-text="lvl.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelompok Belajar</label>
                                        <select x-model="formData.classroom_id"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="">-- Pilih Kelompok --</option>
                                            <template x-for="rombel in availableClassrooms" :key="rombel.id">
                                                <option :value="rombel.id" x-text="rombel.name + (rombel.homeroom_teacher ? ' (' + rombel.homeroom_teacher.name + ')' : '')"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3.5 sm:p-4 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 rounded-xl space-y-2">
                                <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300 font-bold text-xs">
                                    <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                                    <span>Informasi Penempatan</span>
                                </div>
                                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Pilih tahun ajaran aktif dan jenjang pendidikan. Tingkat kelas dan kelompok belajar akan terfilter otomatis berdasarkan jenjang yang dipilih.
                                </p>
                            </div>
                        </div>

                        <!-- TAB 2: Biodata Ananda -->
                        <div x-show="formTab === 'ananda'" class="space-y-3.5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIS <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <input type="text" x-model="formData.nis" @input="if (formErrors.nis) clearError('nis')" placeholder="Contoh: 27.PAUD.001"
                                        :class="formErrors.nis ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    <p x-show="formErrors.nis" x-text="formErrors.nis" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NISN <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <input type="text" x-model="formData.nisn" placeholder="Nomor NISN"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <input type="text" x-model="formData.nik" placeholder="Nomor NIK KTP/KIA"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ananda <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="formData.full_name" @input="if (formErrors.full_name) clearError('full_name')" placeholder="Nama lengkap sesuai akta kelahiran"
                                        :class="formErrors.full_name ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    <p x-show="formErrors.full_name" x-text="formErrors.full_name" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Panggilan</label>
                                    <input type="text" x-model="formData.nickname" placeholder="Nama panggilan akrab"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.gender" @change="clearError('gender')"
                                        :class="formErrors.gender ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                        <option value="L">Laki-laki (Putra)</option>
                                        <option value="P">Perempuan (Putri)</option>
                                    </select>
                                    <p x-show="formErrors.gender" x-text="formErrors.gender" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
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

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Agama</label>
                                    <select x-model="formData.religion"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                        <option value="Islam">Islam</option>
                                        <option value="Kristen">Kristen</option>
                                        <option value="Katolik">Katolik</option>
                                        <option value="Hindu">Hindu</option>
                                        <option value="Buddha">Buddha</option>
                                        <option value="Konghucu">Konghucu</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Murid <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.status" @change="clearError('status')"
                                        :class="formErrors.status ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer font-semibold">
                                        <option value="aktif">Aktif</option>
                                        <option value="lulus">Lulus</option>
                                        <option value="mutasi">Mutasi</option>
                                        <option value="keluar">Keluar</option>
                                        <option value="nonaktif">Nonaktif</option>
                                    </select>
                                    <p x-show="formErrors.status" x-text="formErrors.status" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">PIN Akses Ortu</label>
                                    <input type="text" x-model="formData.pin_access" placeholder="Contoh: 1234"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: Data Orang Tua & Kontak -->
                        <div x-show="formTab === 'ortu'" class="space-y-4">
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                    <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                                    Kontak Keluarga & Alamat
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ayah</label>
                                        <input type="text" x-model="formData.father_name" placeholder="Nama ayah kandung"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. HP Ayah</label>
                                        <input type="text" x-model="formData.father_phone" placeholder="08xxxxxxxxxx"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ibu</label>
                                        <input type="text" x-model="formData.mother_name" placeholder="Nama ibu kandung"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. HP Ibu</label>
                                        <input type="text" x-model="formData.mother_phone" placeholder="08xxxxxxxxxx"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. WhatsApp Utama Kontak <span class="text-rose-500">*</span></label>
                                        <input type="text" x-model="formData.parent_phone" @input="if (formErrors.parent_phone) clearError('parent_phone')" placeholder="08xxxxxxxxxx (untuk notifikasi & WA rapor)"
                                            :class="formErrors.parent_phone ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono font-bold">
                                        <p x-show="formErrors.parent_phone" x-text="formErrors.parent_phone" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kota Domisili</label>
                                        <input type="text" x-model="formData.city" placeholder="Malang"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Rumah Lengkap</label>
                                    <input type="text" x-model="formData.address" placeholder="Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <textarea x-model="formData.notes" rows="2" placeholder="Catatan alergi, riwayat kesehatan, atau kebutuhan khusus ananda..."
                                        class="w-full p-2.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-3 sm:p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/70 flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="formTab !== 'program'" @click="prevStep()"
                                class="px-3.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer shadow-2xs">
                                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                                <span>Kembali</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="formModalOpen = false" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold cursor-pointer shadow-2xs">
                                Batal
                            </button>
                            <button type="button" x-show="formTab !== 'ortu'" @click="nextStep()"
                                class="px-4 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                                <span>Selanjutnya</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="submit" x-show="formTab === 'ortu' || isEdit" :disabled="saving" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold shadow-xs flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Murid')"></span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </template>

    <!-- MODAL: IMPOR EXCEL MURID -->
    <template x-teleport="body">
        <div x-show="importModalOpen" x-cloak
            class="fixed inset-0 z-[99999] overflow-y-auto flex items-center justify-center p-2.5 sm:p-4" style="top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; margin: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
            <div @click.outside="importModalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full max-h-[88vh] shadow-2xl overflow-hidden flex flex-col animate-card my-auto">
                
                <div class="p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/70 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-200/50 dark:border-indigo-900/50">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-50">Impor Data Murid (Excel)</h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Unggah file spreadsheet .xlsx/.xls untuk menambahkan murid secara massal.</p>
                        </div>
                    </div>
                    <button type="button" @click="importModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer shrink-0">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-5 space-y-4 text-left overflow-y-auto flex-1 min-h-0">
                    @csrf

                    <!-- Download Template Alert Banner -->
                    <div class="p-3.5 bg-indigo-50/80 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/60 rounded-xl flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 text-indigo-900 dark:text-indigo-300">
                            <i data-lucide="info" class="w-4 h-4 shrink-0 text-indigo-600 dark:text-indigo-400"></i>
                            <span>Belum memiliki template standar?</span>
                        </div>
                        <a href="{{ route('students.download-template') }}"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shrink-0 flex items-center gap-1.5 transition-colors shadow-xs cursor-pointer">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            Unduh Template
                        </a>
                    </div>

                    <!-- Default Academic Year -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tahun Ajaran Pendaftaran</label>
                        <select name="default_academic_year_id" class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs font-semibold">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }} {{ $ay->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Default Classroom (Optional) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Default Kelompok Belajar (Opsional)</label>
                        <select name="default_classroom_id" class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                            <option value="">-- Sesuaikan dengan Kolom di File Excel --</option>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}">[{{ $c->classLevel?->name ?? $c->sub_unit }}] {{ $c->name }}</option>
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
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
                        <button type="button" @click="importModalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
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
    </template>

    </div>

    <!-- Alpine.js Application Logic for Data Murid -->
    <script>
        function handleClassLevelFilterChange(selectElem) {
            const classroomSelect = document.getElementById('filter_classroom_id');
            if (classroomSelect) {
                classroomSelect.value = 'all';
            }
            selectElem.form.submit();
        }

        function studentApp() {
            const allLevels = Object.freeze(@json($allClassLevels));
            const allClasses = Object.freeze(@json($allClassrooms));

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
                formErrors: {},
                availableClassLevels: Object.freeze(allLevels),
                availableClassrooms: Object.freeze(allClasses),
                formData: {
                    id: null,
                    academic_year_id: '{{ $selectedYearId && $selectedYearId !== "all" ? $selectedYearId : ($academicYears->firstWhere("is_active", true)?->id ?? "") }}',
                    jenjang_id: '{{ $selectedJenjangId }}',
                    class_level_id: '',
                    classroom_id: '',
                    sub_unit: 'TK',
                    daycare_classroom_id: '',
                    is_tpq: false,
                    nis: '',
                    nisn: '',
                    nik: '',
                    pin_access: '1234',
                    full_name: '',
                    nickname: '',
                    gender: 'L',
                    birth_place: '',
                    birth_date: '',
                    religion: 'Islam',
                    address: '',
                    city: 'Malang',
                    father_name: '',
                    father_phone: '',
                    mother_name: '',
                    mother_phone: '',
                    parent_phone: '',
                    status: 'aktif',
                    notes: '',
                },

                updateAvailableOptions() {
                    let levels = !this.formData.jenjang_id 
                        ? allLevels 
                        : allLevels.filter(lvl => lvl.jenjang_id == this.formData.jenjang_id);

                    let res = allClasses;
                    if (this.formData.academic_year_id && this.formData.academic_year_id !== 'all') {
                        res = res.filter(c => !c.academic_year_id || c.academic_year_id == this.formData.academic_year_id);
                    }
                    if (this.formData.jenjang_id) {
                        res = res.filter(c => c.jenjang_id == this.formData.jenjang_id || (c.class_level && c.class_level.jenjang_id == this.formData.jenjang_id));
                    }
                    if (this.formData.class_level_id) {
                        res = res.filter(c => c.class_level_id == this.formData.class_level_id);
                    }
                    this.availableClassLevels = Object.freeze(levels);
                    this.availableClassrooms = Object.freeze(res);
                },

                onAcademicYearChange() {
                    this.clearError('academic_year_id');
                    this.updateAvailableOptions();
                    this.onClassLevelChange();
                },

                onJenjangChange() {
                    this.clearError('jenjang_id');
                    this.updateAvailableOptions();
                    const validLevel = this.availableClassLevels.some(l => l.id == this.formData.class_level_id);
                    if (!validLevel) {
                        this.formData.class_level_id = this.availableClassLevels.length > 0 ? this.availableClassLevels[0].id : '';
                    }
                    this.onClassLevelChange();
                },

                onClassLevelChange() {
                    this.updateAvailableOptions();
                    const validClass = this.availableClassrooms.some(c => c.id == this.formData.classroom_id);
                    if (!validClass) {
                        this.formData.classroom_id = this.availableClassrooms.length > 0 ? this.availableClassrooms[0].id : '';
                    }
                },

                clearError(key) {
                    if (this.formErrors && this.formErrors[key]) {
                        delete this.formErrors[key];
                    }
                },

                hasTabError(tab) {
                    const errs = this.formErrors;
                    if (!errs || Object.keys(errs).length === 0) return false;
                    if (tab === 'program') {
                        return !!(errs.academic_year_id || errs.jenjang_id || errs.class_level_id || errs.classroom_id);
                    }
                    if (tab === 'ananda') {
                        return !!(errs.full_name || errs.nis || errs.nisn || errs.nik || errs.gender || errs.birth_date || errs.status);
                    }
                    if (tab === 'ortu') {
                        return !!(errs.parent_phone || errs.father_phone || errs.mother_phone);
                    }
                    return false;
                },

                goToTab(tab) {
                    this.formTab = tab;
                },

                validateStep(step) {
                    let isValid = true;
                    if (step === 'program') {
                        if (!this.formData.academic_year_id) {
                            this.formErrors.academic_year_id = 'Tahun Ajaran wajib dipilih.';
                            isValid = false;
                        }
                        if (!this.formData.jenjang_id) {
                            this.formErrors.jenjang_id = 'Jenjang Pendidikan wajib dipilih.';
                            isValid = false;
                        }
                    } else if (step === 'ananda') {
                        if (!this.formData.full_name || !this.formData.full_name.trim()) {
                            this.formErrors.full_name = 'Nama lengkap ananda wajib diisi.';
                            isValid = false;
                        }
                        if (!this.formData.gender) {
                            this.formErrors.gender = 'Jenis kelamin wajib dipilih.';
                            isValid = false;
                        }
                        if (!this.formData.status) {
                            this.formErrors.status = 'Status murid wajib dipilih.';
                            isValid = false;
                        }
                    } else if (step === 'ortu') {
                        if (!this.formData.parent_phone || !this.formData.parent_phone.trim()) {
                            this.formErrors.parent_phone = 'No. WhatsApp utama kontak wajib diisi.';
                            isValid = false;
                        }
                    }
                    return isValid;
                },

                validateAll() {
                    this.formErrors = {};
                    let isValid = true;

                    // Step 1
                    if (!this.formData.academic_year_id) {
                        this.formErrors.academic_year_id = 'Tahun Ajaran wajib dipilih.';
                        isValid = false;
                    }
                    if (!this.formData.jenjang_id) {
                        this.formErrors.jenjang_id = 'Jenjang Pendidikan wajib dipilih.';
                        isValid = false;
                    }
                    if (!isValid) {
                        this.formTab = 'program';
                        if (typeof window.showToast === 'function') {
                            window.showToast('Validasi Penempatan', 'Lengkapi Tahun Ajaran dan Jenjang Pendidikan terlebih dahulu.', 'warning');
                        }
                        return false;
                    }

                    // Step 2
                    if (!this.formData.full_name || !this.formData.full_name.trim()) {
                        this.formErrors.full_name = 'Nama lengkap ananda wajib diisi.';
                        isValid = false;
                    }
                    if (!this.formData.gender) {
                        this.formErrors.gender = 'Jenis kelamin wajib dipilih.';
                        isValid = false;
                    }
                    if (!this.formData.status) {
                        this.formErrors.status = 'Status murid wajib dipilih.';
                        isValid = false;
                    }
                    if (!isValid) {
                        this.formTab = 'ananda';
                        if (typeof window.showToast === 'function') {
                            window.showToast('Validasi Biodata Ananda', 'Nama Lengkap Ananda wajib diisi.', 'warning');
                        }
                        return false;
                    }

                    // Step 3
                    if (!this.formData.parent_phone || !this.formData.parent_phone.trim()) {
                        this.formErrors.parent_phone = 'No. WhatsApp utama kontak wajib diisi.';
                        this.formTab = 'ortu';
                        if (typeof window.showToast === 'function') {
                            window.showToast('Validasi Kontak', 'No. WhatsApp Utama Kontak wajib diisi.', 'warning');
                        }
                        return false;
                    }

                    return true;
                },

                nextStep() {
                    if (!this.validateStep(this.formTab)) {
                        const firstError = Object.values(this.formErrors)[0];
                        if (typeof window.showToast === 'function' && firstError) {
                            window.showToast('Validasi Diperlukan', firstError, 'warning');
                        }
                        return;
                    }
                    if (this.formTab === 'program') {
                        this.formTab = 'ananda';
                    } else if (this.formTab === 'ananda') {
                        this.formTab = 'ortu';
                    }
                },

                prevStep() {
                    if (this.formTab === 'ortu') {
                        this.formTab = 'ananda';
                    } else if (this.formTab === 'ananda') {
                        this.formTab = 'program';
                    }
                },

                copyText(text) {
                    navigator.clipboard.writeText(text);
                    if (typeof window.showToast === 'function') {
                        window.showToast('Sukses!', 'Teks berhasil disalin ke papan klip.', 'success');
                    }
                },

                copyStudentSummary() {
                    if (!this.selectedStudent) return;
                    const s = this.selectedStudent;
                    const jName = s.jenjang?.name || s.classroom?.jenjang?.name || s.sub_unit || '-';
                    const cName = s.classroom?.name || '-';
                    const summary = `*DATA MURID PAUD ANAK SALEH*\nNama: ${s.full_name} (${s.nickname || '-'})\nNIS: ${s.nis || '-'}\nPIN Wali: ${s.pin_access || '-'}\nJenjang/Kelompok: ${jName} - ${cName}\nOrtu: ${s.father_name || s.mother_name || '-'} (WA: ${s.parent_phone || '-'})`;
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
                            this.selectedStudent = Object.freeze(res.student);
                            this.studentHistories = Object.freeze(res.histories || []);
                            this.detailModalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal memuat detail murid: " + err.message, 'error');
                        }
                    });
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formTab = 'program';
                    this.formErrors = {};
                    const defaultJenjangId = '{{ $selectedJenjangId }}' || (allLevels.length > 0 ? allLevels[0].jenjang_id : '');
                    this.formData = {
                        id: null,
                        academic_year_id: '{{ $selectedYearId && $selectedYearId !== "all" ? $selectedYearId : ($academicYears->firstWhere("is_active", true)?->id ?? "") }}',
                        jenjang_id: defaultJenjangId,
                        class_level_id: '',
                        classroom_id: '',
                        sub_unit: 'TK',
                        daycare_classroom_id: '',
                        is_tpq: false,
                        nis: '',
                        nisn: '',
                        nik: '',
                        pin_access: '1234',
                        full_name: '',
                        nickname: '',
                        gender: 'L',
                        birth_place: '',
                        birth_date: '',
                        religion: 'Islam',
                        address: '',
                        city: 'Malang',
                        father_name: '',
                        father_phone: '',
                        mother_name: '',
                        mother_phone: '',
                        parent_phone: '',
                        status: 'aktif',
                        notes: '',
                    };
                    this.updateAvailableOptions();
                    this.onJenjangChange();
                    this.formModalOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                openEditModal(id) {
                    this.formTab = 'program';
                    this.formErrors = {};
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
                                jenjang_id: s.jenjang_id || (s.classroom ? s.classroom.jenjang_id : (s.class_level ? s.class_level.jenjang_id : '')),
                                class_level_id: s.class_level_id || (s.classroom ? s.classroom.class_level_id : ''),
                                classroom_id: s.classroom_id || '',
                                sub_unit: s.sub_unit || (s.classroom ? s.classroom.sub_unit : 'TK'),
                                daycare_classroom_id: s.daycare_classroom_id || '',
                                is_tpq: !!s.is_tpq,
                                nis: s.nis || '',
                                nisn: s.nisn || '',
                                nik: s.nik || '',
                                pin_access: s.pin_access || '1234',
                                full_name: s.full_name || '',
                                nickname: s.nickname || '',
                                gender: s.gender || 'L',
                                birth_place: s.birth_place || '',
                                birth_date: s.birth_date ? s.birth_date.substring(0, 10) : '',
                                religion: s.religion || 'Islam',
                                address: s.address || '',
                                city: s.city || 'Malang',
                                father_name: s.father_name || '',
                                father_phone: s.father_phone || '',
                                mother_name: s.mother_name || '',
                                mother_phone: s.mother_phone || '',
                                parent_phone: s.parent_phone || '',
                                status: s.status || 'aktif',
                                notes: s.notes || '',
                            };
                            this.updateAvailableOptions();
                            this.detailModalOpen = false;
                            this.formModalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal mengambil data murid: " + err.message, 'error');
                        }
                    });
                },

                submitForm() {
                    if (this.saving) return;

                    if (!this.validateAll()) {
                        return;
                    }

                    this.saving = true;
                    const url = this.isEdit ? `/students/${this.formData.id}` : '/students';
                    const method = this.isEdit ? 'PUT' : 'POST';

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.formData)
                    })
                    .then(async (res) => {
                        this.saving = false;
                        const data = await res.json().catch(() => null);
                        if (res.ok && data && data.success) {
                            this.formModalOpen = false;
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(data.message || 'Data murid berhasil disimpan!', 'success');
                            }
                            const targetUrl = new URL(window.location.origin + window.location.pathname);
                            const targetJenjang = this.formData.jenjang_id || '{{ $selectedJenjangId }}';
                            const targetTapel = this.formData.academic_year_id || '{{ $selectedYearId }}';
                            targetUrl.searchParams.set('jenjang_id', targetJenjang);
                            if (targetTapel && targetTapel !== 'all') {
                                targetUrl.searchParams.set('academic_year_id', targetTapel);
                            }
                            window.location.href = targetUrl.toString();
                        } else {
                            if (data && data.errors) {
                                this.formErrors = {};
                                for (let k in data.errors) {
                                    this.formErrors[k] = data.errors[k][0];
                                }
                                if (data.errors.academic_year_id || data.errors.jenjang_id || data.errors.classroom_id || data.errors.class_level_id) {
                                    this.formTab = 'program';
                                } else if (data.errors.full_name || data.errors.nis || data.errors.nisn || data.errors.nik || data.errors.gender || data.errors.birth_date || data.errors.status) {
                                    this.formTab = 'ananda';
                                } else if (data.errors.parent_phone || data.errors.father_phone || data.errors.mother_phone) {
                                    this.formTab = 'ortu';
                                }
                            }
                            let msg = (data && (data.message || (data.errors ? Object.values(data.errors).flat().join('<br>') : null))) || 'Terjadi kesalahan saat menyimpan data murid.';
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', msg, 'error');
                            }
                        }
                    })
                    .catch(err => {
                        this.saving = false;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Terjadi kendala koneksi: ' + err.message, 'error');
                        }
                    });
                },

                deleteStudent(id, name) {
                    const action = () => {
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
                                if (typeof window.setPendingToast === 'function') {
                                    window.setPendingToast(res.message || 'Murid berhasil dihapus!', 'success');
                                }
                                const targetUrl = new URL(window.location.origin + window.location.pathname);
                                targetUrl.searchParams.set('jenjang_id', '{{ $selectedJenjangId }}');
                                if ('{{ $selectedYearId }}' && '{{ $selectedYearId }}' !== 'all') {
                                    targetUrl.searchParams.set('academic_year_id', '{{ $selectedYearId }}');
                                }
                                window.location.href = targetUrl.toString();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal menghapus murid.', 'error');
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
                        showGlobalConfirmModal(`Apakah Anda yakin ingin menghapus data murid "${name}"?`, action, true);
                    } else if (confirm(`Apakah Anda yakin ingin menghapus data murid "${name}"?`)) {
                        action();
                    }
                }
            }
        }
    </script>
</x-admin-layout>
