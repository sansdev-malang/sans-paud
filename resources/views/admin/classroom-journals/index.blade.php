<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-3.5 sm:space-y-4" x-data="journalApp()">

        <!-- ========================================================================= -->
        <!-- HEADER SECTION                                                           -->
        <!-- ========================================================================= -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2.5 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                        <i data-lucide="book-open" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </span>
                    <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Jurnal Kelas</h2>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                    Buku agenda harian guru, catatan aktivitas bermain & belajar, dan laporan kondisi kelas PAUD.
                </p>
            </div>

            <!-- T.A. Badge & Actions -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 rounded-lg text-xs shadow-2xs">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">T.A:</span>
                    <span class="font-bold text-indigo-700 dark:text-indigo-300">
                        {{ $selectedAcademicYear ? $selectedAcademicYear->name : ($activeAcademicYear ? $activeAcademicYear->name : 'Aktif') }}
                    </span>
                </div>

                <!-- Tambah Jurnal Button -->
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Tulis Jurnal Baru</span>
                </button>
            </div>
        </section>

        <!-- FLASH NOTIFICATION -->
        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 p-3 rounded-xl flex items-center justify-between gap-3 text-xs shadow-2xs transition-all">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                    <span class="font-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 p-3 rounded-xl flex items-start gap-2.5 text-xs shadow-2xs">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
                <div>
                    <span class="font-bold">Terjadi kesalahan:</span>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- FILTER BAR: ROMBEL (TANPA SUB-UNIT), BULAN, TAHUN AJARAN & ACTION BUTTONS -->
        <!-- ========================================================================= -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-2xs">
            <form method="GET" action="{{ route('classroom-journals.index') }}" id="journalFilterForm" class="flex flex-col lg:flex-row lg:items-end justify-between gap-2.5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 flex-1">
                    <!-- Filter Kelas (Kata Sub-Unit Dihilangkan) -->
                    <div>
                        <label for="classroom_id" class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Rombel / Kelompok <span class="text-rose-500">*</span>
                        </label>
                        <select name="classroom_id" id="classroom_id" onchange="document.getElementById('journalFilterForm').submit()"
                            class="w-full pl-2.5 pr-8 py-1.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                            @php
                                $groupedClasses = $classrooms->groupBy('sub_unit');
                            @endphp
                            @foreach($groupedClasses as $unit => $classes)
                                <optgroup label="{{ $unit }}">
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}" {{ $selectedClassroomId == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} ({{ $c->students_count ?? $c->active_students_count }} Murid)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Bulan -->
                    <div>
                        <label for="month" class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Bulan <span class="text-rose-500">*</span>
                        </label>
                        <select name="month" id="month" onchange="document.getElementById('journalFilterForm').submit()"
                            class="w-full pl-2.5 pr-8 py-1.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                            @foreach($monthNames as $mNum => $mLabel)
                                <option value="{{ $mNum }}" {{ $selectedMonth == $mNum ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Tahun Ajaran (Menggantikan filter tahun kalender) -->
                    <div>
                        <label for="academic_year_id" class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Tahun Ajaran <span class="text-rose-500">*</span>
                        </label>
                        <select name="academic_year_id" id="academic_year_id" onchange="document.getElementById('journalFilterForm').submit()"
                            class="w-full pl-2.5 pr-8 py-1.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $selectedAcademicYearId == $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }} {{ $ay->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- ACTIONS: FILTER, EXCEL, UNDUH PDF & CETAK PDF -->
                <div class="flex flex-wrap items-center gap-1.5 pt-1 lg:pt-0 shrink-0">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-semibold rounded-lg transition-colors cursor-pointer shadow-2xs">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Filter</span>
                    </button>

                    <!-- Ekspor Excel dengan Sinkronisasi Token Otomatis -->
                    <a href="{{ route('classroom-journals.export.excel', ['classroom_id' => $selectedClassroomId, 'academic_year_id' => $selectedAcademicYearId, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer">
                        <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                        <span>Ekspor Excel</span>
                    </a>

                    <!-- Unduh Langsung Berkas PDF -->
                    <a href="{{ route('classroom-journals.download.pdf', ['classroom_id' => $selectedClassroomId, 'academic_year_id' => $selectedAcademicYearId, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer"
                        title="Download file dokumen PDF langsung ke komputer">
                        <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                        <span>Unduh PDF</span>
                    </a>

                    <!-- Cetak Dokumen / Print Dialog -->
                    <a href="{{ route('classroom-journals.print', ['classroom_id' => $selectedClassroomId, 'academic_year_id' => $selectedAcademicYearId, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors cursor-pointer"
                        title="Buka pratinjau cetak browser">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Cetak</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- ========================================================================= -->
        <!-- ULTRA-COMPACT KPI STRIP (RINGKAS & TIDAK MAKAN TEMPAT)                     -->
        <!-- ========================================================================= -->
        <section class="flex flex-wrap items-center justify-between gap-2 w-full text-xs">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Total Entri Jurnal -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="notebook-pen" class="w-3.5 h-3.5 text-indigo-500 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Entri:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-100">{{ $totalEntries }}</span>
                    <span class="text-[10px] text-slate-400">Catatan</span>
                </div>

                <!-- Hari Efektif KBM -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="calendar-check-2" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Hari Efektif KBM:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-100">{{ $distinctDays }}</span>
                    <span class="text-[10px] text-slate-400">Hari</span>
                </div>

                <!-- Guru Terlibat -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-sky-500 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Guru Terlibat:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-100">{{ $involvedTeachers }}</span>
                    <span class="text-[10px] text-slate-400">Pendidik</span>
                </div>
            </div>

            <!-- Rombel & Switcher Tampilan -->
            <div class="flex items-center gap-2">
                @if($selectedClassroom)
                    <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 dark:bg-slate-800/60 rounded-md border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-400">
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $selectedClassroom->name }}</span>
                        <span>•</span>
                        <span>Wali: {{ $selectedClassroom->homeroomTeacher?->name ?? 'Belum Ditugaskan' }}</span>
                    </div>
                @endif

                <!-- View Mode Toggle: Tabel vs Kartu -->
                <div class="inline-flex p-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button type="button" @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        class="px-2 py-1 text-[11px] rounded-md transition-all flex items-center gap-1 cursor-pointer">
                        <i data-lucide="table" class="w-3 h-3"></i>
                        <span>Tabel</span>
                    </button>
                    <button type="button" @click="viewMode = 'cards'"
                        :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                        class="px-2 py-1 text-[11px] rounded-md transition-all flex items-center gap-1 cursor-pointer">
                        <i data-lucide="layout-grid" class="w-3 h-3"></i>
                        <span>Kartu</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- MAIN CONTENT AREA: TABEL JURNAL KELAS (FOKUS LANGSUNG KE TABEL)          -->
        <!-- ========================================================================= -->
        @if($journals->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center shadow-2xs">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-2.5">
                    <i data-lucide="book-open" class="w-6 h-6"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum ada jurnal kelas untuk bulan ini</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-sm mx-auto">
                    Periode: <strong>{{ $monthNames[$selectedMonth] ?? $selectedMonth }} {{ $selectedYear }}</strong> (T.A {{ $selectedAcademicYear?->name }}).
                </p>
                <button type="button" @click="openCreateModal()"
                    class="mt-3.5 inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Tulis Jurnal Pertama</span>
                </button>
            </div>
        @else
            <!-- VIEW MODE 1: TABEL DATA JURNAL (DEFAULT) -->
            <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold uppercase text-[10.5px] tracking-wider">
                                <th class="py-2.5 px-3 text-center w-10">No</th>
                                <th class="py-2.5 px-3 w-40">Hari & Tanggal</th>
                                <th class="py-2.5 px-3 w-28 text-center">Waktu KBM</th>
                                <th class="py-2.5 px-3 w-44">Guru Pendidik</th>
                                <th class="py-2.5 px-3 w-56">Topik / Tema</th>
                                <th class="py-2.5 px-4 min-w-[280px]">Uraian & Kondisi Kelas</th>
                                <th class="py-2.5 px-3 w-36 text-center">Presensi Murid</th>
                                <th class="py-2.5 px-3 w-24 text-center">Dokumen</th>
                                <th class="py-2.5 px-3 w-20 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @foreach($journals as $j)
                                @php
                                    $att = $j->attendance_summary;
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <!-- No -->
                                    <td class="py-3 px-3 text-center font-mono text-slate-400 font-medium">
                                        {{ $loop->iteration }}
                                    </td>

                                    <!-- Tanggal -->
                                    <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500 shrink-0"></i>
                                            <span>{{ \Carbon\Carbon::parse($j->date)->translatedFormat('l, d F Y') }}</span>
                                        </div>
                                    </td>

                                    <!-- Waktu KBM -->
                                    <td class="py-3 px-3 text-center font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 font-semibold">
                                            {{ $j->formatted_time }}
                                        </span>
                                    </td>

                                    <!-- Guru Pendidik -->
                                    <td class="py-3 px-3 text-slate-700 dark:text-slate-300">
                                        <div class="flex items-center gap-1.5 font-medium">
                                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                            <span class="truncate max-w-[150px]" title="{{ $j->teacher?->name ?? ($selectedClassroom->homeroomTeacher?->name ?? '-') }}">
                                                {{ $j->teacher?->name ?? ($selectedClassroom->homeroomTeacher?->name ?? '-') }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Topik / Tema Pembelajaran -->
                                    <td class="py-3 px-3">
                                        @if($j->topic)
                                            <span class="inline-block px-2 py-0.5 rounded text-[10.5px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800 truncate max-w-[200px]" title="{{ $j->topic }}">
                                                {{ $j->topic }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 italic text-[11px]">-</span>
                                        @endif
                                    </td>

                                    <!-- Uraian Kegiatan & Kondisi Kelas -->
                                    <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                                        <div class="line-clamp-2 hover:line-clamp-none transition-all text-xs leading-relaxed" title="{{ $j->class_condition }}">
                                            {{ $j->class_condition }}
                                        </div>
                                    </td>

                                    <!-- Presensi Murid -->
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        @if($att)
                                            <div class="inline-flex items-center gap-1 text-[10.5px] font-mono">
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 font-bold" title="Hadir: {{ $att['h'] }}">H:{{ $att['h'] }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 font-bold" title="Sakit: {{ $att['s'] }}">S:{{ $att['s'] }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 font-bold" title="Izin: {{ $att['i'] }}">I:{{ $att['i'] }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 font-bold" title="Alpa: {{ $att['a'] }}">A:{{ $att['a'] }}</span>
                                            </div>
                                        @else
                                            <span class="text-[11px] text-slate-400 italic">N/A</span>
                                        @endif
                                    </td>

                                    <!-- Foto Dokumentasi -->
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        @if($j->attachment_photo)
                                            <a href="{{ asset('storage/' . $j->attachment_photo) }}" target="_blank"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 rounded border border-indigo-200/60 dark:border-indigo-800/60 transition-colors">
                                                <i data-lucide="image" class="w-3 h-3"></i>
                                                <span>Foto</span>
                                            </a>
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600">-</span>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" @click="openEditModal({{ json_encode($j) }})" title="Edit Jurnal"
                                                class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded transition-colors cursor-pointer">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            </button>

                                            <form method="POST" action="{{ route('classroom-journals.destroy', $j->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan jurnal kelas ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus Jurnal"
                                                    class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded transition-colors cursor-pointer">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW MODE 2: KARTU FEED JURNAL (ALTERNATIF) -->
            <div x-show="viewMode === 'cards'" class="space-y-3">
                @foreach($journals as $j)
                    @php
                        $att = $j->attendance_summary;
                    @endphp
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 sm:p-4 shadow-2xs transition-all hover:border-slate-300 dark:hover:border-slate-700">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                            <!-- Date & Time & Teacher -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold text-xs border border-indigo-200/80 dark:border-indigo-800/60">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    <span>{{ \Carbon\Carbon::parse($j->date)->translatedFormat('l, d F Y') }}</span>
                                </span>

                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium text-xs font-mono">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $j->formatted_time }}</span>
                                </span>

                                @if($j->teacher)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 text-xs">
                                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Guru: <strong>{{ $j->teacher->name }}</strong></span>
                                    </span>
                                @endif
                            </div>

                            <!-- Actions (Edit & Delete) -->
                            <div class="flex items-center gap-1 shrink-0 self-end sm:self-center">
                                <button type="button" @click="openEditModal({{ json_encode($j) }})" title="Edit Jurnal"
                                    class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>

                                <form method="POST" action="{{ route('classroom-journals.destroy', $j->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan jurnal kelas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus Jurnal"
                                        class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Content Body -->
                        <div class="mt-2.5 space-y-2.5">
                            @if($j->topic)
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                        Topik KBM
                                    </span>
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                                        {{ $j->topic }}
                                    </h4>
                                </div>
                            @endif

                            <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50/50 dark:bg-slate-800/30 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                                {{ $j->class_condition }}
                            </div>

                            <!-- Footer Badges: Attendance info & Photo -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                @if($att)
                                    <div class="flex items-center gap-1.5 text-[11px]">
                                        <span class="text-slate-400 font-medium">Presensi:</span>
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 font-bold">H: {{ $att['h'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 font-bold">S: {{ $att['s'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 font-bold">I: {{ $att['i'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-bold">A: {{ $att['a'] }}</span>
                                    </div>
                                @else
                                    <div class="text-[11px] text-slate-400 italic">
                                        Belum ada absensi murid tercatat pada tanggal ini.
                                    </div>
                                @endif

                                @if($j->attachment_photo)
                                    <a href="{{ asset('storage/' . $j->attachment_photo) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 rounded-lg transition-colors border border-indigo-200/60 dark:border-indigo-800/60">
                                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                                        <span>Dokumentasi Kegiatan</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- MODAL FORM: TAMBAH & EDIT JURNAL KELAS                                   -->
        <!-- ========================================================================= -->
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            
            <div @click.outside="modalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-xl w-full p-4 sm:p-5 text-left space-y-3.5">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100"
                            x-text="isEdit ? 'Edit Jurnal Kelas' : 'Tulis Jurnal Kelas Baru'">
                        </h3>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form :action="formAction" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="academic_year_id" :value="formData.academic_year_id">

                    <!-- Classroom Selection (Tanpa Kata Sub-Unit) -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Rombel / Kelas <span class="text-rose-500">*</span>
                        </label>
                        <select name="classroom_id" x-model="formData.classroom_id" required
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                            @foreach($groupedClasses as $unit => $classes)
                                <optgroup label="{{ $unit }}">
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date & Time Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <!-- Tanggal -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Tanggal <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="date" x-model="formData.date" required
                                class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        </div>

                        <!-- Jam Mulai -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Jam Mulai
                            </label>
                            <input type="time" name="start_time" x-model="formData.start_time"
                                class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        </div>

                        <!-- Jam Selesai -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Jam Selesai
                            </label>
                            <input type="time" name="end_time" x-model="formData.end_time"
                                class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        </div>
                    </div>

                    <!-- Guru Pengajar -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Guru / Pendidik Yang Mengajar
                        </label>
                        <select name="teacher_id" x-model="formData.teacher_id"
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                            <option value="">- Default (Wali Kelas) -</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Topik / Tema Pembelajaran -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Topik / Tema Pembelajaran <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="text" name="topic" x-model="formData.topic"
                            placeholder="Contoh: Tanaman Ciptaan Allah - Sentra Bahan Alam"
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Uraian / Kondisi Kelas -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Uraian Kegiatan & Kondisi Kelas <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="class_condition" x-model="formData.class_condition" rows="4" required
                            placeholder="Tuliskan jalannya kegiatan bermain, dinamika anak, hafalan, kejadian khusus, atau pembiasaan makan bersama..."
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed"></textarea>
                    </div>

                    <!-- Dokumentasi Foto (Opsional) -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Foto Dokumentasi Kegiatan <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="file" name="attachment_photo" accept="image/*"
                            class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false"
                            class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all cursor-pointer">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            <span x-text="isEdit ? 'Perbarui Jurnal' : 'Simpan Jurnal'"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- ALPINE.JS LOGIC -->
    <script>
        function journalApp() {
            return {
                modalOpen: false,
                isEdit: false,
                viewMode: 'table', // Default Tampilan: 'table', alternatif: 'cards'
                formAction: "{{ route('classroom-journals.store') }}",
                formData: {
                    id: null,
                    classroom_id: "{{ $selectedClassroomId }}",
                    academic_year_id: "{{ $selectedAcademicYearId }}",
                    teacher_id: "",
                    date: "{{ \Carbon\Carbon::now()->format('Y-m-d') }}",
                    start_time: "07:30",
                    end_time: "10:30",
                    topic: "",
                    class_condition: "",
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formAction = "{{ route('classroom-journals.store') }}";
                    this.formData = {
                        id: null,
                        classroom_id: "{{ $selectedClassroomId }}",
                        academic_year_id: "{{ $selectedAcademicYearId }}",
                        teacher_id: "",
                        date: "{{ \Carbon\Carbon::now()->format('Y-m-d') }}",
                        start_time: "07:30",
                        end_time: "10:30",
                        topic: "",
                        class_condition: "",
                    };
                    this.modalOpen = true;
                },

                openEditModal(journal) {
                    this.isEdit = true;
                    this.formAction = "/classroom-journals/" + journal.id;
                    this.formData = {
                        id: journal.id,
                        classroom_id: journal.classroom_id,
                        academic_year_id: journal.academic_year_id || "{{ $selectedAcademicYearId }}",
                        teacher_id: journal.teacher_id || "",
                        date: journal.date ? journal.date.split('T')[0] : "",
                        start_time: journal.start_time ? journal.start_time.substring(0, 5) : "",
                        end_time: journal.end_time ? journal.end_time.substring(0, 5) : "",
                        topic: journal.topic || "",
                        class_condition: journal.class_condition || "",
                    };
                    this.modalOpen = true;
                }
            };
        }
    </script>
</x-admin-layout>
