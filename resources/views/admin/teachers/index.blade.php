<x-admin-layout>
<div x-data="{ showCreateModal: {{ $errors->any() && !old('edit_id') ? 'true' : 'false' }}, showEditModal: {{ $errors->any() && old('edit_id') ? 'true' : 'false' }}, selectedTeacher: null }">
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-3.5 sm:space-y-5 lg:space-y-6" x-data="{ showEmpDetailModal: false, selectedEmp: null, showTeacherStudentsModal: false, selectedTeacherStudents: null }">

        <!-- HEADER -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Data Guru</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Kelola dan pantau data akademis serta profil pendidik secara real-time.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <form action="{{ route('employees.sync-cache') }}" method="POST" class="m-0 p-0 flex">
                    @csrf
                    <button type="submit" class="h-9 px-4 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm transition-all duration-100 cursor-pointer flex items-center gap-2" onclick="this.disabled=true; this.innerHTML='Syncing...'; this.form.submit();">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-500"></i>
                        Sync ke Server
                    </button>
                </form>
                <button onclick="toggleModal('import-teacher-modal')" class="h-9 px-4 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm transition-all duration-100 cursor-pointer flex items-center gap-2">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-500"></i>
                    Impor Guru
                </button>

                <!-- Ekspor Dropdown (Excel & PDF) -->
                <div x-data="{ exportOpen: false }" class="relative">
                    <button type="button" @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        id="btn_export_teacher"
                        class="h-9 px-3.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm transition-all duration-100 cursor-pointer flex items-center gap-2">
                        <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                        <span>Ekspor</span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': exportOpen }"></i>
                    </button>
                    
                    <div x-show="exportOpen" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-1.5 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1 overflow-hidden">
                        <a href="{{ route('teachers.export.excel', request()->query()) }}"
                            id="btn_export_teacher_excel"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                            <span>Excel (.xlsx)</span>
                        </a>
                        <a href="{{ route('teachers.export.pdf', request()->query()) }}"
                            id="btn_export_teacher_pdf"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-400 transition-colors border-t border-slate-100 dark:border-slate-800">
                            <i data-lucide="file-text" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
                            <span>PDF (.pdf)</span>
                        </a>
                    </div>
                </div>

                <button type="button" @click.prevent="showCreateModal = true" class="h-9 px-4 bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-semibold rounded-lg shadow-sm transition-all cursor-pointer flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Tambah Guru
                </button>
            </div>
        </section>

        <!-- IMPORT ERRORS ALERT -->
        @if(session('import_errors'))
            <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-400 p-4 rounded-xl flex items-start gap-3 text-left w-full">
                <i data-lucide="alert-triangle" class="w-5 h-5 mt-0.5 shrink-0 text-rose-500 dark:text-rose-400"></i>
                <div class="space-y-1">
                    <h5 class="text-xs font-bold">Beberapa baris data gagal diimpor:</h5>
                    <ul class="list-disc list-inside text-[11px] leading-relaxed opacity-90 max-h-40 overflow-y-auto">
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- SECTION 2: TABLE CARD (STYLE ADAPTED FROM MATRIKS BERKAS GURU) -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition-all w-full text-left">
            <!-- Table Header Bar: Filters & KPI Status Strip -->
            <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                <!-- Filters Form -->
                <form method="GET" action="{{ route('teachers.index') }}" class="flex flex-wrap items-center gap-2 m-0 p-0">
                    <!-- Search Input (Placeholder cukup nama saja) -->
                    <div x-data="{ searchVal: '{{ request('search') }}' }" class="flex items-center w-full sm:w-52 search-container bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-lg overflow-hidden focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                        <input type="text" name="search" x-model="searchVal" placeholder="Cari nama..."
                            style="border: none !important; outline: none !important; box-shadow: none !important;"
                            class="w-full h-8 px-2.5 text-xs bg-transparent text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-0">
                        
                        <button type="button" x-show="searchVal.trim() !== ''" @click="searchVal = ''; $el.closest('.search-container').querySelector('input').focus();" class="h-8 px-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer bg-transparent border-0 flex items-center justify-center" title="Bersihkan pencarian">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>

                        <button type="submit" 
                            :class="searchVal.trim() !== '' ? 'bg-indigo-600 text-white dark:bg-indigo-500 dark:text-white' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300'"
                            class="h-8 px-2.5 font-bold text-xs transition-all duration-150 cursor-pointer flex items-center justify-center border-l border-slate-200 dark:border-slate-700">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Filter Jenjang -->
                    <select name="sub_unit" id="filter_sub_unit" onchange="if(this.form.classroom_id) { this.form.classroom_id.value = ''; } this.form.submit()"
                        class="h-8 px-2.5 text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer">
                        <option value="">Semua Jenjang</option>
                        <option value="PG" {{ in_array(request('sub_unit'), ['PG', 'KB']) ? 'selected' : '' }}>Playgroup (KB)</option>
                        <option value="TK" {{ request('sub_unit') == 'TK' ? 'selected' : '' }}>TK</option>
                        <option value="DAYCARE" {{ request('sub_unit') == 'DAYCARE' ? 'selected' : '' }}>Daycare</option>
                        <option value="TPQ" {{ request('sub_unit') == 'TPQ' ? 'selected' : '' }}>TPQ</option>
                    </select>

                    <!-- Filter Kelompok -->
                    <select name="classroom_id" id="filter_classroom_id" onchange="this.form.submit()"
                        class="h-8 px-2.5 text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer">
                        <option value="">Semua Kelompok</option>
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" {{ (string)request('classroom_id') === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Status -->
                    <select name="status" onchange="this.form.submit()"
                        class="h-8 px-2.5 text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Aktif</option>
                        <option value="Leave" {{ request('status') == 'Leave' ? 'selected' : '' }}>Cuti</option>
                        <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    <!-- Per Page -->
                    <select name="per_page" onchange="this.form.submit()"
                        class="h-8 px-2.5 text-xs font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 Data</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 Data</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Data</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Data</option>
                        <option value="99999" {{ request('per_page') == 99999 ? 'selected' : '' }}>Semua</option>
                    </select>

                    @if(request()->anyFilled(['search', 'status', 'sub_unit', 'classroom_id']))
                        <a href="{{ route('teachers.index') }}" class="h-8 px-2.5 flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 rounded-lg transition-colors" title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </form>

                <!-- Status KPI Dots (Pojok Kanan Sejajar dengan Filter) -->
                <div class="flex items-center gap-2.5 shrink-0 ml-auto">
                    <span class="inline-flex flex-wrap items-center gap-3 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Total: <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $totalGuru }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span> KB: <strong class="text-amber-600 dark:text-amber-400 font-mono">{{ $jenjangStats['KB'] ?? 0 }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span> TK: <strong class="text-blue-600 dark:text-blue-400 font-mono">{{ $jenjangStats['TK'] ?? 0 }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span> Daycare: <strong class="text-purple-600 dark:text-purple-400 font-mono">{{ $jenjangStats['DAYCARE'] ?? 0 }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span class="w-2 h-2 rounded-full bg-teal-500 inline-block"></span> TPQ: <strong class="text-teal-600 dark:text-teal-400 font-mono">{{ $jenjangStats['TPQ'] ?? 0 }}</strong>
                        </span>
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto overflow-y-auto" style="max-height: calc(100vh - 220px);">
                <table class="w-full text-xs border-collapse">
                    <thead class="sticky top-0 z-10 shadow-sm">
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-10 whitespace-nowrap">No</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Nama Guru & Email</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Jabatan Utama</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Jabatan Tambahan</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Kelompok</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-24">Status</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-900">
                        @forelse($teachers as $index => $teacher)
                            @php
                                $assignedClassrooms = $teacher->classrooms;
                                $totalMuridAsuhan = $assignedClassrooms->sum(fn($cl) => $cl->students->count());
                            @endphp
                            <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-900/20 transition-colors">
                                <td class="px-3 py-2.5 text-center text-slate-900 dark:text-slate-50 font-medium whitespace-nowrap w-10">
                                    {{ $teachers->firstItem() + $index }}
                                </td>
                                
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        @if($teacher->photo)
                                            <img src="{{ str_contains($teacher->photo, 'photos/') ? asset('storage/' . $teacher->photo) : asset('storage/photos/' . $teacher->photo) }}" class="w-7 h-7 rounded-full object-cover shrink-0 border border-slate-200 dark:border-slate-800" alt="{{ $teacher->name }}">
                                        @else
                                            <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-700 dark:text-slate-300 shrink-0 border border-slate-200 dark:border-slate-800 uppercase">
                                                {{ substr($teacher->name, 0, 2) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            @php
                                                $empData = $teacher->toArray();
                                                $empData['photo_url'] = $teacher->photo ? (str_contains($teacher->photo, 'photos/') ? asset('storage/' . $teacher->photo) : asset('storage/photos/' . $teacher->photo)) : '';
                                                $empData['unit_name'] = 'PAUD';
                                            @endphp
                                            <span @click='selectedEmp = @json($empData); showEmpDetailModal = true' class="text-slate-900 dark:text-slate-50 font-bold tracking-tight block cursor-pointer hover:underline hover:text-indigo-600 dark:hover:text-indigo-400 whitespace-nowrap text-xs">{{ $teacher->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap">{{ $teacher->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="block text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap">{{ $teacher->position ?: '-' }}</span>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="block text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap">{{ $teacher->additional_position ?: '-' }}</span>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="block text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap">
                                        {{ $assignedClassrooms->isNotEmpty() ? $assignedClassrooms->pluck('name')->join(', ') : '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if($teacher->status == 'Active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/40">
                                            Aktif
                                        </span>
                                    @elseif($teacher->status == 'Leave')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200/30 dark:border-amber-900/30">
                                            Cuti
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 border border-rose-200/30 dark:border-rose-900/30">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($assignedClassrooms->count() > 0)
                                            @php
                                                $teacherModalData = [
                                                    'teacher_name' => $teacher->name,
                                                    'teacher_photo' => $teacher->photo ? (str_contains($teacher->photo, 'photos/') ? asset('storage/' . $teacher->photo) : asset('storage/photos/' . $teacher->photo)) : null,
                                                    'position' => $teacher->position ?? 'Guru Kelas',
                                                    'total_murid' => $totalMuridAsuhan,
                                                    'classrooms' => $assignedClassrooms->map(function($c) {
                                                        return [
                                                            'id' => $c->id,
                                                            'name' => $c->name,
                                                            'sub_unit' => $c->sub_unit,
                                                            'class_level' => $c->classLevel ? $c->classLevel->name : $c->sub_unit,
                                                            'students' => $c->students,
                                                        ];
                                                    })->values(),
                                                ];
                                            @endphp
                                            <button type="button" 
                                                @click="selectedTeacherStudents = @js($teacherModalData); showTeacherStudentsModal = true;"
                                                class="w-8 h-8 flex items-center justify-center hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 transition-colors cursor-pointer" 
                                                title="Lihat {{ $totalMuridAsuhan }} Murid Asuhan">
                                                <i data-lucide="users" class="w-4 h-4"></i>
                                            </button>
                                        @endif
                                        <a href="#" @click.prevent='selectedTeacher = @json($teacher); showEditModal = true' class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 transition-colors cursor-pointer" title="Edit Data">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                        <form action="{{ route('teachers.destroy', $teacher->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data guru ini?')" class="inline-flex items-center m-0 p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 flex items-center justify-center hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-red-600 dark:text-red-400 hover:text-red-700 transition-colors cursor-pointer" title="Hapus Data">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="users-2" class="w-8 h-8 text-slate-300 dark:text-slate-700"></i>
                                        <p class="text-xs">Tidak ada data guru yang dapat ditampilkan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($teachers->total() > 0)
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800/80 bg-slate-50/20 dark:bg-slate-900/10 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ $teachers->firstItem() }}</span>
                        sampai
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ $teachers->lastItem() }}</span>
                        dari
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ $teachers->total() }}</span>
                        guru
                    </div>
                    <div class="flex items-center gap-1.5 font-semibold">
                        @if ($teachers->onFirstPage())
                            <span class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 flex items-center justify-center cursor-not-allowed select-none bg-slate-50 dark:bg-slate-900/20">Sebelumnya</span>
                        @else
                            <a href="{{ $teachers->appends(request()->query())->previousPageUrl() }}" class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 text-slate-700 dark:text-slate-300 flex items-center justify-center transition-all bg-white dark:bg-slate-900">Sebelumnya</a>
                        @endif

                        <span class="px-3 py-1 font-medium text-slate-700 dark:text-slate-300">
                            Halaman {{ $teachers->currentPage() }} dari {{ $teachers->lastPage() }}
                        </span>

                        @if ($teachers->hasMorePages())
                            <a href="{{ $teachers->appends(request()->query())->nextPageUrl() }}" class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 text-slate-700 dark:text-slate-300 flex items-center justify-center transition-all bg-white dark:bg-slate-900">Berikutnya</a>
                        @else
                            <span class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 flex items-center justify-center cursor-not-allowed select-none bg-slate-50 dark:bg-slate-900/20">Berikutnya</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>

    <!-- IMPORT MODAL -->
    <div x-data><template x-teleport="body">
    <div id="import-teacher-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity" style="margin-top: 0px !important; z-index: 9999;" onclick="if(event.target === this) toggleModal('import-teacher-modal')">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all scale-95 opacity-0 duration-200">
            <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Impor Guru dari Excel</h3>
                <button onclick="toggleModal('import-teacher-modal')" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form action="{{ route('teachers.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4 text-left text-xs">
                @csrf
                <div class="space-y-2 bg-slate-50 dark:bg-slate-900 p-4 rounded-lg border border-slate-200 dark:border-slate-800">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">Format Template Pengisian</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        Unduh template Excel resmi terlebih dahulu untuk memahami susunan kolom data guru yang benar sebelum memulai pengunggahan.
                    </p>
                    <a href="{{ route('teachers.download-template') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        Unduh Template Excel (.xlsx)
                    </a>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Pilih File Excel (.xlsx)</label>
                    <input type="file" name="file" accept=".xlsx, .xls" required class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-slate-50 focus:outline-none file:mr-4 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-slate-200 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300 hover:file:bg-slate-300 dark:hover:file:bg-slate-700 cursor-pointer">
                </div>
                <div class="p-5 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2.5">
                    <button type="button" onclick="toggleModal('import-teacher-modal')" class="px-4 py-2 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 bg-transparent text-xs font-bold rounded-lg cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 dark:bg-slate-50 text-white dark:text-slate-900 text-xs font-bold rounded-lg cursor-pointer">Mulai Impor</button>
                </div>
            </form>
        </div>
    </div>
    </template></div>

    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = modal.firstElementChild;
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.style.opacity = '1';
                    content.style.opacity = '1';
                    content.style.transform = 'scale(1)';
                }, 50);
            } else {
                content.style.opacity = '0';
                content.style.transform = 'scale(0.95)';
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 200);
            }
        }
    </script>
        <!-- MODAL DETAIL PEGAWAI (ADAPTED FROM HRD) -->
        <div x-data><template x-teleport="body">
        <!-- MODAL DETAIL PEGAWAI -->
        <div x-show="showEmpDetailModal" 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="detail-modal-title" 
             role="dialog" 
             aria-modal="true">
             
            <!-- Backdrop -->
            <div x-show="showEmpDetailModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                 @click="showEmpDetailModal = false"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <!-- Modal Panel -->
                <div x-show="showEmpDetailModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-2xl border border-slate-200 dark:border-slate-800">
                    
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800" style="margin-bottom: 1.25rem; padding-bottom: 1rem;">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-50" id="detail-modal-title">Profil Pegawai</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Detail informasi dan status pegawai terdaftar.</p>
                            </div>
                            <button type="button" @click="showEmpDetailModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-500 cursor-pointer transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="space-y-6" style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <div class="flex items-center gap-4">
                                <!-- Photo / Initials -->
                                <div class="shrink-0">
                                    <template x-if="selectedEmp && selectedEmp.photo_url">
                                        <img :src="selectedEmp.photo_url" class="w-16 h-16 rounded-xl object-cover border border-slate-200 dark:border-slate-800 shadow-sm">
                                    </template>
                                    <template x-if="!selectedEmp || !selectedEmp.photo_url">
                                        <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-2xl uppercase border border-slate-200 dark:border-slate-800 shadow-sm">
                                            <span x-text="selectedEmp ? selectedEmp.name.substring(0,2) : ''"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-slate-50" x-text="selectedEmp ? selectedEmp.name : ''"></h4>
                                    <p class="text-slate-500 dark:text-slate-400 font-mono text-[11px]" x-text="selectedEmp ? 'NIY: ' + (selectedEmp.niy || '-') : ''"></p>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 uppercase" x-text="selectedEmp ? (selectedEmp.position || selectedEmp.subject_position || '') : ''"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-4 gap-x-3 text-[11px] pt-4 border-t border-slate-100 dark:border-slate-800 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                                <div class="col-span-full bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 pb-1.5">Informasi Umum</h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-3 gap-x-3 text-[11px]">
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Unit Kerja</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 uppercase break-words block" x-text="selectedEmp ? (selectedEmp.unit_name || selectedEmp.unit) : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Email</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-all block" x-text="selectedEmp ? (selectedEmp.email || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Jenis Kelamin</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 block" x-text="selectedEmp ? (selectedEmp.gender === 'Male' ? 'Laki-laki' : (selectedEmp.gender === 'Female' ? 'Perempuan' : '-')) : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Tempat, Tgl Lahir</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? ((selectedEmp.birth_place || '-') + ', ' + (selectedEmp.birth_date || '-')) : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Alamat</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? (selectedEmp.address || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">No. HP</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-all block" x-text="selectedEmp ? (selectedEmp.phone || '-') : '-'"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-span-full bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 pb-1.5">Informasi Status Pegawai</h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-3 gap-x-3 text-[11px]">
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Status Pegawai</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 block" x-text="selectedEmp ? (selectedEmp.employment_status || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Jabatan Utama</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? (selectedEmp.position || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Jabatan Tambahan</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? (selectedEmp.additional_position || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Masa Kerja</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 block" x-text="selectedEmp ? (selectedEmp.work_period || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Pangkat/Golongan</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? (selectedEmp.pangkat_golongan || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">ID ZKTeco</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 font-mono break-all block" x-text="selectedEmp ? (selectedEmp.zkteco_uid || '-') : '-'"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-span-full bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 pb-1.5">Informasi Pegawai</h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-3 gap-x-3 text-[11px]">
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">NIK</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 font-mono break-all block" x-text="selectedEmp ? (selectedEmp.nik || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">NUPTK</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 font-mono break-all block" x-text="selectedEmp ? (selectedEmp.nuptk || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">NIY</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 font-mono break-all block" x-text="selectedEmp ? (selectedEmp.niy || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">No. UKG</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 font-mono break-all block" x-text="selectedEmp ? (selectedEmp.no_ukg || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">NRG</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 font-mono break-all block" x-text="selectedEmp ? (selectedEmp.nrg || '-') : '-'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-span-full bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 pb-1.5">Pendidikan & SK</h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-3 gap-x-3 text-[11px]">
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Pendidikan Terakhir</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? (selectedEmp.last_education || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Jurusan</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? (selectedEmp.major || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Tgl Mulai Tugas</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 block" x-text="selectedEmp ? (selectedEmp.task_start_date || '-') : '-'"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-slate-400 text-[9px] uppercase font-semibold">Info SK</span>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 break-words block" x-text="selectedEmp ? ((selectedEmp.last_sk_number || 'Tidak Ada SK') + (selectedEmp.last_sk_date ? ' (' + selectedEmp.last_sk_date + ')' : '')) : '-'"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-span-full mt-2">
                                    <span class="block text-slate-400 text-[9px] uppercase font-semibold">Catatan</span>
                                    <div class="p-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 mt-1" x-text="selectedEmp && selectedEmp.notes ? selectedEmp.notes : 'Tidak ada catatan tambahan.'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 flex items-center justify-end border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="showEmpDetailModal = false" class="h-9 px-4 inline-flex items-center justify-center rounded-lg bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-semibold shadow-sm hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </template>

        <!-- MODAL: DAFTAR MURID ASUHAN GURU -->
        <template x-teleport="body">
            <div x-show="showTeacherStudentsModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="teacher-students-modal-title" role="dialog" aria-modal="true">
                <div x-show="showTeacherStudentsModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showTeacherStudentsModal = false"></div>
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showTeacherStudentsModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-3xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh]">
                        
                        <!-- Modal Header -->
                        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/70 shrink-0">
                            <div class="flex items-center gap-3">
                                <template x-if="selectedTeacherStudents?.teacher_photo">
                                    <img :src="selectedTeacherStudents.teacher_photo" class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                                </template>
                                <template x-if="!selectedTeacherStudents?.teacher_photo">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-sm">
                                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                                    </div>
                                </template>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50" x-text="selectedTeacherStudents?.teacher_name"></h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Wali Kelas / Pendidik &bull; Total: <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedTeacherStudents?.total_murid + ' Murid'"></span>
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="showTeacherStudentsModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <!-- Modal Body: Classrooms & Students List -->
                        <div class="p-5 overflow-y-auto space-y-6 flex-1 text-xs">
                            <template x-for="(cls, cIdx) in (selectedTeacherStudents?.classrooms || [])" :key="cls.id || cIdx">
                                <div class="bg-slate-50/60 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700/60 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                :class="{
                                                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400': cls.sub_unit === 'PG',
                                                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400': cls.sub_unit === 'TK',
                                                    'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-400': cls.sub_unit === 'DAYCARE',
                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400': cls.sub_unit === 'TPQ'
                                                }"
                                                x-text="cls.sub_unit">
                                            </span>
                                            <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100" x-text="cls.name"></h4>
                                            <span class="text-[11px] text-slate-400" x-text="'(' + cls.class_level + ')'"></span>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-400" x-text="cls.students.length + ' Murid Aktif'"></span>
                                    </div>

                                    <!-- Table / List of Students -->
                                    <div class="divide-y divide-slate-200/60 dark:divide-slate-700/60">
                                        <template x-for="(s, sIdx) in cls.students" :key="s.id">
                                            <div class="py-2.5 flex items-center justify-between gap-3 first:pt-0 last:pb-0">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-5 text-slate-400 font-mono text-center" x-text="sIdx + 1"></span>
                                                    <div>
                                                        <h5 class="font-bold text-slate-900 dark:text-slate-100" x-text="s.full_name"></h5>
                                                        <p class="text-[11px] text-slate-500 font-mono">
                                                            NIS: <span x-text="s.nis"></span> &bull; 
                                                            Panggilan: <span x-text="s.nickname || '-'"></span>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2 shrink-0">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                        :class="s.gender === 'L' || s.gender === 'Male' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400'"
                                                        x-text="s.gender === 'L' || s.gender === 'Male' ? 'L' : 'P'">
                                                    </span>
                                                    
                                                    <template x-if="s.parent_phone">
                                                        <a :href="'https://wa.me/' + (s.parent_phone.startsWith('0') ? '62' + s.parent_phone.substring(1) : s.parent_phone).replace(/[^0-9]/g, '')" target="_blank"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded-lg border border-emerald-200/60 dark:border-emerald-800 hover:bg-emerald-100 transition-colors"
                                                            title="Hubungi WhatsApp Orang Tua">
                                                            <i data-lucide="phone" class="w-3 h-3"></i>
                                                            <span x-text="s.parent_phone"></span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="cls.students.length === 0">
                                            <p class="py-4 text-center text-slate-400 italic">Belum ada murid aktif di kelompok ini.</p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex justify-end shrink-0">
                            <button type="button" @click="showTeacherStudentsModal = false"
                                class="px-4 py-2 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-semibold rounded-lg">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

    </div>
    @include('admin.teachers.modals')
</div>
</x-admin-layout>


