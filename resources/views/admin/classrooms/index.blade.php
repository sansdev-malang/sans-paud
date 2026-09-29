<x-admin-layout>
    <div class="p-6 space-y-6" x-data="rombelApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-500/20">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Kelompok Belajar
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-800">
                                PG - TK - DAYCARE
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kelola kelompok, alokasi wali kelas / bunda pendamping, dan daya tampung murid.</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('class-levels.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all duration-100 cursor-pointer">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Jenjang
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Kelompok
                </button>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Total Kelompok -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Kelompok</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total_classrooms']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Kelompok aktif di semua sub-unit
                </div>
            </div>

            <!-- Stat 2: Total Kapasitas Kuota -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Daya Tampung</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total_capacity']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="door-open" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Maksimal kapasitas seluruh kelompok
                </div>
            </div>

            <!-- Stat 3: Murid Terisi -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Murid Terisi</p>
                        <h3 class="text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ number_format($stats['total_enrolled']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Murid aktif yang telah terdaftar
                </div>
            </div>

            <!-- Stat 4: Persentase Keterisian -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tingkat Keterisian</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ $stats['occupancy_rate'] }}%
                        </h3>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <i data-lucide="pie-chart" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Rasio keterisian terhadap daya tampung
                </div>
            </div>
        </section>

        <!-- FILTER SUB-UNIT TABS & CONTROLS -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="activeSubUnit = 'ALL'"
                    :class="activeSubUnit === 'ALL' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 border border-slate-200 dark:border-slate-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer">
                    Semua Kelompok
                </button>
                <button type="button" @click="activeSubUnit = 'PG'"
                    :class="activeSubUnit === 'PG' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 border border-slate-200 dark:border-slate-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                    <span>🧸</span> Playgroup (PG)
                </button>
                <button type="button" @click="activeSubUnit = 'TK'"
                    :class="activeSubUnit === 'TK' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 border border-slate-200 dark:border-slate-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                    <span>🎒</span> TK (Taman Kanak-Kanak)
                </button>
                <button type="button" @click="activeSubUnit = 'DAYCARE'"
                    :class="activeSubUnit === 'DAYCARE' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 border border-slate-200 dark:border-slate-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                    <span>👶</span> Daycare (TPA)
                </button>
                <button type="button" @click="activeSubUnit = 'TPQ'"
                    :class="activeSubUnit === 'TPQ' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 border border-slate-200 dark:border-slate-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                    <span>📖</span> TPQ
                </button>
            </div>

            <!-- Search box -->
            <div class="w-full md:w-64">
                <input type="text" x-model="searchQuery" placeholder="Cari nama / kode kelompok..."
                    class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
            </div>
        </div>

        <!-- TABLE LIST KELOMPOK -->
        <section class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition-all w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="list-ordered" class="w-4 h-4 text-indigo-600"></i>
                    Daftar Kelompok Belajar
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12">No</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Sub Unit</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Kelompok</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Wali Kelas / Pendamping</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">T.A.</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-44">Kapasitas & Murid</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @php $rowIdx = 0; @endphp
                        @forelse($classrooms as $c)
                            @php
                                $rowIdx++;
                                $occupancy = $c->capacity > 0 ? round(($c->active_students_count / $c->capacity) * 100) : 0;
                                $isFull = $c->active_students_count >= $c->capacity;
                            @endphp
                            <tr x-show="(activeSubUnit === 'ALL' || activeSubUnit === '{{ $c->sub_unit }}') && (searchQuery === '' || '{{ strtolower($c->name . ' ' . $c->code) }}'.includes(searchQuery.toLowerCase()))"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                
                                <!-- 1. No -->
                                <td class="px-5 py-3.5 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $rowIdx }}
                                </td>

                                <!-- 2. Sub Unit Badge -->
                                <td class="px-5 py-3.5">
                                    @if($c->sub_unit === 'PG')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            🧸 PG
                                        </span>
                                    @elseif($c->sub_unit === 'TK')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            🎒 TK
                                        </span>
                                    @elseif($c->sub_unit === 'DAYCARE')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                            👶 DAYCARE
                                        </span>
                                    @elseif($c->sub_unit === 'TPQ')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            📖 TPQ
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800">
                                            -
                                        </span>
                                    @endif
                                </td>

                                <!-- 3. Nama Kelompok & Kode -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                {{ $c->name }}
                                            </span>
                                            @if($c->code)
                                                <span class="ml-1 px-1.5 py-0.2 rounded text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                    {{ $c->code }}
                                                </span>
                                            @endif
                                            @if($c->description)
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $c->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 4. Jenjang -->
                                <td class="px-5 py-3.5">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $c->classLevel?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 5. Wali Kelas / Pendamping -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-[10px] shrink-0">
                                            <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <span class="font-medium text-slate-800 dark:text-slate-200">
                                            {{ $c->homeroomTeacher?->name ?? '-' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- 6. Tahun Ajaran -->
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                                        {{ $c->academicYear?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 7. Kapasitas & Murid Progress -->
                                <td class="px-5 py-3.5 text-center">
                                    <div class="flex flex-col gap-1 max-w-[150px] mx-auto">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 font-mono">
                                                {{ $c->active_students_count }} <span class="font-normal text-slate-400">/ {{ $c->capacity }}</span>
                                            </span>
                                            <span class="font-semibold {{ $isFull ? 'text-rose-600' : ($occupancy > 80 ? 'text-amber-600' : 'text-indigo-600') }}">
                                                {{ $occupancy }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-300 {{ $isFull ? 'bg-rose-500' : ($occupancy > 80 ? 'bg-amber-500' : 'bg-indigo-600') }}"
                                                style="width: {{ min($occupancy, 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 8. Aksi -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="viewStudents({{ $c->id }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-semibold transition-colors cursor-pointer"
                                            title="Lihat Daftar Murid">
                                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                            <span>Murid ({{ $c->active_students_count }})</span>
                                        </button>
                                        <button type="button" @click="openEditModal({{ $c->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Kelompok">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="deleteClassroom({{ $c->id }}, '{{ $c->name }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Kelompok">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    Belum ada data Kelompok Belajar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL DAFTAR MURID PER KELOMPOK -->
        <div x-show="studentsModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display: none; margin-top: 0px !important; z-index: 9999; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="studentsModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden shadow-2xl flex flex-col">
                
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white/95 dark:bg-slate-900/95">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            <span x-text="'Daftar Murid ' + selectedClassroom.name"></span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold" x-text="classroomStudents.length + ' Murid'"></span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Wali / Pendamping: <span class="font-medium text-slate-700 dark:text-slate-300" x-text="selectedClassroom.homeroom_teacher ? selectedClassroom.homeroom_teacher.name : '-'"></span>
                        </p>
                    </div>
                    <button type="button" @click="studentsModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-4 overflow-y-auto flex-1 divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-if="classroomStudents.length === 0">
                        <div class="text-center py-8 text-slate-400 text-xs">
                            Belum ada murid aktif yang ditempatkan di kelompok ini.
                        </div>
                    </template>
                    <template x-for="(student, index) in classroomStudents" :key="student.id">
                        <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3">
                                <span class="w-6 text-center font-mono text-slate-400 text-[11px]" x-text="index + 1"></span>
                                <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0"
                                    x-text="student.gender === 'P' ? '👧' : '👦'">
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-slate-100" x-text="student.full_name"></div>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                        <span x-text="'NIS: ' + student.nis"></span>
                                        <span>&bull;</span>
                                        <span x-text="'Panggilan: ' + (student.nickname || '-')"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <template x-if="student.parent_phone">
                                    <a :href="'https://wa.me/' + student.parent_phone" target="_blank"
                                        class="px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 hover:bg-emerald-100 rounded-lg text-[11px] font-semibold flex items-center gap-1 transition-colors">
                                        <span>💬 WA Ortu</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                    <button type="button" @click="studentsModalOpen = false" class="px-4 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold">
                        Tutup
                    </button>
                </div>

            </div>
        </div>

        <!-- MODAL TAMBAH / EDIT KELOMPOK -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display: none; margin-top: 0px !important; z-index: 9999; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col">
                
                <form @submit.prevent="submitForm">
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Kelompok Belajar' : 'Tambah Kelompok Baru'"></h3>
                            <p class="text-xs text-slate-400 mt-0.5">Konfigurasi nama kelompok, jenjang, wali kelas, dan kapasitas murid.</p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Sub Unit <span class="text-rose-500">*</span></label>
                                <select x-model="formData.sub_unit" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    <option value="PG">🧸 Playgroup (PG)</option>
                                    <option value="TK">🎒 TK (Taman Kanak-Kanak)</option>
                                    <option value="DAYCARE">👶 Daycare (TPA)</option>
                                    <option value="TPQ">📖 TPQ</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenjang <span class="text-rose-500">*</span></label>
                                <select x-model="formData.class_level_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    <option value="">-- Pilih Jenjang --</option>
                                    @foreach($classLevels as $lvl)
                                        <option value="{{ $lvl->id }}">[{{ $lvl->sub_unit }}] {{ $lvl->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kelompok <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.name" required placeholder="Contoh: KB A1 / TK A1 / TPA 1"
                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Kelompok</label>
                                <input type="text" x-model="formData.code" placeholder="Contoh: KB-A1 / TK-A1"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono uppercase">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Daya Tampung (Kapasitas) <span class="text-rose-500">*</span></label>
                                <input type="number" x-model.number="formData.capacity" required min="1" max="100" placeholder="Contoh: 15 / 20"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                <select x-model="formData.academic_year_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    @foreach($academicYears as $y)
                                        <option value="{{ $y->id }}">{{ $y->name }} {{ $y->is_active ? '(Aktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Wali Kelas / Bunda Pendamping</label>
                                <select x-model="formData.homeroom_teacher_id"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    <option value="">-- Belum Ditentukan --</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Keterangan Tambahan</label>
                            <textarea x-model="formData.description" rows="2" placeholder="Catatan tambahan kelompok..."
                                class="w-full p-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                            <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Kelompok')"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function rombelApp() {
            return {
                modalOpen: false,
                studentsModalOpen: false,
                isEdit: false,
                saving: false,
                activeSubUnit: 'ALL',
                searchQuery: '',
                selectedClassroom: {},
                classroomStudents: [],
                formData: {
                    id: null,
                    name: '',
                    code: '',
                    sub_unit: 'TK',
                    class_level_id: '{{ $classLevels->first()?->id ?? "" }}',
                    academic_year_id: '{{ $academicYears->where("is_active", true)->first()?->id ?? ($academicYears->first()?->id ?? "") }}',
                    homeroom_teacher_id: '',
                    capacity: 20,
                    description: '',
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formData = {
                        id: null,
                        name: '',
                        code: '',
                        sub_unit: this.activeSubUnit !== 'ALL' ? this.activeSubUnit : 'TK',
                        class_level_id: '{{ $classLevels->first()?->id ?? "" }}',
                        academic_year_id: '{{ $academicYears->where("is_active", true)->first()?->id ?? ($academicYears->first()?->id ?? "") }}',
                        homeroom_teacher_id: '',
                        capacity: 20,
                        description: '',
                    };
                    this.modalOpen = true;
                },

                openEditModal(id) {
                    const c = @json($classrooms).find(item => item.id == id);
                    if (c) {
                        this.isEdit = true;
                        this.formData = {
                            id: c.id,
                            name: c.name,
                            code: c.code || '',
                            sub_unit: c.sub_unit || 'TK',
                            class_level_id: c.class_level_id,
                            academic_year_id: c.academic_year_id,
                            homeroom_teacher_id: c.homeroom_teacher_id || '',
                            capacity: c.capacity || 20,
                            description: c.description || '',
                        };
                        this.modalOpen = true;
                    }
                },

                viewStudents(id) {
                    fetch(`/classrooms/${id}/students`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.selectedClassroom = res.classroom;
                            this.classroomStudents = res.students;
                            this.studentsModalOpen = true;
                        }
                    })
                    .catch(err => alert("Gagal mengambil daftar murid: " + err.message));
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
                            alert(res.message || 'Kelompok berhasil disimpan!');
                            window.location.reload();
                        } else {
                            alert(res.message || 'Terjadi kesalahan saat menyimpan.');
                        }
                    })
                    .catch(err => {
                        this.saving = false;
                        alert('Error: ' + err.message);
                    });
                },

                deleteClassroom(id, name) {
                    if (!confirm(`Apakah Anda yakin ingin menghapus Kelompok "${name}"?`)) return;

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
                            alert(res.message || 'Kelompok berhasil dihapus!');
                            window.location.reload();
                        } else {
                            alert(res.message || 'Gagal menghapus kelompok.');
                        }
                    })
                    .catch(err => alert('Error: ' + err.message));
                }
            }
        }
    </script>
</x-admin-layout>
