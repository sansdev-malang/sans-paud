<x-admin-layout>
    <div class="p-6 space-y-6" x-data="rombelApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">
                    Kelompok Belajar
                </h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Kelola kelompok belajar, alokasi wali kelas, dan kuota daya tampung murid PAUD.</p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('academic-summary.index') }}"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all cursor-pointer">
                    <i data-lucide="file-bar-chart-2" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Rekapitulasi Rombel
                </a>
                <a href="{{ route('class-levels.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Tingkat Kelas
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Kelompok
                </button>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Stat 1: Total Kelompok -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i data-lucide="shapes" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Kelompok</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 font-mono">
                            {{ number_format($stats['total_classrooms']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Kelompok Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Stat 2: Total Kapasitas Kuota -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40">
                    <i data-lucide="door-open" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Kapasitas</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400 font-mono">
                            {{ number_format($stats['total_capacity']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Daya Tampung</span>
                    </div>
                </div>
            </div>

            <!-- Stat 3: Murid Terisi -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Murid Terdaftar</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ number_format($stats['total_enrolled']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Murid Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Stat 4: Persentase Keterisian -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-900/40">
                    <i data-lucide="pie-chart" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Keterisian</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                            {{ $stats['occupancy_rate'] }}%
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Rasio Kuota</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- FILTER JENJANG TABS (BERSIH & KONSISTEN) -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <button type="button" @click="setJenjangTab(0)"
                :class="activeJenjangId == 0 ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                <span>Semua Jenjang</span>
            </button>
            @foreach($jenjangs as $j)
                <button type="button" @click="setJenjangTab({{ $j->id }})"
                    :class="activeJenjangId == {{ $j->id }} ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                    <span>{{ $j->name }}</span>
                </button>
            @endforeach
        </div>

        <!-- SEARCH & FILTERS TOOLBAR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 sm:p-4 shadow-xs w-full">
            <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <!-- Search Box -->
                <div class="relative w-full md:max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 dark:text-slate-500"></i>
                    </span>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama kelompok..."
                        style="padding-left: 2.25rem;"
                        class="w-full h-9 pr-4 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 placeholder-slate-400 dark:placeholder-slate-500 transition-all shadow-inner">
                </div>

                <!-- Filter Toolbar -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Filter Tahun Ajaran -->
                    <select @change="onAcademicYearChange($event.target.value)"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                TA {{ $ay->name }} {{ $ay->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Tingkat Kelas -->
                    <select x-model="selectedClassLevelFilter"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Tingkat</option>
                        <template x-for="lvl in availableFilterLevels" :key="lvl.id">
                            <option :value="lvl.id" x-text="lvl.name"></option>
                        </template>
                    </select>

                    <button type="button" x-show="searchQuery || selectedClassLevelFilter !== 'all' || activeJenjangId != 0"
                        @click="searchQuery = ''; selectedClassLevelFilter = 'all'; setJenjangTab(0)"
                        class="h-9 px-3 inline-flex items-center justify-center text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
                        title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- TABLE KELOMPOK BELAJAR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="shapes" class="w-4 h-4 text-indigo-600"></i>
                        Daftar Kelompok Belajar
                    </h3>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Total: <strong class="text-slate-800 dark:text-slate-200" x-text="visibleClassroomsCount"></strong> kelompok
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-4 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12 whitespace-nowrap">No</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Nama Kelompok</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36 whitespace-nowrap">Tahun Ajaran</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Wali Kelas</th>
                            <th class="px-4 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-48 whitespace-nowrap">Kapasitas & Kuota</th>
                            <th class="px-4 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32 whitespace-nowrap">Daftar Murid</th>
                            <th class="px-4 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24 whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($classrooms as $index => $c)
                            @php
                                $filled = $c->active_students_count;
                                $cap = $c->capacity ?: 15;
                                $pct = min(100, round(($filled / $cap) * 100));
                                $jId = $c->jenjang_id ?: ($c->classLevel?->jenjang_id ?: 0);
                            @endphp
                            <tr x-show="isClassroomVisible({{ $c->id }}, {{ $jId }}, {{ $c->class_level_id ?? 0 }}, '{{ strtolower(addslashes($c->name . ' ' . ($c->code ?? ''))) }}')"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                
                                <!-- 1. No -->
                                <td class="px-4 py-3 text-center text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ $index + 1 }}
                                </td>

                                <!-- 2. Nama Kelompok -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight">
                                        {{ $c->name }}
                                    </span>
                                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        <span>Tingkat: {{ $c->classLevel?->name ?? '-' }}</span>
                                        @if($c->code)
                                            <span>&bull;</span>
                                            <span class="font-mono text-[10px]">{{ $c->code }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 3. Tahun Ajaran -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-mono text-xs font-medium text-slate-700 dark:text-slate-300">
                                        {{ $c->academicYear->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 4. Wali Kelas -->
                                <td class="px-4 py-3">
                                    @if($c->homeroomTeacher)
                                        <div class="flex items-center gap-2.5">
                                            @if($c->homeroomTeacher->photo_url)
                                                <img src="{{ $c->homeroomTeacher->photo_url }}" alt="{{ $c->homeroomTeacher->name }}" class="w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700 shadow-xs">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                                    {{ substr($c->homeroomTeacher->raw_name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="overflow-hidden min-w-0">
                                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">
                                                    {{ $c->homeroomTeacher->name }}
                                                </p>
                                                <p class="text-[10px] text-slate-400 truncate font-mono">
                                                    {{ $c->homeroomTeacher->phone ?: ($c->homeroomTeacher->niy ?: 'Wali Kelas') }}
                                                </p>
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 italic">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            Belum ditentukan
                                        </span>
                                    @endif
                                </td>

                                <!-- 5. Kapasitas & Kuota -->
                                <td class="px-4 py-3">
                                    <div class="w-full max-w-xs">
                                        <div class="flex justify-between items-center text-[11px] mb-1">
                                            <span class="font-bold text-slate-800 dark:text-slate-200">
                                                <span class="text-indigo-600 dark:text-indigo-400 font-bold font-mono">{{ $filled }}</span> / {{ $cap }} Murid
                                            </span>
                                            <span class="text-[10px] font-semibold text-slate-400 font-mono">{{ $pct }}%</span>
                                        </div>
                                        <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-300 {{ $pct >= 90 ? 'bg-rose-500' : ($pct >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                                style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 6. Daftar Murid -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <button type="button" @click="viewStudents({{ $c->id }})"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                                        title="Lihat Daftar Murid di Kelompok">
                                        <i data-lucide="users" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        <span>{{ $filled }} Murid</span>
                                    </button>
                                </td>

                                <!-- 7. Aksi -->
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ $c->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Kelompok">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button type="button" @click="deleteClassroom({{ $c->id }}, '{{ addslashes($c->name) }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Kelompok">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                    Belum ada data Kelompok Belajar.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Empty filter state -->
                        <tr x-show="visibleClassroomsCount === 0" x-cloak>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                        <i data-lucide="folder-open" class="w-5 h-5"></i>
                                    </div>
                                    <p class="font-bold text-slate-700 dark:text-slate-300 text-sm">Tidak Ada Kelompok Sesuai Filter</p>
                                    <p class="text-xs text-slate-400 leading-relaxed">Coba ubah kata kunci pencarian, tingkat kelas, atau tab jenjang.</p>
                                    <button type="button" @click="searchQuery = ''; selectedClassLevelFilter = 'all'; setJenjangTab(0)"
                                        class="mt-1 inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold cursor-pointer">
                                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                        <span>Reset Filter</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL DAFTAR MURID PER KELOMPOK -->
        <template x-teleport="body">
            <div x-show="studentsModalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; margin: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="studentsModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden shadow-2xl flex flex-col text-left">
                    
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white/95 dark:bg-slate-900/95">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2">
                                <span x-text="'Daftar Murid ' + (selectedClassroom?.name || '')"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold" x-text="classroomStudents.length + ' Murid'"></span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5" x-text="'Wali Kelas: ' + (selectedClassroom?.homeroom_teacher?.name || 'Belum Ditentukan')"></p>
                        </div>
                        <button type="button" @click="studentsModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Search inside modal -->
                    <div class="p-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <input type="text" x-model="studentFilterQuery" placeholder="Filter nama atau NIS murid..."
                            class="w-full h-8 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                    </div>

                    <div class="p-4 overflow-y-auto flex-1 divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-if="filteredStudents.length === 0">
                            <div class="text-center py-8 text-slate-400 text-xs">
                                Belum ada murid yang sesuai di kelompok ini.
                            </div>
                        </template>
                        <template x-for="(student, index) in filteredStudents" :key="student.id">
                            <div class="py-2.5 flex items-center justify-between gap-3 text-xs hover:bg-slate-50/50 dark:hover:bg-slate-800/30 px-2 rounded-lg transition-colors">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 text-center font-mono text-slate-400 text-[11px]" x-text="index + 1"></span>
                                    <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0"
                                        x-text="student.gender === 'P' ? '👧' : '👦'">
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-slate-100" x-text="student.full_name"></div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                            <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="'NIS: ' + student.nis"></span>
                                            <span>&bull;</span>
                                            <span x-text="'Panggilan: ' + (student.nickname || '-')"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-if="student.parent_phone">
                                        <a :href="'https://wa.me/' + student.parent_phone.replace(/[^0-9]/g, '')" target="_blank"
                                            class="px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 hover:bg-emerald-100 rounded-lg text-[11px] font-semibold flex items-center gap-1 transition-colors">
                                            <i data-lucide="message-circle" class="w-3 h-3"></i>
                                            <span>WA Ortu</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                        <button type="button" @click="studentsModalOpen = false" class="px-4 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold cursor-pointer">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </template>

        <!-- MODAL TAMBAH / EDIT KELOMPOK BELAJAR -->
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; margin: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col text-left">
                    
                    <form @submit.prevent="submitForm">
                        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Kelompok Belajar' : 'Tambah Kelompok Belajar Baru'"></h3>
                                <p class="text-xs text-slate-400 mt-0.5">Konfigurasi jenjang, tingkat kelas, nama kelompok, wali kelas, dan kuota.</p>
                            </div>
                            <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="p-6 space-y-4 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- 1. Tahun Ajaran -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.academic_year_id" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-semibold cursor-pointer">
                                        @foreach($academicYears as $ay)
                                            <option value="{{ $ay->id }}">{{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 2. Jenjang -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenjang Pendidikan <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.jenjang_id" @change="onJenjangChange()" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                        <option value="">-- Pilih Jenjang --</option>
                                        @foreach($jenjangs as $j)
                                            <option value="{{ $j->id }}">{{ $j->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- 3. Tingkat Kelas -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tingkat Kelas <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.class_level_id" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                        <option value="">-- Pilih Tingkat Kelas --</option>
                                        <template x-for="lvl in filteredClassLevels" :key="lvl.id">
                                            <option :value="lvl.id" x-text="lvl.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- 4. Nama Kelompok -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kelompok Belajar <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="formData.name" required placeholder="Contoh: KB A1 / TK Aisyah / TPA 1"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                            </div>

                            <!-- 5. Wali Kelas (Searchable Dropdown) -->
                            <div class="relative" @click.outside="teacherDropdownOpen = false" @keydown.escape.window="teacherDropdownOpen = false">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Wali Kelas</label>
                                
                                <div @click="teacherDropdownOpen = !teacherDropdownOpen; if(teacherDropdownOpen) $nextTick(() => { $refs.teacherSearchInput.focus(); if (window.lucide) lucide.createIcons(); })"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg flex items-center justify-between cursor-pointer transition-all"
                                    :class="teacherDropdownOpen ? 'ring-2 ring-indigo-500/20 border-indigo-500' : 'hover:border-slate-300 dark:hover:border-slate-700'">
                                    
                                    <div class="flex items-center gap-2 overflow-hidden min-w-0">
                                        <template x-if="selectedTeacher">
                                            <div class="flex items-center gap-2 truncate">
                                                <template x-if="selectedTeacher.photo_url">
                                                    <img :src="selectedTeacher.photo_url" class="w-5 h-5 rounded-full object-cover shrink-0 border border-slate-200 dark:border-slate-700" alt="">
                                                </template>
                                                <template x-if="!selectedTeacher.photo_url">
                                                    <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 flex items-center justify-center text-[10px] font-bold shrink-0">
                                                        <span x-text="(selectedTeacher.name || 'G').charAt(0)"></span>
                                                    </div>
                                                </template>
                                                <span class="font-semibold text-slate-800 dark:text-slate-100 truncate" x-text="selectedTeacher.name"></span>
                                            </div>
                                        </template>
                                        <template x-if="!selectedTeacher">
                                            <span class="text-slate-400 dark:text-slate-500">Pilih Wali Kelas...</span>
                                        </template>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0 ml-1.5">
                                        <button type="button" x-show="formData.homeroom_teacher_id" @click.stop="clearTeacher()"
                                            class="w-5 h-5 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded transition-colors">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                            :class="teacherDropdownOpen ? 'transform rotate-180 text-indigo-600' : ''"></i>
                                    </div>
                                </div>

                                <input type="hidden" name="homeroom_teacher_id" :value="formData.homeroom_teacher_id">

                                <!-- Dropdown Popover -->
                                <div x-show="teacherDropdownOpen" x-cloak
                                    class="absolute left-0 right-0 bottom-full mb-2 z-[100] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl overflow-hidden text-xs">
                                    <div class="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-900/90">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                            </span>
                                            <input type="text" x-model="teacherSearch" x-ref="teacherSearchInput"
                                                @input="$nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                                                placeholder="Ketik nama guru..."
                                                style="padding-left: 2rem;"
                                                class="w-full h-8 pr-3 text-xs bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 dark:text-slate-100 placeholder-slate-400">
                                        </div>
                                    </div>
                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800/40 p-1">
                                        <div @click="selectTeacher(null)"
                                            class="px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer rounded-lg text-slate-500 flex items-center justify-between">
                                            <span>-- Tanpa Wali Kelas --</span>
                                            <template x-if="!formData.homeroom_teacher_id">
                                                <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600"></i>
                                            </template>
                                        </div>
                                        <template x-for="t in filteredTeachers" :key="t.id">
                                            <div @click="selectTeacher(t)"
                                                class="px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer rounded-lg flex items-center justify-between transition-colors">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <template x-if="t.photo_url">
                                                        <img :src="t.photo_url" class="w-6 h-6 rounded-full object-cover shrink-0 border border-slate-200 dark:border-slate-700">
                                                    </template>
                                                    <template x-if="!t.photo_url">
                                                        <div class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] font-bold shrink-0">
                                                            <span x-text="(t.name || 'G').charAt(0)"></span>
                                                        </div>
                                                    </template>
                                                    <div class="min-w-0">
                                                        <p class="truncate font-semibold text-slate-800 dark:text-slate-100" x-text="t.name"></p>
                                                        <p class="text-[10px] text-slate-400 font-mono" x-text="t.phone || t.niy || ''"></p>
                                                    </div>
                                                </div>
                                                <template x-if="formData.homeroom_teacher_id == t.id">
                                                    <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0 ml-2"></i>
                                                </template>
                                            </div>
                                        </template>
                                        <div x-show="filteredTeachers.length === 0" class="py-4 text-center text-xs text-slate-400">
                                            Guru tidak ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Kelompok</label>
                                    <input type="text" x-model="formData.code" placeholder="Contoh: KB-A1 / TK-A1"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono uppercase">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Daya Tampung (Kapasitas)</label>
                                    <input type="number" x-model.number="formData.capacity" min="1" max="100" placeholder="15 / 20" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Keterangan</label>
                                <textarea x-model="formData.description" rows="2" placeholder="Keterangan opsional untuk kelompok ini..."
                                    class="w-full p-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                            </div>

                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-200">Status Aktif</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Aktifkan kelompok ini agar tersedia untuk penempatan murid.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="formData.is_active" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                            <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                                <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Kelompok')"></span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </template>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function rombelApp() {
            const allLevels = @json($classLevels);
            const rawClassrooms = @json($classrooms);
            const jenjangList = @json($jenjangs);
            const teachersList = @json($teachers);
            const urlParams = new URLSearchParams(window.location.search);
            const initialJenjangParam = urlParams.get('jenjang_id');
            const currentSelectedYearId = '{{ $selectedYearId }}';

            // Hitung jumlah kelompok per jenjang pada TA terpilih
            const counts = {};
            jenjangList.forEach(j => { counts[j.id] = 0; });
            rawClassrooms.forEach(c => {
                const jId = c.jenjang_id || (c.class_level ? c.class_level.jenjang_id : 0);
                if (jId) {
                    counts[jId] = (counts[jId] || 0) + 1;
                }
            });

            // Default ke 0 (Semua Jenjang) atau ID jenjang dari URL
            const initialJenjangId = initialJenjangParam !== null ? parseInt(initialJenjangParam) : 0;

            return {
                modalOpen: false,
                studentsModalOpen: false,
                isEdit: false,
                saving: false,
                activeJenjangId: initialJenjangId,
                selectedClassLevelFilter: 'all',
                selectedYearId: currentSelectedYearId,
                searchQuery: '',
                studentFilterQuery: '',
                selectedClassroom: {},
                classroomStudents: [],
                allClassroomsList: rawClassrooms,
                teachersList: teachersList,
                countsPerJenjang: counts,
                teacherSearch: '',
                teacherDropdownOpen: false,

                get selectedTeacher() {
                    if (!this.formData.homeroom_teacher_id) return null;
                    return this.teachersList.find(t => t.id == this.formData.homeroom_teacher_id) || null;
                },

                get filteredTeachers() {
                    if (!this.teacherSearch) return this.teachersList;
                    const q = this.teacherSearch.toLowerCase();
                    return this.teachersList.filter(t => (t.name && t.name.toLowerCase().includes(q)));
                },

                selectTeacher(teacher) {
                    this.formData.homeroom_teacher_id = teacher ? teacher.id : null;
                    this.teacherDropdownOpen = false;
                    this.teacherSearch = '';
                },

                clearTeacher() {
                    this.formData.homeroom_teacher_id = null;
                    this.teacherSearch = '';
                },

                get availableFilterLevels() {
                    if (this.activeJenjangId == 0) return allLevels;
                    return allLevels.filter(l => l.jenjang_id == this.activeJenjangId);
                },

                get visibleClassroomsCount() {
                    return this.allClassroomsList.filter(c => {
                        const jId = c.jenjang_id || (c.class_level ? c.class_level.jenjang_id : 0);
                        const lvlId = c.class_level_id || 0;
                        const matchText = ((c.name || '') + ' ' + (c.code || '')).toLowerCase();
                        return this.isClassroomVisible(c.id, jId, lvlId, matchText);
                    }).length;
                },

                isClassroomVisible(id, jenjangId, levelId, searchStr) {
                    if (this.activeJenjangId != 0 && jenjangId != this.activeJenjangId) {
                        return false;
                    }
                    if (this.selectedClassLevelFilter !== 'all' && levelId != this.selectedClassLevelFilter) {
                        return false;
                    }
                    if (this.searchQuery && !searchStr.includes(this.searchQuery.toLowerCase())) {
                        return false;
                    }
                    return true;
                },

                formData: {
                    id: null,
                    academic_year_id: currentSelectedYearId,
                    jenjang_id: '',
                    class_level_id: '',
                    name: '',
                    code: '',
                    homeroom_teacher_id: null,
                    capacity: 15,
                    is_active: true,
                    description: '',
                },

                setJenjangTab(id) {
                    this.activeJenjangId = id;
                    this.selectedClassLevelFilter = 'all';
                    const url = new URL(window.location);
                    url.searchParams.set('jenjang_id', id);
                    if (this.selectedYearId) {
                        url.searchParams.set('academic_year_id', this.selectedYearId);
                    }
                    window.history.replaceState({}, '', url);
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                onAcademicYearChange(yearId) {
                    this.selectedYearId = yearId;
                    const url = new URL(window.location);
                    url.searchParams.set('academic_year_id', yearId);
                    window.location.href = url.toString();
                },

                get filteredClassLevels() {
                    if (!this.formData.jenjang_id) return allLevels;
                    return allLevels.filter(lvl => lvl.jenjang_id == this.formData.jenjang_id);
                },

                onJenjangChange() {
                    const valid = this.filteredClassLevels.some(l => l.id == this.formData.class_level_id);
                    if (!valid) {
                        this.formData.class_level_id = this.filteredClassLevels.length > 0 ? this.filteredClassLevels[0].id : '';
                    }
                },

                get filteredStudents() {
                    if (!this.studentFilterQuery) return this.classroomStudents;
                    const q = this.studentFilterQuery.toLowerCase();
                    return this.classroomStudents.filter(s => 
                        (s.full_name && s.full_name.toLowerCase().includes(q)) ||
                        (s.nis && s.nis.toLowerCase().includes(q)) ||
                        (s.nickname && s.nickname.toLowerCase().includes(q))
                    );
                },

                openCreateModal() {
                    this.isEdit = false;
                    const defaultJenjang = (this.activeJenjangId != 0 ? this.activeJenjangId : (jenjangList.length > 0 ? jenjangList[0].id : ''));
                    this.formData = {
                        id: null,
                        academic_year_id: this.selectedYearId,
                        jenjang_id: defaultJenjang,
                        class_level_id: '',
                        name: '',
                        code: '',
                        homeroom_teacher_id: null,
                        capacity: 15,
                        is_active: true,
                        description: '',
                    };
                    this.teacherSearch = '';
                    this.teacherDropdownOpen = false;
                    this.onJenjangChange();
                    this.modalOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                openEditModal(id) {
                    fetch(`/classrooms/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const c = res.classroom;
                            this.isEdit = true;
                            this.formData = {
                                id: c.id,
                                academic_year_id: c.academic_year_id || this.selectedYearId,
                                jenjang_id: c.jenjang_id || (c.class_level ? c.class_level.jenjang_id : ''),
                                class_level_id: c.class_level_id,
                                name: c.name,
                                code: c.code || '',
                                homeroom_teacher_id: c.homeroom_teacher_id || null,
                                capacity: c.capacity || 15,
                                is_active: !!c.is_active,
                                description: c.description || '',
                            };
                            this.teacherSearch = '';
                            this.teacherDropdownOpen = false;
                            this.modalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal mengambil data rombel: " + err.message, 'error');
                        }
                    });
                },

                submitForm() {
                    if (this.saving) return;
                    this.saving = true;

                    const url = this.isEdit ? `/classrooms/${this.formData.id}` : '/classrooms';
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
                                window.setPendingToast(res.message || 'Kelompok berhasil disimpan!', 'success');
                            }
                            const targetYear = this.formData.academic_year_id || this.selectedYearId;
                            const targetJenjang = this.formData.jenjang_id || this.activeJenjangId;
                            const url = new URL(window.location);
                            url.searchParams.set('academic_year_id', targetYear);
                            if (targetJenjang != 0) {
                                url.searchParams.set('jenjang_id', targetJenjang);
                            }
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

                viewStudents(id) {
                    fetch(`/classrooms/${id}/students`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                this.selectedClassroom = res.classroom;
                                this.classroomStudents = res.students;
                                this.studentFilterQuery = '';
                                this.studentsModalOpen = true;
                                this.$nextTick(() => {
                                    if (window.lucide) lucide.createIcons();
                                });
                            }
                        })
                        .catch(err => {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', 'Gagal memuat data murid: ' + err.message, 'error');
                            }
                        });
                },

                deleteClassroom(id, name) {
                    const action = () => {
                        fetch(`/classrooms/${id}`, {
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
                                    window.setPendingToast(res.message || 'Kelompok berhasil dihapus!', 'success');
                                }
                                const url = new URL(window.location);
                                url.searchParams.set('academic_year_id', this.selectedYearId);
                                if (this.activeJenjangId != 0) {
                                    url.searchParams.set('jenjang_id', this.activeJenjangId);
                                }
                                window.location.href = url.toString();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal menghapus kelompok.', 'error');
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
                        showGlobalConfirmModal(`Apakah Anda yakin ingin menghapus Kelompok "${name}"?`, action, true);
                    } else if (confirm(`Apakah Anda yakin ingin menghapus Kelompok "${name}"?`)) {
                        action();
                    }
                }
            }
        }
    </script>
</x-admin-layout>