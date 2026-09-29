<x-admin-layout>
    <div class="p-6 space-y-6" x-data="{
        selectedRoster: null,
        showRosterModal: false,
        openRoster(item) {
            this.selectedRoster = item;
            this.showRosterModal = true;
        }
    }">

        <!-- HEADER SECTION -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-500/20">
                        <i data-lucide="pie-chart" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Rekapitulasi Rombel & Kesiswaan
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-800">
                                PAUD Terpadu
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Laporan distribusi kelompok belajar, rasio gender, kapasitas ruang, dan statistik usia murid.</p>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS & YEAR FILTER -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <form method="GET" action="{{ route('student-reports.index') }}" class="m-0 p-0">
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                                T.A. {{ $year->name }} {{ $year->is_active ? '★ (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('student-reports.print', ['academic_year_id' => $selectedYearId]) }}" target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all duration-100 cursor-pointer">
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                    Cetak Laporan
                </a>

                <a href="{{ route('student-reports.export.excel', ['academic_year_id' => $selectedYearId]) }}"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                    Ekspor Excel
                </a>
            </div>
        </section>

        <!-- STATS KPI CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Murid Aktif -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Murid Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total_active']) }} <span class="text-xs font-normal text-slate-500">anak</span>
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800/80 pt-2.5">
                    <span class="text-blue-600 dark:text-blue-400 font-semibold">👦 Putra: {{ $stats['male'] }}</span>
                    <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                    <span class="text-rose-600 dark:text-rose-400 font-semibold">👧 Putri: {{ $stats['female'] }}</span>
                </div>
            </div>

            <!-- Card 2: Kapasitas & Keterisian -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keterisian Daya Tampung</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ $stats['occupancy_pct'] }}%
                        </h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="gauge" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $stats['occupancy_pct']) }}%"></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                        <span>Terisi: {{ $stats['total_active'] }}</span>
                        <span>Kapasitas: {{ $stats['total_capacity'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Kelompok (Rombel) -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Kelompok Belajar</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ $stats['total_classrooms'] }} <span class="text-xs font-normal text-slate-500">kelompok</span>
                        </h3>
                    </div>
                    <div class="p-2.5 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-xl border border-purple-100 dark:border-purple-900/50">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span class="font-semibold text-amber-600">PG: {{ count($groupedBySubUnit['PG']) }}</span> &bull;
                    <span class="font-semibold text-indigo-600">TK: {{ count($groupedBySubUnit['TK']) }}</span> &bull;
                    <span class="font-semibold text-purple-600">Daycare: {{ count($groupedBySubUnit['DAYCARE']) }}</span>
                </div>
            </div>

            <!-- Card 4: Distribusi Murid per Sub-Unit -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Layanan Terintegrasi</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-bold text-amber-600">PG: {{ $stats['pg'] }}</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-xs font-bold text-indigo-600">TK: {{ $stats['tk'] }}</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-xs font-bold text-purple-600">Daycare: {{ $stats['daycare'] }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <i data-lucide="sparkles" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Santri TPQ aktif: <span class="font-bold text-emerald-600">{{ $stats['tpq'] }} anak</span>
                </div>
            </div>
        </section>

        <!-- MAIN DATA TABLE SECTION: REKAPITULASI ROMBEL -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-2">
                    <i data-lucide="table" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        Tabel Rekapitulasi Rombongan Belajar (T.A. {{ $selectedYear ? $selectedYear->name : '' }})
                    </h3>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ count($classroomStats) }}</span> Kelompok Belajar
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-100/60 dark:bg-slate-900 text-slate-600 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Sub Unit & Jenjang</th>
                            <th class="py-3 px-4">Nama Kelompok (Rombel)</th>
                            <th class="py-3 px-4">Wali Kelas / Pendidik</th>
                            <th class="py-3 px-4 text-center">Kapasitas</th>
                            <th class="py-3 px-4 text-center">Putra (L)</th>
                            <th class="py-3 px-4 text-center">Putri (P)</th>
                            <th class="py-3 px-4 text-center">Total Murid</th>
                            <th class="py-3 px-4 text-center">Sisa Kuota</th>
                            <th class="py-3 px-4 text-center min-w-[120px]">Keterisian</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($classroomStats as $index => $item)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">{{ $index + 1 }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                            {{ $item['sub_unit'] === 'PG' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400' : '' }}
                                            {{ $item['sub_unit'] === 'TK' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400' : '' }}
                                            {{ $item['sub_unit'] === 'DAYCARE' ? 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-400' : '' }}
                                            {{ $item['sub_unit'] === 'TPQ' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : '' }}
                                        ">
                                            {{ $item['sub_unit'] }}
                                        </span>
                                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ $item['class_level_name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-slate-100">
                                    {{ $item['name'] }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $item['teacher_name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-400">{{ $item['capacity'] }}</td>
                                <td class="py-3 px-4 text-center font-semibold text-blue-600 dark:text-blue-400">{{ $item['male_count'] }}</td>
                                <td class="py-3 px-4 text-center font-semibold text-rose-600 dark:text-rose-400">{{ $item['female_count'] }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/50">
                                        {{ $item['total_count'] }} anak
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono {{ $item['remaining_capacity'] == 0 ? 'text-rose-500 font-bold' : 'text-slate-500' }}">
                                    {{ $item['remaining_capacity'] }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $item['occupancy_rate'] >= 100 ? 'bg-rose-500' : ($item['occupancy_rate'] >= 80 ? 'bg-amber-500' : 'bg-indigo-500') }}"
                                                style="width: {{ min(100, $item['occupancy_rate']) }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-mono text-slate-500 shrink-0">{{ $item['occupancy_rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button type="button" @click="openRoster(@js($item))"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors cursor-pointer">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        Roster
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-8 text-center text-slate-400">
                                    Belum ada data kelompok belajar pada tahun ajaran ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/80 font-bold text-slate-900 dark:text-slate-100">
                            <td colspan="4" class="py-3 px-4 text-center uppercase tracking-wider text-[11px]">Total Keseluruhan</td>
                            <td class="py-3 px-4 text-center font-mono">{{ $stats['total_capacity'] }}</td>
                            <td class="py-3 px-4 text-center text-blue-600 dark:text-blue-400">{{ $stats['male'] }}</td>
                            <td class="py-3 px-4 text-center text-rose-600 dark:text-rose-400">{{ $stats['female'] }}</td>
                            <td class="py-3 px-4 text-center text-indigo-600 dark:text-indigo-400 text-sm">{{ $stats['total_active'] }}</td>
                            <td class="py-3 px-4 text-center font-mono">{{ max(0, $stats['total_capacity'] - $stats['total_active']) }}</td>
                            <td class="py-3 px-4 text-center font-mono">{{ $stats['occupancy_pct'] }}%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <!-- SECTION 2: DISTRIBUSI USIA & REKAP SUB-UNIT GRID -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Box 1: Rekapitulasi per Sub-Unit -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="layers" class="w-4 h-4 text-amber-500"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Distribusi Rombel & Murid per Sub Unit
                        </h3>
                    </div>

                    <div class="space-y-3">
                        <!-- PG -->
                        <div class="p-3 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-200/50 dark:border-amber-900/30 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">🧸</span>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Playgroup (KB)</h4>
                                    <p class="text-[11px] text-slate-500">{{ count($groupedBySubUnit['PG']) }} Kelompok Belajar</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-amber-700 dark:text-amber-400">{{ $stats['pg'] }} Murid</span>
                            </div>
                        </div>

                        <!-- TK -->
                        <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-200/50 dark:border-indigo-900/30 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">🎒</span>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Taman Kanak-Kanak (TK)</h4>
                                    <p class="text-[11px] text-slate-500">{{ count($groupedBySubUnit['TK']) }} Kelompok Belajar</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-indigo-700 dark:text-indigo-400">{{ $stats['tk'] }} Murid</span>
                            </div>
                        </div>

                        <!-- DAYCARE -->
                        <div class="p-3 bg-purple-50/50 dark:bg-purple-950/20 rounded-xl border border-purple-200/50 dark:border-purple-900/30 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">👶</span>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">Daycare (TPA)</h4>
                                    <p class="text-[11px] text-slate-500">{{ count($groupedBySubUnit['DAYCARE']) }} Ruang Asuh</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-purple-700 dark:text-purple-400">{{ $stats['daycare'] }} Anak</span>
                            </div>
                        </div>

                        <!-- TPQ -->
                        <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200/50 dark:border-emerald-900/30 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">📖</span>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100">TPQ Terpadu Anak Saleh</h4>
                                    <p class="text-[11px] text-slate-500">{{ count($groupedBySubUnit['TPQ']) }} Halaqah / Kelas</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ $stats['tpq'] }} Santri</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Box 2: Distribusi Usia Murid -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-500"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                            Distribusi Usia Murid (Tahun Ajaran {{ $selectedYear ? $selectedYear->name : '' }})
                        </h3>
                    </div>

                    <div class="space-y-3">
                        @forelse($ageDistribution as $ageLabel => $ageData)
                            @php
                                $agePct = $stats['total_active'] > 0 ? round(($ageData['total'] / $stats['total_active']) * 100, 1) : 0;
                            @endphp
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-800/40 rounded-lg border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $ageLabel }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-blue-600 font-semibold">👦 {{ $ageData['male'] }}</span>
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="text-[10px] text-rose-600 font-semibold">👧 {{ $ageData['female'] }}</span>
                                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100 ml-1">{{ $ageData['total'] }} Anak ({{ $agePct }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-600 dark:bg-indigo-500 h-full rounded-full transition-all duration-300" style="width: {{ $agePct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-6">Tidak ada data tanggal lahir murid.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <!-- MODAL: ROSTER MURID KELOMPOK -->
        <div x-show="showRosterModal" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.outside="showRosterModal = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/70">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <i data-lucide="users" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50" x-text="selectedRoster?.name"></h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Wali Kelas: <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="selectedRoster?.teacher_name"></span> &bull; 
                                Total: <span class="font-semibold text-indigo-600" x-text="selectedRoster?.total_count + ' Murid'"></span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showRosterModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Modal Body: Student List -->
                <div class="p-5 overflow-y-auto space-y-2 flex-1 divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-for="(s, sIdx) in (selectedRoster?.students || [])" :key="s.id">
                        <div class="pt-2 pb-2 first:pt-0 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3">
                                <span class="w-6 text-slate-400 text-center font-mono" x-text="sIdx + 1"></span>
                                <div>
                                    <h5 class="font-bold text-slate-900 dark:text-slate-100" x-text="s.full_name"></h5>
                                    <p class="text-[11px] text-slate-500 font-mono">
                                        NIS: <span x-text="s.nis"></span> &bull; 
                                        Panggilan: <span x-text="s.nickname || '-'"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                    :class="s.gender === 'L' || s.gender === 'Male' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400'"
                                    x-text="s.gender === 'L' || s.gender === 'Male' ? 'Laki-laki' : 'Perempuan'">
                                </span>
                                <span class="text-slate-400 font-mono text-[11px]" x-text="s.age ? s.age + ' th' : ''"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex justify-end">
                    <button type="button" @click="showRosterModal = false"
                        class="px-4 py-2 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-semibold rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
