<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-3.5 sm:space-y-5 lg:space-y-6" x-data="rombelApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Kelompok Belajar</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Kelola master kelompok belajar murid berdasarkan jenjang dan kelas.</p>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('class-levels.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors cursor-pointer">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Kelas
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Kelompok
                </button>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Total Kelompok -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
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
                    Kelompok terdaftar di seluruh kelas
                </div>
            </div>

            <!-- Stat 2: Total Kapasitas Kuota -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
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
                    Kapasitas seluruh rombel
                </div>
            </div>

            <!-- Stat 3: Murid Terisi -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Murid Terdaftar</p>
                        <h3 class="text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ number_format($stats['total_enrolled']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Murid aktif saat ini
                </div>
            </div>

            <!-- Stat 4: Persentase Keterisian -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
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
                    Rasio keterisian murid
                </div>
            </div>
        </section>

        <!-- FILTER TOOLBAR (TAHUN AJARAN, JENJANG TABS, SEARCH) -->
        <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3.5 bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Dropdown Tahun Ajaran -->
                <div class="inline-flex items-center bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg h-9 px-3 gap-1.5 shadow-xs">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider shrink-0">TA:</span>
                    <select @change="onAcademicYearChange($event.target.value)"
                        class="h-full py-0 pl-1 pr-6 text-xs font-bold bg-transparent text-slate-900 dark:text-slate-100 focus:outline-none cursor-pointer border-0">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }} class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-semibold">
                                {{ $ay->name }} {{ $ay->is_active ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>

                <!-- Jenjang Tabs -->
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach($jenjangs as $j)
                        <button type="button" @click="setJenjangTab({{ $j->id }})"
                            :class="activeJenjangId == {{ $j->id }} ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer inline-flex items-center gap-1.5">
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
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                                :class="activeJenjangId == {{ $j->id }} ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
                                x-text="countsPerJenjang[{{ $j->id }}] || 0">
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Search box -->
            <div class="w-full lg:w-64 relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400"></i>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Cari nama kelompok..."
                    style="padding-left: 2.25rem;"
                    class="w-full h-9 pr-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
            </div>
        </div>

        <!-- TABLE LIST KELOMPOK BELAJAR -->
        <!-- Kolom: jenjang, kelas, kelompok, status, aksi -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-indigo-600"></i>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                        Daftar Kelompok Belajar
                    </h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        TA {{ $academicYears->firstWhere('id', $selectedYearId)?->name ?? '-' }}
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-40">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Kelas</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kelompok</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($classrooms as $c)
                            <tr x-show="(activeJenjangId == {{ $c->jenjang_id ?: ($c->classLevel?->jenjang_id ?: '0') }}) && (searchQuery === '' || '{{ strtolower($c->name . ' ' . $c->code) }}'.includes(searchQuery.toLowerCase()))"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                
                                <!-- 1. Jenjang (Relasi dari Table Jenjang) -->
                                <td class="px-5 py-3.5">
                                    @php
                                        $jenjangName = $c->jenjang?->name ?? $c->classLevel?->jenjang?->name;
                                    @endphp
                                    @if($jenjangName)
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                             {{ $jenjangName }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- 2. Kelas (Relasi dari Table Jenjang & Kelas) -->
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                        {{ $c->classLevel?->name ?? '-' }}
                                    </span>
                                </td>

                                <!-- 3. Rombel / Kelompok -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs shrink-0">
                                            <i data-lucide="shapes" class="w-4 h-4 text-indigo-600"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                                    {{ $c->name }}
                                                </span>
                                                @if($c->homeroomTeacher)
                                                    <span class="text-[10px] font-medium text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/40 px-1.5 py-0.5 rounded border border-purple-200 dark:border-purple-800/60">
                                                        Wali: {{ $c->homeroomTeacher->name }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($c->description)
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $c->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 4. Status -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($c->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Non-aktif
                                        </span>
                                    @endif
                                </td>

                                <!-- 5. Aksi -->
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
                         @endforelse

                         <!-- Reactive Empty State for Active Jenjang Tab or Search -->
                         <tr x-show="!hasVisibleClassrooms" x-cloak>
                             <td colspan="5" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                 <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                                     <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 shadow-2xs">
                                         <i data-lucide="folder-open" class="w-5 h-5"></i>
                                     </div>
                                     <p class="font-bold text-slate-700 dark:text-slate-300 text-sm">Belum Ada Kelompok Belajar</p>
                                     <p class="text-xs text-slate-400 leading-relaxed">Belum ada kelompok belajar untuk jenjang ini pada tahun ajaran terpilih.</p>
                                     <button type="button" @click="openCreateModal()" class="mt-1 inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs cursor-pointer">
                                         <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                         <span>Tambah Kelompok</span>
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
                                <span x-text="'Daftar Murid ' + selectedClassroom.name"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold" x-text="classroomStudents.length + ' Murid'"></span>
                            </h3>
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
                        <button type="button" @click="studentsModalOpen = false" class="px-4 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold">
                            Tutup
                        </button>
                    </div>

                </div>
            </div>
        </template>

        <!-- MODAL TAMBAH / EDIT ROMBEL / KELOMPOK -->
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; margin: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
                <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col text-left">
                    
                    <form @submit.prevent="submitForm">
                        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Rombel / Kelompok' : 'Tambah Rombel / Kelompok Baru'"></h3>
                                <p class="text-xs text-slate-400 mt-0.5">Pilih tahun ajaran, jenjang, kelas, dan nama rombel/kelompok belajar.</p>
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

                                <!-- 2. Jenjang (Dropdown Dinamis) -->
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
                                <!-- 3. Kelas (Dropdown Dinamis Sesuai Jenjang) -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelas <span class="text-rose-500">*</span></label>
                                    <select x-model="formData.class_level_id" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                        <option value="">-- Pilih Kelas --</option>
                                        <template x-for="lvl in filteredClassLevels" :key="lvl.id">
                                            <option :value="lvl.id" x-text="lvl.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- 4. Nama Kelompok -->
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Rombel / Kelompok <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="formData.name" required placeholder="Contoh: KB A1 / TK A1 / TPA 1 / TPQ"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
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
                                    <input type="number" x-model.number="formData.capacity" min="1" max="100" placeholder="15 / 20"
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
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Aktifkan rombel / kelompok ini agar tersedia untuk penempatan siswa.</p>
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
                                <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Rombel')"></span>
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
            const defaultJenjangId = jenjangList.length > 0 ? jenjangList[0].id : 1;
            const urlParams = new URLSearchParams(window.location.search);
            const requestedJenjangId = urlParams.get('jenjang_id');
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

            // Tentukan activeJenjangId awal:
            // 1. Jika di URL ada jenjang_id dan jenjang tersebut memiliki kelompok (>0), gunakan itu.
            // 2. Jika tab yang diminta tidak ada data tapi jenjang lain punya data, otomatis sesuaikan ke jenjang pertama yang memiliki data.
            // 3. Jika pada TA ini belum ada kelompok sama sekali, otomatis aktifkan tab jenjang pertama (Playgroup/KB).
            let initialJenjangId = requestedJenjangId ? parseInt(requestedJenjangId) : null;
            const hasDataInRequested = initialJenjangId && (counts[initialJenjangId] > 0);
            const hasAnyData = Object.values(counts).some(v => v > 0);

            if (!hasDataInRequested) {
                if (hasAnyData) {
                    const firstWithData = jenjangList.find(j => (counts[j.id] || 0) > 0);
                    initialJenjangId = firstWithData ? firstWithData.id : defaultJenjangId;
                } else {
                    initialJenjangId = defaultJenjangId;
                }
            }

            // Sync URL query param
            const initialUrl = new URL(window.location);
            if (currentSelectedYearId) {
                initialUrl.searchParams.set('academic_year_id', currentSelectedYearId);
            }
            initialUrl.searchParams.set('jenjang_id', initialJenjangId);
            window.history.replaceState({}, '', initialUrl);

            return {
                modalOpen: false,
                studentsModalOpen: false,
                isEdit: false,
                saving: false,
                activeJenjangId: initialJenjangId,
                selectedYearId: currentSelectedYearId,
                searchQuery: '',
                studentFilterQuery: '',
                selectedClassroom: {},
                classroomStudents: [],
                allClassroomsList: rawClassrooms,
                countsPerJenjang: counts,

                formData: {
                    id: null,
                    academic_year_id: currentSelectedYearId,
                    jenjang_id: '',
                    class_level_id: '',
                    name: '',
                    code: '',
                    capacity: 20,
                    is_active: true,
                    description: '',
                },

                get hasVisibleClassrooms() {
                    return this.allClassroomsList.some(c => {
                        const jId = c.jenjang_id || (c.class_level ? c.class_level.jenjang_id : 0);
                        if (jId != this.activeJenjangId) return false;
                        if (this.searchQuery && !((c.name || '') + ' ' + (c.code || '')).toLowerCase().includes(this.searchQuery.toLowerCase())) {
                            return false;
                        }
                        return true;
                    });
                },

                setJenjangTab(id) {
                    this.activeJenjangId = id;
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
                    url.searchParams.delete('jenjang_id'); // Biarkan halaman baru otomatis menyesuaikan tab jenjang yang memiliki data
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
                    const defaultJenjangId = this.activeJenjangId || (allLevels.length > 0 ? allLevels[0].jenjang_id : '');
                    this.formData = {
                        id: null,
                        academic_year_id: this.selectedYearId,
                        jenjang_id: defaultJenjangId,
                        class_level_id: '',
                        name: '',
                        code: '',
                        capacity: 20,
                        is_active: true,
                        description: '',
                    };
                    this.onJenjangChange();
                    this.modalOpen = true;
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
                                capacity: c.capacity || 20,
                                is_active: !!c.is_active,
                                description: c.description || '',
                            };
                            this.modalOpen = true;
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
                                window.setPendingToast(res.message || 'Rombel berhasil disimpan!', 'success');
                            }
                            const targetYear = this.formData.academic_year_id || this.selectedYearId;
                            const targetJenjang = this.formData.jenjang_id || this.activeJenjangId;
                            const url = new URL(window.location);
                            url.searchParams.set('academic_year_id', targetYear);
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

                viewStudents(id) {
                    fetch(`/classrooms/${id}/students`)
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                this.selectedClassroom = res.classroom;
                                this.classroomStudents = res.students;
                                this.studentFilterQuery = '';
                                this.studentsModalOpen = true;
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
                                    window.setPendingToast(res.message || 'Rombel berhasil dihapus!', 'success');
                                }
                                const url = new URL(window.location);
                                url.searchParams.set('academic_year_id', this.selectedYearId);
                                url.searchParams.set('jenjang_id', this.activeJenjangId);
                                window.location.href = url.toString();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal menghapus rombel.', 'error');
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
                        showGlobalConfirmModal(`Apakah Anda yakin ingin menghapus Rombel/Kelompok "${name}"?`, action, true);
                    } else if (confirm(`Apakah Anda yakin ingin menghapus Rombel/Kelompok "${name}"?`)) {
                        action();
                    }
                }
            }
        }
    </script>
</x-admin-layout>
