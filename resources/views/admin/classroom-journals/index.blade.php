<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-4 sm:space-y-5" x-data="journalApp()">

        <!-- HEADER SECTION -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </span>
                    <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Jurnal Kelas</h2>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                    Buku agenda harian guru, catatan aktivitas bermain & belajar, dan laporan kondisi kelas PAUD.
                </p>
            </div>

            <!-- T.A. & Actions -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 rounded-xl text-[11px] sm:text-xs shadow-xs">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Tahun Ajaran:</span>
                    <span class="font-bold text-indigo-700 dark:text-indigo-300">
                        {{ $activeAcademicYear ? $activeAcademicYear->name : 'Aktif' }}
                    </span>
                </div>

                <!-- Tambah Jurnal Button -->
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] sm:text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Tulis Jurnal Baru</span>
                </button>
            </div>
        </section>

        <!-- FLASH NOTIFICATION -->
        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 p-3 sm:p-3.5 rounded-xl flex items-center justify-between gap-3 text-xs shadow-xs transition-all">
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
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 p-3 sm:p-3.5 rounded-xl flex items-start gap-2.5 text-xs shadow-xs">
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

        <!-- FILTER BAR: KELAS, BULAN, TAHUN -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
            <form method="GET" action="{{ route('classroom-journals.index') }}" id="journalFilterForm" class="flex flex-col lg:flex-row lg:items-end justify-between gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
                    <!-- Filter Kelas -->
                    <div>
                        <label for="classroom_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Rombel / Kelompok <span class="text-rose-500">*</span>
                        </label>
                        <select name="classroom_id" id="classroom_id" onchange="document.getElementById('journalFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                            @php
                                $groupedClasses = $classrooms->groupBy('sub_unit');
                            @endphp
                            @foreach($groupedClasses as $unit => $classes)
                                <optgroup label="Sub-Unit {{ $unit }}">
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
                        <label for="month" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Bulan <span class="text-rose-500">*</span>
                        </label>
                        <select name="month" id="month" onchange="document.getElementById('journalFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                            @foreach($monthNames as $mNum => $mLabel)
                                <option value="{{ $mNum }}" {{ $selectedMonth == $mNum ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Tahun -->
                    <div>
                        <label for="year" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Tahun <span class="text-rose-500">*</span>
                        </label>
                        <select name="year" id="year" onchange="document.getElementById('journalFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                            @foreach($yearsList as $yr)
                                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>
                                    {{ $yr }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- ACTIONS: EXPORT & PRINT -->
                <div class="flex flex-wrap items-center gap-2 pt-1 lg:pt-0 shrink-0">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-semibold rounded-lg transition-colors cursor-pointer shadow-xs">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Filter</span>
                    </button>

                    <a href="{{ route('classroom-journals.export.excel', ['classroom_id' => $selectedClassroomId, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer">
                        <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                        <span>Ekspor Excel</span>
                    </a>

                    <a href="{{ route('classroom-journals.print', ['classroom_id' => $selectedClassroomId, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors cursor-pointer">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Cetak PDF</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- STATS OVERVIEW CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i data-lucide="notebook-pen" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-[10.5px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Entri Jurnal</p>
                    <div class="flex items-baseline gap-1 mt-0.5">
                        <h4 class="text-xl font-bold font-mono text-slate-900 dark:text-slate-50">{{ $totalEntries }}</h4>
                        <span class="text-[11px] text-slate-400">Catatan</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                    <i data-lucide="calendar-check-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-[10.5px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hari Efektif KBM</p>
                    <div class="flex items-baseline gap-1 mt-0.5">
                        <h4 class="text-xl font-bold font-mono text-slate-900 dark:text-slate-50">{{ $distinctDays }}</h4>
                        <span class="text-[11px] text-slate-400">Hari Terisi</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0 border border-sky-100 dark:border-sky-900/40">
                    <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-[10.5px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Guru Terlibat</p>
                    <div class="flex items-baseline gap-1 mt-0.5">
                        <h4 class="text-xl font-bold font-mono text-slate-900 dark:text-slate-50">{{ $involvedTeachers }}</h4>
                        <span class="text-[11px] text-slate-400">Pendidik</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- JOURNAL ENTRIES LIST -->
        @if($journals->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                    <i data-lucide="book-open" class="w-6 h-6"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum ada jurnal kelas untuk bulan ini</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    Klik tombol "Tulis Jurnal Baru" di atas untuk menambahkan catatan pembelajaran dan kondisi kelas hari ini.
                </p>
                <button type="button" @click="openCreateModal()"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tulis Jurnal Pertama</span>
                </button>
            </div>
        @else
            <div class="space-y-3.5">
                @foreach($journals as $j)
                    @php
                        $att = $j->attendance_summary;
                    @endphp
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5 shadow-xs transition-all hover:border-slate-300 dark:hover:border-slate-700">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <!-- Date & Time & Teacher -->
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-bold text-xs border border-indigo-200/80 dark:border-indigo-800/60">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    <span>{{ \Carbon\Carbon::parse($j->date)->translatedFormat('l, d F Y') }}</span>
                                </span>

                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium text-xs font-mono">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $j->formatted_time }}</span>
                                </span>

                                @if($j->teacher)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 text-xs">
                                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Guru: <strong>{{ $j->teacher->name }}</strong></span>
                                    </span>
                                @endif
                            </div>

                            <!-- Actions (Edit & Delete) -->
                            <div class="flex items-center gap-1 shrink-0 self-end sm:self-center">
                                <button type="button" @click="openEditModal({{ json_encode($j) }})" title="Edit Jurnal"
                                    class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>

                                <form method="POST" action="{{ route('classroom-journals.destroy', $j->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan jurnal kelas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus Jurnal"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Content Body -->
                        <div class="mt-3.5 space-y-3">
                            <!-- Topic / Theme if available -->
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

                            <!-- Description / Class Condition -->
                            <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50/50 dark:bg-slate-800/30 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                                {{ $j->class_condition }}
                            </div>

                            <!-- Footer Badges: Attendance info & Photo -->
                            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                                <!-- Student Attendance Summary for that day -->
                                @if($att)
                                    <div class="flex items-center gap-2 text-[11px]">
                                        <span class="text-slate-400 font-medium">Presensi Murid:</span>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 font-bold">H: {{ $att['h'] }}</span>
                                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 font-bold">S: {{ $att['s'] }}</span>
                                        <span class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 font-bold">I: {{ $att['i'] }}</span>
                                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-bold">A: {{ $att['a'] }}</span>
                                    </div>
                                @else
                                    <div class="text-[11px] text-slate-400 italic">
                                        Belum ada absensi murid tercatat pada tanggal ini.
                                    </div>
                                @endif

                                <!-- Photo Attachment -->
                                @if($j->attachment_photo)
                                    <div class="flex items-center gap-2">
                                        <a href="{{ asset('storage/' . $j->attachment_photo) }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 rounded-lg transition-colors border border-indigo-200/60 dark:border-indigo-800/60">
                                            <i data-lucide="image" class="w-3.5 h-3.5"></i>
                                            <span>Lihat Dokumentasi Kegiatan</span>
                                        </a>
                                    </div>
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
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-xl w-full p-4 sm:p-6 text-left space-y-4">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100"
                            x-text="isEdit ? 'Edit Jurnal Kelas' : 'Tulis Jurnal Kelas Baru'">
                        </h3>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form :action="formAction" method="POST" enctype="multipart/form-data" class="space-y-3.5">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Classroom Selection -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Rombel / Kelas <span class="text-rose-500">*</span>
                        </label>
                        <select name="classroom_id" x-model="formData.classroom_id" required
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($groupedClasses as $unit => $classes)
                                <optgroup label="Sub-Unit {{ $unit }}">
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->sub_unit }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date & Time Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Tanggal -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Tanggal <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="date" x-model="formData.date" required
                                class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Jam Mulai -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Jam Mulai
                            </label>
                            <input type="time" name="start_time" x-model="formData.start_time"
                                class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Jam Selesai -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Jam Selesai
                            </label>
                            <input type="time" name="end_time" x-model="formData.end_time"
                                class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Guru Pengajar -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Guru / Pendidik Yang Mengajar
                        </label>
                        <select name="teacher_id" x-model="formData.teacher_id"
                            class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-all cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
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
                formAction: "{{ route('classroom-journals.store') }}",
                formData: {
                    id: null,
                    classroom_id: "{{ $selectedClassroomId }}",
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
