<x-admin-layout>
    <div class="p-4 sm:p-5 lg:p-6 space-y-4 lg:space-y-5" x-data="{ exportDropdownOpen: false, activeReportTab: 'rekap', activeJenjangFilter: {{ $selectedJenjangId ?: 0 }}, teacherRoleFilter: 'all', teacherSearch: '' }">
        
        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">
                    Rekapitulasi Rombel
                </h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Laporan komprehensif distribusi peserta didik, rasio gender, PDBK, dan penugasan wali kelas {{ function_exists('setting') ? setting('unit_name', 'PAUD Terpadu Anak Saleh') : 'PAUD Terpadu Anak Saleh' }}.</p>
            </div>

            <!-- ACTION CONTROLS & FILTER -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Filter Tahun Ajaran -->
                <form action="{{ route('academic-summary.index') }}" method="GET" class="m-0 p-0 flex items-center">
                    <input type="hidden" name="jenjang_id" :value="activeJenjangFilter">
                    <div class="relative flex items-center">
                        <select name="academic_year_id" onchange="this.form.submit()"
                            class="h-9 pl-8 pr-8 text-xs font-semibold rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 shadow-xs cursor-pointer appearance-none transition-colors">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                    TA {{ $ay->name }} {{ $ay->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </form>

                <!-- DROPDOWN EKSPOR & CETAK -->
                <div class="relative flex items-center" @click.outside="exportDropdownOpen = false">
                    <button type="button" @click="exportDropdownOpen = !exportDropdownOpen"
                        class="h-9 inline-flex items-center justify-center gap-1.5 px-3.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all cursor-pointer">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                        <span>Ekspor & Cetak</span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': exportDropdownOpen }"></i>
                    </button>

                    <div x-show="exportDropdownOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        x-cloak
                        class="absolute right-0 top-full mt-1.5 w-60 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1.5 z-50 divide-y divide-slate-100 dark:divide-slate-800/60">
                        
                        <div class="px-3 py-1.5 bg-slate-50/50 dark:bg-slate-800/30">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Pilihan Format Dokumen</p>
                        </div>

                        <div class="py-1">
                            <!-- Ekspor Excel -->
                            <a href="{{ route('academic-summary.export.excel', ['academic_year_id' => $selectedYearId, 'jenjang_id' => $selectedJenjangId]) }}" @click="exportDropdownOpen = false"
                                class="flex items-center gap-2.5 px-3 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors group">
                                <div class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60 group-hover:scale-105 transition-transform">
                                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1">
                                    <span class="font-semibold block text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Ekspor File Excel</span>
                                    <span class="text-[10px] text-slate-400 block">Format .xlsx Rekapitulasi TU</span>
                                </div>
                            </a>
                        </div>

                        <div class="py-1">
                            <!-- Cetak / Print Browser -->
                            <a href="{{ route('academic-summary.print', ['academic_year_id' => $selectedYearId, 'jenjang_id' => $selectedJenjangId]) }}" target="_blank" @click="exportDropdownOpen = false"
                                class="flex items-center gap-2.5 px-3 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:text-indigo-700 dark:hover:text-indigo-400 transition-colors group">
                                <div class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60 group-hover:scale-105 transition-transform">
                                    <i data-lucide="printer" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1">
                                    <span class="font-semibold block text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Cetak Laporan Resmi</span>
                                    <span class="text-[10px] text-slate-400 block">Preview kop surat & tanda tangan</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NAVIGATION TABS -->
        <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto text-xs font-semibold">
            <!-- TAB 1 -->
            <button type="button" @click="activeReportTab = 'rekap'"
                :class="activeReportTab === 'rekap' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 border-transparent'"
                class="px-3.5 py-1.5 rounded-lg border transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <i data-lucide="file-bar-chart-2" class="w-3.5 h-3.5" :class="activeReportTab === 'rekap' ? 'text-indigo-600 dark:text-indigo-400' : ''"></i>
                <span>Rekapitulasi Distribusi Murid & Kelompok</span>
            </button>

            <!-- TAB 2 (Formasi Pendidik) -->
            <button type="button" @click="activeReportTab = 'roster'"
                :class="activeReportTab === 'roster' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 border-transparent'"
                class="px-3.5 py-1.5 rounded-lg border transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <i data-lucide="users-2" class="w-3.5 h-3.5" :class="activeReportTab === 'roster' ? 'text-indigo-600 dark:text-indigo-400' : ''"></i>
                <span>Formasi & Penugasan Pendidik</span>
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: REKAPITULASI DISTRIBUSI MURID & KELOMPOK -->
        <!-- ========================================================================= -->
        <div x-show="activeReportTab === 'rekap'" class="space-y-4 lg:space-y-5">
            <!-- 5 KPI SUMMARY CARDS -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3 lg:gap-3.5">
                <!-- 1. Total Murid -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Murid</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 font-mono">{{ $grandTotalStudents }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">Anak</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Laki-laki -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Putra (L)</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-blue-700 dark:text-blue-300 font-mono">{{ $grandTotalMale }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">({{ $malePercent }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Perempuan -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-pink-50 dark:bg-pink-950/50 text-pink-600 dark:text-pink-400 flex items-center justify-center shrink-0 border border-pink-100 dark:border-pink-900/40">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Putri (P)</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-pink-700 dark:text-pink-300 font-mono">{{ $grandTotalFemale }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">({{ $femalePercent }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Inklusi / PDBK -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 border border-purple-100 dark:border-purple-900/40">
                        <i data-lucide="heart-handshake" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Inklusi (PDBK)</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-purple-700 dark:text-purple-300 font-mono">{{ $grandTotalPdbk }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">({{ $pdbkPercent }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Total Kelompok -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors col-span-2 sm:col-span-1">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Kelompok</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 font-mono">{{ $grandTotalClassrooms }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">Rombel</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UNASSIGNED STUDENTS ALERT (IF ANY) -->
            @if(!empty($unassignedStudents) && $unassignedTotal > 0)
                <div class="p-3.5 sm:p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-start gap-3 shadow-xs">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 text-xs text-amber-900 dark:text-amber-200">
                        <div class="font-bold text-sm text-amber-800 dark:text-amber-100 mb-0.5 flex flex-wrap items-center gap-2">
                            <span>Perhatian: Terdapat {{ $unassignedTotal }} Murid Aktif Belum Memiliki Kelompok Belajar</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-200 dark:bg-amber-900 text-amber-900 dark:text-amber-100">
                                Perlu Penempatan Rombel
                            </span>
                        </div>
                        <p class="text-amber-700 dark:text-amber-300">
                            Murid berstatus aktif di bawah ini belum dimasukkan ke kelompok belajar manapun. Murid tetap dihitung dalam total rekapitulasi ({{ $grandTotalStudents }} Murid) dan ditampilkan di baris bawah tabel agar Admin dapat segera menentukan kelompoknya.
                        </p>
                    </div>
                </div>
            @endif

            <!-- FILTER JENJANG TABS (BERSIH & KONSISTEN TANPA EMOJI / BADGE ANGKA) -->
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <button type="button" @click="activeJenjangFilter = 0"
                    :class="activeJenjangFilter == 0 ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                    <span>Semua Jenjang</span>
                </button>
                @foreach($jenjangs as $j)
                    <button type="button" @click="activeJenjangFilter = {{ $j->id }}"
                        :class="activeJenjangFilter == {{ $j->id }} ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                        <span>{{ $j->name }}</span>
                    </button>
                @endforeach
            </div>

            <!-- TABEL REKAPITULASI BERSTRUKTUR PER TINGKAT KELAS -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="table" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                            Tabel Distribusi Murid & Wali Kelas per Kelompok Belajar
                        </h3>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                        TA {{ $selectedYear ? $selectedYear->name : '-' }}
                    </span>
                </div>

                <div class="overflow-x-auto relative">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-100/90 dark:bg-slate-800/75 text-slate-700 dark:text-slate-200">
                                <th class="px-2.5 py-3 text-center text-[11px] font-bold uppercase tracking-wider w-12 sticky left-0 z-20 bg-slate-100 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700">No</th>
                                <th class="px-3.5 py-3 text-left text-[11px] font-bold uppercase tracking-wider min-w-[160px] sticky left-12 z-20 bg-slate-100 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.08)]">Nama Kelompok</th>
                                <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wider w-24 text-blue-600 dark:text-blue-400">Laki-Laki</th>
                                <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wider w-24 text-pink-600 dark:text-pink-400">Perempuan</th>
                                <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wider w-28 text-slate-900 dark:text-slate-100">Jml Murid</th>
                                <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wider w-28 text-purple-600 dark:text-purple-400">Inklusi (PDBK)</th>
                                <th class="px-3.5 py-3 text-left text-[11px] font-bold uppercase tracking-wider min-w-[200px]">Wali Kelas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @php $rowCounter = 1; @endphp
                            @forelse($levelReports as $lvl)
                                <!-- LEVEL SECTION HEADER -->
                                <tr x-show="activeJenjangFilter == 0 || activeJenjangFilter == {{ $lvl['level']->jenjang_id ?: '0' }}"
                                    class="bg-slate-50 dark:bg-slate-800/50 border-t-2 border-b border-slate-200 dark:border-slate-700/80">
                                    <td colspan="7" class="px-3.5 py-2.5">
                                        <div class="flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                                <span class="font-bold uppercase tracking-wide text-slate-800 dark:text-slate-200">
                                                    {{ $lvl['level']->name }}
                                                </span>
                                                <span class="text-[11px] text-slate-400 font-normal">
                                                    ({{ $lvl['level']->jenjang?->name ?? 'PAUD' }})
                                                </span>
                                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                                                    &bull; {{ count($lvl['classrooms']) }} Kelompok
                                                </span>
                                            </div>
                                            @if(count($lvl['classrooms']) > 0)
                                                <span class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 font-mono">
                                                    Subtotal: {{ $lvl['subtotal_students'] }} Murid
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <!-- CLASSROOM ROWS -->
                                @forelse($lvl['classrooms'] as $cr)
                                    <tr x-show="activeJenjangFilter == 0 || activeJenjangFilter == {{ $lvl['level']->jenjang_id ?: '0' }}"
                                        class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors group">
                                        <td class="px-2.5 py-2.5 text-center text-slate-400 font-mono text-[11px] sticky left-0 z-10 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800/70 border-r border-slate-100 dark:border-slate-800/80 transition-colors">
                                            {{ $rowCounter++ }}
                                        </td>
                                        <td class="px-3.5 py-2.5 font-bold text-slate-900 dark:text-slate-100 text-xs whitespace-nowrap sticky left-12 z-10 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800/70 border-r border-slate-200 dark:border-slate-800 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.06)] transition-colors">
                                            {{ $cr['name'] }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-mono font-bold text-blue-600 dark:text-blue-400">
                                            {{ $cr['male'] }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-mono font-bold text-pink-600 dark:text-pink-400">
                                            {{ $cr['female'] }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-mono font-bold text-slate-900 dark:text-slate-100 bg-slate-50/50 dark:bg-slate-800/20">
                                            {{ $cr['total'] }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-mono {{ $cr['pdbk'] > 0 ? 'font-bold text-purple-700 dark:text-purple-300' : 'text-slate-400' }}">
                                            {{ $cr['pdbk'] > 0 ? $cr['pdbk'] : '-' }}
                                        </td>
                                        <td class="px-3.5 py-2.5">
                                            <div class="flex items-center gap-2">
                                                @if($cr['homeroom_teacher'] && $cr['homeroom_teacher'] !== 'Belum Ditugaskan')
                                                    @if($cr['homeroom_teacher_photo'])
                                                        <img src="{{ $cr['homeroom_teacher_photo'] }}" alt="{{ $cr['homeroom_teacher'] }}" class="w-6 h-6 rounded-full object-cover shrink-0">
                                                    @else
                                                        <div class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] font-bold shrink-0">
                                                            {{ substr($cr['homeroom_teacher'], 0, 1) }}
                                                        </div>
                                                    @endif
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $cr['homeroom_teacher'] }}</p>
                                                        @if($cr['homeroom_teacher_phone'])
                                                            <p class="text-[10px] text-slate-400 font-mono">{{ $cr['homeroom_teacher_phone'] }}</p>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr x-show="activeJenjangFilter == 0 || activeJenjangFilter == {{ $lvl['level']->jenjang_id ?: '0' }}">
                                        <td colspan="7" class="px-3 py-3 text-center text-slate-400 italic text-[11px]">
                                            Tidak ada kelompok belajar aktif pada {{ $lvl['level']->name }} untuk tahun ajaran ini.
                                        </td>
                                    </tr>
                                @endforelse

                                <!-- SUBTOTAL PER LEVEL -->
                                @if(count($lvl['classrooms']) > 0)
                                    <tr x-show="activeJenjangFilter == 0 || activeJenjangFilter == {{ $lvl['level']->jenjang_id ?: '0' }}"
                                        class="bg-indigo-50/50 dark:bg-indigo-950/30 font-bold border-t border-indigo-100 dark:border-indigo-900/40 text-slate-800 dark:text-slate-200">
                                        <td colspan="2" class="px-3.5 py-2 text-right uppercase tracking-wider text-[11px] text-indigo-900 dark:text-indigo-200 sticky left-0 z-10 bg-indigo-50/90 dark:bg-indigo-950/80 border-r border-indigo-200 dark:border-indigo-800 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.06)]">
                                            Subtotal {{ $lvl['level']->name }}:
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-blue-600 dark:text-blue-400">
                                            {{ $lvl['subtotal_male'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-pink-600 dark:text-pink-400">
                                            {{ $lvl['subtotal_female'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-indigo-700 dark:text-indigo-300 bg-indigo-100/50 dark:bg-indigo-900/40">
                                            {{ $lvl['subtotal_students'] }}
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-purple-700 dark:text-purple-300">
                                            {{ $lvl['subtotal_pdbk'] }}
                                        </td>
                                        <td class="px-3.5 py-2 text-slate-400 font-normal italic text-[10px]">
                                            Total {{ count($lvl['classrooms']) }} kelompok pada tingkat ini
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-slate-400">
                                        Belum ada data tingkat kelas atau kelompok belajar yang terkonfigurasi.
                                    </td>
                                </tr>
                            @endforelse

                            <!-- MURID AKTIF BELUM MEMILIKI KELOMPOK (JIKA ADA) -->
                            @if(!empty($unassignedStudents) && $unassignedTotal > 0)
                                <tr class="bg-amber-50/80 dark:bg-amber-950/40 border-t-2 border-b border-amber-200 dark:border-amber-800/80">
                                    <td colspan="7" class="px-3.5 py-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span class="font-bold text-xs uppercase tracking-wide text-amber-900 dark:text-amber-200">
                                                    MURID BELUM MEMILIKI KELOMPOK (PERLU PENEMPATAN)
                                                </span>
                                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-amber-200 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 font-semibold font-mono">
                                                    {{ $unassignedTotal }} Murid
                                                </span>
                                            </div>
                                            <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 font-mono">
                                                Total: {{ $unassignedTotal }} Murid
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                @foreach($unassignedStudents as $us)
                                    @php
                                        $isPdbk = in_array($us->special_needs, ['02) Ya', 'Ya']) || ($us->special_needs && !str_contains(strtolower($us->special_needs), 'tidak'));
                                    @endphp
                                    <tr class="bg-amber-50/30 dark:bg-amber-950/20 hover:bg-amber-100/40 dark:hover:bg-amber-900/30 transition-colors">
                                        <td class="px-2.5 py-2 text-center font-mono text-slate-400 text-[11px] sticky left-0 z-10 bg-amber-50/30 dark:bg-amber-950/20 border-r border-amber-200/50">
                                            {{ $rowCounter++ }}
                                        </td>
                                        <td class="px-3.5 py-2 font-medium text-amber-950 dark:text-amber-100 text-xs whitespace-nowrap sticky left-12 z-10 bg-amber-50/30 dark:bg-amber-950/20 border-r border-amber-200/50">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $us->full_name }}</span>
                                                <span class="text-[10px] text-amber-700 dark:text-amber-300 font-mono">NIS: {{ $us->nis }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono font-bold text-blue-600">
                                            {{ $us->gender === 'L' ? 1 : 0 }}
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono font-bold text-pink-600">
                                            {{ $us->gender === 'P' ? 1 : 0 }}
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono font-bold text-slate-800 dark:text-slate-200">
                                            1
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono {{ $isPdbk ? 'font-bold text-purple-700' : 'text-slate-400' }}">
                                            {{ $isPdbk ? 1 : '-' }}
                                        </td>
                                        <td class="px-3.5 py-2 text-amber-700 dark:text-amber-300 italic text-[11px]">
                                            Menunggu penempatan kelompok belajar
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- SUBTOTAL UNASSIGNED -->
                                <tr class="bg-amber-100/50 dark:bg-amber-950/40 font-bold border-t border-amber-200 dark:border-amber-800 text-slate-800 dark:text-slate-200">
                                    <td colspan="2" class="px-3.5 py-2 text-right uppercase tracking-wider text-[11px] text-amber-900 dark:text-amber-200 sticky left-0 z-10 bg-amber-100/90 dark:bg-amber-950/80 border-r border-amber-200 dark:border-amber-800 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.06)]">
                                        Subtotal Belum Masuk Kelompok:
                                    </td>
                                    <td class="px-3 py-2 text-center font-mono text-blue-600 dark:text-blue-400">
                                        {{ $unassignedMale }}
                                    </td>
                                    <td class="px-3 py-2 text-center font-mono text-pink-600 dark:text-pink-400">
                                        {{ $unassignedFemale }}
                                    </td>
                                    <td class="px-3 py-2 text-center font-mono text-amber-800 dark:text-amber-200 bg-amber-200/50 dark:bg-amber-900/40">
                                        {{ $unassignedTotal }}
                                    </td>
                                    <td class="px-3 py-2 text-center font-mono text-purple-700 dark:text-purple-300">
                                        {{ $unassignedPdbk }}
                                    </td>
                                    <td class="px-3.5 py-2 text-amber-700 dark:text-amber-400 font-normal italic text-[10px]">
                                        Murid aktif menunggu plotting kelompok
                                    </td>
                                </tr>
                            @endif

                            <!-- GRAND TOTAL FOOTER ROW -->
                            <tr class="bg-slate-900 text-white font-bold border-t-2 border-indigo-500 shadow-inner">
                                <td colspan="2" class="px-4 py-3.5 text-right uppercase tracking-wider text-xs font-bold text-slate-200 sticky left-0 z-10 bg-slate-900 border-r border-slate-800 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.35)]">
                                    JUMLAH PESERTA DIDIK KESELURUHAN:
                                </td>
                                <td class="px-3 py-3.5 text-center font-mono text-sm text-blue-300">
                                    {{ $grandTotalMale }}
                                </td>
                                <td class="px-3 py-3.5 text-center font-mono text-sm text-pink-300">
                                    {{ $grandTotalFemale }}
                                </td>
                                <td class="px-3 py-3.5 text-center font-mono text-base text-white bg-indigo-700/80">
                                    {{ $grandTotalStudents }}
                                </td>
                                <td class="px-3 py-3.5 text-center font-mono text-sm text-purple-300">
                                    {{ $grandTotalPdbk }}
                                </td>
                                <td class="px-4 py-3.5 text-slate-300 font-normal text-xs">
                                    <span class="inline-flex items-center gap-2">
                                        <span>Rasio: <strong class="font-mono text-white">{{ $malePercent }}% L</strong> / <strong class="font-mono text-white">{{ $femalePercent }}% P</strong></span>
                                        <span>&bull;</span>
                                        <span>Total: <strong class="font-mono text-white">{{ $grandTotalClassrooms }}</strong> Kelompok</span>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: FORMASI & PENUGASAN PENDIDIK PAUD -->
        <!-- ========================================================================= -->
        <div x-show="activeReportTab === 'roster'" class="space-y-4 lg:space-y-5" style="display: none;">
            <!-- 4 SUMMARY CARDS FOR TEACHERS & STAFF -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 lg:gap-3.5">
                <!-- 1. Total Pendidik (Guru) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Guru</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 font-mono">{{ $totalTeachers }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">Pendidik</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Wali Kelas Bertugas -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Wali Kelas</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">{{ $totalHomeroom }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">Kelompok</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Guru Pendamping / Sentra -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-900/40">
                        <i data-lucide="users-2" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Pendamping / Sentra</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">{{ $totalSupport }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">Pendidik</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Tenaga Kependidikan / Staf -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0 border border-slate-200 dark:border-slate-700">
                        <i data-lucide="briefcase" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Staf & Manajemen</p>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <h3 class="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-200 font-mono">{{ $totalStaff }}</h3>
                            <span class="text-[10px] text-slate-400 font-medium truncate">Pegawai</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXPLANATORY BANNER TENTANG FORMASI PAUD -->
            <div class="bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-xl p-3.5 sm:p-4 text-xs text-slate-700 dark:text-slate-300 flex items-start gap-3 shadow-xs">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5">
                    <i data-lucide="info" class="w-4 h-4"></i>
                </div>
                <div class="space-y-1">
                    <h4 class="font-bold text-indigo-900 dark:text-indigo-200">
                        Struktur & Formasi Pendidik PAUD Terpadu Anak Saleh
                    </h4>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-[11.5px]">
                        Pembelajaran anak usia dini mengadopsi pendekatan sentra dan pendampingan intensif. Sebanyak <strong class="text-indigo-700 dark:text-indigo-300 font-semibold">{{ $totalHomeroom }} Guru</strong> memimpin kelompok belajar sebagai Wali Kelas (penanggung jawab administrasi rapor dan komunikasi orang tua). Sebanyak <strong class="text-amber-700 dark:text-amber-300 font-semibold">{{ $totalSupport }} Guru</strong> lainnya bertindak sebagai Guru Pendamping (*Team Teaching* di kelas), Guru Pendamping Khusus (GPK bagi murid inklusi), Guru Pengasuh Daycare/TPA, dan Guru Al-Qur'an/TPQ.
                    </p>
                </div>
            </div>

            <!-- SEARCH & FILTER TOOLBAR FOR ROSTER -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 sm:p-4 shadow-xs w-full">
                <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                    <!-- Search Box -->
                    <div class="relative w-full md:max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 dark:text-slate-500"></i>
                        </span>
                        <input type="text" x-model="teacherSearch" placeholder="Cari nama guru, NIY, jabatan, atau kelompok..."
                            style="padding-left: 2.25rem;"
                            class="w-full h-9 pr-4 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 placeholder-slate-400 dark:placeholder-slate-500 transition-all shadow-inner">
                    </div>

                    <!-- Role Filter Tabs -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" @click="teacherRoleFilter = 'all'"
                            :class="teacherRoleFilter === 'all' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                            Semua ({{ count($teacherRoster) }})
                        </button>
                        <button type="button" @click="teacherRoleFilter = 'homeroom'"
                            :class="teacherRoleFilter === 'homeroom' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                            Wali Kelas ({{ $totalHomeroom }})
                        </button>
                        <button type="button" @click="teacherRoleFilter = 'support'"
                            :class="teacherRoleFilter === 'support' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                            Pendamping / Sentra ({{ $totalSupport }})
                        </button>
                        <button type="button" @click="teacherRoleFilter = 'staff'"
                            :class="teacherRoleFilter === 'staff' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer">
                            Staf & Manajemen ({{ $totalStaff }})
                        </button>
                    </div>
                </div>
            </section>

            <!-- ROSTER TABLE -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="users-2" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                            Daftar Pendidik & Penugasan TA {{ $selectedYear ? $selectedYear->name : '-' }}
                        </h3>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                        Total {{ count($teacherRoster) }} Personil
                    </span>
                </div>

                <div class="overflow-x-auto relative">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                                <th class="px-3 py-3 text-center text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12">No</th>
                                <th class="px-4 py-3 text-left text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider min-w-[220px]">Nama Pendidik</th>
                                <th class="px-3 py-3 text-center text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">NIY / NIP</th>
                                <th class="px-4 py-3 text-left text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider min-w-[260px]">Penugasan & Peran (TA {{ $selectedYear ? $selectedYear->name : '-' }})</th>
                                <th class="px-3 py-3 text-left text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider min-w-[150px]">Pendidikan Terakhir</th>
                                <th class="px-3 py-3 text-center text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Kontak</th>
                                <th class="px-3 py-3 text-center text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @foreach($teacherRoster as $index => $t)
                                @php
                                    $searchString = strtolower($t['name'] . ' ' . $t['niy'] . ' ' . $t['role_label'] . ' ' . ($t['assigned_classroom']['name'] ?? '') . ' ' . ($t['position'] ?? ''));
                                @endphp
                                <tr x-show="(teacherRoleFilter === 'all' || teacherRoleFilter === '{{ $t['role_type'] }}') && (teacherSearch === '' || '{{ $searchString }}'.includes(teacherSearch.toLowerCase()))"
                                    class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    
                                    <!-- No -->
                                    <td class="px-3 py-3 text-center text-slate-400 font-mono text-[11px]">
                                        {{ $index + 1 }}
                                    </td>

                                    <!-- Nama Pendidik & Avatar -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if(!empty($t['photo_url']))
                                                <img src="{{ $t['photo_url'] }}" alt="{{ $t['name'] }}"
                                                    class="w-8 h-8 rounded-full object-cover shrink-0 border border-slate-200 dark:border-slate-700 shadow-xs">
                                            @else
                                                @php
                                                    $nameParts = explode(' ', trim($t['name']));
                                                    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                                                @endphp
                                                <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/50">
                                                    {{ $initials }}
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-900 dark:text-slate-100 text-xs block truncate">
                                                    {{ $t['name'] }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 dark:text-slate-500 block truncate">
                                                    {{ $t['employee_type'] }} &bull; {{ $t['gender'] === 'L' ? 'Laki-laki' : ($t['gender'] === 'P' ? 'Perempuan' : '-') }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- NIY / NIP -->
                                    <td class="px-3 py-3 text-center font-mono text-slate-600 dark:text-slate-400 text-[11px]">
                                        {{ $t['niy'] }}
                                    </td>

                                    <!-- Penugasan & Peran -->
                                    <td class="px-4 py-3">
                                        @if($t['role_type'] === 'homeroom' && $t['assigned_classroom'])
                                            <div class="flex flex-col gap-0.5">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/60">
                                                        Wali Kelas
                                                    </span>
                                                    <a href="{{ route('classrooms.index', ['academic_year_id' => $selectedYearId]) }}"
                                                        class="font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                                        Kelompok {{ $t['assigned_classroom']['name'] }}
                                                    </a>
                                                </div>
                                                <span class="text-[10px] text-slate-400">
                                                    {{ $t['assigned_classroom']['jenjang_name'] }} &bull; {{ $t['assigned_classroom']['class_level_name'] }} ({{ $t['assigned_classroom']['student_count'] }} Murid)
                                                </span>
                                            </div>
                                        @elseif($t['role_type'] === 'support')
                                            <div class="flex flex-col gap-0.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200/70 dark:border-amber-800/60">
                                                        Pendamping / Sentra / GPK
                                                    </span>
                                                </div>
                                                <span class="text-[10px] text-slate-400">
                                                    Tim Co-Teacher Sentra & Pendampingan Tumbuh Kembang
                                                </span>
                                            </div>
                                        @else
                                            <div class="flex flex-col gap-0.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                        {{ $t['position'] ?: $t['employee_type'] }}
                                                    </span>
                                                </div>
                                                <span class="text-[10px] text-slate-400">
                                                    Tenaga Kependidikan & Pendukung PAUD
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Pendidikan Terakhir -->
                                    <td class="px-3 py-3 text-slate-600 dark:text-slate-400 text-[11px]">
                                        {{ $t['last_education'] }}
                                    </td>

                                    <!-- Kontak -->
                                    <td class="px-3 py-3 text-center">
                                        @if(!empty($t['phone']))
                                            @php
                                                $waNum = preg_replace('/[^0-9]/', '', $t['phone']);
                                                if (str_starts_with($waNum, '0')) {
                                                    $waNum = '62' . substr($waNum, 1);
                                                }
                                            @endphp
                                            <a href="https://wa.me/{{ $waNum }}" target="_blank"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition-colors"
                                                title="Hubungi via WhatsApp">
                                                <i data-lucide="message-circle" class="w-3 h-3 text-emerald-600"></i>
                                                <span class="font-mono text-[10.5px]">{{ $t['phone'] }}</span>
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-[10.5px]">-</span>
                                        @endif
                                    </td>

                                    <!-- Aksi / Detail Guru -->
                                    <td class="px-3 py-3 text-center">
                                        <a href="{{ route('teachers.show', $t['id']) }}"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                            title="Lihat Profil Guru">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

    </div>
</x-admin-layout>
