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
                        <span>Program & Akademik</span>
                    </button>
                    <button type="button" @click="detailTab = 'biodata'"
                        :class="detailTab === 'biodata' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 text-[11px] sm:text-xs">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        <span>Identitas & Domisili</span>
                    </button>
                    <button type="button" @click="detailTab = 'ortu'"
                        :class="detailTab === 'ortu' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 text-[11px] sm:text-xs">
                        <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                        <span>Keluarga & Kontak</span>
                    </button>
                    <button type="button" @click="detailTab = 'fisik'"
                        :class="detailTab === 'fisik' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 text-[11px] sm:text-xs">
                        <i data-lucide="heart-pulse" class="w-3.5 h-3.5"></i>
                        <span>Fisik & PAUD</span>
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
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                                Penempatan Akademik & Program
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
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Kelompok Daycare</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.daycare_classroom?.name || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Program TPQ</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.is_tpq || selectedStudent?.tpq_classroom_id) ? ('Ikut TPQ ' + (selectedStudent?.tpq_classroom?.name ? ' (' + selectedStudent.tpq_classroom.name + ')' : '')) : 'Tidak Ikut TPQ'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">PIN Akses Ortu</span>
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 text-xs mt-0.5 block" x-text="selectedStudent?.pin_access || '1234'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Identitas Pribadi & Domisili -->
                    <div x-show="detailTab === 'biodata'" class="space-y-4">
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                Identitas Resmi & Dokumen Kependudukan
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Nama Lengkap Ananda</span>
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
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Agama / Kewarganegaraan</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.religion || 'Islam') + ' / ' + (selectedStudent?.citizenship || 'WNI')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">NIS / NISN</span>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.nis || '-') + ' / ' + (selectedStudent?.nisn || '-')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">NIK Ananda</span>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.nik || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">No. Kartu Keluarga (KK)</span>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.no_kk || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">No. Registrasi Akta Lahir</span>
                                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.no_akta || '-'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Domisili Details -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-teal-600 dark:text-teal-400">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                Domisili & Moda Transportasi
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                                <div class="col-span-2 sm:col-span-3">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Alamat Jalan Lengkap</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.address || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">RT / RW</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.rt ? 'RT ' + selectedStudent.rt : '-') + ' / ' + (selectedStudent?.rw ? 'RW ' + selectedStudent.rw : '-')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Kelurahan & Kecamatan</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.kelurahan || '-') + ', ' + (selectedStudent?.kecamatan || '-')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Kota / Kode Pos</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.city || 'Malang') + (selectedStudent?.postal_code ? ' (' + selectedStudent.postal_code + ')' : '')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Tempat Tinggal</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.living_with || 'Bersama orang tua'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Moda Transportasi</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.transportation || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Jarak & Waktu Tempuh</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="(selectedStudent?.distance_to_school || '-') + (selectedStudent?.travel_time_minutes ? ' (' + selectedStudent.travel_time_minutes + ' menit)' : '')"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Data Orang Tua & Kontak -->
                    <div x-show="detailTab === 'ortu'" class="space-y-4">
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                                Profil Lengkap Orang Tua & Kontak
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Card Ayah -->
                                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg space-y-2">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Ayah Kandung</span>
                                    <p class="font-bold text-slate-900 dark:text-slate-100 text-xs" x-text="selectedStudent?.father_name || '-'"></p>
                                    <div class="text-[11px] space-y-0.5 text-slate-600 dark:text-slate-400">
                                        <p>NIK: <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedStudent?.family_data?.father?.nik || '-'"></span></p>
                                        <p>Pendidikan: <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedStudent?.family_data?.father?.education || '-'"></span></p>
                                        <p>Pekerjaan: <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedStudent?.father_job || selectedStudent?.family_data?.father?.job || '-'"></span></p>
                                        <p class="font-mono text-indigo-600 dark:text-indigo-400" x-text="selectedStudent?.father_phone ? '📞 ' + selectedStudent.father_phone : '-'"></p>
                                    </div>
                                </div>
                                <!-- Card Ibu -->
                                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg space-y-2">
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Ibu Kandung</span>
                                    <p class="font-bold text-slate-900 dark:text-slate-100 text-xs" x-text="selectedStudent?.mother_name || '-'"></p>
                                    <div class="text-[11px] space-y-0.5 text-slate-600 dark:text-slate-400">
                                        <p>NIK: <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedStudent?.family_data?.mother?.nik || '-'"></span></p>
                                        <p>Pendidikan: <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedStudent?.family_data?.mother?.education || '-'"></span></p>
                                        <p>Pekerjaan: <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedStudent?.mother_job || selectedStudent?.family_data?.mother?.job || '-'"></span></p>
                                        <p class="font-mono text-indigo-600 dark:text-indigo-400" x-text="selectedStudent?.mother_phone ? '📞 ' + selectedStudent.mother_phone : '-'"></p>
                                    </div>
                                </div>
                                <!-- Card Wali (Jika ada) -->
                                <template x-if="selectedStudent?.guardian_name || selectedStudent?.family_data?.guardian?.name">
                                    <div class="col-span-1 sm:col-span-2 p-3 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-lg space-y-1">
                                        <span class="text-amber-700 dark:text-amber-400 block text-[10.5px] uppercase font-bold tracking-wider">Wali Murid</span>
                                        <p class="font-bold text-slate-900 dark:text-slate-100 text-xs" x-text="(selectedStudent?.guardian_name || selectedStudent?.family_data?.guardian?.name) + (selectedStudent?.family_data?.guardian?.relation ? ' (' + selectedStudent.family_data.guardian.relation + ')' : '')"></p>
                                        <p class="text-[11px] text-slate-600 dark:text-slate-400 font-mono" x-text="selectedStudent?.guardian_phone ? '📞 ' + selectedStudent.guardian_phone : '-'"></p>
                                    </div>
                                </template>
                                <!-- WhatsApp Utama Banner -->
                                <div class="col-span-1 sm:col-span-2 p-3 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
                                    <div>
                                        <span class="text-[10.5px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider block">No. WhatsApp Utama Kontak</span>
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

                    <!-- TAB 4: Fisik & Kebiasaan PAUD -->
                    <div x-show="detailTab === 'fisik'" class="space-y-4">
                        <!-- Antropometri & Fisik -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                                <i data-lucide="ruler" class="w-3.5 h-3.5"></i>
                                Antropometri & Kondisi Fisik Ananda
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Tinggi Badan</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.height ? selectedStudent.height + ' cm' : '-'"></span>
                                </div>
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Berat Badan</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.weight ? selectedStudent.weight + ' kg' : '-'"></span>
                                </div>
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Lingkar Kepala</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.head_circumference ? selectedStudent.head_circumference + ' cm' : '-'"></span>
                                </div>
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Lingkar Lengan (LiLa)</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm mt-0.5 block" x-text="selectedStudent?.arm_circumference ? selectedStudent.arm_circumference + ' cm' : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Golongan Darah</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.blood_type || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Kebutuhan Khusus</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.special_needs || 'Tidak ada'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Urutan Anak</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="'Anak ke-' + (selectedStudent?.child_order || '1') + ' dari ' + ((selectedStudent?.siblings_count || 0) + 1) + ' bersaudara'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Riwayat Alergi</span>
                                    <span class="font-semibold text-rose-600 dark:text-rose-400 text-xs mt-0.5 block" x-text="selectedStudent?.health_habits?.allergy?.has_allergies ? selectedStudent.health_habits.allergy.types : 'Tidak ada alergi'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Kebiasaan & Perkembangan PAUD -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-purple-600 dark:text-purple-400">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                Kebiasaan Makan, Tidur & Tumbuh Kembang
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Pola/Selera Makan</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.health_habits?.diet?.eating_morning ? ('Pagi: ' + selectedStudent.health_habits.diet.eating_morning + ', Siang: ' + selectedStudent.health_habits.diet.eating_afternoon) : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Toilet Training</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.health_habits?.habits?.toilet_training || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Screen Time</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.development_psychology?.habits?.screen_time_hours ? (selectedStudent.development_psychology.habits.screen_time_hours + ' jam/hari') : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Pola Tidur</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.development_psychology?.habits?.sleep_time ? (selectedStudent.development_psychology.habits.sleep_time + ' - ' + selectedStudent.development_psychology.habits.wake_time) : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Milestone Jalan / Bicara</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.development_psychology?.milestones?.walking_age_months ? ('Jalan: ' + selectedStudent.development_psychology.milestones.walking_age_months + ' bln, Bicara: ' + selectedStudent.development_psychology.milestones.speaking_age_months + ' bln') : '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10.5px] uppercase font-semibold tracking-wider">Penjemput Resmi</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs mt-0.5 block" x-text="selectedStudent?.development_psychology?.security?.authorized_pickups || 'Orang tua kandung'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: Riwayat Perjalanan Belajar (Timeline) -->
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

        <!-- MODAL TAMBAH / EDIT MURID (5-STEP WIZARD FORM) -->
        <template x-teleport="body">
            <div x-show="formModalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-2.5 sm:p-4" style="top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; margin: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="formModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-4xl overflow-hidden shadow-2xl flex flex-col text-left my-auto" style="height: 640px; max-height: min(640px, calc(100vh - 32px));">
                
                <form novalidate @submit.prevent="submitForm" class="flex flex-col h-full w-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-900/80 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <i :data-lucide="isEdit ? 'edit-2' : 'user-plus'" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Formulir Data Siswa Lengkap' : 'Tambah Murid Baru (Formulir Lengkap)'"></h3>
                                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">Konfigurasi lengkap 5 tahap: identitas diri, domisili, keluarga, fisik PAUD, dan tumbuh kembang.</p>
                            </div>
                        </div>
                        <button type="button" @click="formModalOpen = false" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer">
                            <i data-lucide="x" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                        </button>
                    </div>

                    <!-- 5-Step Stepper Navigation -->
                    <div class="flex items-center border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs px-3.5 sm:px-5 py-2 sm:py-2.5 gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar shrink-0">
                        <button type="button" @click="goToTab('tab1_identitas')"
                            :class="formTab === 'tab1_identitas' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs transition-colors">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">1</span>
                            <span>Identitas & Akademik</span>
                            <span x-show="hasTabError('tab1_identitas')" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                        <button type="button" @click="goToTab('tab2_domisili')"
                            :class="formTab === 'tab2_domisili' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs transition-colors">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">2</span>
                            <span>Domisili & Alamat</span>
                            <span x-show="hasTabError('tab2_domisili')" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                        <button type="button" @click="goToTab('tab3_keluarga')"
                            :class="formTab === 'tab3_keluarga' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs transition-colors">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">3</span>
                            <span>Keluarga & Kontak</span>
                            <span x-show="hasTabError('tab3_keluarga')" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                        <button type="button" @click="goToTab('tab4_fisik')"
                            :class="formTab === 'tab4_fisik' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs transition-colors">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">4</span>
                            <span>Fisik & PAUD</span>
                            <span x-show="hasTabError('tab4_fisik')" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                        <button type="button" @click="goToTab('tab5_tumbuhkembang')"
                            :class="formTab === 'tab5_tumbuhkembang' ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-2.5 sm:px-3 py-1.5 rounded-lg cursor-pointer flex items-center gap-1.5 shrink-0 relative text-[11px] sm:text-xs transition-colors">
                            <span class="w-4 h-4 rounded-full bg-white/20 text-[10px] flex items-center justify-center font-bold">5</span>
                            <span>Tumbuh Kembang</span>
                            <span x-show="hasTabError('tab5_tumbuhkembang')" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 ring-2 ring-white"></span>
                        </button>
                    </div>

                    <!-- Modal Body (Flex-1 container ensuring identical height across all tabs) -->
                    <div class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto flex-1 min-h-0 overscroll-contain" style="flex: 1 1 0%; min-height: 0;">
                        
                        <!-- TAB 1: IDENTITAS & PENEMPATAN AKADEMIK -->
                        <div x-show="formTab === 'tab1_identitas'" class="space-y-4">
                            <!-- Card 1: Penempatan Akademik -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                    <i data-lucide="shapes" class="w-3.5 h-3.5"></i>
                                    Penempatan Akademik & Kelompok Belajar
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
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer font-semibold">
                                            <option value="">-- Pilih Jenjang --</option>
                                            @foreach($jenjangs as $j)
                                                <option value="{{ $j->id }}">{{ $j->name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="formErrors.jenjang_id" x-text="formErrors.jenjang_id" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer font-semibold">
                                            <option value="">-- Pilih Kelompok --</option>
                                            <template x-for="rombel in availableClassrooms" :key="rombel.id">
                                                <option :value="rombel.id" x-text="rombel.name + (rombel.homeroom_teacher ? ' (' + rombel.homeroom_teacher.name + ')' : '')"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Murid <span class="text-rose-500">*</span></label>
                                        <select x-model="formData.status" @change="clearError('status')"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer font-semibold">
                                            <option value="aktif">Aktif</option>
                                            <option value="lulus">Lulus</option>
                                            <option value="mutasi">Mutasi</option>
                                            <option value="keluar">Keluar</option>
                                            <option value="nonaktif">Nonaktif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-200/60 dark:border-slate-800/60">
                                    <div class="flex items-center gap-2 pt-2">
                                        <input type="checkbox" id="form_is_tpq" x-model="formData.is_tpq" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 cursor-pointer">
                                        <label for="form_is_tpq" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                                            Ikut Program TPQ Sekolah
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelompok Daycare <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                        <select x-model="formData.daycare_classroom_id"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="">-- Tidak Ikut Daycare --</option>
                                            @foreach($daycareClassrooms as $dc)
                                                <option value="{{ $dc->id }}">{{ $dc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Biodata Induk Ananda -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                    Biodata & Legalitas Kependudukan Ananda
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ananda <span class="text-rose-500">*</span></label>
                                        <input type="text" x-model="formData.full_name" @input="if (formErrors.full_name) clearError('full_name')" placeholder="Nama lengkap sesuai akta kelahiran"
                                            :class="formErrors.full_name ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-slate-200 dark:border-slate-800'"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
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
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer font-semibold">
                                            <option value="L">Laki-laki (Putra)</option>
                                            <option value="P">Perempuan (Putri)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                                        <input type="text" x-model="formData.birth_place" placeholder="Kota kelahiran (cth: Malang)"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                                        <input type="date" x-model="formData.birth_date"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIS <span class="text-slate-400 font-normal">(Nomor Induk Siswa)</span></label>
                                        <input type="text" x-model="formData.nis" placeholder="Contoh: 27.PAUD.001"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NISN</label>
                                        <input type="text" x-model="formData.nisn" placeholder="10 digit nomor NISN"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Anak (KTP/KIA)</label>
                                        <input type="text" x-model="formData.nik" placeholder="16 digit NIK"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Kartu Keluarga (KK)</label>
                                        <input type="text" x-model="formData.no_kk" placeholder="16 digit nomor KK"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Registrasi Akta Lahir</label>
                                        <input type="text" x-model="formData.no_akta" placeholder="Nomor registrasi akta kelahiran"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kewarganegaraan</label>
                                        <select x-model="formData.citizenship"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="WNI">WNI (Warga Negara Indonesia)</option>
                                            <option value="WNA">WNA (Warga Negara Asing)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: DOMISILI & ALAMAT -->
                        <div x-show="formTab === 'tab2_domisili'" class="space-y-4">
                            <!-- Card 1: Alamat Rumah -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-sky-600 dark:text-sky-400">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                    Alamat Domisili Tempat Tinggal
                                </h4>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Jalan / Tempat Tinggal</label>
                                    <input type="text" x-model="formData.address" placeholder="Nama Jalan, Gang, Nomor Rumah"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">RT</label>
                                        <input type="text" x-model="formData.rt" placeholder="01"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">RW</label>
                                        <input type="text" x-model="formData.rw" placeholder="05"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelurahan / Desa</label>
                                        <input type="text" x-model="formData.kelurahan" placeholder="Kelurahan"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kecamatan</label>
                                        <input type="text" x-model="formData.kecamatan" placeholder="Kecamatan"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kota / Kabupaten</label>
                                        <input type="text" x-model="formData.city" placeholder="Malang"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Pos</label>
                                        <input type="text" x-model="formData.postal_code" placeholder="65141"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Tempat Tinggal & Aksesibilitas Sekolah -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-sky-600 dark:text-sky-400">
                                    <i data-lucide="home" class="w-3.5 h-3.5"></i>
                                    Tempat Tinggal, Transportasi & Lingkungan
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tinggal Bersama</label>
                                        <select x-model="formData.living_with"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Bersama Orang Tua">Bersama Orang Tua</option>
                                            <option value="Bersama Wali">Bersama Wali</option>
                                            <option value="Kos / Asrama">Kos / Asrama</option>
                                            <option value="Panti Asuhan">Panti Asuhan</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Transportasi ke Sekolah</label>
                                        <select x-model="formData.transportation"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Sepeda Motor">Sepeda Motor</option>
                                            <option value="Mobil Pribadi">Mobil Pribadi</option>
                                            <option value="Antar Jemput">Antar Jemput Sekolah</option>
                                            <option value="Jalan Kaki">Jalan Kaki</option>
                                            <option value="Angkutan Umum">Angkutan Umum</option>
                                            <option value="Sepeda">Sepeda</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jarak ke Sekolah (km)</label>
                                        <input type="text" x-model="formData.distance_to_school" placeholder="Contoh: 2.5"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Waktu Tempuh (menit)</label>
                                        <input type="number" min="0" max="300" x-model="formData.travel_time_minutes" placeholder="Contoh: 15"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-slate-200/60 dark:border-slate-800/60">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kondisi Sekitar Rumah</label>
                                        <select x-model="formData.surroundings"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Perumahan">Perumahan / Kompleks</option>
                                            <option value="Perkampungan">Perkampungan</option>
                                            <option value="Tepi Jalan Raya">Tepi Jalan Raya</option>
                                            <option value="Dekat Sawah/Sungai">Dekat Sawah / Sungai</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Dekat Masjid / Musholla?</label>
                                        <select x-model="formData.near_mosque"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Ya">Ya (Dekat)</option>
                                            <option value="Tidak">Tidak</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Ikut TPQ Luar Rumah?</label>
                                        <select x-model="formData.external_tpq"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Tidak">Tidak</option>
                                            <option value="Ya">Ya (Ikut TPQ Luar)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: DATA KELUARGA & KONTAK -->
                        <div x-show="formTab === 'tab3_keluarga'" class="space-y-4">
                            <!-- Banner No WA Utama -->
                            <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 rounded-xl space-y-1.5">
                                <label class="block font-bold text-emerald-900 dark:text-emerald-200 text-xs flex items-center gap-1.5">
                                    <i data-lucide="phone-call" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                    Nomor WhatsApp Utama Kontak Keluarga <span class="text-rose-500">*</span>
                                </label>
                                <p class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                    Nomor ini digunakan untuk pengiriman laporan harian, notifikasi pengumuman, dan tautan e-Rapor resmi sekolah.
                                </p>
                                <input type="text" x-model="formData.parent_phone" @input="if (formErrors.parent_phone) clearError('parent_phone')" placeholder="Contoh: 081234567890"
                                    :class="formErrors.parent_phone ? 'border-rose-500 ring-1 ring-rose-500/30' : 'border-emerald-300 dark:border-emerald-700'"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-900 dark:text-slate-50 font-mono font-bold">
                                <p x-show="formErrors.parent_phone" x-text="formErrors.parent_phone" class="text-[10px] text-rose-500 font-semibold mt-1"></p>
                            </div>

                            <!-- Ayah Kandung -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                    Data Ayah Kandung
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ayah</label>
                                        <input type="text" x-model="formData.father_name" placeholder="Nama ayah kandung"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Ayah</label>
                                        <input type="text" x-model="formData.father_title" placeholder="Contoh: S.T., M.M."
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Ayah</label>
                                        <input type="text" x-model="formData.father_nik" placeholder="16 digit NIK"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat & Tgl Lahir Ayah</label>
                                        <input type="text" x-model="formData.father_birth" placeholder="Malang, 12-05-1985"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pendidikan Terakhir</label>
                                        <select x-model="formData.father_education"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="">-- Pilih Pendidikan --</option>
                                            <option value="SD / Sederajat">SD / Sederajat</option>
                                            <option value="SMP / Sederajat">SMP / Sederajat</option>
                                            <option value="SMA / SMK / Sederajat">SMA / SMK / Sederajat</option>
                                            <option value="D3">Diploma (D3)</option>
                                            <option value="S1 / D4">Sarjana (S1 / D4)</option>
                                            <option value="S2">Magister (S2)</option>
                                            <option value="S3">Doktor (S3)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan Ayah</label>
                                        <input type="text" x-model="formData.father_job" placeholder="PNS / Swasta / Wiraswasta"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Tempat Kerja</label>
                                        <input type="text" x-model="formData.father_workplace" placeholder="Nama kantor / instansi"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. HP / WA Ayah</label>
                                        <input type="text" x-model="formData.father_phone" placeholder="08xxxxxxxxxx"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>
                            </div>

                            <!-- Ibu Kandung -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-pink-600 dark:text-pink-400">
                                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                    Data Ibu Kandung
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ibu</label>
                                        <input type="text" x-model="formData.mother_name" placeholder="Nama ibu kandung"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Ibu</label>
                                        <input type="text" x-model="formData.mother_title" placeholder="Contoh: S.Pd."
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Ibu</label>
                                        <input type="text" x-model="formData.mother_nik" placeholder="16 digit NIK"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat & Tgl Lahir Ibu</label>
                                        <input type="text" x-model="formData.mother_birth" placeholder="Malang, 20-08-1988"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pendidikan Terakhir</label>
                                        <select x-model="formData.mother_education"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="">-- Pilih Pendidikan --</option>
                                            <option value="SD / Sederajat">SD / Sederajat</option>
                                            <option value="SMP / Sederajat">SMP / Sederajat</option>
                                            <option value="SMA / SMK / Sederajat">SMA / SMK / Sederajat</option>
                                            <option value="D3">Diploma (D3)</option>
                                            <option value="S1 / D4">Sarjana (S1 / D4)</option>
                                            <option value="S2">Magister (S2)</option>
                                            <option value="S3">Doktor (S3)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan Ibu</label>
                                        <input type="text" x-model="formData.mother_job" placeholder="Ibu Rumah Tangga / Guru / PNS"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Tempat Kerja</label>
                                        <input type="text" x-model="formData.mother_workplace" placeholder="Nama kantor / instansi"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. HP / WA Ibu</label>
                                        <input type="text" x-model="formData.mother_phone" placeholder="08xxxxxxxxxx"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>
                            </div>

                            <!-- Data Wali Murid (Optional Toggle) -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                                        Data Wali Murid <span class="text-slate-400 font-normal lowercase">(jika tinggal bersama wali)</span>
                                    </h4>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="formData.has_guardian" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 cursor-pointer">
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Ada Data Wali</span>
                                    </label>
                                </div>

                                <div x-show="formData.has_guardian" class="space-y-3 pt-2 border-t border-slate-200/60 dark:border-slate-800/60">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Wali</label>
                                            <input type="text" x-model="formData.guardian_name" placeholder="Nama wali murid"
                                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan dengan Ananda</label>
                                            <input type="text" x-model="formData.guardian_relation" placeholder="Contoh: Kakek / Paman / Kakak"
                                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Wali</label>
                                            <input type="text" x-model="formData.guardian_nik" placeholder="16 digit NIK"
                                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan Wali</label>
                                            <input type="text" x-model="formData.guardian_job" placeholder="Pekerjaan"
                                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. HP Wali</label>
                                            <input type="text" x-model="formData.guardian_phone" placeholder="08xxxxxxxxxx"
                                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: FISIK & KEBIASAAN PAUD -->
                        <div x-show="formTab === 'tab4_fisik'" class="space-y-4">
                            <!-- Card 1: Antropometri & Kesehatan Fisik -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-teal-600 dark:text-teal-400">
                                    <i data-lucide="heart-pulse" class="w-3.5 h-3.5"></i>
                                    Pengukuran Fisik (Antropometri) & Medis Dasar
                                </h4>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tinggi Badan (cm)</label>
                                        <input type="number" step="0.1" min="0" max="250" x-model="formData.height" placeholder="Contoh: 105"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Berat Badan (kg)</label>
                                        <input type="number" step="0.1" min="0" max="150" x-model="formData.weight" placeholder="Contoh: 17.5"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lingkar Kepala (cm)</label>
                                        <input type="number" step="0.1" min="0" max="80" x-model="formData.head_circumference" placeholder="Contoh: 50"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">LiLa (cm)</label>
                                        <input type="number" step="0.1" min="0" max="50" x-model="formData.arm_circumference" placeholder="Contoh: 15.5"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Golongan Darah</label>
                                        <select x-model="formData.blood_type"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer font-semibold">
                                            <option value="">-- Pilih Golongan --</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="AB">AB</option>
                                            <option value="O">O</option>
                                            <option value="Belum Tahu">Belum Tahu</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Anak Ke-</label>
                                        <input type="number" min="1" max="30" x-model="formData.child_order" placeholder="1"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jumlah Saudara Kandung</label>
                                        <input type="number" min="0" max="30" x-model="formData.siblings_count" placeholder="0"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kebutuhan Khusus / Diagnosa Khusus</label>
                                    <input type="text" x-model="formData.special_needs" placeholder="Contoh: Tidak Ada / Speech Delay / Alergi Dingin"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                            </div>

                            <!-- Card 2: Pola Makan & Kebiasaan Nutrisi -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-teal-600 dark:text-teal-400">
                                    <i data-lucide="utensils" class="w-3.5 h-3.5"></i>
                                    Pola Makan & Kemandirian Sehari-hari
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Sarapan Pagi</label>
                                        <select x-model="formData.breakfast_pattern"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Teratur">Teratur</option>
                                            <option value="Kadang-kadang">Kadang-kadang</option>
                                            <option value="Jarang">Jarang</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Makan Siang</label>
                                        <select x-model="formData.lunch_pattern"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Teratur">Teratur</option>
                                            <option value="Kadang-kadang">Kadang-kadang</option>
                                            <option value="Jarang">Jarang</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Makan Malam</label>
                                        <select x-model="formData.dinner_pattern"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Teratur">Teratur</option>
                                            <option value="Kadang-kadang">Kadang-kadang</option>
                                            <option value="Jarang">Jarang</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Makanan Kesukaan (Favorit)</label>
                                        <input type="text" x-model="formData.favorite_food" placeholder="Nasi goreng, telur, sayur bayam, buah pisang..."
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Makanan Dihindari / Tidak Disukai</label>
                                        <input type="text" x-model="formData.avoided_food" placeholder="Sayur pedas, ikan amis, dll..."
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Riwayat Alergi (Makanan / Obat / Debu)</label>
                                        <input type="text" x-model="formData.allergy_details" placeholder="Contoh: Alergi udang, telur, debu"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penanganan / Obat Darurat Alergi</label>
                                        <input type="text" x-model="formData.allergy_treatment" placeholder="Beri obat sirup cetirizine, balur minyak telon..."
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-slate-200/60 dark:border-slate-800/60">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Adab & Cara Makan</label>
                                        <input type="text" x-model="formData.eating_manner" placeholder="Mandiri dan duduk tertib"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kebiasaan Menggosok Gigi</label>
                                        <select x-model="formData.brush_teeth"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Mandiri">Mandiri</option>
                                            <option value="Dibantu orang tua">Dibantu orang tua</option>
                                            <option value="Belum terbiasa">Belum terbiasa</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Toilet Training (BAK & BAB)</label>
                                        <select x-model="formData.toilet_training"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer font-semibold">
                                            <option value="Mandiri (Bisa BAK/BAB sendiri)">Mandiri (Bisa BAK/BAB sendiri)</option>
                                            <option value="Mengatakan jika ingin tapi dibantu">Mengatakan jika ingin tapi dibantu</option>
                                            <option value="Masih memakai diapers/popok">Masih memakai diapers/popok</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 5: TUMBUH KEMBANG & LAINNYA -->
                        <div x-show="formTab === 'tab5_tumbuhkembang'" class="space-y-4">
                            <!-- Card 1: Pola Tidur & Gadget -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-violet-600 dark:text-violet-400">
                                    <i data-lucide="moon" class="w-3.5 h-3.5"></i>
                                    Pola Tidur & Durasi Screen Time (Gadget)
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Screen Time (Gadget) Harian</label>
                                        <select x-model="formData.screen_time_daily"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Tidak pernah">Tidak pernah</option>
                                            <option value="Kurang dari 1 jam">Kurang dari 1 jam</option>
                                            <option value="1 - 2 jam">1 - 2 jam</option>
                                            <option value="Lebih dari 2 jam">Lebih dari 2 jam</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jam Tidur Malam</label>
                                        <input type="text" x-model="formData.bedtime_pattern" placeholder="Pukul 20:30"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jam Bangun Pagi</label>
                                        <input type="text" x-model="formData.wake_time_pattern" placeholder="Pukul 05:00"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Riwayat Kehamilan & Persalinan -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-violet-600 dark:text-violet-400">
                                    <i data-lucide="baby" class="w-3.5 h-3.5"></i>
                                    Riwayat Kelahiran & Perkembangan Dini
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Usia Kehamilan</label>
                                        <select x-model="formData.gestational_age"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Cukup Bulan (Aterm)">Cukup Bulan (Aterm)</option>
                                            <option value="Prematur">Prematur</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Proses Persalinan</label>
                                        <select x-model="formData.birth_type"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Normal">Normal</option>
                                            <option value="Operasi Caesar">Operasi Caesar</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Berat Lahir (gram/kg)</label>
                                        <input type="text" x-model="formData.birth_weight" placeholder="3200 gram / 3.2 kg"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Panjang Badan Lahir (cm)</label>
                                        <input type="text" x-model="formData.birth_length" placeholder="50 cm"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Riwayat ASI</label>
                                        <select x-model="formData.exclusive_breastfeeding"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                            <option value="Ya (ASI Eksklusif 6 Bulan)">Ya (ASI Eksklusif 6 Bulan)</option>
                                            <option value="ASI + Susu Formula">ASI + Susu Formula</option>
                                            <option value="Susu Formula Saja">Susu Formula Saja</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Usia Mulai MPASI</label>
                                        <input type="text" x-model="formData.mpasi_start_age" placeholder="6 Bulan"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <!-- Milestones -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-slate-200/60 dark:border-slate-800/60">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mulai Berjalan (Bulan)</label>
                                        <input type="text" x-model="formData.walking_age_months" placeholder="Contoh: 12"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mulai Bicara / Kata (Bulan)</label>
                                        <input type="text" x-model="formData.talking_age_months" placeholder="Contoh: 14"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tumbuh Gigi Pertama (Bulan)</label>
                                        <input type="text" x-model="formData.teeth_age_months" placeholder="Contoh: 7"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Penjemputan, Keamanan & Akun Ortu -->
                            <div class="p-3.5 sm:p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-violet-600 dark:text-violet-400">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    Protokol Penjemputan, Deteksi Dini & Portal Ortu
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Penjemput Resmi & Hubungan</label>
                                        <input type="text" x-model="formData.official_pickup_person" placeholder="Ibu Kandung / Pengasuh (Mbak Sri)"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kontak Darurat Selain Ortu (Nama & No HP)</label>
                                        <input type="text" x-model="formData.emergency_contact" placeholder="Kakek (08xxxxxxxxxx)"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">PIN Akses Portal Wali Murid</label>
                                        <input type="text" x-model="formData.pin_access" placeholder="Contoh: 1234"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono font-bold">
                                    </div>
                                    <div class="flex items-center gap-2 pt-6">
                                        <input type="checkbox" id="form_early_detection" x-model="formData.early_detection_consent" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 cursor-pointer">
                                        <label for="form_early_detection" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                                            Persetujuan Deteksi Dini & Screening Tumbuh Kembang
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan untuk Guru PAUD</label>
                                    <textarea x-model="formData.notes" rows="2" placeholder="Catatan kebiasaan tidur siang, makanan khusus, atau instruksi perhatian pendampingan..."
                                        class="w-full p-2.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer Navigation Buttons -->
                    <div class="p-3 sm:p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/70 flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-2">
                            <button type="button" x-show="formTab !== 'tab1_identitas'" @click="prevStep()"
                                class="px-3.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer shadow-2xs transition-colors">
                                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                                <span>Kembali</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="formModalOpen = false" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold cursor-pointer shadow-2xs transition-colors">
                                Batal
                            </button>
                            <button type="button" x-show="formTab !== 'tab5_tumbuhkembang'" @click="nextStep()"
                                class="px-4 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer border border-indigo-200 dark:border-indigo-800 shadow-2xs transition-colors">
                                <span>Selanjutnya</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="submit" x-show="formTab === 'tab5_tumbuhkembang' || isEdit" :disabled="saving" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold shadow-xs flex items-center gap-1.5 cursor-pointer transition-colors">
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
                formTab: 'tab1_identitas',
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
                    
                    // Tab 1: Identitas & Legalitas
                    nis: '',
                    nisn: '',
                    nik: '',
                    no_kk: '',
                    no_akta: '',
                    full_name: '',
                    nickname: '',
                    gender: 'L',
                    birth_place: '',
                    birth_date: '',
                    religion: 'Islam',
                    citizenship: 'WNI',
                    status: 'aktif',
                    
                    // Tab 2: Domisili & Transportasi
                    address: '',
                    rt: '',
                    rw: '',
                    kelurahan: '',
                    kecamatan: '',
                    city: 'Malang',
                    postal_code: '',
                    living_with: 'Bersama Orang Tua',
                    transportation: 'Sepeda Motor',
                    distance_to_school: '',
                    travel_time_minutes: '',
                    surroundings: 'Perumahan',
                    near_mosque: 'Ya',
                    external_tpq: 'Tidak',
                    
                    // Tab 3: Keluarga & Kontak
                    parent_phone: '',
                    father_name: '',
                    father_title: '',
                    father_nik: '',
                    father_birth: '',
                    father_education: '',
                    father_job: '',
                    father_workplace: '',
                    father_phone: '',
                    
                    mother_name: '',
                    mother_title: '',
                    mother_nik: '',
                    mother_birth: '',
                    mother_education: '',
                    mother_job: '',
                    mother_workplace: '',
                    mother_phone: '',
                    
                    has_guardian: false,
                    guardian_name: '',
                    guardian_relation: '',
                    guardian_nik: '',
                    guardian_education: '',
                    guardian_job: '',
                    guardian_phone: '',
                    
                    // Tab 4: Fisik & Kebiasaan PAUD
                    height: '',
                    weight: '',
                    head_circumference: '',
                    arm_circumference: '',
                    blood_type: '',
                    child_order: 1,
                    siblings_count: 0,
                    special_needs: '',
                    breakfast_pattern: 'Teratur',
                    lunch_pattern: 'Teratur',
                    dinner_pattern: 'Teratur',
                    favorite_food: '',
                    avoided_food: '',
                    allergy_details: '',
                    allergy_treatment: '',
                    eating_manner: 'Mandiri dan duduk tertib',
                    brush_teeth: 'Mandiri',
                    toilet_training: 'Mandiri (Bisa BAK/BAB sendiri)',
                    
                    // Tab 5: Tumbuh Kembang & Tambahan
                    screen_time_daily: 'Kurang dari 1 jam',
                    bedtime_pattern: 'Pukul 20:30',
                    wake_time_pattern: 'Pukul 05:00',
                    gestational_age: 'Cukup Bulan (Aterm)',
                    birth_type: 'Normal',
                    birth_weight: '',
                    birth_length: '',
                    exclusive_breastfeeding: 'Ya (ASI Eksklusif 6 Bulan)',
                    mpasi_start_age: '6 Bulan',
                    walking_age_months: '',
                    talking_age_months: '',
                    teeth_age_months: '',
                    official_pickup_person: '',
                    emergency_contact: '',
                    pin_access: '1234',
                    early_detection_consent: true,
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
                    if (tab === 'tab1_identitas') {
                        return !!(errs.academic_year_id || errs.jenjang_id || errs.class_level_id || errs.classroom_id || errs.full_name || errs.gender || errs.status || errs.nis || errs.nisn || errs.nik || errs.no_kk || errs.no_akta);
                    }
                    if (tab === 'tab2_domisili') {
                        return !!(errs.address || errs.rt || errs.rw || errs.kelurahan || errs.kecamatan || errs.city || errs.postal_code || errs.living_with || errs.transportation || errs.distance_to_school || errs.travel_time_minutes);
                    }
                    if (tab === 'tab3_keluarga') {
                        return !!(errs.parent_phone || errs.father_phone || errs.mother_phone || errs.father_name || errs.mother_name);
                    }
                    if (tab === 'tab4_fisik') {
                        return !!(errs.height || errs.weight || errs.head_circumference || errs.arm_circumference || errs.blood_type || errs.child_order || errs.siblings_count);
                    }
                    if (tab === 'tab5_tumbuhkembang') {
                        return !!(errs.pin_access || errs.notes || errs.official_pickup_person || errs.emergency_contact);
                    }
                    return false;
                },

                goToTab(tab) {
                    this.formTab = tab;
                },

                validateStep(step) {
                    let isValid = true;
                    if (step === 'tab1_identitas') {
                        if (!this.formData.academic_year_id) {
                            this.formErrors.academic_year_id = 'Tahun Ajaran wajib dipilih.';
                            isValid = false;
                        }
                        if (!this.formData.jenjang_id) {
                            this.formErrors.jenjang_id = 'Jenjang Pendidikan wajib dipilih.';
                            isValid = false;
                        }
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
                    } else if (step === 'tab2_domisili') {
                        // Optional step, always passes basic validation
                        isValid = true;
                    } else if (step === 'tab3_keluarga') {
                        if (!this.formData.parent_phone || !this.formData.parent_phone.trim()) {
                            this.formErrors.parent_phone = 'Nomor WhatsApp utama kontak wajib diisi.';
                            isValid = false;
                        }
                    } else if (step === 'tab4_fisik') {
                        // Optional numeric sanity checks if filled
                        if (this.formData.height && isNaN(this.formData.height)) {
                            this.formErrors.height = 'Tinggi badan harus angka.';
                            isValid = false;
                        }
                        if (this.formData.weight && isNaN(this.formData.weight)) {
                            this.formErrors.weight = 'Berat badan harus angka.';
                            isValid = false;
                        }
                    } else if (step === 'tab5_tumbuhkembang') {
                        isValid = true;
                    }
                    return isValid;
                },

                validateAll() {
                    this.formErrors = {};

                    // Step 1 Check
                    if (!this.formData.academic_year_id) {
                        this.formErrors.academic_year_id = 'Tahun Ajaran wajib dipilih.';
                    }
                    if (!this.formData.jenjang_id) {
                        this.formErrors.jenjang_id = 'Jenjang Pendidikan wajib dipilih.';
                    }
                    if (!this.formData.full_name || !this.formData.full_name.trim()) {
                        this.formErrors.full_name = 'Nama lengkap ananda wajib diisi.';
                    }
                    if (!this.formData.gender) {
                        this.formErrors.gender = 'Jenis kelamin wajib dipilih.';
                    }
                    if (!this.formData.status) {
                        this.formErrors.status = 'Status murid wajib dipilih.';
                    }
                    if (this.hasTabError('tab1_identitas')) {
                        this.formTab = 'tab1_identitas';
                        if (typeof window.showToast === 'function') {
                            window.showToast('Validasi Identitas', 'Lengkapi identitas ananda dan penempatan akademik.', 'warning');
                        }
                        return false;
                    }

                    // Step 3 Check (WA kontak)
                    if (!this.formData.parent_phone || !this.formData.parent_phone.trim()) {
                        this.formErrors.parent_phone = 'Nomor WhatsApp utama kontak wajib diisi.';
                        this.formTab = 'tab3_keluarga';
                        if (typeof window.showToast === 'function') {
                            window.showToast('Validasi Kontak', 'Nomor WhatsApp Utama Kontak wajib diisi untuk notifikasi rapor.', 'warning');
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

                    const steps = ['tab1_identitas', 'tab2_domisili', 'tab3_keluarga', 'tab4_fisik', 'tab5_tumbuhkembang'];
                    const currentIndex = steps.indexOf(this.formTab);
                    if (currentIndex >= 0 && currentIndex < steps.length - 1) {
                        this.formTab = steps[currentIndex + 1];
                    }
                },

                prevStep() {
                    const steps = ['tab1_identitas', 'tab2_domisili', 'tab3_keluarga', 'tab4_fisik', 'tab5_tumbuhkembang'];
                    const currentIndex = steps.indexOf(this.formTab);
                    if (currentIndex > 0) {
                        this.formTab = steps[currentIndex - 1];
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
                    this.formTab = 'tab1_identitas';
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
                        no_kk: '',
                        no_akta: '',
                        full_name: '',
                        nickname: '',
                        gender: 'L',
                        birth_place: '',
                        birth_date: '',
                        religion: 'Islam',
                        citizenship: 'WNI',
                        status: 'aktif',
                        address: '',
                        rt: '',
                        rw: '',
                        kelurahan: '',
                        kecamatan: '',
                        city: 'Malang',
                        postal_code: '',
                        living_with: 'Bersama Orang Tua',
                        transportation: 'Sepeda Motor',
                        distance_to_school: '',
                        travel_time_minutes: '',
                        surroundings: 'Perumahan',
                        near_mosque: 'Ya',
                        external_tpq: 'Tidak',
                        parent_phone: '',
                        father_name: '',
                        father_title: '',
                        father_nik: '',
                        father_birth: '',
                        father_education: '',
                        father_job: '',
                        father_workplace: '',
                        father_phone: '',
                        mother_name: '',
                        mother_title: '',
                        mother_nik: '',
                        mother_birth: '',
                        mother_education: '',
                        mother_job: '',
                        mother_workplace: '',
                        mother_phone: '',
                        has_guardian: false,
                        guardian_name: '',
                        guardian_relation: '',
                        guardian_nik: '',
                        guardian_education: '',
                        guardian_job: '',
                        guardian_phone: '',
                        height: '',
                        weight: '',
                        head_circumference: '',
                        arm_circumference: '',
                        blood_type: '',
                        child_order: 1,
                        siblings_count: 0,
                        special_needs: '',
                        breakfast_pattern: 'Teratur',
                        lunch_pattern: 'Teratur',
                        dinner_pattern: 'Teratur',
                        favorite_food: '',
                        avoided_food: '',
                        allergy_details: '',
                        allergy_treatment: '',
                        eating_manner: 'Mandiri dan duduk tertib',
                        brush_teeth: 'Mandiri',
                        toilet_training: 'Mandiri (Bisa BAK/BAB sendiri)',
                        screen_time_daily: 'Kurang dari 1 jam',
                        bedtime_pattern: 'Pukul 20:30',
                        wake_time_pattern: 'Pukul 05:00',
                        gestational_age: 'Cukup Bulan (Aterm)',
                        birth_type: 'Normal',
                        birth_weight: '',
                        birth_length: '',
                        exclusive_breastfeeding: 'Ya (ASI Eksklusif 6 Bulan)',
                        mpasi_start_age: '6 Bulan',
                        walking_age_months: '',
                        talking_age_months: '',
                        teeth_age_months: '',
                        official_pickup_person: '',
                        emergency_contact: '',
                        pin_access: '1234',
                        early_detection_consent: true,
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
                    this.formTab = 'tab1_identitas';
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
                            const fd = s.family_data || {};
                            const hh = s.health_habits || {};
                            const dp = s.development_psychology || {};

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
                                
                                // Tab 1: Identitas
                                nis: s.nis || '',
                                nisn: s.nisn || '',
                                nik: s.nik || '',
                                no_kk: s.no_kk || '',
                                no_akta: s.no_akta || '',
                                full_name: s.full_name || '',
                                nickname: s.nickname || '',
                                gender: s.gender || 'L',
                                birth_place: s.birth_place || '',
                                birth_date: s.birth_date ? s.birth_date.substring(0, 10) : '',
                                religion: s.religion || 'Islam',
                                citizenship: s.citizenship || 'WNI',
                                status: s.status || 'aktif',
                                
                                // Tab 2: Domisili
                                address: s.address || '',
                                rt: s.rt || '',
                                rw: s.rw || '',
                                kelurahan: s.kelurahan || '',
                                kecamatan: s.kecamatan || '',
                                city: s.city || 'Malang',
                                postal_code: s.postal_code || '',
                                living_with: s.living_with || 'Bersama Orang Tua',
                                transportation: s.transportation || 'Sepeda Motor',
                                distance_to_school: s.distance_to_school || '',
                                travel_time_minutes: s.travel_time_minutes || '',
                                surroundings: hh.surroundings || 'Perumahan',
                                near_mosque: hh.near_mosque || 'Ya',
                                external_tpq: hh.external_tpq || 'Tidak',
                                
                                // Tab 3: Keluarga & Kontak
                                parent_phone: s.parent_phone || '',
                                father_name: s.father_name || fd.father?.name || '',
                                father_title: fd.father?.title || '',
                                father_nik: fd.father?.nik || '',
                                father_birth: fd.father?.birth || '',
                                father_education: fd.father?.education || '',
                                father_job: s.father_job || fd.father?.job || '',
                                father_workplace: fd.father?.workplace || '',
                                father_phone: s.father_phone || fd.father?.phone || '',
                                
                                mother_name: s.mother_name || fd.mother?.name || '',
                                mother_title: fd.mother?.title || '',
                                mother_nik: fd.mother?.nik || '',
                                mother_birth: fd.mother?.birth || '',
                                mother_education: fd.mother?.education || '',
                                mother_job: s.mother_job || fd.mother?.job || '',
                                mother_workplace: fd.mother?.workplace || '',
                                mother_phone: s.mother_phone || fd.mother?.phone || '',
                                
                                has_guardian: !!(s.guardian_name || fd.guardian?.name),
                                guardian_name: s.guardian_name || fd.guardian?.name || '',
                                guardian_relation: fd.guardian?.relation || '',
                                guardian_nik: fd.guardian?.nik || '',
                                guardian_education: fd.guardian?.education || '',
                                guardian_job: fd.guardian?.job || '',
                                guardian_phone: s.guardian_phone || fd.guardian?.phone || '',
                                
                                // Tab 4: Fisik & Kebiasaan PAUD
                                height: s.height || '',
                                weight: s.weight || '',
                                head_circumference: s.head_circumference || '',
                                arm_circumference: s.arm_circumference || '',
                                blood_type: s.blood_type || '',
                                child_order: s.child_order || 1,
                                siblings_count: s.siblings_count || 0,
                                special_needs: s.special_needs || '',
                                breakfast_pattern: hh.meals?.breakfast || 'Teratur',
                                lunch_pattern: hh.meals?.lunch || 'Teratur',
                                dinner_pattern: hh.meals?.dinner || 'Teratur',
                                favorite_food: hh.meals?.favorite || '',
                                avoided_food: hh.meals?.avoided || '',
                                allergy_details: hh.allergy?.details || '',
                                allergy_treatment: hh.allergy?.treatment || '',
                                eating_manner: hh.eating_manner || 'Mandiri dan duduk tertib',
                                brush_teeth: hh.brush_teeth || 'Mandiri',
                                toilet_training: hh.toilet_training || 'Mandiri (Bisa BAK/BAB sendiri)',
                                
                                // Tab 5: Tumbuh Kembang & Tambahan
                                screen_time_daily: dp.screen_time_daily || 'Kurang dari 1 jam',
                                bedtime_pattern: dp.bedtime || 'Pukul 20:30',
                                wake_time_pattern: dp.wake_time || 'Pukul 05:00',
                                gestational_age: dp.birth_history?.gestational_age || 'Cukup Bulan (Aterm)',
                                birth_type: dp.birth_history?.birth_type || 'Normal',
                                birth_weight: dp.birth_history?.birth_weight || '',
                                birth_length: dp.birth_history?.birth_length || '',
                                exclusive_breastfeeding: dp.birth_history?.exclusive_breastfeeding || 'Ya (ASI Eksklusif 6 Bulan)',
                                mpasi_start_age: dp.birth_history?.mpasi_start_age || '6 Bulan',
                                walking_age_months: dp.milestones?.walking_age || '',
                                talking_age_months: dp.milestones?.talking_age || '',
                                teeth_age_months: dp.milestones?.teeth_age || '',
                                official_pickup_person: dp.official_pickup_person || '',
                                emergency_contact: dp.emergency_contact || '',
                                pin_access: s.pin_access || '1234',
                                early_detection_consent: dp.early_detection_consent !== false,
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

                    // Bundle payload including structured JSON objects
                    const payload = {
                        ...this.formData,
                        family_data: {
                            father: {
                                name: this.formData.father_name || null,
                                title: this.formData.father_title || null,
                                nik: this.formData.father_nik || null,
                                birth: this.formData.father_birth || null,
                                education: this.formData.father_education || null,
                                job: this.formData.father_job || null,
                                workplace: this.formData.father_workplace || null,
                                phone: this.formData.father_phone || null,
                            },
                            mother: {
                                name: this.formData.mother_name || null,
                                title: this.formData.mother_title || null,
                                nik: this.formData.mother_nik || null,
                                birth: this.formData.mother_birth || null,
                                education: this.formData.mother_education || null,
                                job: this.formData.mother_job || null,
                                workplace: this.formData.mother_workplace || null,
                                phone: this.formData.mother_phone || null,
                            },
                            guardian: this.formData.has_guardian ? {
                                name: this.formData.guardian_name || null,
                                relation: this.formData.guardian_relation || null,
                                nik: this.formData.guardian_nik || null,
                                education: this.formData.guardian_education || null,
                                job: this.formData.guardian_job || null,
                                phone: this.formData.guardian_phone || null,
                            } : null,
                        },
                        health_habits: {
                            surroundings: this.formData.surroundings || null,
                            near_mosque: this.formData.near_mosque || null,
                            external_tpq: this.formData.external_tpq || null,
                            meals: {
                                breakfast: this.formData.breakfast_pattern || null,
                                lunch: this.formData.lunch_pattern || null,
                                dinner: this.formData.dinner_pattern || null,
                                favorite: this.formData.favorite_food || null,
                                avoided: this.formData.avoided_food || null,
                            },
                            allergy: {
                                details: this.formData.allergy_details || null,
                                treatment: this.formData.allergy_treatment || null,
                            },
                            eating_manner: this.formData.eating_manner || null,
                            brush_teeth: this.formData.brush_teeth || null,
                            toilet_training: this.formData.toilet_training || null,
                        },
                        development_psychology: {
                            screen_time_daily: this.formData.screen_time_daily || null,
                            bedtime: this.formData.bedtime_pattern || null,
                            wake_time: this.formData.wake_time_pattern || null,
                            birth_history: {
                                gestational_age: this.formData.gestational_age || null,
                                birth_type: this.formData.birth_type || null,
                                birth_weight: this.formData.birth_weight || null,
                                birth_length: this.formData.birth_length || null,
                                exclusive_breastfeeding: this.formData.exclusive_breastfeeding || null,
                                mpasi_start_age: this.formData.mpasi_start_age || null,
                            },
                            milestones: {
                                walking_age: this.formData.walking_age_months || null,
                                talking_age: this.formData.talking_age_months || null,
                                teeth_age: this.formData.teeth_age_months || null,
                            },
                            official_pickup_person: this.formData.official_pickup_person || null,
                            emergency_contact: this.formData.emergency_contact || null,
                            early_detection_consent: !!this.formData.early_detection_consent,
                        }
                    };

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
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
                                if (data.errors.academic_year_id || data.errors.jenjang_id || data.errors.classroom_id || data.errors.class_level_id || data.errors.full_name || data.errors.nis || data.errors.nisn || data.errors.nik || data.errors.gender || data.errors.status) {
                                    this.formTab = 'tab1_identitas';
                                } else if (data.errors.address || data.errors.rt || data.errors.rw || data.errors.city) {
                                    this.formTab = 'tab2_domisili';
                                } else if (data.errors.parent_phone || data.errors.father_phone || data.errors.mother_phone) {
                                    this.formTab = 'tab3_keluarga';
                                } else if (data.errors.height || data.errors.weight || data.errors.blood_type) {
                                    this.formTab = 'tab4_fisik';
                                } else {
                                    this.formTab = 'tab5_tumbuhkembang';
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
