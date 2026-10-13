<x-admin-layout>
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
                    <span class="font-bold">Terjadi kesalahan validasi:</span>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- FILTER BAR (KELAS, PERIODE, TANGGAL) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
            <form method="GET" action="{{ route('student-ddtk.index') }}" id="ddtkFilterForm" class="flex flex-col md:flex-row md:items-end justify-between gap-3">
                <input type="hidden" name="tab" :value="activeTab">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
                    <!-- Filter Kelas -->
                    <div>
                        <label for="classroom_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Rombel / Kelas <span class="text-rose-500">*</span>
                        </label>
                        <select name="classroom_id" id="classroom_id" onchange="document.getElementById('ddtkFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors cursor-pointer">
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

                    <!-- Filter Periode -->
                    <div>
                        <label for="period" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Periode Pemeriksaan <span class="text-rose-500">*</span>
                        </label>
                        <select name="period" id="period" onchange="document.getElementById('ddtkFilterForm').submit()"
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
                        <label for="date" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Tanggal Ukur <span class="text-slate-400 font-normal">(Default)</span>
                        </label>
                        <input type="date" name="date" id="date" value="{{ $selectedDate }}" onchange="document.getElementById('ddtkFilterForm').submit()"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-colors cursor-pointer">
                    </div>
                </div>

                <div class="flex items-end gap-2 shrink-0">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-semibold rounded-lg transition-colors cursor-pointer shadow-xs">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Terapkan</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- SUMMARY STATS OVERVIEW CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-2.5">
            <!-- Total Murid -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 sm:p-3 shadow-xs">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Murid</p>
                <div class="flex items-baseline justify-between mt-1">
                    <h4 class="text-lg sm:text-xl font-bold font-mono text-slate-900 dark:text-slate-100">{{ $totalStudents }}</h4>
                    <span class="text-[10px] text-slate-400 font-medium">Anak</span>
                </div>
            </div>

            <!-- Diukur DDTK -->
            <div class="bg-sky-50/60 dark:bg-sky-950/20 border border-sky-200/80 dark:border-sky-800/60 rounded-xl p-2.5 sm:p-3 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-sky-700 dark:text-sky-400">Data DDTK</p>
                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                </div>
                <div class="flex items-baseline justify-between mt-1">
                    <h4 class="text-lg sm:text-xl font-bold font-mono text-sky-700 dark:text-sky-300">{{ $ddtkCount }}</h4>
                    <span class="text-[10px] text-sky-600 dark:text-sky-400 font-medium">/ {{ $totalStudents }}</span>
                </div>
            </div>

            <!-- Diperiksa SDIDTK -->
            <div class="bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/60 rounded-xl p-2.5 sm:p-3 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Data SDIDTK</p>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
                <div class="flex items-baseline justify-between mt-1">
                    <h4 class="text-lg sm:text-xl font-bold font-mono text-emerald-700 dark:text-emerald-300">{{ $sdidtkCount }}</h4>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">/ {{ $totalStudents }}</span>
                </div>
            </div>

            <!-- Rata-rata TB -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 sm:p-3 shadow-xs">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rata-rata TB</p>
                <div class="flex items-baseline justify-between mt-1">
                    <h4 class="text-lg sm:text-xl font-bold font-mono text-slate-900 dark:text-slate-100">{{ $avgHeight }}</h4>
                    <span class="text-[10px] text-slate-400 font-medium">cm</span>
                </div>
            </div>

            <!-- Rata-rata BB -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 sm:p-3 shadow-xs">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rata-rata BB</p>
                <div class="flex items-baseline justify-between mt-1">
                    <h4 class="text-lg sm:text-xl font-bold font-mono text-slate-900 dark:text-slate-100">{{ $avgWeight }}</h4>
                    <span class="text-[10px] text-slate-400 font-medium">kg</span>
                </div>
            </div>

            <!-- Periode Info -->
            <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl p-2.5 sm:p-3 shadow-xs">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Periode Aktif</p>
                <div class="flex items-baseline justify-between mt-1">
                    <h4 class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200 truncate">
                        {{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}
                    </h4>
                </div>
            </div>
        </div>

        <!-- 4 TAB NAVIGATION -->
        <div class="border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4 overflow-x-auto">
            <nav class="flex space-x-1 sm:space-x-2 shrink-0" aria-label="Tabs">
                <!-- Tab 1: Input DDTK -->
                <button type="button" @click="setTab('inputDDTK')"
                    :class="activeTab === 'inputDDTK' 
                        ? 'border-sky-600 text-sky-600 dark:text-sky-400 dark:border-sky-400 bg-sky-50/40 dark:bg-sky-950/20' 
                        : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
                    class="group inline-flex items-center gap-2 py-2.5 px-3 sm:px-4 border-b-2 font-semibold text-xs sm:text-sm rounded-t-lg transition-colors cursor-pointer">
                    <i data-lucide="ruler" class="w-4 h-4"></i>
                    <span>Input DDTK (Antropometri)</span>
                    <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-sky-100 text-sky-700 dark:bg-sky-900/60 dark:text-sky-300 font-bold">
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
                    <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold">
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
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: INPUT DDTK (INLINE FORM)                                          -->
        <!-- Nama Murid, Tinggi(cm), Berat(kg), L.Kepala(cm), L.Lengan(cm)           -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'inputDDTK'" x-cloak class="space-y-4">
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
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Input Antropometri Periode: <strong class="text-sky-600 dark:text-sky-400">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</strong>
                            </span>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-all cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan Data DDTK</span>
                        </button>
                    </div>

                    <!-- INLINE TABLE DDTK -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[11px] font-bold border-b border-slate-200 dark:border-slate-800">
                                        <th class="py-3 px-3 w-12 text-center uppercase tracking-wider">No</th>
                                        <th class="py-3 px-4 min-w-[200px] uppercase tracking-wider">Nama Murid</th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-sky-50/50 dark:bg-sky-950/20 text-sky-800 dark:text-sky-300">
                                            Tinggi (cm)
                                        </th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-amber-50/50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300">
                                            Berat (kg)
                                        </th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-800 dark:text-indigo-300">
                                            L. Kepala (cm)
                                        </th>
                                        <th class="py-3 px-3 w-36 text-center uppercase tracking-wider bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300">
                                            L. Lengan (cm)
                                        </th>
                                        <th class="py-3 px-3 w-28 text-center uppercase tracking-wider text-slate-500">
                                            IMT (BMI)
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
                                        @endphp
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                            <!-- NO -->
                                            <td class="py-2.5 px-3 text-center text-slate-400 dark:text-slate-500 font-mono text-[11px]">
                                                {{ $loop->iteration }}
                                            </td>

                                            <!-- NAMA MURID -->
                                            <td class="py-2.5 px-4 font-semibold text-slate-900 dark:text-slate-100">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="truncate">{{ $st->full_name }}</span>
                                                            <span class="px-1.5 py-0.2 rounded text-[9.5px] font-semibold {{ $st->gender === 'L' || $st->gender === 'Laki-laki' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300' : 'bg-pink-50 text-pink-700 dark:bg-pink-950/50 dark:text-pink-300' }}">
                                                                {{ $st->gender === 'L' || $st->gender === 'Laki-laki' ? 'L' : 'P' }}
                                                            </span>
                                                        </div>
                                                        <span class="text-[10px] text-slate-400 font-mono">NIS: {{ $st->nis ?: '-' }}</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- TINGGI (cm) -->
                                            <td class="py-2 px-3 text-center bg-sky-50/20 dark:bg-sky-950/10">
                                                <input type="number" step="0.1" min="0" max="250"
                                                    name="ddtk[{{ $st->id }}][height]" 
                                                    value="{{ $hVal }}"
                                                    placeholder="0.0"
                                                    class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                            </td>

                                            <!-- BERAT (kg) -->
                                            <td class="py-2 px-3 text-center bg-amber-50/20 dark:bg-amber-950/10">
                                                <input type="number" step="0.1" min="0" max="150"
                                                    name="ddtk[{{ $st->id }}][weight]" 
                                                    value="{{ $wVal }}"
                                                    placeholder="0.0"
                                                    class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                            </td>

                                            <!-- L. KEPALA (cm) -->
                                            <td class="py-2 px-3 text-center bg-indigo-50/20 dark:bg-indigo-950/10">
                                                <input type="number" step="0.1" min="0" max="100"
                                                    name="ddtk[{{ $st->id }}][head_circumference]" 
                                                    value="{{ $lkVal }}"
                                                    placeholder="0.0"
                                                    class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                            </td>

                                            <!-- L. LENGAN (cm) -->
                                            <td class="py-2 px-3 text-center bg-emerald-50/20 dark:bg-emerald-950/10">
                                                <input type="number" step="0.1" min="0" max="60"
                                                    name="ddtk[{{ $st->id }}][arm_circumference]" 
                                                    value="{{ $lilaVal }}"
                                                    placeholder="0.0"
                                                    class="w-full text-center px-2 py-1.5 text-xs font-mono font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                            </td>

                                            <!-- IMT (PREVIEW) -->
                                            <td class="py-2 px-3 text-center font-mono text-[11px] text-slate-500">
                                                @if($rec && $rec->bmi)
                                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ $rec->bmi }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- FOOTER ACTION -->
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Data tinggi, berat, lingkar kepala, dan lingkar lengan otomatis mensinkronkan data profil fisik murid.
                            </span>
                            <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-all cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Simpan Data DDTK</span>
                            </button>
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
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Input Skrining SDIDTK Periode: <strong class="text-emerald-600 dark:text-emerald-400">{{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}</strong>
                            </span>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-all cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan Data SDIDTK</span>
                        </button>
                    </div>

                    <!-- INLINE TABLE SDIDTK -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <!-- ROW 1 HEADER -->
                                    <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[11px] font-bold border-b border-slate-200 dark:border-slate-800">
                                        <th rowspan="2" class="py-3 px-3 w-10 text-center uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">No</th>
                                        <th rowspan="2" class="py-3 px-3 min-w-[170px] uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">Nama Murid</th>
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
                                        @endphp
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                            <!-- NO -->
                                            <td class="py-2.5 px-3 text-center text-slate-400 dark:text-slate-500 font-mono text-[11px] border-r border-slate-100 dark:border-slate-800">
                                                {{ $loop->iteration }}
                                            </td>

                                            <!-- NAMA MURID -->
                                            <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-slate-100 border-r border-slate-100 dark:border-slate-800">
                                                <div class="truncate">{{ $st->full_name }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono">NIS: {{ $st->nis ?: '-' }}</div>
                                            </td>

                                            <!-- VISUS MATA: KANAN -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800">
                                                <input type="text"
                                                    name="sdidtk[{{ $st->id }}][visus_right]"
                                                    value="{{ $rec?->visus_right ?? '' }}"
                                                    placeholder="Normal / 6/6"
                                                    class="w-full text-center px-2 py-1 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-emerald-500">
                                            </td>

                                            <!-- VISUS MATA: KIRI -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800">
                                                <input type="text"
                                                    name="sdidtk[{{ $st->id }}][visus_left]"
                                                    value="{{ $rec?->visus_left ?? '' }}"
                                                    placeholder="Normal / 6/6"
                                                    class="w-full text-center px-2 py-1 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-emerald-500">
                                            </td>

                                            <!-- KPSP -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800">
                                                <select name="sdidtk[{{ $st->id }}][kpsp_result]"
                                                    class="w-full px-2 py-1 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-emerald-500 cursor-pointer">
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
                                                    placeholder="Sehat / Karies / dll"
                                                    class="w-full px-2 py-1 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-emerald-500">
                                            </td>

                                            <!-- GDA (Gula Darah Acak) -->
                                            <td class="py-2 px-2 border-r border-slate-100 dark:border-slate-800 text-center">
                                                <input type="number" step="0.1" min="0" max="600"
                                                    name="sdidtk[{{ $st->id }}][blood_sugar]"
                                                    value="{{ $rec?->blood_sugar !== null ? $rec->blood_sugar : '' }}"
                                                    placeholder="mg/dL"
                                                    class="w-full text-center px-2 py-1 text-xs font-mono bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-emerald-500">
                                            </td>

                                            <!-- GEJALA KUSTA (YA/TIDAK) -->
                                            <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                                <label class="inline-flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox"
                                                        name="sdidtk[{{ $st->id }}][has_leprosy]"
                                                        value="1"
                                                        {{ $rec?->has_leprosy ? 'checked' : '' }}
                                                        class="rounded border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-rose-500">
                                                    <span class="ml-1 text-[11px] font-semibold text-slate-600 dark:text-slate-400">Ya</span>
                                                </label>
                                            </td>

                                            <!-- GEJALA FRAMBUSIA (YA/TIDAK) -->
                                            <td class="py-2 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                                <label class="inline-flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox"
                                                        name="sdidtk[{{ $st->id }}][has_frambesia]"
                                                        value="1"
                                                        {{ $rec?->has_frambesia ? 'checked' : '' }}
                                                        class="rounded border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-rose-500">
                                                    <span class="ml-1 text-[11px] font-semibold text-slate-600 dark:text-slate-400">Ya</span>
                                                </label>
                                            </td>

                                            <!-- CATATAN -->
                                            <td class="py-2 px-4">
                                                <input type="text"
                                                    name="sdidtk[{{ $st->id }}][notes]"
                                                    value="{{ $rec?->notes ?? '' }}"
                                                    placeholder="Catatan hasil skrining..."
                                                    class="w-full px-2 py-1 text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-emerald-500">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- FOOTER ACTION -->
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Centang kotak "Ya" bila ditemukan indikasi gejala klinis Kusta atau Frambusia pada murid.
                            </span>
                            <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition-all cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Simpan Data SDIDTK</span>
                            </button>
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
            
            <!-- TOOLBAR EXPORT & PRINT -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                        Rekapitulasi DDTK & SDIDTK: {{ $selectedClassroom?->name }} &mdash; {{ $availablePeriods[$selectedPeriod] ?? $selectedPeriod }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Export Excel -->
                    <a href="{{ route('student-ddtk.export.excel', ['classroom_id' => $selectedClassroomId, 'period' => $selectedPeriod]) }}"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition-colors cursor-pointer">
                        <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                        <span>Ekspor Excel</span>
                    </a>

                    <!-- Print PDF -->
                    <a href="{{ route('student-ddtk.print', ['classroom_id' => $selectedClassroomId, 'period' => $selectedPeriod]) }}"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs transition-colors cursor-pointer">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Cetak PDF</span>
                    </a>
                </div>
            </div>

            <!-- TABLE REKAP -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[11px] font-bold border-b border-slate-200 dark:border-slate-800">
                                <th rowspan="2" class="py-3 px-3 w-10 text-center uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">No</th>
                                <th rowspan="2" class="py-3 px-3 w-24 uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">NIS</th>
                                <th rowspan="2" class="py-3 px-4 min-w-[180px] uppercase tracking-wider border-r border-slate-200 dark:border-slate-800">Nama Murid</th>
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
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px] border-r border-slate-100 dark:border-slate-800">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-semibold text-slate-700 dark:text-slate-300 border-r border-slate-100 dark:border-slate-800">
                                        {{ $st->nis ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 font-semibold text-slate-900 dark:text-slate-100 border-r border-slate-100 dark:border-slate-800">
                                        {{ $st->full_name }}
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
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                                                {{ $rec->kpsp_result === 'Sesuai' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : ($rec->kpsp_result === 'Meragukan' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300') }}">
                                                {{ $rec->kpsp_result }}
                                            </span>
                                        @else
                                            -
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
                                            <span class="font-bold {{ $rec->has_leprosy ? 'text-rose-600' : 'text-slate-400' }}">
                                                {{ $rec->has_leprosy ? 'YA' : 'TIDAK' }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <!-- Frambusia -->
                                    <td class="py-2.5 px-2 text-center border-r border-slate-100 dark:border-slate-800">
                                        @if($rec)
                                            <span class="font-bold {{ $rec->has_frambesia ? 'text-rose-600' : 'text-slate-400' }}">
                                                {{ $rec->has_frambesia ? 'YA' : 'TIDAK' }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <!-- Catatan -->
                                    <td class="py-2.5 px-4 text-slate-600 dark:text-slate-400">
                                        {{ $rec?->notes ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="py-6 text-center text-slate-400">
                                        Tidak ada data murid pada rombel ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: GRAFIK PERTUMBUHAN                                                -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'grafik'" x-cloak class="space-y-4">
            
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
