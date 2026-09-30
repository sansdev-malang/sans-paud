<x-admin-layout>
    <div class="p-6 space-y-6" x-data="reportCardApp()">

        <!-- PAGE TITLE & HEADER -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-500/20 shadow-xs">
                        <i data-lucide="book-open-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            E-Rapor PAUD & Penilaian
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 font-semibold border border-emerald-200 dark:border-emerald-800">
                                Kurikulum Merdeka PAUD
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Evaluasi naratif capaian pembelajaran (NABP, Jati Diri, STEAM, P5), tumbuh kembang daycare, dan capaian TPQ.</p>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            @php
                $isAdmin = auth()->user() && in_array(auth()->user()->role, ['super_admin', 'admin_sd', 'admin_paud', 'admin_smp', 'kepala_sekolah', 'waka']);
            @endphp
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                @if($selectedClassroom && $students->isNotEmpty())
                    @if($isAdmin)
                    <!-- Batch Approve 1 Kelompok (Khusus KS / Admin) -->
                    <form method="POST" action="{{ route('report-cards.batch-approve') }}" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui seluruh rapor kelompok {{ $selectedClassroom->name }}?')">
                        @csrf
                        <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
                        <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 text-xs font-semibold rounded-lg border border-indigo-200 dark:border-indigo-800 shadow-xs transition-colors cursor-pointer">
                            <i data-lucide="check-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                            Setujui Rapor 1 Kelompok
                        </button>
                    </form>

                    <!-- Batch Publish 1 Kelompok -->
                    <form method="POST" action="{{ route('report-cards.batch-publish') }}" onsubmit="return confirm('Apakah Anda yakin ingin menerbitkan seluruh rapor kelompok {{ $selectedClassroom->name }} ke Portal Wali Murid?')">
                        @csrf
                        <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
                        <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 text-xs font-semibold rounded-lg border border-emerald-200 dark:border-emerald-800 shadow-xs transition-colors cursor-pointer">
                            <i data-lucide="send" class="w-3.5 h-3.5 text-emerald-600"></i>
                            Terbitkan Semua ke Portal
                        </button>
                    </form>
                    @endif

                    <!-- Batch Print Rapor 1 Kelompok -->
                    <a href="{{ route('report-cards.batch-print', ['classroom_id' => $selectedClassroomId, 'academic_year_id' => $selectedYearId, 'semester' => $selectedSemester]) }}" target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-600 dark:text-slate-400"></i>
                        Cetak Batch
                    </a>
                @endif

                <!-- Portal Akses Wali Murid Link -->
                <a href="{{ route('portal.report-cards.index') }}" target="_blank"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    Portal Rapor Ortu
                </a>
            </div>
        </section>

        <!-- STATS CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Total Siswa Kelompok -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Siswa Kelompok</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ $stats['total_students'] }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    Kelompok: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $selectedClassroom?->name ?? 'Belum Dipilih' }}</span>
                </div>
            </div>

            <!-- Stat 2: Rapor Terbit / Selesai -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rapor Terbit / Disetujui</p>
                        <h3 class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ $stats['published'] }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Siap diakses orang tua di portal
                </div>
            </div>

            <!-- Stat 3: Rapor Berkas PDF (Bypass) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Upload Berkas PDF</p>
                        <h3 class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400 mt-1">
                            {{ $stats['pdf_count'] }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-xl border border-purple-100 dark:border-purple-900/50">
                        <i data-lucide="file-up" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Bypass form via berkas PDF langsung
                </div>
            </div>

            <!-- Stat 4: Draft / Belum Dibuat -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Draft / Belum Rapor</p>
                        <h3 class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 mt-1">
                            {{ $stats['draft'] + $stats['uncreated'] }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    {{ $stats['draft'] }} draft, {{ $stats['uncreated'] }} belum dibuat
                </div>
            </div>
        </section>

        <!-- FILTER TOOLBAR (Tahun Ajaran, Semester, Kelompok) -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs w-full">
            <form method="GET" action="{{ route('report-cards.index') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <input type="hidden" name="sub_unit" value="{{ $selectedSubUnit }}">

                <!-- Left: Pilih Kelompok (Classroom) -->
                <div class="flex items-center gap-2 flex-1 max-w-md">
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-400 shrink-0">Kelompok:</label>
                    <select name="classroom_id" onchange="this.form.submit()"
                        class="h-9 w-full px-3 text-xs font-bold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer shadow-xs">
                        @forelse($classrooms as $c)
                            <option value="{{ $c->id }}" {{ $selectedClassroomId == $c->id ? 'selected' : '' }}>
                                [{{ $c->sub_unit }}] {{ $c->name }} ({{ $c->homeroomTeacher?->name ?? 'Wali Kelas -' }})
                            </option>
                        @empty
                            <option value="">Belum ada kelompok belajar di tahun ajaran ini</option>
                        @endforelse
                    </select>
                </div>

                <!-- Right: Pilih Tahun Ajaran & Semester -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filter Tahun Ajaran -->
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer shadow-xs">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                                T.A. {{ $year->name }} {{ $year->is_active ? '★ (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Semester -->
                    <select name="semester" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer shadow-xs">
                        <option value="mid_ganjil" {{ $selectedSemester == 'mid_ganjil' ? 'selected' : '' }}>Mid Semester Ganjil</option>
                        <option value="1" {{ in_array((string)$selectedSemester, ['1', 'ganjil', 'Ganjil']) ? 'selected' : '' }}>Semester Ganjil</option>
                        <option value="mid_genap" {{ $selectedSemester == 'mid_genap' ? 'selected' : '' }}>Mid Semester Genap</option>
                        <option value="2" {{ in_array((string)$selectedSemester, ['2', 'genap', 'Genap']) ? 'selected' : '' }}>Semester Genap</option>
                    </select>
                </div>
            </form>
        </section>

        <!-- TABLE LIST SISWA & STATUS RAPOR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800">
                        {{ $selectedClassroom?->name ?? 'Kelompok' }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Wali Kelas: <strong class="text-slate-800 dark:text-slate-200">{{ $selectedClassroom?->homeroomTeacher?->name ?? 'Belum Ditentukan' }}</strong>
                    </span>
                </div>
                @php
                    $semTitle = match((string)$selectedSemester) {
                        'mid_ganjil' => 'Mid Semester Ganjil',
                        '1', 'ganjil', 'Ganjil' => 'Semester Ganjil',
                        'mid_genap' => 'Mid Semester Genap',
                        '2', 'genap', 'Genap' => 'Semester Genap',
                        default => 'Semester ' . $selectedSemester,
                    };
                @endphp
                <span class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300">
                    Periode: T.A. {{ $selectedYear?->name }} ({{ $semTitle }})
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12">No</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">NIS & PIN</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Ananda</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Mode & Status Rapor</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Capaian / Berkas Rapor</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">TB & BB</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($students as $index => $s)
                            @php
                                $r = $reportCards->get($s->id);
                                $isPdfMode = $r && $r->entry_mode === 'pdf';
                                $hasNabp = !empty($r?->nabp_narrative);
                                $hasJatiDiri = !empty($r?->jati_diri_narrative);
                                $hasSteam = !empty($r?->steam_narrative);
                                $hasP5 = !empty($r?->p5_narrative);
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                <td class="px-5 py-3.5 text-slate-400 font-mono text-[11px]">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ $s->nis }}
                                        </span>
                                        @if($s->pin_access)
                                            <span class="font-mono text-[10px] text-slate-400 mt-0.5">
                                                PIN: <span class="font-bold text-slate-600 dark:text-slate-300">{{ $s->pin_access }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-100 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                            {{ $s->avatar_initials }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight">
                                                {{ $s->full_name }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">
                                                {{ $s->formatted_gender }} &bull; {{ $s->age ?? '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if(!$r)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            Belum Dibuat
                                        </span>
                                    @else
                                        <div class="flex flex-col gap-1 items-start">
                                            @if($isPdfMode)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40">
                                                    <i data-lucide="file-text" class="w-3 h-3"></i>
                                                    Berkas PDF
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40">
                                                    <i data-lucide="pen-tool" class="w-3 h-3"></i>
                                                    Form Digital
                                                </span>
                                            @endif

                                            @if($r->status === 'published')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                                                    🚀 Terbit / Aktif
                                                </span>
                                            @elseif($r->status === 'approved')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/40">
                                                    ✓ Disetujui KS
                                                </span>
                                            @elseif($r->status === 'submitted')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40">
                                                    📤 Menunggu Review
                                                </span>
                                            @elseif($r->status === 'revision' || $r->status === 'rejected')
                                                <div class="flex flex-col items-start gap-0.5">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40" title="{{ $r->revision_notes }}">
                                                        ⚠️ Perlu Revisi
                                                    </span>
                                                    @if($r->revision_notes)
                                                        <span class="text-[10px] text-rose-600 dark:text-rose-400 italic truncate max-w-[130px]" title="{{ $r->revision_notes }}">
                                                            "{{ Str::limit($r->revision_notes, 22) }}"
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.2 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                                                    ✎ Draft
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($isPdfMode && $r->pdf_file)
                                        <div class="flex items-center gap-2">
                                            <a href="{{ asset('storage/' . $r->pdf_file) }}" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 rounded-lg text-xs font-semibold border border-purple-200 dark:border-purple-800/50 transition-colors">
                                                <i data-lucide="file-check-2" class="w-3.5 h-3.5 text-purple-600"></i>
                                                <span>Buka PDF</span>
                                                <i data-lucide="external-link" class="w-3 h-3 opacity-60"></i>
                                            </a>
                                        </div>
                                    @elseif($r)
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $hasNabp ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}" title="Nilai Agama & Budi Pekerti">
                                                NABP
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $hasJatiDiri ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}" title="Jati Diri">
                                                Jati Diri
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $hasSteam ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}" title="Dasar Literasi & STEAM">
                                                STEAM
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $hasP5 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}" title="Projek P5">
                                                P5
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">Belum ada data rapor</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($r && ($r->height || $r->weight))
                                        <span class="font-mono text-slate-700 dark:text-slate-300">
                                            {{ $r->height ? $r->height . ' cm' : '-' }} / {{ $r->weight ? $r->weight . ' kg' : '-' }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit / Isi Rapor Naratif -->
                                        <a href="{{ route('report-cards.edit', ['student' => $s->id, 'academic_year_id' => $selectedYearId, 'semester' => $selectedSemester]) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-semibold rounded-lg text-xs transition-colors"
                                            title="{{ $r ? 'Edit Pengisian Rapor' : 'Mulai Isi Form Rapor' }}">
                                            <i data-lucide="{{ $r ? 'edit-2' : 'plus-circle' }}" class="w-3.5 h-3.5"></i>
                                            {{ $r && !$isPdfMode ? 'Edit Form' : 'Isi Form' }}
                                        </a>

                                        <!-- Bypass Quick Upload PDF Modal -->
                                        <button type="button" @click="openUploadModal({{ Js::from($s) }}, {{ Js::from($r) }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/40 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 font-semibold rounded-lg text-xs transition-colors cursor-pointer"
                                            title="Unggah Rapor Berkas PDF (Bypass Isi Form)">
                                            <i data-lucide="file-up" class="w-3.5 h-3.5"></i>
                                            {{ $isPdfMode ? 'Ganti PDF' : 'Upload PDF' }}
                                        </button>

                                        @if($r)
                                            @if($isAdmin)
                                                <!-- Quick Setujui Rapor -->
                                                @if($r->status !== 'approved' && $r->status !== 'published')
                                                    <form method="POST" action="{{ route('report-cards.approve', $r->id) }}" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                            class="p-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-lg transition-colors cursor-pointer"
                                                            title="Setujui Rapor (Approval KS)">
                                                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- Quick Minta Revisi Rapor (Khusus KS) -->
                                                @if($r->status !== 'published')
                                                    <button type="button" @click="openRevisionModal({{ Js::from($s) }}, {{ Js::from($r) }})"
                                                        class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                                        title="Minta Revisi / Beri Catatan Perbaikan">
                                                        <i data-lucide="message-square-warning" class="w-4 h-4"></i>
                                                    </button>
                                                @endif

                                                @if($r->status === 'approved')
                                                    <!-- Quick Terbitkan ke Portal -->
                                                    <form method="POST" action="{{ route('report-cards.publish', $r->id) }}" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                            class="p-1.5 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-lg transition-colors cursor-pointer"
                                                            title="Terbitkan ke Portal Wali Murid">
                                                            <i data-lucide="send" class="w-4 h-4"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif

                                            <!-- Print / View Rapor -->
                                            <a href="{{ route('report-cards.show', $r->id) }}" target="_blank"
                                                class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-lg transition-colors"
                                                title="Pratinjau / Cetak Rapor">
                                                <i data-lucide="{{ $isPdfMode ? 'eye' : 'printer' }}" class="w-4 h-4"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="max-w-sm mx-auto flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-500 mb-3 border border-emerald-100 dark:border-emerald-900/50">
                                            <i data-lucide="book-open" class="w-6 h-6"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Murid di Kelompok Ini</h4>
                                        <p class="text-xs text-slate-400 mt-1 text-center">
                                            Pilih kelompok lain melalui dropdown di atas atau tempatkan siswa pada menu Data Murid.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL: QUICK UPLOAD RAPOR PDF (BYPASS FORM) -->
        <div x-show="uploadModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="uploadModalOpen = false" class="w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-2xl space-y-5 text-left">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-xl border border-purple-100 dark:border-purple-900/50">
                            <i data-lucide="file-up" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Unggah Berkas Rapor PDF (Bypass)</h3>
                            <p class="text-xs text-slate-400">Wali kelas dapat langsung mengunggah file PDF tanpa harus mengisi form naratif.</p>
                        </div>
                    </div>
                    <button type="button" @click="uploadModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Target Student Info -->
                <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="selectedStudent?.full_name || 'Nama Siswa'"></p>
                        <p class="text-[11px] text-slate-400">
                            NIS: <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedStudent?.nis || '-'"></span> &bull;
                            Periode: <span class="font-semibold text-slate-600 dark:text-slate-300">T.A. {{ $selectedYear?->name }} (Sem {{ $selectedSemester }})</span>
                        </p>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/50 dark:border-purple-800">
                        Bypass Mode
                    </span>
                </div>

                <!-- Upload Form -->
                <form :action="'/report-cards/' + (selectedStudent?.id || 0) + '/upload-pdf'" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="academic_year_id" :value="uploadForm.academic_year_id">
                    <input type="hidden" name="semester" :value="uploadForm.semester">

                    <!-- File Dropzone -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Pilih Dokumen Rapor PDF <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-purple-500 rounded-xl p-6 text-center transition-colors bg-slate-50/50 dark:bg-slate-900/50">
                            <input type="file" name="pdf_file" required accept=".pdf,application/pdf" @change="handleFileChange($event)"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
                                <div class="w-10 h-10 rounded-full bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                    <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                                </div>
                                <div class="text-xs">
                                    <span class="font-bold text-purple-600 dark:text-purple-400" x-text="uploadForm.pdf_file_name || 'Klik atau tarik file PDF ke sini'"></span>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Format dokumen .PDF (Maksimal 15 MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Rapor & Status Terbit -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Rapor</label>
                            <input type="date" name="report_date" x-model="uploadForm.report_date"
                                class="w-full h-9 px-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Publikasi</label>
                            <select name="status" x-model="uploadForm.status"
                                class="w-full h-9 px-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 cursor-pointer">
                                <option value="published">Terbit (Siap Diakses Ortu)</option>
                                <option value="approved">Disetujui Kepala Sekolah</option>
                                <option value="draft">Draft (Disimpan Sementara)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Keterangan / Catatan Tambahan (Opsional) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="teacher_notes" x-model="uploadForm.teacher_notes" placeholder="Contoh: Berkas Rapor Semester 1 Tahun Ajaran 2026/2027"
                            class="w-full h-9 px-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-900 dark:text-slate-50">
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button" @click="uploadModalOpen = false"
                            class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            Unggah & Simpan Rapor
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: MINTA REVISI RAPOR DARI KEPALA SEKOLAH -->
        <div x-show="revisionModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="revisionModalOpen = false" class="w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-2xl space-y-4 text-left">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl border border-rose-100 dark:border-rose-900/50">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Minta Revisi / Catatan Perbaikan Rapor</h3>
                            <p class="text-xs text-slate-400">Kirimkan catatan perbaikan langsung ke wali kelas / ustadzah pembuat rapor.</p>
                        </div>
                    </div>
                    <button type="button" @click="revisionModalOpen = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Info Ananda -->
                <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl">
                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="revisionForm.student_name || 'Nama Siswa'"></p>
                    <p class="text-[11px] text-slate-400">Rapor ananda akan diubah statusnya menjadi <strong>Perlu Revisi</strong>.</p>
                </div>

                <!-- Revision Form -->
                <form :action="'/report-cards/' + (revisionForm.report_card_id || 0) + '/request-revision'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Poin Catatan / Alasan Revisi <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="revision_notes" x-model="revisionForm.notes" rows="4" required
                            placeholder="Contoh: Mohon perbaiki narasi elemen Jati Diri agar lebih mencerminkan kemandirian ananda, atau lampirkan foto kegiatan yang lebih jelas..."
                            class="w-full p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button" @click="revisionModalOpen = false"
                            class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Kirim Permintaan Revisi
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function reportCardApp() {
            return {
                uploadModalOpen: false,
                revisionModalOpen: false,
                selectedStudent: null,
                uploadForm: {
                    student_id: '',
                    academic_year_id: '{{ $selectedYearId }}',
                    semester: '{{ $selectedSemester }}',
                    report_date: '{{ date('Y-m-d') }}',
                    status: 'published',
                    teacher_notes: '',
                    pdf_file_name: ''
                },
                revisionForm: {
                    report_card_id: '',
                    student_name: '',
                    notes: ''
                },
                openUploadModal(student, reportCard = null) {
                    this.selectedStudent = student;
                    this.uploadForm.student_id = student.id;
                    this.uploadForm.academic_year_id = '{{ $selectedYearId }}';
                    this.uploadForm.semester = '{{ $selectedSemester }}';
                    this.uploadForm.report_date = reportCard?.report_date ? reportCard.report_date.split('T')[0] : '{{ date('Y-m-d') }}';
                    this.uploadForm.status = reportCard?.status || 'published';
                    this.uploadForm.teacher_notes = reportCard?.teacher_notes || '';
                    this.uploadForm.pdf_file_name = reportCard?.pdf_file ? 'File PDF Tersedia (' + reportCard.pdf_file.split('/').pop() + ')' : '';
                    this.uploadModalOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                openRevisionModal(student, reportCard) {
                    this.revisionForm.report_card_id = reportCard.id;
                    this.revisionForm.student_name = student.full_name;
                    this.revisionForm.notes = reportCard.revision_notes || '';
                    this.revisionModalOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                handleFileChange(event) {
                    const file = event.target.files[0];
                    this.uploadForm.pdf_file_name = file ? file.name : '';
                }
            };
        }
    </script>
</x-admin-layout>
