<x-admin-layout>
    <div class="p-6 space-y-6" x-data="{ searchQuery: '' }">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Data Akademik</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Matriks data akademik per tahun pelajaran, jenjang, kelas, rombel, jumlah murid dan penugasan wali kelas.</p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('academic-summary.export.excel', request()->query()) }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    Ekspor Excel
                </a>
                <a href="{{ route('academic-summary.print', request()->query()) }}" target="_blank"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    Cetak Rekap
                </a>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Tahun Pelajaran Aktif -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tahun Pelajaran</p>
                        <h3 class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ $selectedYear ? $selectedYear->name : '-' }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Periode akademik yang aktif / dipilih
                </div>
            </div>

            <!-- Stat 2: Total Murid Terdaftar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Murid Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ number_format($stats['total_students']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Siswa terdaftar di seluruh rombel
                </div>
            </div>

            <!-- Stat 3: Total Rombel / Kelompok -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Rombel</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total_classrooms']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Rombongan / kelompok belajar aktif
                </div>
            </div>

            <!-- Stat 4: Wali Kelas Ditugaskan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Wali Kelas Ditugaskan</p>
                        <h3 class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($stats['total_assigned_teachers']) }} / {{ $stats['total_classrooms'] }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Rata-rata: <strong class="text-slate-700 dark:text-slate-300">{{ $stats['avg_students'] }}</strong> murid/rombel
                </div>
            </div>
        </section>

        <!-- FILTER JENJANG TABS (DINAMIS DARI RELASI JENJANG - TANPA SEMUA JENJANG) -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            @foreach($jenjangs as $j)
                <a href="{{ route('academic-summary.index', array_merge(request()->query(), ['jenjang_id' => $j->id])) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-150 inline-flex items-center gap-2 cursor-pointer {{ $selectedJenjangId == $j->id ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                    @if($j->code === 'KB' || $j->code === 'PG')
                        <span>🧸</span>
                    @elseif($j->code === 'TK')
                        <span>🎒</span>
                    @elseif($j->code === 'DAYCARE' || $j->code === 'TPA')
                        <span>👶</span>
                    @elseif($j->code === 'TPQ')
                        <span>📖</span>
                    @endif
                    <span>{{ $j->name }}</span>
                </a>
            @endforeach
        </div>

        <!-- FILTERS BAR -->
        <form method="GET" action="{{ route('academic-summary.index') }}" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <input type="hidden" name="jenjang_id" value="{{ $selectedJenjangId }}">
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <!-- Filter 1: Tahun Pelajaran -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Tahun Pelajaran</label>
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold cursor-pointer">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter 2: Kelas -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Kelas</label>
                    <select name="class_level_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                        <option value="all" {{ empty($selectedClassLevelId) || $selectedClassLevelId === 'all' ? 'selected' : '' }}>Semua Kelas</option>
                        @foreach($classLevels as $lvl)
                            <option value="{{ $lvl->id }}" {{ $selectedClassLevelId == $lvl->id ? 'selected' : '' }}>
                                [{{ $lvl->jenjang?->name ?? 'PAUD' }}] {{ $lvl->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Search box -->
            <div class="w-full md:w-64 relative mt-2 md:mt-0">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Pencarian</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400"></i>
                    </span>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama rombel/kelompok..."
                        style="padding-left: 2.25rem;"
                        class="w-full h-9 pr-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                </div>
            </div>
        </form>

        <!-- TABLE DATA AKADEMIK -->
        <!-- Kolom: tahun pelajaran, jenjang, kelas, rombel, jumlah murid, wali kelas -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="table" class="w-4 h-4 text-indigo-600"></i>
                    Matriks Data Akademik
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-40">Tahun Pelajaran</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Kelas</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rombel</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Jumlah Murid</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Wali Kelas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($academicRows as $r)
                            <tr x-show="searchQuery === '' || '{{ strtolower($r['classroom_name'] . ' ' . $r['class_level_name'] . ' ' . $r['homeroom_teacher_name']) }}'.includes(searchQuery.toLowerCase())"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                
                                <!-- 1. Tahun Pelajaran -->
                                <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-slate-100">
                                    <span class="inline-flex items-center gap-1.5 font-bold">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $r['academic_year_name'] }}
                                    </span>
                                </td>

                                <!-- 2. Jenjang -->
                                <td class="px-5 py-3.5">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                        {{ $r['jenjang_name'] }}
                                    </span>
                                </td>

                                <!-- 3. Kelas -->
                                <td class="px-5 py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $r['class_level_name'] }}
                                </td>

                                <!-- 4. Rombel -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs shrink-0">
                                            <i data-lucide="shapes" class="w-3.5 h-3.5 text-indigo-600"></i>
                                        </div>
                                        <span class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $r['classroom_name'] }}
                                        </span>
                                    </div>
                                </td>

                                <!-- 5. Jumlah Murid -->
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold {{ $r['student_count'] > 0 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                        <span>{{ $r['student_count'] }} Murid</span>
                                    </span>
                                </td>

                                <!-- 6. Wali Kelas -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        @if($r['teacher_photo'])
                                            <img src="{{ asset('storage/' . $r['teacher_photo']) }}" alt="{{ $r['homeroom_teacher_name'] }}" class="w-7 h-7 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ substr($r['homeroom_teacher_name'], 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-slate-100 leading-tight">
                                                {{ $r['homeroom_teacher_name'] }}
                                            </p>
                                            @if($r['teacher_phone'])
                                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $r['teacher_phone'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    Belum ada data rombel / kelompok belajar pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/60 font-bold">
                        <tr>
                            <td colspan="4" class="px-5 py-3.5 text-right uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Total Keseluruhan:
                            </td>
                            <td class="px-5 py-3.5 text-center text-indigo-600 dark:text-indigo-400 text-sm">
                                {{ number_format($stats['total_students']) }} Murid
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 dark:text-slate-300">
                                {{ $stats['total_assigned_teachers'] }} Wali Kelas Terplot
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

    </div>
</x-admin-layout>
