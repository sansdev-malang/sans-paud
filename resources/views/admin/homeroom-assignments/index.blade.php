<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-3.5 sm:space-y-4" x-data="homeroomApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Wali Kelas</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Penugasan wali kelas / pendidik per rombongan belajar tersimpan per tahun pelajaran.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('teachers.index') }}"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Data Guru
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tugaskan Wali Kelas
                </button>
            </div>
        </section>

        <!-- COMPACT KPI STATS (MINIMALIS & LANGSUNG FOKUS KE TABEL) -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
            <!-- Stat 1: Total Penugasan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Penugasan</p>
                    <p class="text-sm sm:text-base font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-0.5">
                        {{ number_format($stats['total_assignments'] ?? $assignments->total()) }} <span class="text-[11px] font-normal text-slate-400">Wali Kelas</span>
                    </p>
                </div>
                <div class="p-2 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-lg shrink-0">
                    <i data-lucide="user-check" class="w-4 h-4"></i>
                </div>
            </div>

            <!-- Stat 2: Total Rombel Terisi -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rombel Terisi</p>
                    <p class="text-sm sm:text-base font-bold tracking-tight text-emerald-600 dark:text-emerald-400 mt-0.5">
                        {{ number_format($stats['assigned_classrooms'] ?? 0) }} <span class="text-[11px] font-normal text-slate-400">/ {{ number_format($stats['total_classrooms'] ?? $classrooms->count()) }} Kelompok</span>
                    </p>
                </div>
                <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-lg shrink-0">
                    <i data-lucide="shapes" class="w-4 h-4"></i>
                </div>
            </div>

            <!-- Stat 3: Guru Siap Ditugaskan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Guru Tersedia</p>
                    <p class="text-sm sm:text-base font-bold tracking-tight text-amber-600 dark:text-amber-400 mt-0.5">
                        {{ number_format($stats['total_teachers'] ?? $teachers->count()) }} <span class="text-[11px] font-normal text-slate-400">Pendidik</span>
                    </p>
                </div>
                <div class="p-2 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg shrink-0">
                    <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                </div>
            </div>
        </section>

        <!-- FILTERS TOOLBAR (KIRI: TAPEL, JENJANG, KELAS | KANAN: SEARCH MANDIRI) -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
            <form method="GET" action="{{ route('homeroom-assignments.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                
                <!-- Left: Dropdown Tapel, Jenjang & Kelas -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- 1. Tahun Pelajaran (Tapel - Tanpa Pilihan Semua Tapel) -->
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- 2. Jenjang -->
                    <select name="jenjang_id" onchange="handleJenjangFilterChange(this)"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="all" {{ $selectedJenjangId === 'all' || empty($selectedJenjangId) ? 'selected' : '' }}>Semua Jenjang</option>
                        @foreach($jenjangs as $j)
                            <option value="{{ $j->id }}" {{ $selectedJenjangId == $j->id ? 'selected' : '' }}>
                                {{ $j->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- 3. Kelas (Sesuai Jenjang yang Sedang Dipilih) -->
                    <select name="class_level_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="all" {{ $selectedClassLevelId === 'all' || empty($selectedClassLevelId) ? 'selected' : '' }}>Semua Kelas</option>
                        @foreach($availableClassLevels as $lvl)
                            <option value="{{ $lvl->id }}" {{ $selectedClassLevelId == $lvl->id ? 'selected' : '' }}>
                                {{ $lvl->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- 4. Jumlah Baris -->
                    <select name="per_page" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                        <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10 Data</option>
                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 Data</option>
                        <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25 Data</option>
                        <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50 Data</option>
                        <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100 Data</option>
                        <option value="all" {{ request('per_page') == 'all' || request('per_page') == '99999' ? 'selected' : '' }}>Semua</option>
                    </select>

                    @if(request()->filled('search') || (request('jenjang_id') && request('jenjang_id') !== 'all') || (request('class_level_id') && request('class_level_id') !== 'all') || (request()->filled('per_page') && request('per_page') != 15))
                        <a href="{{ route('homeroom-assignments.index', ['academic_year_id' => $selectedYearId]) }}" class="h-9 px-2.5 flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 rounded-lg transition-colors" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>

                <!-- Right: Search Bar Berdiri Sendiri -->
                <div x-data="{ searchVal: '{{ request('search') }}' }" class="w-full sm:w-72 md:w-80 flex items-center search-container bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500">
                    <span class="pl-3 text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="text" name="search" x-model="searchVal" placeholder="Cari nama guru, email, NUPTK, rombel..."
                        style="border: none !important; outline: none !important; box-shadow: none !important;"
                        class="w-full h-9 px-2.5 text-xs bg-transparent text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-0">
                    
                    <button type="button" x-show="searchVal.trim() !== ''" @click="searchVal = ''; $el.closest('.search-container').querySelector('input').focus();" class="h-9 px-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer bg-transparent border-0 flex items-center justify-center" title="Bersihkan pencarian">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>

                    <button type="submit" 
                        class="h-9 px-3.5 font-semibold text-xs bg-slate-100 dark:bg-slate-700/60 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 text-slate-700 dark:text-slate-200 transition-colors cursor-pointer whitespace-nowrap flex items-center justify-center border-l border-slate-200 dark:border-slate-700/80">
                        Cari
                    </button>
                </div>

            </form>
        </section>

        <!-- TABLE LIST WALI KELAS -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/30">
                <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Daftar Penugasan Wali Kelas
                </h3>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                    Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $assignments->count() }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $assignments->total() }}</span> data
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Tahun Pelajaran</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Jenjang</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Kelas</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Rombel</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Wali Kelas</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-24">Status</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($assignments as $a)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                
                                <!-- 1. Tahun Pelajaran -->
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 font-bold">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $a->academicYear?->name ?? '-' }}</span>
                                    </div>
                                </td>

                                <!-- 2. Jenjang -->
                                <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    {{ $a->jenjang?->name ?? $a->classroom?->jenjang?->name ?? $a->classLevel?->jenjang?->name ?? '-' }}
                                </td>

                                <!-- 3. Kelas -->
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $a->classLevel?->name ?? $a->classroom?->classLevel?->name ?? '-' }}
                                </td>

                                <!-- 4. Rombel -->
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                    {{ $a->classroom?->name ?? '-' }}
                                </td>

                                <!-- 5. Wali Kelas -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5 min-w-[200px]">
                                        @if($a->teacher?->photo)
                                            <img src="{{ asset('storage/' . $a->teacher->photo) }}" alt="{{ $a->teacher->name }}" class="w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ substr($a->teacher?->name ?? 'G', 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-slate-900 dark:text-slate-100 leading-snug truncate">
                                                {{ $a->teacher?->name ?? 'Belum Ditugaskan' }}
                                            </p>
                                            
                                            @php
                                                $teacherEmail = $a->teacher?->email ?? $a->teacher?->user?->email;
                                            @endphp
                                            
                                            @if($teacherEmail)
                                                <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium truncate flex items-center gap-1 mt-0.5" title="{{ $teacherEmail }}">
                                                    <i data-lucide="mail" class="w-3 h-3 shrink-0 text-slate-400"></i>
                                                    <span class="truncate">{{ $teacherEmail }}</span>
                                                </div>
                                            @endif

                                            <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono mt-0.5">
                                                @if($a->teacher?->nuptk)
                                                    <span>NUPTK: {{ $a->teacher->nuptk }}</span>
                                                @elseif($a->teacher?->nip)
                                                    <span>NIP: {{ $a->teacher->nip }}</span>
                                                @elseif($a->teacher?->phone)
                                                    <span>{{ $a->teacher->phone }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Status -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($a->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Non-aktif
                                        </span>
                                    @endif
                                </td>

                                <!-- 7. Aksi -->
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ json_encode($a) }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Penugasan">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button type="button" @click="deleteAssignment({{ $a->id }}, '{{ addslashes($a->classroom?->name ?? '') }}', '{{ addslashes($a->teacher?->name ?? '') }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Penugasan">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="p-3 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400">
                                            <i data-lucide="user-x" class="w-6 h-6"></i>
                                        </div>
                                        <p class="font-medium text-xs text-slate-600 dark:text-slate-400">Belum ada penugasan wali kelas pada periode / filter ini.</p>
                                        <button type="button" @click="openCreateModal()" class="mt-1 inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            Tugaskan Wali Kelas Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $assignments->links() }}
                </div>
            @endif
        </section>

        <!-- MODAL TAMBAH / EDIT WALI KELAS -->
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="margin: 0px !important; margin-top: 0px !important; top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col text-left">
                    
                    <form @submit.prevent="submitForm">
                        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Penugasan Wali Kelas' : 'Tugaskan Wali Kelas Baru'"></h3>
                                <p class="text-xs text-slate-400 mt-0.5">Pilih tahun pelajaran, jenjang, kelas, rombel, dan guru yang ditugaskan.</p>
                            </div>
                            <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="p-6 space-y-4 text-xs">
                            <!-- 1. Tahun Pelajaran -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Pelajaran <span class="text-rose-500">*</span></label>
                                <select x-model="formData.academic_year_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                                    <option value="">-- Pilih Tahun Pelajaran --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}">{{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- 2. Jenjang -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenjang <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.jenjang_id" @change="onJenjangChange()" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        <option value="">-- Pilih Jenjang --</option>
                                        @foreach($jenjangs as $j)
                                            <option value="{{ $j->id }}">{{ $j->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 3. Kelas -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelas <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.class_level_id" @change="onClassLevelChange()" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                        <option value="">-- Pilih Kelas --</option>
                                        <template x-for="lvl in filteredClassLevels" :key="lvl.id">
                                            <option :value="lvl.id" x-text="lvl.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <!-- 4. Rombel -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Rombel / Kelompok <span class="text-rose-500">*</span></label>
                                <select x-model="formData.classroom_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-bold">
                                    <option value="">-- Pilih Rombel / Kelompok --</option>
                                    <template x-for="c in filteredClassrooms" :key="c.id">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- 5. Wali Kelas (Pilihan dari Table Data Guru) -->
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Wali Kelas (Pendidik / Guru) <span class="text-rose-500">*</span></label>
                                <select x-model="formData.employee_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    <option value="">-- Pilih Guru / Pendidik --</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} {{ $t->position ? '(' . $t->position . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan / Keterangan</label>
                                <textarea x-model="formData.notes" rows="2" placeholder="Catatan opsional mengenai penugasan ini..."
                                    class="w-full p-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                            </div>

                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-200">Status Penugasan</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Tandai aktif agar otomatis tersambung ke data rombel dan rapor.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="formData.is_active" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                            <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                                <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tugaskan Wali Kelas')"></span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </template>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function homeroomApp() {
            const allLevels = @json($classLevels);
            const allClasses = @json($classrooms);
            const activeAYId = '{{ $selectedYearId ?? ($activeAcademicYear?->id ?? '') }}';

            return {
                modalOpen: false,
                isEdit: false,
                saving: false,

                formData: {
                    id: null,
                    academic_year_id: activeAYId,
                    jenjang_id: '',
                    class_level_id: '',
                    classroom_id: '',
                    employee_id: '',
                    is_active: true,
                    notes: '',
                },

                get filteredClassLevels() {
                    if (!this.formData.jenjang_id) return allLevels;
                    return allLevels.filter(lvl => lvl.jenjang_id == this.formData.jenjang_id);
                },

                get filteredClassrooms() {
                    let res = allClasses;
                    if (this.formData.jenjang_id) {
                        res = res.filter(c => c.jenjang_id == this.formData.jenjang_id || (c.class_level && c.class_level.jenjang_id == this.formData.jenjang_id));
                    }
                    if (this.formData.class_level_id) {
                        res = res.filter(c => c.class_level_id == this.formData.class_level_id);
                    }
                    return res;
                },

                onJenjangChange() {
                    const validLevel = this.filteredClassLevels.some(l => l.id == this.formData.class_level_id);
                    if (!validLevel) {
                        this.formData.class_level_id = this.filteredClassLevels.length > 0 ? this.filteredClassLevels[0].id : '';
                    }
                    this.onClassLevelChange();
                },

                onClassLevelChange() {
                    const validClass = this.filteredClassrooms.some(c => c.id == this.formData.classroom_id);
                    if (!validClass) {
                        this.formData.classroom_id = this.filteredClassrooms.length > 0 ? this.filteredClassrooms[0].id : '';
                    }
                },

                openCreateModal() {
                    this.isEdit = false;
                    const defaultJenjangId = ('{{ $selectedJenjangId }}' && '{{ $selectedJenjangId }}' !== 'all')
                        ? '{{ $selectedJenjangId }}'
                        : (allLevels.length > 0 ? allLevels[0].jenjang_id : '');
                    this.formData = {
                        id: null,
                        academic_year_id: activeAYId,
                        jenjang_id: defaultJenjangId,
                        class_level_id: '',
                        classroom_id: '',
                        employee_id: '',
                        is_active: true,
                        notes: '',
                    };
                    this.onJenjangChange();
                    this.modalOpen = true;
                },

                openEditModal(assignment) {
                    this.isEdit = true;
                    this.formData = {
                        id: assignment.id,
                        academic_year_id: assignment.academic_year_id,
                        jenjang_id: assignment.jenjang_id || (assignment.classroom ? assignment.classroom.jenjang_id : ''),
                        class_level_id: assignment.class_level_id || (assignment.classroom ? assignment.classroom.class_level_id : ''),
                        classroom_id: assignment.classroom_id,
                        employee_id: assignment.employee_id,
                        is_active: !!assignment.is_active,
                        notes: assignment.notes || '',
                    };
                    this.modalOpen = true;
                },

                submitForm() {
                    if (this.saving) return;
                    this.saving = true;

                    const url = this.isEdit ? `/homeroom-assignments/${this.formData.id}` : '/homeroom-assignments';
                    const method = this.isEdit ? 'PUT' : 'POST';

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.formData)
                    })
                    .then(res => res.json())
                    .then(res => {
                        this.saving = false;
                        if (res.success) {
                            this.modalOpen = false;
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(res.message || 'Wali kelas berhasil disimpan!', 'success');
                            }
                            const targetJenjang = res.jenjang_id || this.formData.jenjang_id || '{{ $selectedJenjangId }}';
                            const targetTapel = res.academic_year_id || this.formData.academic_year_id || '{{ $selectedYearId }}';
                            const url = new URL(window.location.origin + window.location.pathname);
                            url.searchParams.set('academic_year_id', targetTapel);
                            url.searchParams.set('jenjang_id', targetJenjang);
                            window.location.href = url.toString();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', res.message || 'Terjadi kesalahan saat menyimpan.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        this.saving = false;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                        }
                    });
                },

                deleteAssignment(id, rombelName, teacherName) {
                    const action = () => {
                        fetch(`/homeroom-assignments/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                if (typeof window.setPendingToast === 'function') {
                                    window.setPendingToast(res.message || 'Penugasan berhasil dihapus!', 'success');
                                }
                                const targetJenjang = res.jenjang_id || '{{ $selectedJenjangId }}';
                                const targetTapel = res.academic_year_id || '{{ $selectedYearId }}';
                                const url = new URL(window.location.origin + window.location.pathname);
                                url.searchParams.set('academic_year_id', targetTapel);
                                url.searchParams.set('jenjang_id', targetJenjang);
                                window.location.href = url.toString();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal menghapus penugasan.', 'error');
                                }
                            }
                        })
                        .catch(err => {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                            }
                        });
                    };

                    if (typeof showGlobalConfirmModal === 'function') {
                        showGlobalConfirmModal(`Apakah Anda yakin ingin menghapus penugasan wali kelas "${teacherName}" di rombel "${rombelName}"?`, action, true);
                    } else if (confirm(`Apakah Anda yakin ingin menghapus penugasan wali kelas "${teacherName}" di rombel "${rombelName}"?`)) {
                        action();
                    }
                }
            }
        }

        function handleJenjangFilterChange(selectEl) {
            const form = selectEl.form;
            const classLevelSelect = form.querySelector('select[name="class_level_id"]');
            if (classLevelSelect) {
                classLevelSelect.value = 'all';
            }
            form.submit();
        }
    </script>
</x-admin-layout>
