<x-admin-layout>
    @php
        $ddtkPct = $totalStudents > 0 ? round(($ddtkCount / $totalStudents) * 100) : 0;
        $sdidtkPct = $totalStudents > 0 ? round(($sdidtkCount / $totalStudents) * 100) : 0;
    @endphp
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-4 sm:space-y-5" x-data="ddtkApp({
        initialTab: '{{ $activeTab }}',
        classroomId: '{{ $selectedClassroomId }}',
        selectedPeriod: '{{ $selectedPeriod }}',
        selectedDate: '{{ $selectedDate }}',
        chartHistory: {{ json_encode($chartHistory) }},
        students: {{ json_encode($students->map(fn($s) => ['id' => $s->id, 'name' => $s->full_name, 'nis' => $s->nis])) }}
    })">

        <!-- HEADER SECTION -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800">
                        <i data-lucide="activity" class="w-5 h-5"></i>
                    </span>
                    <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">DDTK & SDIDTK</h2>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                    Deteksi Dini Tumbuh Kembang (DDTK) antropometri fisik dan skrining stimulasi, deteksi & intervensi (SDIDTK) kesehatan anak PAUD.
                </p>
            </div>

            <!-- T.A. & Badge Info -->
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
            </div>
        </section>

        @if(isset($errors) && $errors->any())
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 p-3 sm:p-3.5 rounded-xl flex items-start gap-2.5 text-xs shadow-xs">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
                <div>
                    <span class="font-bold">Terjadi kesalahan validasi:</span>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- TAB NAVIGATION -->
        <div class="border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4">
            <nav class="flex space-x-1 sm:space-x-2" aria-label="Tabs">
                <!-- Tab 1: Input DDTK -->
                <button type="button" @click="setTab('inputDDTK')"
                    :class="activeTab === 'inputDDTK' 
                        ? 'border-sky-600 text-sky-600 dark:text-sky-400 dark:border-sky-400 bg-sky-50/40 dark:bg-sky-950/20' 
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
                    class="group inline-flex items-center gap-2 py-2.5 px-3 sm:px-4 border-b-2 font-semibold text-xs sm:text-sm rounded-t-lg transition-colors cursor-pointer">
                    <i data-lucide="ruler" class="w-4 h-4"></i>
                    <span>Input DDTK (Antropometri)</span>
                    <span class="ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full bg-sky-100 text-sky-700 dark:bg-sky-900/60 dark:text-sky-300 font-bold">
                        {{ $ddtkCount }}/{{ $totalStudents }}
                    </span>
                </button>

                <!-- Tab 2: Input SDIDTK -->
                <button type="button" @click="setTab('inputSDIDTK')"
                    :class="activeTab === 'inputSDIDTK' 
                        ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20' 
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
                    class="group inline-flex items-center gap-2 py-2.5 px-3 sm:px-4 border-b-2 font-semibold text-xs sm:text-sm rounded-t-lg transition-colors cursor-pointer">
                    <i data-lucide="stethoscope" class="w-4 h-4"></i>
                    <span>Input SDIDTK (Skrining)</span>
                    <span class="ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold">
                        {{ $sdidtkCount }}/{{ $totalStudents }}
                    </span>
                </button>

                <!-- Tab 3: Rekap Data -->
                <button type="button" @click="setTab('rekap')"
                    :class="activeTab === 'rekap' 
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/20' 
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
                    class="group inline-flex items-center gap-2 py-2.5 px-3 sm:px-4 border-b-2 font-semibold text-xs sm:text-sm rounded-t-lg transition-colors cursor-pointer">
                    <i data-lucide="table-2" class="w-4 h-4"></i>
                    <span>Rekap Data</span>
                </button>

                <!-- Tab 4: Grafik Pertumbuhan -->
                <button type="button" @click="setTab('grafik')"
                    :class="activeTab === 'grafik' 
                        ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400 bg-purple-50/40 dark:bg-purple-950/20' 
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
                    class="group inline-flex items-center gap-2 py-2.5 px-3 sm:px-4 border-b-2 font-semibold text-xs sm:text-sm rounded-t-lg transition-colors cursor-pointer">
                    <i data-lucide="line-chart" class="w-4 h-4"></i>
                    <span>Grafik Pertumbuhan</span>
                </button>
            </nav>

            <!-- Classroom badge info -->
            @if($selectedClassroom)
                <div class="hidden sm:flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 pb-1">
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700">
                        {{ $selectedClassroom->name }}
                    </span>
                    @if($selectedClassroom->homeroomTeacher)
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[200px]">
                            Wali: <strong>{{ $selectedClassroom->homeroomTeacher->name }}</strong>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: INPUT DDTK (INLINE FORM)                                          -->
        <!-- Nama Murid, Tinggi(cm), Berat(kg), L.Kepala(cm), L.Lengan(cm)           -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'inputDDTK'" x-cloak class="space-y-4">
            
            <!-- FILTER BAR TAB 1 (KELAS, PERIODE, TANGGAL UKUR) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
                <form method="GET" action="{{ route('student-ddtk.index') }}" id="ddtkFilterForm">
                    <input type="hidden" name="tab" value="inputDDTK">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full">
                        <!-- Filter Kelas -->
                        <div>
                            <label for="ddtk_classroom_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Rombel / Kelas <span class="text-rose-500">*</span>
                            </label>
                            <select name="classroom_id" id="ddtk_classroom_id" onchange="document.getElementById('ddtkFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors cursor-pointer">
                                @foreach($classrooms as $c)
                                    <option value="{{ $c->id }}" {{ $selectedClassroomId == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} ({{ $c->students_count ?? $c->active_students_count }} Murid)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Periode -->
                        <div>
                            <label for="ddtk_period" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Periode Pemeriksaan <span class="text-rose-500">*</span>
                            </label>
                            <select name="period" id="ddtk_period" onchange="document.getElementById('ddtkFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors cursor-pointer">
                                @foreach($availablePeriods as $pKey => $pLabel)
                                    <option value="{{ $pKey }}" {{ $selectedPeriod == $pKey ? 'selected' : '' }}>
                                        {{ $pLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Tanggal Pemeriksaan -->
                        <div>
                            <label for="ddtk_date" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Tanggal Ukur <span class="text-slate-400 font-normal">(Default)</span>
                            </label>
                            <input type="date" name="date" id="ddtk_date" value="{{ $selectedDate }}" onchange="document.getElementById('ddtkFilterForm').submit()"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors cursor-pointer">
                        </div>
                    </div>
                </form>
            </div>

            <!-- ULTRA-COMPACT DDTK SUMMARY STRIP -->
            @php
                $ddtkPct = $totalStudents > 0 ? round(($ddtkCount / $totalStudents) * 100) : 0;
            @endphp
            <section class="flex flex-wrap items-center gap-2 sm:gap-2.5 w-full text-xs">
                <!-- Total Murid -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Murid:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-50">{{ $totalStudents }} Anak</span>
                </div>

                <!-- Diukur DDTK -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-sky-50/70 dark:bg-sky-950/30 border border-sky-200/60 dark:border-sky-900/40 rounded-lg text-sky-800 dark:text-sky-300 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                    <span class="text-[11px] font-medium">Terukur DDTK:</span>
                    <span class="font-bold font-mono">{{ $ddtkCount }}/{{ $totalStudents }}</span>
                    <span class="text-[10px] font-semibold text-sky-600/80 dark:text-sky-400/80">({{ $ddtkPct }}%)</span>
                </div>

                <!-- Rata-rata TB -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5 text-sky-500 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Rerata TB:</span>
                    <span class="font-bold font-mono text-sky-700 dark:text-sky-300">{{ $avgHeight }} cm</span>
                </div>

                <!-- Rata-rata BB -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="scale" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Rerata BB:</span>
                    <span class="font-bold font-mono text-amber-700 dark:text-amber-300">{{ $avgWeight }} kg</span>
                </div>

                <!-- Periode Aktif -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/60 dark:border-purple-900/40 rounded-lg text-purple-800 dark:text-purple-300 shadow-2xs">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400 shrink-0"></i>
                    <span class="text-[11px] font-medium">Periode:</span>
                    <span class="font-bold">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</span>
                </div>
            </section>
            @if($students->isEmpty())
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center">
                    <i data-lucide="users-round" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                    <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Tidak ada murid aktif dalam rombel ini</h4>
                </div>
            @else
                <form method="POST" action="{{ route('student-ddtk.store-ddtk') }}">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
                    <input type="hidden" name="period" value="{{ $selectedPeriod }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    <!-- FORM TOOLBAR -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/80 dark:border-sky-800/60">
                                <i data-lucide="ruler" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                    Input Antropometri Fisik Murid
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Periode: <strong class="text-sky-600 dark:text-sky-400">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</strong> &bull; Rombel: <strong class="text-slate-700 dark:text-slate-300">{{ $selectedClassroom?->name ?? '-' }}</strong>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Tombol Simpan -->
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 active:scale-95 text-white text-xs font-bold rounded-lg shadow-xs hover:shadow transition-all cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Simpan Data DDTK</span>
                            </button>
                        </div>
                    </div>

                    <!-- INLINE TABLE DDTK -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[11px] font-bold border-b border-slate-200 dark:border-slate-800">
                                        <th class="py-3 px-3 w-12 text-center uppercase tracking-wider">No</th>
                                        <th class="py-3 px-4 min-w-[200px] uppercase tracking-wider">Nama Murid</th>
                                        <th class="py-3 px-2 w-14 text-center uppercase tracking-wider border-r border-slate-200/60 dark:border-slate-800/60">L/P</th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-sky-50/50 dark:bg-sky-950/20 text-sky-800 dark:text-sky-300 border-x border-slate-200/60 dark:border-slate-800/60">
                                            Tinggi (cm)
                                        </th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-amber-50/50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300 border-r border-slate-200/60 dark:border-slate-800/60">
                                            Berat (kg)
                                        </th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-800 dark:text-indigo-300 border-r border-slate-200/60 dark:border-slate-800/60">
                                            L. Kepala (cm)
                                        </th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 border-r border-slate-200/60 dark:border-slate-800/60">
                                            L. Lengan (cm)
                                        </th>
                                        <th class="py-3 px-3 w-28 text-center uppercase tracking-wider text-slate-500" title="Indeks Massa Tubuh (Dihitung otomatis: Berat kg / (Tinggi m)²)">
                                            <div class="flex items-center justify-center gap-1 cursor-help">
                                                <span>IMT (BMI)</span>
                                                <i data-lucide="help-circle" class="w-3 h-3 text-slate-400"></i>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                                    @foreach($students as $st)
                                        @php
                                            $rec = $existingRecords[$st->id] ?? null;
                                            $hVal = $rec?->height ?? ($st->height ?: '');
                                            $wVal = $rec?->weight ?? ($st->weight ?: '');
                                            $lkVal = $rec?->head_circumference ?? ($st->head_circumference ?: '');
                                            $lilaVal = $rec?->arm_circumference ?? ($st->arm_circumference ?: '');
                                            $isBoy = ($st->gender === 'L' || $st->gender === 'Laki-laki');
                                        @endphp
                                        <tr x-data="{
                                                h: '{{ $hVal }}',
                                                w: '{{ $wVal }}',
                                                get bmi() {
                                                    const height = parseFloat(this.h);
                                                    const weight = parseFloat(this.w);
                                                    if (height > 0 && weight > 0) {
                                                        const m = height / 100;
                                                        return (weight / (m * m)).toFixed(1);
                                                    }
                                                    return null;
                                                }
                                            }"
                                            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                            <!-- NO -->
                                            <td class="py-2.5 px-3 text-center text-slate-400 dark:text-slate-500 font-mono text-[11px]">
                                                {{ $loop->iteration }}
                                            </td>

                                            <!-- NAMA MURID -->
                                            <td class="py-2.5 px-4 font-semibold text-slate-900 dark:text-slate-100">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0 {{ $isBoy ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300' : 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300' }}">
                                                        {{ mb_substr($st->full_name, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ $st->full_name }}</div>
                                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">NIS: {{ $st->nis ?: '-' }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- L/P (KOLOM TERSENDIRI) -->
                                            <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800/60">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md text-[11px] font-bold {{ $isBoy ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/80 dark:border-blue-800/60' : 'bg-pink-50 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300 border border-pink-200/80 dark:border-pink-800/60' }}">
                                                    {{ $isBoy ? 'L' : 'P' }}
                                                </span>
                                            </td>

                                            <!-- TINGGI (cm) -->
                                            <td class="py-2 px-3 text-center bg-sky-50/20 dark:bg-sky-950/10 border-x border-slate-100 dark:border-slate-800/60">
                                                <div class="relative max-w-[120px] mx-auto">
                                                    <input type="number" step="0.1" min="0" max="250"
                                                        name="ddtk[{{ $st->id }}][height]" 
                                                        x-model="h"
                                                        value="{{ $hVal }}"
                                                        placeholder="0.0"
                                                        class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                </div>
                                            </td>

                                            <!-- BERAT (kg) -->
                                            <td class="py-2 px-3 text-center bg-amber-50/20 dark:bg-amber-950/10 border-r border-slate-100 dark:border-slate-800/60">
                                                <div class="relative max-w-[120px] mx-auto">
                                                    <input type="number" step="0.1" min="0" max="150"
                                                        name="ddtk[{{ $st->id }}][weight]" 
                                                        x-model="w"
                                                        value="{{ $wVal }}"
                                                        placeholder="0.0"
                                                        class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                </div>
                                            </td>

                                            <!-- L. KEPALA (cm) -->
                                            <td class="py-2 px-3 text-center bg-indigo-50/20 dark:bg-indigo-950/10 border-r border-slate-100 dark:border-slate-800/60">
                                                <div class="relative max-w-[120px] mx-auto">
                                                    <input type="number" step="0.1" min="0" max="100"
                                                        name="ddtk[{{ $st->id }}][head_circumference]" 
                                                        value="{{ $lkVal }}"
                                                        placeholder="0.0"
                                                        class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                </div>
                                            </td>

                                            <!-- L. LENGAN (cm) -->
                                            <td class="py-2 px-3 text-center bg-emerald-50/20 dark:bg-emerald-950/10 border-r border-slate-100 dark:border-slate-800/60">
                                                <div class="relative max-w-[120px] mx-auto">
                                                    <input type="number" step="0.1" min="0" max="60"
                                                        name="ddtk[{{ $st->id }}][arm_circumference]" 
                                                        value="{{ $lilaVal }}"
                                                        placeholder="0.0"
                                                        class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                </div>
                                            </td>

                                            <!-- IMT (PREVIEW REAL-TIME) -->
                                            <td class="py-2 px-3 text-center font-mono text-[11px]">
                                                <template x-if="bmi">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700" x-text="bmi">
                                                    </span>
                                                </template>
                                                <template x-if="!bmi">
                                                    <span class="text-slate-300 dark:text-slate-600">-</span>
                                                </template>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- FOOTER INFO (SINGLE SAVE BUTTON IS AT THE TOP TOOLBAR) -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-2">
                                <i data-lucide="info" class="w-4 h-4 text-sky-500 shrink-0"></i>
                                <span>Nilai IMT terhitung otomatis secara real-time saat mengisi TB & BB. Klik tombol <strong>Simpan Data DDTK</strong> di atas untuk menyimpan.</span>
                            </div>
                            <span class="text-[11px] text-slate-400 shrink-0">Total: <strong class="text-slate-700 dark:text-slate-300">{{ $students->count() }}</strong> anak</span>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: INPUT SDIDTK (INLINE FORM)                                        -->
        <!-- Visus Mata(kanan,kiri), KPSP, Gigi, GDA, Gejala: Kusta, Frambusia, Catatan-->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'inputSDIDTK'" x-cloak class="space-y-4">
            
            <!-- FILTER BAR TAB 2 (KELAS, PERIODE, TANGGAL UKUR) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
                <form method="GET" action="{{ route('student-ddtk.index') }}" id="sdidtkFilterForm">
                    <input type="hidden" name="tab" value="inputSDIDTK">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full">
                        <!-- Filter Kelas -->
                        <div>
                            <label for="sdidtk_classroom_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Rombel / Kelas <span class="text-rose-500">*</span>
                            </label>
                            <select name="classroom_id" id="sdidtk_classroom_id" onchange="document.getElementById('sdidtkFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors cursor-pointer">
                                @foreach($classrooms as $c)
                                    <option value="{{ $c->id }}" {{ $selectedClassroomId == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} ({{ $c->students_count ?? $c->active_students_count }} Murid)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Periode -->
                        <div>
                            <label for="sdidtk_period" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Periode Pemeriksaan <span class="text-rose-500">*</span>
                            </label>
                            <select name="period" id="sdidtk_period" onchange="document.getElementById('sdidtkFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors cursor-pointer">
                                @foreach($availablePeriods as $pKey => $pLabel)
                                    <option value="{{ $pKey }}" {{ $selectedPeriod == $pKey ? 'selected' : '' }}>
                                        {{ $pLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Tanggal Pemeriksaan -->
                        <div>
                            <label for="sdidtk_date" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Tanggal Ukur <span class="text-slate-400 font-normal">(Default)</span>
                            </label>
                            <input type="date" name="date" id="sdidtk_date" value="{{ $selectedDate }}" onchange="document.getElementById('sdidtkFilterForm').submit()"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors cursor-pointer">
                        </div>
                    </div>
                </form>
            </div>

            <!-- ULTRA-COMPACT SDIDTK SUMMARY STRIP -->
            <section class="flex flex-wrap items-center gap-2 sm:gap-2.5 w-full text-xs">
                <!-- Total Murid -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Murid:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-50">{{ $totalStudents }} Anak</span>
                </div>

                <!-- Diperiksa SDIDTK -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-900/40 rounded-lg text-emerald-800 dark:text-emerald-300 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-[11px] font-medium">Terskrining SDIDTK:</span>
                    <span class="font-bold font-mono">{{ $sdidtkCount }}/{{ $totalStudents }}</span>
                    <span class="text-[10px] font-semibold text-emerald-600/80 dark:text-emerald-400/80">({{ $sdidtkPct }}%)</span>
                </div>

                <!-- Periode Aktif -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/60 dark:border-purple-900/40 rounded-lg text-purple-800 dark:text-purple-300 shadow-2xs">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400 shrink-0"></i>
                    <span class="text-[11px] font-medium">Periode:</span>
                    <span class="font-bold">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</span>
                </div>
            </section>
            @if($students->isEmpty())
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center">
                    <i data-lucide="users-round" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                    <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Tidak ada murid aktif dalam rombel ini</h4>
                </div>
            @else
                <form method="POST" action="{{ route('student-ddtk.store-sdidtk') }}">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
                    <input type="hidden" name="period" value="{{ $selectedPeriod }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    <!-- FORM TOOLBAR -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-800/60">
                                <i data-lucide="stethoscope" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                                    Input Skrining Stimulasi, Deteksi & Intervensi Dini (SDIDTK)
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Periode: <strong class="text-emerald-600 dark:text-emerald-400">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</strong> &bull; Rombel: <strong class="text-slate-700 dark:text-slate-300">{{ $selectedClassroom?->name ?? '-' }}</strong>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Tombol Simpan -->
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold rounded-lg shadow-xs hover:shadow transition-all cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Simpan Data SDIDTK</span>
                            </button>
                        </div>
                    </div>

                    <!-- INLINE TABLE SDIDTK -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <!-- ROW 1 HEADER -->
                                    <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[11px] font-bold border-b border-slate-200 dark:border-slate-800">
                                        <th rowspan="2" class="py-3 px-3 w-10 text-center uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">No</th>
                                        <th rowspan="2" class="py-3 px-4 min-w-[200px] uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">Nama Murid</th>
                                        <th rowspan="2" class="py-3 px-2 w-14 text-center uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">L/P</th>
                                        <!-- Visus Mata (Kanan, Kiri) -->
                                        <th colspan="2" class="py-2 px-2 text-center uppercase tracking-wider border-b border-r border-slate-200 dark:border-slate-800 bg-sky-50/50 dark:bg-sky-950/20 text-sky-800 dark:text-sky-300">
                                            Visus Mata
                                        </th>
                                        <th rowspan="2" class="py-3 px-3 w-36 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800 text-center">
                                            KPSP
                                        </th>
                                        <th rowspan="2" class="py-3 px-3 w-36 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800 text-center">
                                            Gigi
                                        </th>
                                        <th rowspan="2" class="py-3 px-3 w-28 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800 text-center">
                                            GDA (mg/dL)
                                        </th>
                                        <!-- Gejala (Kusta, Frambusia) -->
                                        <th colspan="2" class="py-2 px-2 text-center uppercase tracking-wider border-b border-r border-slate-200 dark:border-slate-800 bg-rose-50/50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-300">
                                            Gejala (Ya/Tidak)
                                        </th>
                                        <th rowspan="2" class="py-3 px-4 min-w-[180px] uppercase tracking-wider">Catatan</th>
                                    </tr>
                                    <!-- ROW 2 SUB-HEADER -->
                                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                                        <th class="py-2 px-2 w-24 text-center border-r border-slate-200 dark:border-slate-800 text-sky-700 dark:text-sky-300">Kanan</th>
                                        <th class="py-2 px-2 w-24 text-center border-r border-slate-200 dark:border-slate-800 text-sky-700 dark:text-sky-300">Kiri</th>
                                        <th class="py-2 px-2 w-20 text-center border-r border-slate-200 dark:border-slate-800 text-rose-700 dark:text-rose-300">Kusta</th>
                                        <th class="py-2 px-2 w-20 text-center border-r border-slate-200 dark:border-slate-800 text-rose-700 dark:text-rose-300">Frambusia</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                                    @foreach($students as $st)
                                        @php
                                            $rec = $existingRecords[$st->id] ?? null;
                                            $isBoy = ($st->gender === 'L' || $st->gender === 'Laki-laki');
                                        @endphp
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                            <!-- NO -->
                                            <td class="py-2.5 px-3 text-center text-slate-400 dark:text-slate-500 font-mono text-[11px] border-r border-slate-100 dark:border-slate-800">
                                                {{ $loop->iteration }}
                                            </td>

                                            <!-- NAMA MURID -->
                                            <td class="py-2.5 px-4 font-semibold text-slate-900 dark:text-slate-100 border-r border-slate-100 dark:border-slate-800">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0 {{ $isBoy ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300' }}">
                                                        {{ mb_substr($st->full_name, 0, 1) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="truncate font-semibold">{{ $st->full_name }}</div>
                                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">NIS: {{ $st->nis ?: '-' }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- L/P (KOLOM TERSENDIRI) -->
                                            <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-md text-[11px] font-bold {{ $isBoy ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/80 dark:border-blue-800/60' : 'bg-pink-50 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300 border border-pink-200/80 dark:border-pink-800/60' }}">
                                                    {{ $isBoy ? 'L' : 'P' }}
                                                </span>
                                            </td>

                                            <!-- VISUS MATA: KANAN -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800">
                                                <input type="text"
                                                    name="sdidtk[{{ $st->id }}][visus_right]"
                                                    value="{{ $rec?->visus_right ?? '' }}"
                                                    placeholder="Normal / 6/6"
                                                    class="w-full text-center px-2 py-1.5 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                            </td>

                                            <!-- VISUS MATA: KIRI -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800">
                                                <input type="text"
                                                    name="sdidtk[{{ $st->id }}][visus_left]"
                                                    value="{{ $rec?->visus_left ?? '' }}"
                                                    placeholder="Normal / 6/6"
                                                    class="w-full text-center px-2 py-1.5 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                            </td>

                                            <!-- KPSP -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800">
                                                <select name="sdidtk[{{ $st->id }}][kpsp_result]"
                                                    class="w-full px-2 py-1.5 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors cursor-pointer">
                                                    <option value="">- Pilih KPSP -</option>
                                                    <option value="Sesuai" {{ ($rec?->kpsp_result ?? '') === 'Sesuai' ? 'selected' : '' }}>Sesuai (S)</option>
                                                    <option value="Meragukan" {{ ($rec?->kpsp_result ?? '') === 'Meragukan' ? 'selected' : '' }}>Meragukan (M)</option>
                                                    <option value="Penyimpangan" {{ ($rec?->kpsp_result ?? '') === 'Penyimpangan' ? 'selected' : '' }}>Penyimpangan (P)</option>
                                                </select>
                                            </td>

                                            <!-- GIGI -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800">
                                                <input type="text"
                                                    name="sdidtk[{{ $st->id }}][dental_health]"
                                                    value="{{ $rec?->dental_health ?? '' }}"
                                                    placeholder="Sehat / Karies"
                                                    class="w-full px-2 py-1.5 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                            </td>

                                            <!-- GDA (Gula Darah Acak) -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800 text-center">
                                                <input type="number" step="0.1" min="0" max="600"
                                                    name="sdidtk[{{ $st->id }}][blood_sugar]"
                                                    value="{{ $rec?->blood_sugar !== null ? $rec->blood_sugar : '' }}"
                                                    placeholder="mg/dL"
                                                    class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            </td>

                                            <!-- GEJALA KUSTA (YA/TIDAK) -->
                                            <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                                <label class="inline-flex items-center justify-center gap-1.5 px-2 py-1 rounded-md bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 hover:border-rose-400 cursor-pointer transition-colors">
                                                    <input type="checkbox"
                                                        name="sdidtk[{{ $st->id }}][has_leprosy]"
                                                        value="1"
                                                        {{ $rec?->has_leprosy ? 'checked' : '' }}
                                                        class="rounded border-slate-300 dark:border-slate-600 text-rose-600 focus:ring-rose-500 w-3.5 h-3.5 cursor-pointer">
                                                    <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Ya</span>
                                                </label>
                                            </td>

                                            <!-- GEJALA FRAMBUSIA (YA/TIDAK) -->
                                            <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                                <label class="inline-flex items-center justify-center gap-1.5 px-2 py-1 rounded-md bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 hover:border-rose-400 cursor-pointer transition-colors">
                                                    <input type="checkbox"
                                                        name="sdidtk[{{ $st->id }}][has_frambesia]"
                                                        value="1"
                                                        {{ $rec?->has_frambesia ? 'checked' : '' }}
                                                        class="rounded border-slate-300 dark:border-slate-600 text-rose-600 focus:ring-rose-500 w-3.5 h-3.5 cursor-pointer">
                                                    <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Ya</span>
                                                </label>
                                            </td>

                                            <!-- CATATAN -->
                                            <td class="py-2 px-4" x-data="{ note: '{{ addslashes($rec?->notes ?? '') }}' }">
                                                <div class="relative w-full flex items-center">
                                                    <input type="text"
                                                        name="sdidtk[{{ $st->id }}][notes]"
                                                        x-model="note"
                                                        placeholder="Catatan hasil skrining..."
                                                        class="w-full pl-3 pr-8 py-1.5 text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                                    
                                                    <!-- Clear note button (x) -->
                                                    <button type="button" @click="note = ''" title="Hapus catatan"
                                                        x-show="note && note.length > 0"
                                                        x-cloak
                                                        class="absolute right-1.5 p-1 text-slate-400 hover:text-rose-500 transition-colors rounded cursor-pointer">
                                                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- FOOTER INFO (SINGLE SAVE BUTTON IS AT THE TOP TOOLBAR) -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-2">
                                <i data-lucide="info" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                                <span>Centang kotak "Ya" bila ditemukan indikasi gejala klinis Kusta atau Frambusia pada murid. Klik tombol <strong>Simpan Data SDIDTK</strong> di atas untuk menyimpan.</span>
                            </div>
                            <span class="text-[11px] text-slate-400 shrink-0">Total: <strong class="text-slate-700 dark:text-slate-300">{{ $students->count() }}</strong> anak</span>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: REKAP DATA                                                        -->
        <!-- Nis, Nama Murid, Pertumbuhan(TB,BB,L.KA,L.LE), Visus, KPSP, Gigi, GDA,   -->
        <!-- Gejala: Kusta, Frambusia, Catatan                                         -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'rekap'" x-cloak class="space-y-4">
            
            <!-- FILTER BAR TAB 3: KELAS & PERIODE + EXPORT BUTTONS (SERASIKAN DENGAN REKAP BULANAN ABSENSI) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
                <form method="GET" action="{{ route('student-ddtk.index') }}" id="rekapFilterForm" class="flex flex-col lg:flex-row lg:items-end justify-between gap-3">
                    <input type="hidden" name="tab" value="rekap">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1">
                        <!-- Filter Kelas -->
                        <div>
                            <label for="rekap_classroom_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Rombel / Kelas <span class="text-rose-500">*</span>
                            </label>
                            <select name="classroom_id" id="rekap_classroom_id" onchange="document.getElementById('rekapFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                                @foreach($classrooms as $c)
                                    <option value="{{ $c->id }}" {{ $selectedClassroomId == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} ({{ $c->students_count ?? $c->active_students_count }} Murid)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Periode -->
                        <div>
                            <label for="rekap_period" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Periode Pemeriksaan <span class="text-rose-500">*</span>
                            </label>
                            <select name="period" id="rekap_period" onchange="document.getElementById('rekapFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors cursor-pointer">
                                @foreach($availablePeriods as $pKey => $pLabel)
                                    <option value="{{ $pKey }}" {{ $selectedPeriod == $pKey ? 'selected' : '' }}>
                                        {{ $pLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- EXPORT & PRINT BUTTONS -->
                    <div class="flex flex-wrap items-center gap-2 pt-1 lg:pt-0 shrink-0">
                        <!-- Tombol Ekspor Excel -->
                        <a href="{{ route('student-ddtk.export.excel', ['classroom_id' => $selectedClassroomId, 'period' => $selectedPeriod]) }}"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer">
                            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                            <span>Ekspor Excel</span>
                        </a>

                        <!-- Tombol Cetak PDF -->
                        <a href="{{ route('student-ddtk.print', ['classroom_id' => $selectedClassroomId, 'period' => $selectedPeriod]) }}"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors cursor-pointer">
                            <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>Cetak PDF</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- ULTRA-COMPACT REKAP SUMMARY STRIP -->
            <section class="flex flex-wrap items-center gap-2 sm:gap-2.5 w-full text-xs">
                <!-- Total Murid -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Murid:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-50">{{ $totalStudents }} Anak</span>
                </div>

                <!-- Terukur DDTK -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-sky-50/70 dark:bg-sky-950/30 border border-sky-200/60 dark:border-sky-900/40 rounded-lg text-sky-800 dark:text-sky-300 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                    <span class="text-[11px] font-medium">Terukur DDTK:</span>
                    <span class="font-bold font-mono">{{ $ddtkCount }}/{{ $totalStudents }}</span>
                    <span class="text-[10px] font-semibold text-sky-600/80 dark:text-sky-400/80">({{ $ddtkPct }}%)</span>
                </div>

                <!-- Terskrining SDIDTK -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-900/40 rounded-lg text-emerald-800 dark:text-emerald-300 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-[11px] font-medium">Terskrining SDIDTK:</span>
                    <span class="font-bold font-mono">{{ $sdidtkCount }}/{{ $totalStudents }}</span>
                    <span class="text-[10px] font-semibold text-emerald-600/80 dark:text-emerald-400/80">({{ $sdidtkPct }}%)</span>
                </div>

                <!-- Rerata TB -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5 text-sky-500 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Rerata TB:</span>
                    <span class="font-bold font-mono text-sky-700 dark:text-sky-300">{{ $avgHeight }} cm</span>
                </div>

                <!-- Rerata BB -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 shadow-2xs">
                    <i data-lucide="scale" class="w-3.5 h-3.5 text-amber-500 shrink-0"></i>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Rerata BB:</span>
                    <span class="font-bold font-mono text-amber-700 dark:text-amber-300">{{ $avgWeight }} kg</span>
                </div>

                <!-- Periode Aktif -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/60 dark:border-purple-900/40 rounded-lg text-purple-800 dark:text-purple-300 shadow-2xs">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400 shrink-0"></i>
                    <span class="text-[11px] font-medium">Periode:</span>
                    <span class="font-bold">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</span>
                </div>
            </section>

            <!-- TABLE REKAP -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                <div class="p-3 sm:p-3.5 bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-800/60">
                        <i data-lucide="table-2" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                            Rekapitulasi DDTK & SDIDTK: {{ $selectedClassroom?->name }}
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            Periode: <strong class="text-indigo-600 dark:text-indigo-400">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</strong> &bull; Total: <strong class="text-slate-700 dark:text-slate-300">{{ $students->count() }} Murid</strong>
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[11px] font-bold border-b border-slate-200 dark:border-slate-800">
                                <th rowspan="2" class="py-3 px-3 w-10 text-center uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">No</th>
                                <th rowspan="2" class="py-3 px-3 w-24 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">NIS</th>
                                <th rowspan="2" class="py-3 px-4 min-w-[200px] uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">Nama Murid</th>
                                <th rowspan="2" class="py-3 px-2 w-14 text-center uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">L/P</th>
                                <!-- Pertumbuhan (DDTK): TB, BB, L.KA, L.LE -->
                                <th colspan="4" class="py-2 px-2 text-center uppercase tracking-wider border-b border-r border-slate-200 dark:border-slate-800 bg-sky-50/50 dark:bg-sky-950/20 text-sky-800 dark:text-sky-300">
                                    Pertumbuhan (DDTK)
                                </th>
                                <!-- Visus Mata: Kanan, Kiri -->
                                <th colspan="2" class="py-2 px-2 text-center uppercase tracking-wider border-b border-r border-slate-200 dark:border-slate-800 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-800 dark:text-indigo-300">
                                    Visus Mata
                                </th>
                                <th rowspan="2" class="py-3 px-3 w-28 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800 text-center">KPSP</th>
                                <th rowspan="2" class="py-3 px-3 w-28 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800 text-center">Gigi</th>
                                <th rowspan="2" class="py-3 px-3 w-24 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800 text-center">GDA</th>
                                <!-- Gejala (Ya/Tidak): Kusta, Frambusia -->
                                <th colspan="2" class="py-2 px-2 text-center uppercase tracking-wider border-b border-r border-slate-200 dark:border-slate-800 bg-rose-50/50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-300">
                                    Gejala
                                </th>
                                <th rowspan="2" class="py-3 px-4 min-w-[150px] uppercase tracking-wider">Catatan</th>
                            </tr>
                            <tr class="bg-slate-50/80 dark:bg-slate-800/60 text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-sky-700 dark:text-sky-300">TB</th>
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-sky-700 dark:text-sky-300">BB</th>
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-sky-700 dark:text-sky-300">L.KA</th>
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-sky-700 dark:text-sky-300">L.LE</th>
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-indigo-700 dark:text-indigo-300">Kanan</th>
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-indigo-700 dark:text-indigo-300">Kiri</th>
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-rose-700 dark:text-rose-300">Kusta</th>
                                <th class="py-1.5 px-2 text-center border-r border-slate-200 dark:border-slate-800 text-rose-700 dark:text-rose-300">Frambusia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($students as $st)
                                @php
                                    $rec = $existingRecords[$st->id] ?? null;
                                    $isBoy = ($st->gender === 'L' || $st->gender === 'Laki-laki');
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px] border-r border-slate-100 dark:border-slate-800">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-semibold text-slate-700 dark:text-slate-300 border-r border-slate-100 dark:border-slate-800">
                                        {{ $st->nis ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 font-semibold text-slate-900 dark:text-slate-100 border-r border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 {{ $isBoy ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300' : 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300' }}">
                                                {{ mb_substr($st->full_name, 0, 1) }}
                                            </div>
                                            <span class="truncate">{{ $st->full_name }}</span>
                                        </div>
                                    </td>
                                    <!-- L/P (KOLOM TERSENDIRI) -->
                                    <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-md text-[11px] font-bold {{ $isBoy ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/80 dark:border-blue-800/60' : 'bg-pink-50 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300 border border-pink-200/80 dark:border-pink-800/60' }}">
                                            {{ $isBoy ? 'L' : 'P' }}
                                        </span>
                                    </td>
                                    <!-- TB -->
                                    <td class="py-2.5 px-2 text-center font-mono font-bold text-sky-700 dark:text-sky-300 border-r border-slate-100 dark:border-slate-800 bg-sky-50/10">
                                        {{ $rec?->height ? $rec->height . ' cm' : '-' }}
                                    </td>
                                    <!-- BB -->
                                    <td class="py-2.5 px-2 text-center font-mono font-bold text-amber-700 dark:text-amber-300 border-r border-slate-100 dark:border-slate-800 bg-amber-50/10">
                                        {{ $rec?->weight ? $rec->weight . ' kg' : '-' }}
                                    </td>
                                    <!-- L.KA -->
                                    <td class="py-2.5 px-2 text-center font-mono text-slate-700 dark:text-slate-300 border-r border-slate-100 dark:border-slate-800">
                                        {{ $rec?->head_circumference ? $rec->head_circumference . ' cm' : '-' }}
                                    </td>
                                    <!-- L.LE -->
                                    <td class="py-2.5 px-2 text-center font-mono text-slate-700 dark:text-slate-300 border-r border-slate-100 dark:border-slate-800">
                                        {{ $rec?->arm_circumference ? $rec->arm_circumference . ' cm' : '-' }}
                                    </td>
                                    <!-- Visus Kanan -->
                                    <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                        {{ $rec?->visus_right ?: '-' }}
                                    </td>
                                    <!-- Visus Kiri -->
                                    <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                        {{ $rec?->visus_left ?: '-' }}
                                    </td>
                                    <!-- KPSP -->
                                    <td class="py-2.5 px-3 text-center border-r border-slate-100 dark:border-slate-800">
                                        @if($rec?->kpsp_result)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                                {{ $rec->kpsp_result === 'Sesuai' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60' : ($rec->kpsp_result === 'Meragukan' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/60') }}">
                                                {{ $rec->kpsp_result }}
                                            </span>
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600">-</span>
                                        @endif
                                    </td>
                                    <!-- Gigi -->
                                    <td class="py-2.5 px-3 text-center border-r border-slate-100 dark:border-slate-800">
                                        {{ $rec?->dental_health ?: '-' }}
                                    </td>
                                    <!-- GDA -->
                                    <td class="py-2.5 px-3 text-center font-mono border-r border-slate-100 dark:border-slate-800">
                                        {{ $rec?->blood_sugar !== null ? $rec->blood_sugar . ' mg/dL' : '-' }}
                                    </td>
                                    <!-- Kusta -->
                                    <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                        @if($rec)
                                            @if($rec->has_leprosy)
                                                <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                                    YA
                                                </span>
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500 font-medium text-[11px]">TIDAK</span>
                                            @endif
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600">-</span>
                                        @endif
                                    </td>
                                    <!-- Frambusia -->
                                    <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                        @if($rec)
                                            @if($rec->has_frambesia)
                                                <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                                    YA
                                                </span>
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500 font-medium text-[11px]">TIDAK</span>
                                            @endif
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600">-</span>
                                        @endif
                                    </td>
                                    <!-- Catatan -->
                                    <td class="py-2.5 px-4 text-slate-600 dark:text-slate-400">
                                        {{ $rec?->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="py-8 text-center text-slate-400">
                                        <i data-lucide="inbox" class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600 mb-2"></i>
                                        <p class="font-medium text-xs">Tidak ada data murid pada rombel ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- TABLE FOOTER -->
                    <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-2">
                            <span>Total Murid: <strong class="text-slate-700 dark:text-slate-300">{{ $students->count() }}</strong> anak</span>
                            <span>&bull;</span>
                            <span>Rombel: <strong class="text-slate-700 dark:text-slate-300">{{ $selectedClassroom?->name ?? '-' }}</strong></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                Terukur DDTK: <strong class="text-slate-700 dark:text-slate-300">{{ $ddtkCount }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Terskrining SDIDTK: <strong class="text-slate-700 dark:text-slate-300">{{ $sdidtkCount }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: GRAFIK PERTUMBUHAN                                                -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'grafik'" x-cloak class="space-y-4">
            
            <!-- FILTER BAR TAB 4 (KELAS) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
                <form method="GET" action="{{ route('student-ddtk.index') }}" id="grafikFilterForm">
                    <input type="hidden" name="tab" value="grafik">

                    <div class="max-w-xs">
                        <label for="grafik_classroom_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Rombel / Kelas <span class="text-rose-500">*</span>
                        </label>
                        <select name="classroom_id" id="grafik_classroom_id" onchange="document.getElementById('grafikFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors cursor-pointer">
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}" {{ $selectedClassroomId == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->students_count ?? $c->active_students_count }} Murid)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- GRAFIK HEADER / SELECTOR -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-purple-600"></i>
                        <span>Tren Pertumbuhan Antropometri & Kurva Perkembangan</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Memantau peningkatan rata-rata tinggi badan, berat badan, lingkar kepala, dan lingkar lengan dari waktu ke waktu.
                    </p>
                </div>
                <!-- Metric View Switcher -->
                <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 p-0.5 bg-slate-100 dark:bg-slate-800 shrink-0">
                    <button type="button" @click="setMetric('tb_bb')"
                        :class="selectedMetric === 'tb_bb' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-2xs font-bold' : 'text-slate-500 dark:text-slate-400 font-medium'"
                        class="px-3 py-1 text-xs rounded-md transition-all cursor-pointer">
                        TB & BB
                    </button>
                    <button type="button" @click="setMetric('lk_lila')"
                        :class="selectedMetric === 'lk_lila' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-2xs font-bold' : 'text-slate-500 dark:text-slate-400 font-medium'"
                        class="px-3 py-1 text-xs rounded-md transition-all cursor-pointer">
                        L. Kepala & LiLA
                    </button>
                </div>
            </div>

            <!-- CHART CANVAS CONTAINER -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="h-72 sm:h-80 w-full relative">
                    <canvas id="growthChartCanvas"></canvas>
                </div>
            </div>

            <!-- GROWTH DATA TABLE PER PERIODE -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800">
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                        Histori Rata-Rata Pertumbuhan Rombel: {{ $selectedClassroom?->name }}
                    </h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <th class="py-2.5 px-4">Periode</th>
                                <th class="py-2.5 px-3 text-center text-sky-700 dark:text-sky-300">Rata-rata TB</th>
                                <th class="py-2.5 px-3 text-center text-amber-700 dark:text-amber-300">Rata-rata BB</th>
                                <th class="py-2.5 px-3 text-center text-indigo-700 dark:text-indigo-300">Rata-rata L. Kepala</th>
                                <th class="py-2.5 px-3 text-center text-emerald-700 dark:text-emerald-300">Rata-rata LiLA</th>
                                <th class="py-2.5 px-4 text-center">Murid Diperiksa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($chartHistory as $h)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                    <td class="py-2.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $h['label'] }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono font-bold text-sky-600">
                                        {{ $h['avg_height'] ? $h['avg_height'] . ' cm' : '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono font-bold text-amber-600">
                                        {{ $h['avg_weight'] ? $h['avg_weight'] . ' kg' : '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono text-indigo-600">
                                        {{ $h['avg_head'] ? $h['avg_head'] . ' cm' : '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono text-emerald-600">
                                        {{ $h['avg_arm'] ? $h['avg_arm'] . ' cm' : '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 text-center font-mono">
                                        {{ $h['tested_count'] }} Murid
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">
                                        Belum ada riwayat pengukuran pertumbuhan pada rombel ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <!-- Chart.js CDN for Interactive Growth Graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ALPINE.JS COMPONENT -->
    <script>
        function ddtkApp(config) {
            return {
                activeTab: config.initialTab || 'inputDDTK',
                classroomId: config.classroomId,
                selectedPeriod: config.selectedPeriod,
                selectedDate: config.selectedDate,
                chartHistory: config.chartHistory || [],
                selectedMetric: 'tb_bb',
                chartInstance: null,

                init() {
                    this.$watch('activeTab', (val) => {
                        if (val === 'grafik') {
                            this.$nextTick(() => this.renderChart());
                        }
                    });

                    if (this.activeTab === 'grafik') {
                        this.$nextTick(() => this.renderChart());
                    }
                },

                setTab(tabName) {
                    this.activeTab = tabName;
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabName);
                    window.history.replaceState({}, '', url);
                },

                setMetric(metric) {
                    this.selectedMetric = metric;
                    this.renderChart();
                },

                renderChart() {
                    const ctx = document.getElementById('growthChartCanvas');
                    if (!ctx) return;

                    if (this.chartInstance) {
                        this.chartInstance.destroy();
                    }

                    const labels = this.chartHistory.map(item => item.label);
                    let datasets = [];

                    if (this.selectedMetric === 'tb_bb') {
                        datasets = [
                            {
                                label: 'Tinggi Badan Rata-Rata (cm)',
                                data: this.chartHistory.map(item => item.avg_height),
                                borderColor: '#0284c7',
                                backgroundColor: 'rgba(2, 132, 199, 0.1)',
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                            },
                            {
                                label: 'Berat Badan Rata-Rata (kg)',
                                data: this.chartHistory.map(item => item.avg_weight),
                                borderColor: '#d97706',
                                backgroundColor: 'rgba(217, 119, 6, 0.1)',
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y1',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                            }
                        ];
                    } else {
                        datasets = [
                            {
                                label: 'Lingkar Kepala Rata-Rata (cm)',
                                data: this.chartHistory.map(item => item.avg_head),
                                borderColor: '#4f46e5',
                                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                            },
                            {
                                label: 'Lingkar Lengan (LiLA) Rata-Rata (cm)',
                                data: this.chartHistory.map(item => item.avg_arm),
                                borderColor: '#059669',
                                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                            }
                        ];
                    }

                    this.chartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        font: { size: 11, family: 'Inter, sans-serif' }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    titleFont: { size: 12, weight: 'bold' },
                                    bodyFont: { size: 11 },
                                    padding: 10,
                                    cornerRadius: 8,
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 10 } }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: {
                                        display: true,
                                        text: this.selectedMetric === 'tb_bb' ? 'Tinggi (cm)' : 'Ukuran (cm)',
                                        font: { size: 10 }
                                    },
                                    grid: { color: 'rgba(203, 213, 225, 0.3)' }
                                },
                                y1: {
                                    type: 'linear',
                                    display: this.selectedMetric === 'tb_bb',
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: 'Berat (kg)',
                                        font: { size: 10 }
                                    },
                                    grid: { drawOnChartArea: false }
                                }
                            }
                        }
                    });
                }
            };
        }
    </script>
</x-admin-layout>
