<x-admin-layout>
    <div class="p-6 space-y-6" x-data="studentApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-500/20">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Daftar Siswa
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-800">
                                SANS PAUD
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kelola dan pantau data akademis siswa aktif SANS PAUD Malang.</p>
                    </div>
                </div>
            </div>
            <!-- ACTION CONTROLS: INFO TAHUN AJARAN AKTIF & ACTION BUTTONS -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <!-- Info Badge Tahun Ajaran Aktif -->
                <div class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 rounded-xl text-xs shadow-xs">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">T.A. Aktif:</span>
                    <span class="font-bold text-indigo-700 dark:text-indigo-300">
                        {{ $activeAcademicYear ? $activeAcademicYear->name : '2026/2027' }} ({{ $activeAcademicYear->semester ?? 'Ganjil' }})
                    </span>
                </div>

                <a href="{{ route('spmb.candidates.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all duration-100 cursor-pointer">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    Tarik Siswa dari SPMB
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Siswa
                </button>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat Card 1: Total Siswa Aktif -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Siswa Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total_active']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Total terdaftar: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($stats['total_all']) }}</span> siswa
                </div>
            </div>

            <!-- Stat Card 2: Laki-laki -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Laki-laki</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['male']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Siswa aktif putra
                </div>
            </div>

            <!-- Stat Card 3: Perempuan -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Perempuan</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['female']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl border border-rose-100 dark:border-rose-900/50">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Siswa aktif putri
                </div>
            </div>

            <!-- Stat Card 4: Rombongan Belajar -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Rombel</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['classrooms']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="university" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Rombongan belajar aktif di PAUD
                </div>
            </div>
        </section>

        <!-- SEARCH & FILTERS -->
        <section class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs w-full">
            <form method="GET" action="{{ route('students.index') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <!-- Search Box -->
                <div class="relative w-full md:max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 dark:text-slate-500"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa, NIS, NISN, NIK, No. Ortu..."
                        style="padding-left: 2.25rem;"
                        class="w-full h-9 pr-4 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 placeholder-slate-400 dark:placeholder-slate-500 transition-all shadow-inner">
                </div>

                <!-- Filter Select Toolbar -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Filter Tahun Ajaran -->
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                                T.A. {{ $year->name }} {{ $year->is_active ? '★ (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Tingkat Kelas -->
                    <select name="class_level_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Tingkat</option>
                        @foreach($classLevels as $lvl)
                            <option value="{{ $lvl->id }}" {{ request('class_level_id') == $lvl->id ? 'selected' : '' }}>
                                {{ $lvl->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Rombel -->
                    <select name="classroom_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Rombel</option>
                        @foreach($classrooms as $rombel)
                            <option value="{{ $rombel->id }}" {{ request('classroom_id') == $rombel->id ? 'selected' : '' }}>
                                {{ $rombel->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Status -->
                    <select name="status" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Status</option>
                        <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="lulus" {{ request('status') == 'lulus' ? 'selected' : '' }}>Lulus</option>
                        <option value="mutasi" {{ request('status') == 'mutasi' ? 'selected' : '' }}>Mutasi</option>
                        <option value="keluar" {{ request('status') == 'keluar' ? 'selected' : '' }}>Keluar</option>
                        <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    @if(request()->hasAny(['search', 'academic_year_id', 'class_level_id', 'classroom_id', 'status', 'gender']))
                        <a href="{{ route('students.index') }}" 
                            class="h-9 px-3 inline-flex items-center justify-center text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors"
                            title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- TABLE LIST SISWA -->
        <section class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition-all w-full">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12">No</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">NIS</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Siswa</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Tingkat & Rombel</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">L/P & Usia</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-44">Orang Tua & WA</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-24">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($students as $index => $s)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                <td class="px-5 py-3.5 text-slate-400 font-mono text-[11px]">
                                    {{ $students->firstItem() + $index }}
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $s->nis }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if($s->student_photo_url)
                                            <img src="{{ $s->student_photo_url }}" alt="{{ $s->full_name }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700 shrink-0">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ $s->avatar_initials }}
                                            </div>
                                        @endif
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer" @click="openDetailModal({{ $s->id }})">
                                                {{ $s->full_name }}
                                            </span>
                                            <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                                @if($s->nik)
                                                    <span>NIK: {{ $s->nik }}</span>
                                                @endif
                                                @if($s->spmb_candidate_id)
                                                    <span class="px-1.5 py-0.2 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-medium">SPMB</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">
                                            {{ $s->classroom ? $s->classroom->name : 'Belum Ditentukan' }}
                                        </span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[11px] text-slate-400">
                                                {{ $s->classroom && $s->classroom->classLevel ? $s->classroom->classLevel->name : '-' }}
                                            </span>
                                            @if($s->academicYear)
                                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-medium font-mono border border-indigo-100 dark:border-indigo-900/50">
                                                    {{ $s->academicYear->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col text-slate-600 dark:text-slate-300">
                                        <span class="font-medium">{{ $s->formatted_gender }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $s->age ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[160px]">
                                            {{ $s->father_name ?: ($s->mother_name ?: ($s->guardian_name ?: '-')) }}
                                        </span>
                                        @if($s->clean_parent_phone)
                                            <a href="{{ $s->whatsapp_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400 hover:underline font-mono mt-0.5">
                                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                                {{ $s->parent_phone }}
                                            </a>
                                        @else
                                            <span class="text-[11px] text-slate-400">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($s->status === 'aktif')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                                            Aktif
                                        </span>
                                    @elseif($s->status === 'lulus')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40">
                                            Lulus
                                        </span>
                                    @elseif($s->status === 'mutasi')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                                            Mutasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            {{ ucfirst($s->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openDetailModal({{ $s->id }})"
                                            class="p-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg transition-colors cursor-pointer"
                                            title="Lihat Detail Siswa">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="openEditModal({{ $s->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Data Siswa">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="deleteStudent({{ $s->id }}, '{{ addslashes($s->full_name) }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Siswa">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="max-w-sm mx-auto flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-500 mb-3 border border-indigo-100 dark:border-indigo-900/50">
                                            <i data-lucide="users" class="w-6 h-6"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Data Siswa Aktif</h4>
                                        <p class="text-xs text-slate-400 mt-1 mb-4 text-center">
                                            Data siswa belum tersedia atau tidak cocok dengan filter pencarian. Anda dapat menarik calon siswa dari menu SPMB atau menambah siswa manual.
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('spmb.candidates.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                                                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                                Buka Siswa Baru SPMB
                                            </a>
                                            <button type="button" @click="openCreateModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 text-white dark:text-slate-900 rounded-lg text-xs font-semibold shadow-xs">
                                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                                Tambah Manual
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($students->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $students->firstItem() }}-{{ $students->lastItem() }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $students->total() }}</span> siswa
                    </span>
                    <div>
                        {{ $students->links() }}
                    </div>
                </div>
            @endif
        </section>

        <!-- MODAL DETAIL SISWA -->
        <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="detailModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                    <div class="flex items-center gap-3">
                        <template x-if="selectedStudent?.student_photo_url">
                            <img :src="selectedStudent.student_photo_url" class="w-12 h-12 rounded-xl object-cover ring-2 ring-indigo-500/20">
                        </template>
                        <template x-if="!selectedStudent?.student_photo_url">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 font-bold text-lg flex items-center justify-center" x-text="selectedStudent?.avatar_initials || 'S'">
                            </div>
                        </template>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="selectedStudent?.full_name"></h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/50 dark:border-emerald-800" x-text="selectedStudent?.status || 'Aktif'"></span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">NIS: <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedStudent?.nis"></span> | Rombel: <span class="font-medium text-slate-700 dark:text-slate-300" x-text="selectedStudent?.classroom?.name || '-'"></span></p>
                        </div>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body Tabs -->
                <div class="p-6 space-y-6 text-xs" x-show="selectedStudent">
                    
                    <!-- Section 1: Identitas & Akademik -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <i data-lucide="graduation-cap" class="w-4 h-4 text-indigo-600"></i>
                            Informasi Akademik & Pribadi
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Lengkap</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.full_name"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Panggilan</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.nickname || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Jenis Kelamin</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.formatted_gender"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Tingkat & Rombel</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(selectedStudent?.classroom?.name || '-') + (selectedStudent?.classroom?.class_level ? ' (' + selectedStudent.classroom.class_level.name + ')' : '')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Wali Kelas</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.classroom?.homeroom_teacher?.name || 'Belum Ditugaskan'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Tahun Ajaran</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedStudent?.academic_year?.name ? 'T.A. ' + selectedStudent.academic_year.name : '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Tempat, Tgl Lahir</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(selectedStudent?.birth_place ? selectedStudent.birth_place + ', ' : '') + (selectedStudent?.birth_date || '-')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Usia</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.age || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Agama</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.religion || 'Islam'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">NIK Siswa</span>
                                <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedStudent?.nik || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">NISN</span>
                                <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedStudent?.nisn || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Status</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400 uppercase" x-text="selectedStudent?.status || 'Aktif'"></span>
                            </div>
                            <div class="col-span-2 sm:col-span-3">
                                <span class="text-slate-400 block text-[11px]">Alamat Domisili</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedStudent?.address || '-'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Data Orang Tua / Wali -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <i data-lucide="users-2" class="w-4 h-4 text-emerald-600"></i>
                            Data Orang Tua & Kontak
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ayah</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.father_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Pekerjaan Ayah</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.father_job || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ibu</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.mother_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Pekerjaan Ibu</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.mother_job || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">WhatsApp Utama</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="selectedStudent?.parent_phone || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Email Orang Tua</span>
                                <span class="text-slate-800 dark:text-slate-200" x-text="selectedStudent?.parent_email || '-'"></span>
                            </div>
                        </div>

                        <!-- Action Chat WA -->
                        <template x-if="selectedStudent?.whatsapp_url">
                            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-start">
                                <a :href="selectedStudent.whatsapp_url" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    Hubungi Orang Tua via WhatsApp
                                </a>
                            </div>
                        </template>
                    </div>

                    <!-- Section 3: Berkas Dokumen SPMB -->
                    <template x-if="selectedStudent?.documents && Object.keys(selectedStudent.documents).length > 0">
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-blue-600"></i>
                                Dokumen & Berkas Terlampir (SPMB)
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="(doc, idx) in formattedDocuments(selectedStudent.documents)" :key="idx">
                                    <a :href="doc.url" target="_blank" 
                                        class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 hover:border-indigo-500 hover:bg-indigo-50/20 transition-all group">
                                        <div class="flex items-center gap-2.5 overflow-hidden">
                                            <div class="p-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-indigo-600 shrink-0">
                                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                            </div>
                                            <span class="font-medium text-slate-700 dark:text-slate-300 truncate" x-text="doc.name"></span>
                                        </div>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 shrink-0 ml-2"></i>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                    <button type="button" @click="detailModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                        Tutup
                    </button>
                    <button type="button" @click="openEditModal(selectedStudent.id)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs flex items-center gap-1.5">
                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                        Edit Data Siswa
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH / EDIT SISWA -->
        <div x-show="formModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="formModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col">
                
                <form @submit.prevent="submitForm">
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Data Siswa' : 'Tambah Siswa Baru Manual'"></h3>
                            <p class="text-xs text-slate-400 mt-0.5">Isi data identitas dan rombongan belajar siswa.</p>
                        </div>
                        <button type="button" @click="formModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 text-xs">
                        <!-- Tahun Ajaran, NIS & Rombel -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                                <select x-model="formData.academic_year_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                    <option value="">Pilih Tahun Ajaran...</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}">{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Induk Siswa (NIS) <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="formData.nis" required placeholder="Contoh: 27.PAUD.001"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Rombongan Belajar <span class="text-rose-500">*</span></label>
                                <select x-model="formData.classroom_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                    <option value="">Pilih Rombel...</option>
                                    @foreach($allClassrooms as $rombel)
                                        <option value="{{ $rombel->id }}">
                                            {{ $rombel->name }} ({{ $rombel->classLevel->name ?? '-' }}) - {{ $rombel->academicYear->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Nama Lengkap & Panggilan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="formData.full_name" required placeholder="Nama lengkap sesuai akta"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Panggilan</label>
                                <input type="text" x-model="formData.nickname" placeholder="Panggilan"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>

                        <!-- JK & Tempat Tgl Lahir -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                <select x-model="formData.gender" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                                <input type="text" x-model="formData.birth_place" placeholder="Kota lahir"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                                <input type="date" x-model="formData.birth_date"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>

                        <!-- NIK & NISN -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Siswa</label>
                                <input type="text" x-model="formData.nik" placeholder="16 digit NIK"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Siswa</label>
                                <select x-model="formData.status" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                    <option value="aktif">Aktif</option>
                                    <option value="lulus">Lulus</option>
                                    <option value="mutasi">Mutasi</option>
                                    <option value="keluar">Keluar</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <!-- Orang Tua & Kontak -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ayah</label>
                                <input type="text" x-model="formData.father_name" placeholder="Nama ayah kandung"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ibu</label>
                                <input type="text" x-model="formData.mother_name" placeholder="Nama ibu kandung"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>

                        <!-- No WhatsApp & Alamat -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">WhatsApp Utama Ortu</label>
                                <input type="text" x-model="formData.parent_phone" placeholder="08xxxxxxxxxx"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Domisili</label>
                                <input type="text" x-model="formData.address" placeholder="Jalan, RT/RW, Kelurahan, Kota"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                        <button type="button" @click="formModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                            <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Siswa')"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- Alpine.js Application Logic for Data Siswa -->
    <script>
        function studentApp() {
            return {
                detailModalOpen: false,
                formModalOpen: false,
                isEdit: false,
                saving: false,
                selectedStudent: null,
                formData: {
                    id: null,
                    academic_year_id: '{{ $selectedYearId && $selectedYearId !== "all" ? $selectedYearId : ($academicYears->firstWhere("is_active", true)?->id ?? "") }}',
                    classroom_id: '',
                    nis: '',
                    nisn: '',
                    nik: '',
                    full_name: '',
                    nickname: '',
                    gender: 'L',
                    birth_place: '',
                    birth_date: '',
                    religion: 'Islam',
                    address: '',
                    father_name: '',
                    mother_name: '',
                    parent_phone: '',
                    status: 'aktif',
                },

                formattedDocuments(docs) {
                    if (!docs) return [];
                    if (Array.isArray(docs)) {
                        return docs.filter(d => d && d.url && d.url !== '#');
                    }
                    if (typeof docs === 'object') {
                        const labelMap = {
                            'student_photo': 'Pas Foto Calon Murid (Foto Formal)',
                            'student_photo_path': 'Pas Foto Calon Murid (Foto Formal)',
                            'birth_certificate': 'Akta Kelahiran',
                            'birth_certificate_path': 'Akta Kelahiran',
                            'family_card': 'Kartu Keluarga (KK)',
                            'family_card_path': 'Kartu Keluarga (KK)',
                            'diploma_certificate': 'Ijazah / Surat Keterangan Aktif',
                            'diploma_certificate_path': 'Ijazah / Surat Keterangan Aktif',
                            'student_card': 'NISN / KIA / Kartu Pelajar',
                            'student_card_path': 'NISN / KIA / Kartu Pelajar',
                            'special_needs_assessment_path': 'Asesmen Kebutuhan Khusus',
                            'payment_receipt_path': 'Bukti Pembayaran Pendaftaran',
                        };
                        return Object.entries(docs).filter(([k, v]) => v && typeof v === 'string' && v.trim() !== '').map(([k, v]) => ({
                            name: labelMap[k] || (k.includes('_') ? k.replace(/_/g, ' ') : k),
                            url: v
                        }));
                    }
                    return [];
                },

                openDetailModal(id) {
                    fetch(`/students/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.selectedStudent = res.student;
                            this.detailModalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => alert("Gagal memuat detail siswa: " + err.message));
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formData = {
                        id: null,
                        academic_year_id: '{{ $selectedYearId && $selectedYearId !== "all" ? $selectedYearId : ($academicYears->firstWhere("is_active", true)?->id ?? "") }}',
                        classroom_id: '',
                        nis: '',
                        nisn: '',
                        nik: '',
                        full_name: '',
                        nickname: '',
                        gender: 'L',
                        birth_place: '',
                        birth_date: '',
                        religion: 'Islam',
                        address: '',
                        father_name: '',
                        mother_name: '',
                        parent_phone: '',
                        status: 'aktif',
                    };
                    this.formModalOpen = true;
                },

                openEditModal(id) {
                    fetch(`/students/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const s = res.student;
                            this.isEdit = true;
                            this.formData = {
                                id: s.id,
                                academic_year_id: s.academic_year_id || '',
                                classroom_id: s.classroom_id || '',
                                nis: s.nis,
                                nisn: s.nisn || '',
                                nik: s.nik || '',
                                full_name: s.full_name,
                                nickname: s.nickname || '',
                                gender: s.gender || 'L',
                                birth_place: s.birth_place || '',
                                birth_date: s.birth_date ? s.birth_date.substring(0, 10) : '',
                                religion: s.religion || 'Islam',
                                address: s.address || '',
                                father_name: s.father_name || '',
                                mother_name: s.mother_name || '',
                                parent_phone: s.parent_phone || '',
                                status: s.status || 'aktif',
                            };
                            this.detailModalOpen = false;
                            this.formModalOpen = true;
                        }
                    })
                    .catch(err => alert("Gagal mengambil data siswa: " + err.message));
                },

                submitForm() {
                    if (this.saving) return;
                    this.saving = true;

                    const url = this.isEdit ? `/students/${this.formData.id}` : '/students';
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
                            this.formModalOpen = false;
                            alert(res.message || 'Data siswa berhasil disimpan!');
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

                deleteStudent(id, name) {
                    if (!confirm(`Apakah Anda yakin ingin menghapus data siswa "${name}"?`)) return;

                    fetch(`/students/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            alert(res.message || 'Siswa berhasil dihapus!');
                            window.location.reload();
                        } else {
                            alert(res.message || 'Gagal menghapus siswa.');
                        }
                    })
                    .catch(err => alert('Error: ' + err.message));
                }
            }
        }
    </script>
</x-admin-layout>
