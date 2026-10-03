<x-admin-layout>
    <div class="p-6 space-y-6" x-data="homeroomApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2.5 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-500/20 shadow-xs">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Wali Kelas
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-800">
                                Master Akademik
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Penugasan wali kelas / pendidik per rombongan belajar tersimpan per tahun pelajaran.</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('teachers.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Data Guru
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tugaskan Wali Kelas
                </button>
            </div>
        </section>

        <!-- STATS CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tahun Pelajaran</p>
                        <h3 class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ $academicYears->firstWhere('id', $selectedYearId)?->name ?? ($activeAcademicYear?->name ?? '-') }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Periode akademik yang dipilih
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Penugasan</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($assignments->total()) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Wali kelas terdaftar pada periode ini
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Rombel Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($classrooms->count()) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Jumlah rombel / kelompok belajar
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Data Guru</p>
                        <h3 class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($teachers->count()) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Pendidik aktif siap ditugaskan
                </div>
            </div>
        </section>

        <!-- FILTER JENJANG TABS (DINAMIS DARI RELASI JENJANG - TANPA SEMUA JENJANG) -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            @foreach($jenjangs as $j)
                <a href="{{ route('homeroom-assignments.index', array_merge(request()->query(), ['jenjang_id' => $j->id])) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-150 inline-flex items-center gap-2 cursor-pointer {{ $selectedJenjangId == $j->id ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                    @if($j->code === 'KB' || $j->code === 'PG')
                        <span>🧸</span>
                    @elseif($j->code === 'TK')
                        <span>🎒</span>
                    @elseif($j->code === 'DAYCARE' || $j->code === 'TPA')
                        <span>👶</span>
                    @elseif($j->code === 'TPQ')
                        <span>📖</span>
                    @endif
                    <span>{{ $j->name }}</span>
                </a>
            @endforeach
        </div>

        <!-- FILTERS BAR -->
        <form method="GET" action="{{ route('homeroom-assignments.index') }}" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <input type="hidden" name="jenjang_id" value="{{ $selectedJenjangId }}">
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <!-- Filter 1: Tahun Pelajaran -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Tahun Pelajaran</label>
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold">
                        <option value="all" {{ $selectedYearId === 'all' ? 'selected' : '' }}>-- Semua Tapel --</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter 2: Status -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Status</label>
                    <select name="status" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                        <option value="all" {{ $selectedStatus === 'all' || $selectedStatus === null ? 'selected' : '' }}>Semua Status</option>
                        <option value="1" {{ $selectedStatus === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $selectedStatus === '0' ? 'selected' : '' }}>Non-aktif</option>
                    </select>
                </div>
            </div>

            <!-- Search box -->
            <div class="w-full md:w-64 relative mt-2 md:mt-0">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">Pencarian</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama wali kelas/rombel..."
                        style="padding-left: 2.25rem;"
                        class="w-full h-9 pr-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                </div>
            </div>
        </form>

        <!-- TABLE LIST WALI KELAS -->
        <!-- Kolom: tahun pelajaran, jenjang, kelas, rombel, wali kelas (pilihan dari table data guru), status, aksi -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="user-check" class="w-4 h-4 text-indigo-600"></i>
                    Daftar Penugasan Wali Kelas
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Tahun Pelajaran</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Kelas</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-44">Rombel</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Wali Kelas</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($assignments as $a)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                
                                <!-- 1. Tahun Pelajaran -->
                                <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-slate-100">
                                    <span class="inline-flex items-center gap-1.5 font-bold">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        {{ $a->academicYear?->name ?? '-' }}
                                    </span>
                                    @if($a->academicYear?->is_active)
                                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-bold">Aktif</span>
                                    @endif
                                </td>

                                <!-- 2. Jenjang (Relasi dari Table Jenjang) -->
                                <td class="px-5 py-3.5">
                                    @php
                                        $jName = $a->jenjang?->name ?? $a->classroom?->jenjang?->name ?? $a->classLevel?->jenjang?->name;
                                    @endphp
                                    @if($jName)
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            {{ $jName }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>

                                <!-- 3. Kelas (Relasi dari Table Kelas) -->
                                <td class="px-5 py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $a->classLevel?->name ?? $a->classroom?->classLevel?->name ?? '-' }}
                                </td>

                                <!-- 4. Rombel (Relasi dari Table Rombel) -->
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $a->classroom?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 5. Wali Kelas (Pilihan dari Table Data Guru) -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        @if($a->teacher?->photo)
                                            <img src="{{ asset('storage/' . $a->teacher->photo) }}" alt="{{ $a->teacher->name }}" class="w-7 h-7 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ substr($a->teacher?->name ?? 'G', 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-slate-100 leading-tight">
                                                {{ $a->teacher?->name ?? 'Belum Ditugaskan' }}
                                            </p>
                                            @if($a->teacher?->phone)
                                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $a->teacher->phone }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Status -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($a->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Non-aktif
                                        </span>
                                    @endif
                                </td>

                                <!-- 7. Aksi -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ json_encode($a) }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Wali Kelas">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="deleteAssignment({{ $a->id }}, '{{ $a->classroom?->name }}', '{{ $a->teacher?->name }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Penugasan">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    Belum ada penugasan wali kelas pada periode / filter ini.
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
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
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
                    const defaultJenjangId = '{{ $selectedJenjangId }}' || (allLevels.length > 0 ? allLevels[0].jenjang_id : '');
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
    </script>
</x-admin-layout>
