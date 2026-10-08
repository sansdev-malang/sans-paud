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

                <!-- Ekspor Excel Button -->
                <a href="{{ route('students.export.excel', request()->query()) }}"
                    class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    Ekspor Excel
                </a>

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
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Stat Card 1: Total Murid Aktif -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 lg:p-4 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Murid Aktif</p>
                        <h3 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total_active']) }}
                        </h3>
                    </div>
                    <div class="p-1.5 sm:p-2 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="users" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                </div>
                <div class="mt-2.5 sm:mt-3 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400">
                    Total terdaftar: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($stats['total_all']) }}</span> anak
                </div>
            </div>

            <!-- Stat Card 2: Laki-laki -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 lg:p-4 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Laki-laki (Putra)</p>
                        <h3 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['male']) }}
                        </h3>
                    </div>
                    <div class="p-1.5 sm:p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="user" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                </div>
                <div class="mt-2.5 sm:mt-3 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400">
                    Murid aktif putra
                </div>
            </div>

            <!-- Stat Card 3: Perempuan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 lg:p-4 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Perempuan (Putri)</p>
                        <h3 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['female']) }}
                        </h3>
                    </div>
                    <div class="p-1.5 sm:p-2 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl border border-rose-100 dark:border-rose-900/50">
                        <i data-lucide="user-check" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                </div>
                <div class="mt-2.5 sm:mt-3 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400">
                    Murid aktif putri
                </div>
            </div>

            <!-- Stat Card 4: Total Kelompok Belajar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 lg:p-4 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kelompok Belajar</p>
                        <h3 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['classrooms']) }}
                        </h3>
                    </div>
                    <div class="p-1.5 sm:p-2 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-xl border border-purple-100 dark:border-purple-900/50">
                        <i data-lucide="shapes" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                </div>
                <div class="mt-2.5 sm:mt-3 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400">
                    Rombel aktif jenjang ini
                </div>
            </div>
        </section>

        <!-- FILTER JENJANG TABS (DINAMIS DARI DATA JENJANG) -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            @foreach($jenjangs as $j)
                <a href="{{ route('students.index', array_merge(request()->except('jenjang_id', 'class_level_id', 'classroom_id'), ['jenjang_id' => $j->id])) }}"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer inline-flex items-center gap-1.5 {{ $selectedJenjangId == $j->id ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
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
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $selectedJenjangId == $j->id ? 'bg-indigo-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                        {{ $j->students_count ?? 0 }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- SEARCH & FILTERS -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs w-full">
            <form method="GET" action="{{ route('students.index') }}" class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
                <input type="hidden" name="jenjang_id" value="{{ $selectedJenjangId }}">

                <!-- Search Box -->
                <div class="relative w-full lg:max-w-xs">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 dark:text-slate-500"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama ananda, NIS, No. Ortu..."
                        style="padding-left: 2.25rem;"
                        class="w-full h-9 pr-4 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 placeholder-slate-400 dark:placeholder-slate-500 transition-colors">
                </div>

                <!-- Filter Select Toolbar -->
                <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                    <!-- Filter 1: Tahun Ajaran -->
                    <div>
                        <select name="academic_year_id" onchange="this.form.submit()"
                            class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                            <option value="all" {{ $selectedYearId === 'all' ? 'selected' : '' }}>-- Semua Tahun Ajaran --</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                                    TA {{ $year->name }} {{ $year->is_active ? '★ (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter 2: Kelas (Sesuai Jenjang Aktif) -->
                    <div>
                        <select name="class_level_id" id="filter_class_level_id" onchange="handleClassLevelFilterChange(this)"
                            class="h-9 px-3 text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                            <option value="all">Semua Kelas</option>
                            @foreach($classLevels as $lvl)
                                <option value="{{ $lvl->id }}" {{ ($selectedClassLevelId ?? request('class_level_id')) == $lvl->id ? 'selected' : '' }}>
                                    Kelas {{ $lvl->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter 3: Kelompok / Rombel -->
                    <div>
                        <select name="classroom_id" id="filter_classroom_id" onchange="this.form.submit()"
                            class="h-9 px-3 text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                            <option value="all">Semua Kelompok</option>
                            @foreach($classrooms as $rombel)
                                <option value="{{ $rombel->id }}" {{ ($selectedClassroomId ?? request('classroom_id')) == $rombel->id ? 'selected' : '' }}>
                                    {{ $rombel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter 4: Status Murid -->
                    <div>
                        <select name="status" onchange="this.form.submit()"
                            class="h-9 px-3 text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                            <option value="all" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="aktif" {{ $selectedStatus === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="lulus" {{ $selectedStatus === 'lulus' ? 'selected' : '' }}>Lulus</option>
                            <option value="mutasi" {{ $selectedStatus === 'mutasi' ? 'selected' : '' }}>Mutasi</option>
                            <option value="keluar" {{ $selectedStatus === 'keluar' ? 'selected' : '' }}>Keluar</option>
                            <option value="nonaktif" {{ $selectedStatus === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>

                    <!-- Filter 5: Gender -->
                    <div>
                        <select name="gender" onchange="this.form.submit()"
                            class="h-9 px-3 text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer">
                            <option value="all" {{ $selectedGender === 'all' ? 'selected' : '' }}>Semua Gender</option>
                            <option value="L" {{ $selectedGender === 'L' ? 'selected' : '' }}>Putra (L)</option>
                            <option value="P" {{ $selectedGender === 'P' ? 'selected' : '' }}>Putri (P)</option>
                        </select>
                    </div>

                    @if(request()->hasAny(['search', 'academic_year_id', 'class_level_id', 'classroom_id', 'status', 'gender']))
                        <a href="{{ route('students.index', ['jenjang_id' => $selectedJenjangId]) }}" 
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
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Tahun Ajaran</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Kelas</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-40">Rombel</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Murid</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($students as $index => $s)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                <!-- 1. Tahun Ajaran -->
                                <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-slate-100">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $s->academicYear?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 2. Jenjang -->
                                <td class="px-5 py-3.5">
                                    @php
                                        $jenjangName = $s->jenjang?->name ?? $s->classroom?->jenjang?->name ?? $s->classLevel?->jenjang?->name;
                                    @endphp
                                    @if($jenjangName)
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            {{ $jenjangName }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- 3. Kelas -->
                                <td class="px-5 py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $s->classLevel?->name ?? $s->classroom?->classLevel?->name ?? '-' }}
                                </td>

                                <!-- 4. Rombel -->
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $s->classroom?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 5. Nama Murid & NIS -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                            {{ $s->avatar_initials }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition-colors" @click="openDetailModal({{ $s->id }})">
                                                {{ $s->full_name }}
                                            </span>
                                            <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                                <span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">NIS: {{ $s->nis }}</span>
                                                <span>&bull;</span>
                                                <span>{{ $s->formatted_gender }}</span>
                                                @if($s->nickname)
                                                    <span>&bull;</span>
                                                    <span>({{ $s->nickname }})</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Status -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($s->status === 'aktif')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
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
                                    Penempatan Akademik
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                        <select x-model="formData.academic_year_id" required
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                                            @foreach($academicYears as $year)
                                                <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
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
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelas</label>
                                        <select x-model="formData.class_level_id" @change="onClassLevelChange()"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                            <option value="">-- Pilih Kelas --</option>
                                            <template x-for="lvl in filteredClassLevels" :key="lvl.id">
                                                <option :value="lvl.id" x-text="lvl.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Rombel / Kelompok</label>
                                        <select x-model="formData.classroom_id"
                                            class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                            <option value="">-- Pilih Rombel --</option>
                                            <template x-for="rombel in filteredClassrooms" :key="rombel.id">
                                                <option :value="rombel.id" x-text="rombel.name"></option>
                                            </template>
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
        function handleClassLevelFilterChange(selectElem) {
            const classroomSelect = document.getElementById('filter_classroom_id');
            if (classroomSelect) {
                classroomSelect.value = 'all';
            }
            selectElem.form.submit();
        }

        function studentApp() {
            const allLevels = @json($allClassLevels);
            const allClasses = @json($allClassrooms);

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
                    jenjang_id: '{{ $selectedJenjangId }}',
                    class_level_id: '',
                    classroom_id: '',
                    sub_unit: 'TK',
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

                get filteredClassLevels() {
                    if (!this.formData.jenjang_id) return allLevels;
                    return allLevels.filter(lvl => lvl.jenjang_id == this.formData.jenjang_id);
                },

                get filteredClassrooms() {
                    let res = allClasses;
                    if (this.formData.jenjang_id) {
                        res = res.filter(c => c.jenjang_id == this.formData.jenjang_id || (c.class_level && c.class_level.jenjang_id == this.formData.jenjang_id));
                    }
                    if (this.formData.class_level_id) {
                        res = res.filter(c => c.class_level_id == this.formData.class_level_id);
                    }
                    return res;
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

                copyText(text) {
                    navigator.clipboard.writeText(text);
                    if (typeof window.showToast === 'function') {
                        window.showToast('Sukses!', 'Teks berhasil disalin ke papan klip.', 'success');
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
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal memuat detail murid: " + err.message, 'error');
                        }
                    });
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formTab = 'program';
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
                    this.onJenjangChange();
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
                                jenjang_id: s.jenjang_id || (s.classroom ? s.classroom.jenjang_id : ''),
                                class_level_id: s.class_level_id || (s.classroom ? s.classroom.class_level_id : ''),
                                classroom_id: s.classroom_id || '',
                                sub_unit: s.sub_unit || 'TK',
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
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal mengambil data murid: " + err.message, 'error');
                        }
                    });
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
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(res.message || 'Data murid berhasil disimpan!', 'success');
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
