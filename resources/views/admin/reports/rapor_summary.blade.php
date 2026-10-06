<x-admin-layout>
    <div class="p-6 space-y-6" x-data="{
        selectedStudent: null,
        showDetailModal: false,
        openDetail(item) {
            this.selectedStudent = item;
            this.showDetailModal = true;
        }
    }">

        <!-- HEADER SECTION -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Hasil & Rekap Rapor</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Ringkasan capaian pembelajaran, narasi evaluasi, tumbuh kembang, dan cetak dokumen rapor murid.</p>
            </div>

            <!-- SSO LAUNCHER ACTION -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ $ssoLaunchUrl }}" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    Buka Aplikasi SANS Rapor (Input & Kelola)
                </a>
            </div>
        </section>

        <!-- KPI STATS CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Siswa Kelompok -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Murid</span>
                        <div class="text-2xl font-bold text-slate-900 dark:text-slate-50">{{ $totalStudents }}</div>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-lg">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/60 text-[11px] text-slate-500">
                    Kelompok: <strong class="text-slate-700 dark:text-slate-300">{{ $selectedClassroom?->name ?? '-' }}</strong>
                </div>
            </div>

            <!-- Card 2: Rapor Lengkap -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Rapor Terisi Lengkap</span>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $completedCount }}</div>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-lg">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/60 text-[11px] text-slate-500">
                    {{ $totalStudents > 0 ? round(($completedCount / $totalStudents) * 100) : 0 }}% dari rombel telah terisi narasi CP
                </div>
            </div>

            <!-- Card 3: Data Pertumbuhan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Tumbuh Kembang Terdata</span>
                        <div class="text-2xl font-bold text-sky-600 dark:text-sky-400">{{ $growthRecordedCount }}</div>
                    </div>
                    <div class="p-2.5 bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 rounded-lg">
                        <i data-lucide="activity" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/60 text-[11px] text-slate-500">
                    Data tinggi badan, berat badan & lingkar kepala
                </div>
            </div>

            <!-- Card 4: Belum Terisi -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Belum Lengkap</span>
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ max(0, $totalStudents - $completedCount) }}</div>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-lg">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/60 text-[11px] text-slate-500">
                    Menunggu pengisian narasi di SANS Rapor
                </div>
            </div>
        </section>

        <!-- FILTER & SELECTION BAR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
            <form method="GET" action="{{ route('rekap-rapor.index') }}" class="flex flex-wrap items-center gap-3">
                <!-- Filter Kelompok / Rombel -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Kelompok:</span>
                    <select name="classroom_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                        @foreach($classrooms as $cls)
                            <option value="{{ $cls->id }}" {{ $selectedClassroomId == $cls->id ? 'selected' : '' }}>
                                {{ $cls->name }} {{ $cls->homeroomTeacher ? '('.$cls->homeroomTeacher->name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Tahun Ajaran -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Tahun Ajaran:</span>
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                        @foreach($academicYears as $yr)
                            <option value="{{ $yr->id }}" {{ $selectedYearId == $yr->id ? 'selected' : '' }}>
                                T.A. {{ $yr->name }} {{ $yr->is_active ? '★ (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Semester -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Semester:</span>
                    <select name="semester" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                        <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Semester Ganjil</option>
                        <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Semester Genap</option>
                    </select>
                </div>

                @if($selectedClassroom && $selectedClassroom->homeroomTeacher)
                <div class="ml-auto text-xs text-slate-500 flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Wali Kelas: <strong class="text-slate-700 dark:text-slate-200">{{ $selectedClassroom->homeroomTeacher->name }}</strong></span>
                </div>
                @endif
            </form>
        </section>

        <!-- TABLE HASIL & REKAP RAPOR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        Daftar Rapor Murid
                        <span class="text-xs font-normal text-slate-400">({{ count($studentData) }} Murid)</span>
                    </h3>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="py-3 px-4 font-semibold text-center w-12">No</th>
                            <th class="py-3 px-4 font-semibold">Nama Murid & NIS</th>
                            <th class="py-3 px-4 font-semibold">Capaian Naratif (CP)</th>
                            <th class="py-3 px-4 font-semibold">Pertumbuhan Fisik</th>
                            <th class="py-3 px-4 font-semibold text-center">Kehadiran</th>
                            <th class="py-3 px-4 font-semibold text-center">Status</th>
                            <th class="py-3 px-4 font-semibold text-center">Aksi Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($studentData as $idx => $row)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <!-- No -->
                            <td class="py-3.5 px-4 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>

                            <!-- Murid -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $row->student->full_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono flex items-center gap-2 mt-0.5">
                                    <span>NIS: {{ $row->student->nis ?: '-' }}</span>
                                    <span>•</span>
                                    <span>{{ $row->student->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                </div>
                            </td>

                            <!-- Capaian Naratif Elemen -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $row->has_religion ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}">
                                        {{ $row->has_religion ? '✓ Agama & Budi' : '— Agama' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $row->has_identity ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}">
                                        {{ $row->has_identity ? '✓ Jati Diri' : '— Jati Diri' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $row->has_steam ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}">
                                        {{ $row->has_steam ? '✓ STEAM' : '— STEAM' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Pertumbuhan Fisik -->
                            <td class="py-3.5 px-4">
                                @if($row->has_growth)
                                <div class="space-y-0.5 text-[11px]">
                                    <div class="text-slate-700 dark:text-slate-300">
                                        TB: <span class="font-bold">{{ $row->height ?? '-' }} cm</span>, 
                                        BB: <span class="font-bold">{{ $row->weight ?? '-' }} kg</span>
                                    </div>
                                    @if($row->head_circ)
                                    <div class="text-slate-400 text-[10px]">LK: {{ $row->head_circ }} cm</div>
                                    @endif
                                </div>
                                @else
                                <span class="text-slate-400 text-[11px] italic">Belum terdata</span>
                                @endif
                            </td>

                            <!-- Kehadiran -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5 text-[11px] font-medium font-mono text-slate-600 dark:text-slate-300">
                                    <span title="Sakit" class="px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300">{{ $row->sick }}S</span>
                                    <span title="Izin" class="px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300">{{ $row->permission }}I</span>
                                    <span title="Alpa" class="px-1.5 py-0.5 rounded bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300">{{ $row->unexcused }}A</span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($row->is_complete)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800">
                                    <i data-lucide="check" class="w-3 h-3"></i> Lengkap
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800">
                                    <i data-lucide="clock" class="w-3 h-3"></i> Belum Lengkap
                                </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Cetak PDF -->
                                    <a href="{{ $row->print_url }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 dark:text-indigo-300 font-semibold text-[11px] rounded-md transition cursor-pointer">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        Cetak PDF
                                    </a>

                                    <!-- Detail Modal Trigger -->
                                    <button @click="openDetail({{ json_encode($row) }})"
                                        class="p-1.5 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition cursor-pointer" title="Lihat Ringkasan Catatan">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                                <p>Tidak ada data murid pada kelompok belajar ini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- DETAIL MODAL -->
        <div x-show="showDetailModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden"
                @click.away="showDetailModal = false">
                
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/30">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-slate-100" x-text="selectedStudent?.student?.full_name"></h3>
                        <p class="text-xs text-slate-400" x-text="'NIS: ' + (selectedStudent?.student?.nis || '-')"></p>
                    </div>
                    <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 text-xs">
                    <!-- Elemen Agama -->
                    <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 rounded-xl space-y-1">
                        <span class="font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                            <i data-lucide="book" class="w-3.5 h-3.5"></i> Nilai Agama & Budi Pekerti
                        </span>
                        <p class="text-slate-600 dark:text-slate-300 leading-relaxed" x-text="selectedStudent?.narrative?.element_religion || 'Belum ada catatan'"></p>
                    </div>

                    <!-- Elemen Jati Diri -->
                    <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 rounded-xl space-y-1">
                        <span class="font-bold text-indigo-800 dark:text-indigo-300 flex items-center gap-1.5">
                            <i data-lucide="smile" class="w-3.5 h-3.5"></i> Jati Diri
                        </span>
                        <p class="text-slate-600 dark:text-slate-300 leading-relaxed" x-text="selectedStudent?.narrative?.element_identity || 'Belum ada catatan'"></p>
                    </div>

                    <!-- Elemen STEAM -->
                    <div class="p-3 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/40 rounded-xl space-y-1">
                        <span class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                            <i data-lucide="compass" class="w-3.5 h-3.5"></i> Dasar-dasar Literasi & STEAM
                        </span>
                        <p class="text-slate-600 dark:text-slate-300 leading-relaxed" x-text="selectedStudent?.narrative?.element_literacy_steam || 'Belum ada catatan'"></p>
                    </div>

                    <!-- Refleksi Guru -->
                    <template x-if="selectedStudent?.teacher_reflection">
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl space-y-1">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Catatan / Refleksi Guru:</span>
                            <p class="text-slate-600 dark:text-slate-400 italic" x-text="selectedStudent?.teacher_reflection"></p>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex justify-between items-center">
                    <a :href="selectedStudent?.print_url" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> Buka Dokumen Rapor Lengkap
                    </a>
                    <button @click="showDetailModal = false"
                        class="px-4 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
