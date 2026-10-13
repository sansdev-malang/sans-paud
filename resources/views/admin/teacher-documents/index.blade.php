<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-4 sm:space-y-5" x-data="teacherDocsApp()">

        <!-- ========================================================================= -->
        <!-- HEADER SECTION                                                            -->
        <!-- ========================================================================= -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                        <i data-lucide="folder-archive" class="w-5 h-5"></i>
                    </span>
                    <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Berkas Guru</h2>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                    Pengarsipan perangkat pembelajaran (PROTA, PROSEM, RPP), anggaran (RKAS, LPJ), dan supervisi administrasi guru PAUD.
                </p>
            </div>

            <!-- Header Actions -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <!-- Academic Year Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200/80 dark:border-purple-800/60 rounded-xl text-[11px] sm:text-xs shadow-xs">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">T.A:</span>
                    <span class="font-bold text-purple-700 dark:text-purple-300">
                        {{ $academicYears->firstWhere('id', $selectedYearId)?->name ?? 'Aktif' }}
                    </span>
                </div>

                <!-- Export Matrix Excel -->
                <a href="{{ route('teacher-documents.export.excel', ['academic_year_id' => $selectedYearId]) }}"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] sm:text-xs font-semibold rounded-lg shadow-sm transition-colors">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                    <span>Ekspor Matriks</span>
                </a>

                <!-- Print Matrix -->
                <a href="{{ route('teacher-documents.print', ['academic_year_id' => $selectedYearId]) }}" target="_blank"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-slate-700 hover:bg-slate-800 text-white text-[11px] sm:text-xs font-semibold rounded-lg shadow-sm transition-colors">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span>Cetak Matriks</span>
                </a>

                <!-- Upload New Document -->
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-purple-600 hover:bg-purple-700 text-white text-[11px] sm:text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
                    <span>Unggah Berkas Baru</span>
                </button>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- FLASH NOTIFICATIONS                                                       -->
        <!-- ========================================================================= -->
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
                    <span class="font-bold">Terjadi kesalahan input:</span>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- METRIC CARDS                                                              -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Total Berkas -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Berkas</span>
                    <span class="p-1.5 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                        <i data-lucide="files" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-50">
                    {{ $stats['total'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Dokumen Terunggah
                </div>
            </div>

            <!-- Sudah Disetujui -->
            <div class="bg-white dark:bg-slate-900 border border-emerald-200/80 dark:border-emerald-900/60 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Disetujui</span>
                    <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
                    {{ $stats['approved'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Lolos Verifikasi
                </div>
            </div>

            <!-- Menunggu Review -->
            <div class="bg-white dark:bg-slate-900 border border-amber-200/80 dark:border-amber-900/60 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Menunggu Review</span>
                    <span class="p-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                    {{ $stats['pending'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Antrean Supervisi
                </div>
            </div>

            <!-- Perlu Revisi -->
            <div class="bg-white dark:bg-slate-900 border border-rose-200/80 dark:border-rose-900/60 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Perlu Revisi</span>
                    <span class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400">
                    {{ $stats['needs_revision'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Perlu Diperbaiki Guru
                </div>
            </div>

            <!-- Guru Aktif Mengunggah -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Guru Partisipan</span>
                    <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="users" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400">
                    {{ $stats['teachers_count'] }} <span class="text-xs font-normal text-slate-400">/ {{ $teachers->count() }} Guru</span>
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Telah Mengunggah Berkas
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB SWITCHER: 1. ARSIP BERKAS  |  2. MATRIKS KELENGKAPAN ADMINISTRASI    -->
        <!-- ========================================================================= -->
        <div class="flex items-center border-b border-slate-200 dark:border-slate-800 space-x-1 sm:space-x-2">
            <a href="{{ route('teacher-documents.index', array_merge(request()->query(), ['tab' => 'files'])) }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-colors {{ $activeTab === 'files' ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 hover:border-slate-300' }}">
                <i data-lucide="folder-open" class="w-4 h-4"></i>
                <span>Arsip & Daftar Berkas</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 font-bold">
                    {{ $documents->count() }}
                </span>
            </a>

            <a href="{{ route('teacher-documents.index', array_merge(request()->query(), ['tab' => 'matrix'])) }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-colors {{ $activeTab === 'matrix' ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 hover:border-slate-300' }}">
                <i data-lucide="table-2" class="w-4 h-4"></i>
                <span>Matriks Kelengkapan Administrasi</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                    Monitoring
                </span>
            </a>
        </div>

        @if($activeTab === 'files')
            <!-- ========================================================================= -->
            <!-- TAB 1: ARSIP & DAFTAR BERKAS (FILTER + TABEL)                            -->
            <!-- ========================================================================= -->

            <!-- Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
                <form method="GET" action="{{ route('teacher-documents.index') }}" id="docsFilterForm" class="flex flex-col lg:flex-row lg:items-end justify-between gap-3">
                    <input type="hidden" name="tab" value="files">

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 flex-1">
                        <!-- Tahun Ajaran -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Tahun Ajaran
                            </label>
                            <select name="academic_year_id" onchange="document.getElementById('docsFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 cursor-pointer">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                        {{ $ay->name }} {{ $ay->is_active ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Guru / Pendidik -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Guru / Pendidik
                            </label>
                            <select name="teacher_id" onchange="document.getElementById('docsFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 cursor-pointer">
                                <option value="all">Semua Guru</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" {{ $selectedTeacherId == $t->id ? 'selected' : '' }}>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kategori Berkas -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Jenis / Kategori
                            </label>
                            <select name="category" onchange="document.getElementById('docsFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 cursor-pointer">
                                <option value="all">Semua Jenis Berkas</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Semester -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Semester
                            </label>
                            <select name="semester" onchange="document.getElementById('docsFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 cursor-pointer">
                                <option value="all">Semua Semester</option>
                                @foreach($semesters as $sem)
                                    <option value="{{ $sem }}" {{ $selectedSemester === $sem ? 'selected' : '' }}>
                                        {{ $sem }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Supervisi -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Status Supervisi
                            </label>
                            <select name="status" onchange="document.getElementById('docsFilterForm').submit()"
                                class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 cursor-pointer">
                                <option value="all">Semua Status</option>
                                <option value="Menunggu Review" {{ $selectedStatus === 'Menunggu Review' ? 'selected' : '' }}>Menunggu Review</option>
                                <option value="Disetujui" {{ $selectedStatus === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                                <option value="Perlu Revisi" {{ $selectedStatus === 'Perlu Revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                            </select>
                        </div>
                    </div>

                    <!-- Search Input & Buttons -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="relative w-full sm:w-48">
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Cari judul berkas..."
                                class="w-full pl-8 pr-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none"></i>
                        </div>

                        <button type="submit"
                            class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors cursor-pointer">
                            Filter
                        </button>

                        @if($search || $selectedCategory !== 'all' || $selectedTeacherId !== 'all' || $selectedStatus !== 'all' || $selectedSemester !== 'all')
                            <a href="{{ route('teacher-documents.index', ['academic_year_id' => $selectedYearId, 'tab' => 'files']) }}"
                                class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Documents Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 uppercase tracking-wider font-bold text-[10px]">
                                <th class="py-3 px-3.5 text-center w-10">No</th>
                                <th class="py-3 px-3.5 min-w-[180px]">Guru / Pendidik</th>
                                <th class="py-3 px-3.5 w-24">Jenis</th>
                                <th class="py-3 px-3.5 min-w-[220px]">Judul Dokumen & File</th>
                                <th class="py-3 px-3.5 w-24">Semester</th>
                                <th class="py-3 px-3.5 min-w-[140px]">Keterangan</th>
                                <th class="py-3 px-3.5 w-32">Status Supervisi</th>
                                <th class="py-3 px-3.5 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                            @forelse($documents as $idx => $doc)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <!-- No -->
                                    <td class="py-3 px-3.5 text-center text-slate-400 font-bold">
                                        {{ $idx + 1 }}
                                    </td>

                                    <!-- Guru -->
                                    <td class="py-3 px-3.5">
                                        <div class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $doc->teacher ? $doc->teacher->name : 'Guru Dihapus' }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">
                                            {{ $doc->teacher?->niy ?: ($doc->teacher?->nik ?: 'NIP: -') }}
                                        </div>
                                    </td>

                                    <!-- Kategori -->
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $doc->category_badge_class }}">
                                            {{ $doc->category }}
                                        </span>
                                    </td>

                                    <!-- Judul & File -->
                                    <td class="py-3 px-3.5">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                            {{ $doc->title }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-1 text-[11px]">
                                            <span class="inline-flex items-center gap-1 font-mono text-slate-500">
                                                <i data-lucide="{{ $doc->file_icon }}" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400"></i>
                                                <span>{{ strtoupper($doc->file_extension ?: 'FILE') }}</span>
                                                <span class="text-slate-400">({{ $doc->formatted_file_size }})</span>
                                            </span>
                                            <a href="{{ route('teacher-documents.download', $doc->id) }}"
                                                class="text-purple-600 hover:text-purple-800 dark:text-purple-400 font-semibold underline">
                                                Unduh
                                            </a>
                                        </div>
                                    </td>

                                    <!-- Semester -->
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        <span class="text-slate-700 dark:text-slate-300 font-semibold">
                                            {{ $doc->semester }}
                                        </span>
                                        <div class="text-[10px] text-slate-400">
                                            {{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y') }}
                                        </div>
                                    </td>

                                    <!-- Keterangan Tambahan -->
                                    <td class="py-3 px-3.5">
                                        <p class="text-slate-600 dark:text-slate-400 line-clamp-2 text-[11px]" title="{{ $doc->notes }}">
                                            {{ $doc->notes ?: '-' }}
                                        </p>
                                    </td>

                                    <!-- Status Supervisi -->
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $doc->status_badge_class }}">
                                            {{ $doc->status }}
                                        </span>
                                        @if($doc->feedback)
                                            <div class="text-[10px] text-rose-600 dark:text-rose-400 line-clamp-1 italic mt-0.5" title="Catatan Review: {{ $doc->feedback }}">
                                                "{{ $doc->feedback }}"
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Detail Modal -->
                                            <button type="button" @click="openViewModal({{ json_encode($doc) }})" title="Lihat Detail Berkas"
                                                class="p-1.5 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>

                                            <!-- Review / Status Modal (Admin / Kepala Sekolah) -->
                                            @if($isAdmin)
                                                <button type="button" @click="openReviewModal({{ json_encode($doc) }})" title="Supervisi / Verifikasi Berkas"
                                                    class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                                    <i data-lucide="check-square" class="w-4 h-4"></i>
                                                </button>
                                            @endif

                                            <!-- Edit Modal -->
                                            @if($isAdmin || $doc->uploaded_by == auth()->id())
                                                <button type="button" @click="openEditModal({{ json_encode($doc) }})" title="Edit Berkas"
                                                    class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </button>

                                                <!-- Delete -->
                                                <form method="POST" action="{{ route('teacher-documents.destroy', $doc->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus Berkas"
                                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i data-lucide="folder-x" class="w-8 h-8 text-slate-300 dark:text-slate-600"></i>
                                            <p class="font-medium text-xs">Belum ada berkas guru yang ditemukan sesuai filter.</p>
                                            <button type="button" @click="openCreateModal()" class="mt-2 text-purple-600 dark:text-purple-400 text-xs font-semibold hover:underline">
                                                + Unggah Berkas Baru
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Menampilkan <strong>{{ $documents->count() }}</strong> berkas</span>
                    <span>Tahun Ajaran: <strong>{{ $academicYears->firstWhere('id', $selectedYearId)?->name ?? 'Semua' }}</strong></span>
                </div>
            </div>

        @else
            <!-- ========================================================================= -->
            <!-- TAB 2: MATRIKS KELENGKAPAN ADMINISTRASI GURU                             -->
            <!-- ========================================================================= -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs space-y-4 p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">Matriks Ceklis Supervisi Administrasi Guru</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Pantau kelengkapan perangkat tahunan (PROTA, PROSEM), pembelajaran (RPP), dan laporan (RKAS, LPJ) setiap guru.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Disetujui
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block ml-1"></span> Menunggu
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-700 inline-block ml-1"></span> Belum Ada
                        </span>
                    </div>
                </div>

                <!-- Matrix Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 uppercase tracking-wider font-bold text-[10px]">
                                <th class="py-3 px-3.5 text-center w-10">No</th>
                                <th class="py-3 px-3.5 min-w-[200px]">Nama Guru / Pendidik</th>
                                <th class="py-3 px-3 text-center w-24">PROTA</th>
                                <th class="py-3 px-3 text-center w-24">PROSEM</th>
                                <th class="py-3 px-3 text-center w-24">RPP</th>
                                <th class="py-3 px-3 text-center w-24">RKAS</th>
                                <th class="py-3 px-3 text-center w-24">LPJ</th>
                                <th class="py-3 px-3 text-center w-24">Lainnya</th>
                                <th class="py-3 px-3 text-center w-28">Total Berkas</th>
                                <th class="py-3 px-3.5 text-center w-36">Persentase</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                            @foreach($matrixData as $mIdx => $m)
                                @php
                                    $completedCats = 0;
                                    // Categories considered core: PROTA, PROSEM, RPP (3 main core pedagogy categories)
                                    foreach(['PROTA', 'PROSEM', 'RPP'] as $cCore) {
                                        if (($m['categories'][$cCore]['count'] ?? 0) > 0) $completedCats++;
                                    }
                                    $corePercent = round(($completedCats / 3) * 100);
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3 px-3.5 text-center text-slate-400 font-bold">
                                        {{ $mIdx + 1 }}
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <div class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $m['teacher']->name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ $m['teacher']->position ?: 'Tenaga Pendidik' }}
                                        </div>
                                    </td>

                                    <!-- Categories Columns -->
                                    @foreach($categories as $cat)
                                        @php
                                            $catStat = $m['categories'][$cat];
                                            $count = $catStat['count'];
                                            $approved = $catStat['approved'];
                                        @endphp
                                        <td class="py-3 px-3 text-center">
                                            @if($count > 0)
                                                @if($approved > 0)
                                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        <i data-lucide="check" class="w-3 h-3"></i>
                                                        <span>{{ $count }}</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                                        <span>{{ $count }}</span>
                                                    </span>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-300 dark:text-slate-600 font-bold text-xs">
                                                    -
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Total Berkas -->
                                    <td class="py-3 px-3 text-center font-bold text-purple-700 dark:text-purple-300">
                                        {{ $m['total_uploaded'] }} File
                                    </td>

                                    <!-- Progress Bar -->
                                    <td class="py-3 px-3.5">
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[10px] font-bold">
                                                <span class="text-slate-500">Perangkat Inti</span>
                                                <span class="{{ $corePercent === 100 ? 'text-emerald-600' : 'text-slate-700 dark:text-slate-300' }}">{{ $corePercent }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-300 {{ $corePercent === 100 ? 'bg-emerald-500' : ($corePercent >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                                    style="width: {{ $corePercent }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- MODAL 1: UNGGAH / EDIT BERKAS GURU                                        -->
        <!-- ========================================================================= -->
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            
            <div @click.outside="modalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-xl w-full p-4 sm:p-6 text-left space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100"
                            x-text="isEdit ? 'Edit Data Berkas Guru' : 'Unggah Berkas Baru'">
                        </h3>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form :action="formAction" method="POST" enctype="multipart/form-data" class="space-y-3.5">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Row 1: Guru & Tahun Ajaran -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Guru -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Guru Pemilik Berkas <span class="text-rose-500">*</span>
                            </label>
                            <select name="teacher_id" x-model="formData.teacher_id" required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tahun Ajaran -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Tahun Ajaran <span class="text-rose-500">*</span>
                            </label>
                            <select name="academic_year_id" x-model="formData.academic_year_id" required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Jenis Berkas & Semester -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Jenis Berkas -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Kategori / Jenis Berkas <span class="text-rose-500">*</span>
                            </label>
                            <select name="category" x-model="formData.category" required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Semester -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Semester <span class="text-rose-500">*</span>
                            </label>
                            <select name="semester" x-model="formData.semester" required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                @foreach($semesters as $sem)
                                    <option value="{{ $sem }}">{{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Judul Dokumen -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Judul / Nama Dokumen <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" x-model="formData.title" required
                            placeholder="Contoh: Modul Ajar Sentra Main Peran Minggu 1-4"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>

                    <!-- File Upload Input -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            File Dokumen <span x-show="!isEdit" class="text-rose-500">*</span>
                            <span class="text-[10px] text-slate-400 font-normal lowercase">(PDF, DOCX, XLSX, ZIP, Gambar maks 25MB)</span>
                        </label>
                        <input type="file" name="file" :required="!isEdit"
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-purple-950/50 dark:file:text-purple-300 cursor-pointer">
                        <template x-if="isEdit && currentFileName">
                            <p class="text-[11px] text-slate-400 mt-1">
                                File saat ini: <strong class="text-purple-600" x-text="currentFileName"></strong> (Biarkan kosong jika tidak ingin mengganti file)
                            </p>
                        </template>
                    </div>

                    <!-- Keterangan Tambahan -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Keterangan Tambahan (Deskripsi / Catatan)
                        </label>
                        <textarea name="notes" x-model="formData.notes" rows="2"
                            placeholder="Tuliskan catatan tambahan mengenai berkas ini jika diperlukan..."
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 leading-relaxed"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                            <span x-text="isEdit ? 'Simpan Perubahan' : 'Unggah Berkas'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL 2: VERIFIKASI / SUPERVISI STATUS (ADMIN / KEPALA SEKOLAH)           -->
        <!-- ========================================================================= -->
        <div x-show="reviewModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            
            <div @click.outside="reviewModalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-4 sm:p-6 text-left space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                            <i data-lucide="check-square" class="w-4 h-4"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100">
                            Supervisi & Verifikasi Berkas
                        </h3>
                    </div>
                    <button type="button" @click="reviewModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form :action="reviewAction" method="POST" class="space-y-3.5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Dokumen:</span>
                        <span class="font-bold text-xs text-slate-800 dark:text-slate-200 block" x-text="reviewDoc?.title"></span>
                        <span class="text-[11px] text-purple-600 dark:text-purple-400 font-semibold" x-text="'Guru: ' + (reviewDoc?.teacher?.name || '')"></span>
                    </div>

                    <!-- Status Selection -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Status Hasil Telaah / Supervisi <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" x-model="reviewForm.status" required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="Menunggu Review">Menunggu Review</option>
                            <option value="Disetujui">Disetujui (Lolos Supervisi)</option>
                            <option value="Perlu Revisi">Perlu Revisi (Perbaiki)</option>
                        </select>
                    </div>

                    <!-- Catatan Feedback Supervisi -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Catatan Telaah / Rekomendasi Perbaikan
                        </label>
                        <textarea name="feedback" x-model="reviewForm.feedback" rows="3"
                            placeholder="Tuliskan masukan dari Kepala Sekolah atau bagian kurikulum..."
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 leading-relaxed"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="reviewModalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                            Simpan Hasil Supervisi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL 3: LIHAT DETAIL BERKAS                                              -->
        <!-- ========================================================================= -->
        <div x-show="viewModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            
            <div @click.outside="viewModalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-4 sm:p-6 text-left space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                            <i data-lucide="file-check" class="w-4 h-4"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100">
                            Rincian Berkas Administrasi
                        </h3>
                    </div>
                    <button type="button" @click="viewModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="space-y-3.5 text-xs" x-show="viewData">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase"
                                    :class="viewData?.category_badge_class"
                                    x-text="viewData?.category"></span>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100 mt-1" x-text="viewData?.title"></h4>
                                <div class="text-[11px] text-slate-500 mt-0.5" x-text="'Guru: ' + (viewData?.teacher?.name || '-')"></div>
                            </div>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                :class="viewData?.status_badge_class"
                                x-text="viewData?.status"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-2.5 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold text-slate-400 block uppercase">Semester & T.A</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(viewData?.semester || '-') + ' / ' + (viewData?.academic_year?.name || '-')"></span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold text-slate-400 block uppercase">Ukuran File</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="viewData?.formatted_file_size || '-'"></span>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="p-2.5 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-400 block uppercase">Keterangan Tambahan</span>
                        <p class="text-slate-700 dark:text-slate-300 mt-0.5 whitespace-pre-line" x-text="viewData?.notes || 'Tidak ada keterangan tambahan.'"></p>
                    </div>

                    <!-- Feedback Catatan Supervisi -->
                    <template x-if="viewData?.feedback">
                        <div class="p-3 rounded-lg bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40">
                            <span class="text-[10px] font-bold text-rose-700 dark:text-rose-400 block uppercase">Catatan Review Kepala Sekolah:</span>
                            <p class="text-rose-900 dark:text-rose-200 mt-0.5 whitespace-pre-line font-medium italic" x-text="viewData?.feedback"></p>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                    <template x-if="viewData">
                        <a :href="'/teacher-documents/' + viewData.id + '/download'"
                            class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg transition-colors inline-flex items-center gap-1.5">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span>Unduh File Dokumen</span>
                        </a>
                    </template>
                    <button type="button" @click="viewModalOpen = false"
                        class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors cursor-pointer ml-auto">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- ALPINE.JS SCRIPT -->
    <script>
        function teacherDocsApp() {
            return {
                modalOpen: false,
                reviewModalOpen: false,
                viewModalOpen: false,
                isEdit: false,
                formAction: "{{ route('teacher-documents.store') }}",
                reviewAction: "",
                viewData: null,
                reviewDoc: null,
                currentFileName: "",
                formData: {
                    id: null,
                    teacher_id: "{{ $myEmployeeId ?: ($teachers->first()?->id ?? '') }}",
                    academic_year_id: "{{ $selectedYearId }}",
                    semester: "Tahunan",
                    category: "RPP",
                    title: "",
                    notes: ""
                },
                reviewForm: {
                    status: "Disetujui",
                    feedback: ""
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formAction = "{{ route('teacher-documents.store') }}";
                    this.currentFileName = "";
                    this.formData = {
                        id: null,
                        teacher_id: "{{ $myEmployeeId ?: ($teachers->first()?->id ?? '') }}",
                        academic_year_id: "{{ $selectedYearId }}",
                        semester: "Tahunan",
                        category: "RPP",
                        title: "",
                        notes: ""
                    };
                    this.modalOpen = true;
                },

                openEditModal(doc) {
                    this.isEdit = true;
                    this.formAction = "/teacher-documents/" + doc.id;
                    this.currentFileName = doc.file_name || doc.file_path;
                    this.formData = {
                        id: doc.id,
                        teacher_id: doc.teacher_id,
                        academic_year_id: doc.academic_year_id,
                        semester: doc.semester || "Tahunan",
                        category: doc.category || "RPP",
                        title: doc.title || "",
                        notes: doc.notes || ""
                    };
                    this.modalOpen = true;
                },

                openReviewModal(doc) {
                    this.reviewDoc = doc;
                    this.reviewAction = "/teacher-documents/" + doc.id + "/status";
                    this.reviewForm = {
                        status: doc.status || "Disetujui",
                        feedback: doc.feedback || ""
                    };
                    this.reviewModalOpen = true;
                },

                openViewModal(doc) {
                    this.viewData = doc;
                    this.viewModalOpen = true;
                }
            };
        }
    </script>
</x-admin-layout>
