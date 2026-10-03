<x-admin-layout>
    <div class="p-6 space-y-6" x-data="alumniApp()">

        <!-- PAGE TITLE & HEADER -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-500/20">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Buku Induk Alumni
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 font-semibold border border-amber-200 dark:border-amber-800">
                                KB & TK ANAK SALEH
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Database multi-tahun kelulusan ananda, riwayat kelas & wali kelas terdahulu, serta sekolah lanjutan.</p>
                    </div>
                </div>
            </div>

            <!-- ACTION CONTROLS -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <!-- Ekspor Excel Button -->
                <a href="{{ route('alumni.export.excel', request()->query()) }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all duration-100 cursor-pointer">
                    <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                    Ekspor Buku Alumni (Excel)
                </a>

                <!-- Link to Kelulusan & Kenaikan -->
                <a href="{{ route('promotions.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                    Kelola Kelulusan Siswa
                </a>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat Card 1: Total Alumni -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Alumni Terdata</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">
                            {{ number_format($stats['total']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Siswa lulus KB & TK
                </div>
            </div>

            <!-- Stat Card 2: Alumni TK -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alumni TK (Lanjut SD)</p>
                        <h3 class="text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ number_format($stats['tk']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Lulusan jenjang TK-B
                </div>
            </div>

            <!-- Stat Card 3: Alumni Playgroup -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alumni Playgroup (KB)</p>
                        <h3 class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($stats['pg']) }}
                        </h3>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Lulusan jenjang KB-B
                </div>
            </div>

            <!-- Stat Card 4: Gender Alumni -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Komposisi Gender</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-bold text-blue-600">Putra: {{ $stats['male'] }}</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-xs font-bold text-rose-600">Putri: {{ $stats['female'] }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Tercatat dalam Buku Induk
                </div>
            </div>
        </section>

        <!-- SUB-UNIT FILTER TABS -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <a href="{{ route('alumni.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'ALL'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $selectedSubUnit === 'ALL' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                🌟 Semua Alumni ({{ $stats['total'] }})
            </a>
            <a href="{{ route('alumni.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'TK'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5 {{ $selectedSubUnit === 'TK' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                <span>🎒</span> Alumni TK ({{ $stats['tk'] }})
            </a>
            <a href="{{ route('alumni.index', array_merge(request()->except('sub_unit'), ['sub_unit' => 'PG'])) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5 {{ $selectedSubUnit === 'PG' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' }}">
                <span>🧸</span> Alumni Playgroup / KB ({{ $stats['pg'] }})
            </a>
        </div>

        <!-- SEARCH & FILTERS -->
        <section class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs w-full">
            <form method="GET" action="{{ route('alumni.index') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <input type="hidden" name="sub_unit" value="{{ $selectedSubUnit }}">

                <!-- Search Box -->
                <div class="relative w-full md:max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 dark:text-slate-500"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama alumni, NIS, No. Ortu, sekolah lanjutan..."
                        style="padding-left: 2.25rem;"
                        class="w-full h-9 pr-4 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-900 dark:text-slate-50 placeholder-slate-400 dark:placeholder-slate-500 transition-all shadow-inner">
                </div>

                <!-- Filter Select Toolbar -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <!-- Filter Tahun Kelulusan -->
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Tahun Kelulusan</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                Lulusan T.A. {{ $year->name }} {{ $year->is_active ? '★ (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Filter Gender -->
                    <select name="gender" onchange="this.form.submit()"
                        class="h-9 px-3 text-xs font-medium bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 cursor-pointer shadow-xs">
                        <option value="all">Semua Gender</option>
                        <option value="L" {{ request('gender') == 'L' ? 'selected' : '' }}>Putra (L)</option>
                        <option value="P" {{ request('gender') == 'P' ? 'selected' : '' }}>Putri (P)</option>
                    </select>

                    @if(request()->hasAny(['search', 'academic_year_id', 'gender']))
                        <a href="{{ route('alumni.index', ['sub_unit' => $selectedSubUnit]) }}" 
                            class="h-9 px-3 inline-flex items-center justify-center text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors"
                            title="Reset Filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- TABLE LIST ALUMNI -->
        <section class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition-all w-full">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-12">No</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">NIS / NISN</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Alumni</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kelompok & Wali Kelas Terakhir</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Tahun Lulus</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-40">Orang Tua & WA</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sekolah Lanjutan / Keterangan</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($alumniList as $index => $s)
                            @php
                                $latestGrad = $s->classroomHistories->firstWhere('status', 'lulus') ?? $s->classroomHistories->first();
                                $subUnit = $latestGrad?->sub_unit ?? $s->sub_unit ?? 'TK';
                                $lastClass = $latestGrad?->classroom_name ?? $s->classroom?->name ?? '-';
                                $lastTeacher = $latestGrad?->homeroom_teacher_name ?? $s->classroom?->homeroomTeacher?->name ?? '-';
                                $gradYear = $latestGrad?->academicYear?->name ?? $s->academicYear?->name ?? '-';
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                <td class="px-5 py-3.5 text-slate-400 font-mono text-[11px]">
                                    {{ $alumniList->firstItem() + $index }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="font-mono text-xs font-bold text-amber-600 dark:text-amber-400">
                                            {{ $s->nis }}
                                        </span>
                                        @if($s->nisn)
                                            <span class="font-mono text-[10px] text-slate-400">
                                                NISN: {{ $s->nisn }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-amber-50 dark:bg-amber-950/50 border border-amber-100 dark:border-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ $s->avatar_initials }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight hover:text-amber-600 dark:hover:text-amber-400 cursor-pointer" @click="openJourneyModal({{ $s->id }})">
                                                {{ $s->full_name }}
                                            </span>
                                            <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                                <span>{{ $s->formatted_gender }}</span>
                                                @if($s->birth_date)
                                                    <span>&bull; Lahir: {{ date('d/m/Y', strtotime($s->birth_date)) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $subUnit === 'PG' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800' : 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800' }}">
                                                {{ $lastClass }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <i data-lucide="user-check" class="w-3 h-3 text-slate-400"></i>
                                            Wali Kelas: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $lastTeacher }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        T.A. {{ $gradYear }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[150px]">
                                            {{ $s->father_name ?: ($s->mother_name ?: '-') }}
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
                                    <div class="max-w-[200px] truncate text-slate-600 dark:text-slate-400" title="{{ $s->notes }}">
                                        @if($s->notes)
                                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $s->notes }}</span>
                                        @else
                                            <span class="italic text-slate-400">Belum ada data sekolah lanjutan</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- View Journey Timeline -->
                                        <button type="button" @click="openJourneyModal({{ $s->id }})"
                                            class="p-1.5 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-slate-500 hover:text-amber-600 dark:hover:text-amber-400 rounded-lg transition-colors cursor-pointer"
                                            title="Lihat Riwayat & Perjalanan Belajar">
                                            <i data-lucide="history" class="w-4 h-4"></i>
                                        </button>
                                        <!-- Edit Notes / Destination School -->
                                        <button type="button" @click="openEditNotesModal({{ $s->id }}, '{{ addslashes($s->full_name) }}', '{{ addslashes($s->notes ?? '') }}', '{{ addslashes($s->parent_phone ?? '') }}')"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Update Catatan / Sekolah Lanjutan">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="max-w-sm mx-auto flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-500 mb-3 border border-amber-100 dark:border-amber-900/50">
                                            <i data-lucide="award" class="w-6 h-6"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada Data Alumni</h4>
                                        <p class="text-xs text-slate-400 mt-1 mb-4 text-center">
                                            Belum ada murid yang berstatus lulus pada filter tahun ajaran ini. Anda dapat memproses kelulusan murid pada menu Kenaikan & Kelulusan.
                                        </p>
                                        <a href="{{ route('promotions.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors">
                                            <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                                            Buka Menu Kelulusan
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($alumniList->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $alumniList->firstItem() }}-{{ $alumniList->lastItem() }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $alumniList->total() }}</span> alumni
                    </span>
                    <div>
                        {{ $alumniList->links() }}
                    </div>
                </div>
            @endif
        </section>

        <!-- MODAL INTERAKTIF: RIWAYAT & PERJALANAN BELAJAR ALUMNI -->
        <div x-show="journeyModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display: none; margin-top: 0px !important; z-index: 9999; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="journeyModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col">
                
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-400 font-bold text-lg flex items-center justify-center" x-text="selectedAlumni?.avatar_initials || 'A'">
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="selectedAlumni?.full_name"></h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/50 dark:border-amber-800">
                                    ALUMNI
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                NIS: <span class="font-mono font-bold text-amber-600 dark:text-amber-400" x-text="selectedAlumni?.nis"></span> | 
                                NISN: <span class="font-mono text-slate-600 dark:text-slate-300" x-text="selectedAlumni?.nisn || '-'"></span> |
                                T.A. Lulus: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedAlumni?.academic_year?.name || '-'"></span>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="journeyModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 text-xs" x-show="selectedAlumni">
                    
                    <!-- TIMELINE PERJALANAN SISWA -->
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider flex items-center gap-2">
                                <i data-lucide="milestone" class="w-4 h-4 text-amber-600"></i>
                                Riwayat & Perjalanan Belajar Ananda
                            </h4>
                            <span class="text-[11px] text-slate-400">Snapshot data guru & rombel tersimpan permanen</span>
                        </div>

                        <!-- Timeline list -->
                        <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                            
                            <template x-if="journeyHistories.length === 0">
                                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 text-slate-500 text-center">
                                    Belum ada catatan riwayat kelas tersimpan untuk ananda ini.
                                </div>
                            </template>

                            <template x-for="(h, idx) in journeyHistories" :key="h.id">
                                <div class="relative group">
                                    <!-- Node marker -->
                                    <div class="absolute -left-6 top-1 w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center shadow-xs"
                                        :class="h.status === 'lulus' ? 'bg-amber-500 text-white' : (h.status === 'aktif' ? 'bg-emerald-500 text-white' : 'bg-indigo-500 text-white')">
                                        <i data-lucide="check" class="w-2.5 h-2.5" x-show="h.status === 'lulus'"></i>
                                    </div>

                                    <!-- Content card -->
                                    <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-2">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold"
                                                    :class="h.sub_unit === 'PG' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400'"
                                                    x-text="h.sub_unit || 'TK'">
                                                </span>
                                                <h5 class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="h.classroom_name || 'Kelompok Belajar'"></h5>
                                                <span class="text-[11px] text-slate-400" x-text="h.grade_level ? '(' + h.grade_level + ')' : ''"></span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold"
                                                    :class="{
                                                        'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800': h.status === 'lulus',
                                                        'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800': h.status === 'aktif',
                                                        'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800': h.status === 'mutasi'
                                                    }"
                                                    x-text="h.status ? h.status.toUpperCase() : 'AKTIF'">
                                                </span>
                                                <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300" x-text="h.academic_year?.name ? 'T.A. ' + h.academic_year.name : ''"></span>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1 border-t border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                                                <i data-lucide="user-check" class="w-3.5 h-3.5 text-indigo-500"></i>
                                                <span>Wali Kelas: <strong class="text-slate-800 dark:text-slate-200" x-text="h.homeroom_teacher_name || 'Ustadzah / Guru Pendamping'"></strong></span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-slate-500" x-show="h.start_date || h.end_date">
                                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                                <span>Periode: <span x-text="h.start_date || '-'"></span> s/d <span x-text="h.end_date || 'Selesai'"></span></span>
                                            </div>
                                        </div>

                                        <template x-if="h.notes">
                                            <div class="text-[11px] bg-slate-50 dark:bg-slate-800/50 p-2 rounded-lg text-slate-600 dark:text-slate-400 italic">
                                                Catatan: <span x-text="h.notes"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- BIODATA & KONTAK ORTU -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <i data-lucide="users-2" class="w-4 h-4 text-blue-600"></i>
                            Biodata & Kontak Orang Tua
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Lengkap</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedAlumni?.full_name"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ayah</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedAlumni?.father_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ibu</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedAlumni?.mother_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nomor WhatsApp</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="selectedAlumni?.parent_phone || '-'"></span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-400 block text-[11px]">Sekolah Lanjutan / Catatan</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedAlumni?.notes || 'Belum diisi'"></span>
                            </div>
                        </div>

                        <template x-if="selectedAlumni?.whatsapp_url">
                            <div class="mt-4 flex justify-start">
                                <a :href="selectedAlumni.whatsapp_url" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    Hubungi Orang Tua Alumni via WhatsApp
                                </a>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                    <button type="button" @click="journeyModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                        Tutup
                    </button>
                    <button type="button" @click="openEditNotesModal(selectedAlumni.id, selectedAlumni.full_name, selectedAlumni.notes, selectedAlumni.parent_phone)" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        Update Catatan / Sekolah Lanjutan
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT CATATAN ALUMNI -->
        <div x-show="editNotesModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display: none; margin-top: 0px !important; z-index: 9999; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="editNotesModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl flex flex-col">
                <form @submit.prevent="submitNotesForm">
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Update Catatan Alumni</h3>
                            <p class="text-xs text-slate-400 mt-0.5" x-text="editForm.student_name"></p>
                        </div>
                        <button type="button" @click="editNotesModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Sekolah Lanjutan / Prestasi / Keterangan</label>
                            <textarea x-model="editForm.notes" rows="3" placeholder="Contoh: Melanjutkan ke SD Anak Saleh Malang / MIN 1 Kota Malang..."
                                class="w-full p-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. WhatsApp Wali yang Masih Aktif</label>
                            <input type="text" x-model="editForm.parent_phone" placeholder="081234567890"
                                class="w-full h-9 px-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                        <button type="button" @click="editNotesModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5">
                            <span x-show="!isSubmitting">Simpan Perubahan</span>
                            <span x-show="isSubmitting">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function alumniApp() {
            return {
                journeyModalOpen: false,
                editNotesModalOpen: false,
                isSubmitting: false,
                selectedAlumni: null,
                journeyHistories: [],
                editForm: {
                    student_id: null,
                    student_name: '',
                    notes: '',
                    parent_phone: ''
                },

                openJourneyModal(studentId) {
                    fetch(`/alumni/${studentId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.selectedAlumni = data.student;
                                this.journeyHistories = data.histories || [];
                                this.journeyModalOpen = true;
                                this.$nextTick(() => {
                                    if (window.lucide) window.lucide.createIcons();
                                });
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', 'Gagal memuat detail data alumni.', 'error');
                                }
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', 'Terjadi kesalahan jaringan.', 'error');
                            }
                        });
                },

                openEditNotesModal(studentId, studentName, notes, parentPhone) {
                    this.editForm.student_id = studentId;
                    this.editForm.student_name = studentName;
                    this.editForm.notes = notes || '';
                    this.editForm.parent_phone = parentPhone || '';
                    this.editNotesModalOpen = true;
                },

                submitNotesForm() {
                    this.isSubmitting = true;
                    fetch(`/alumni/${this.editForm.student_id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            notes: this.editForm.notes,
                            parent_phone: this.editForm.parent_phone
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.isSubmitting = false;
                        if (data.success) {
                            this.editNotesModalOpen = false;
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(data.message || 'Catatan alumni berhasil disimpan.', 'success');
                            }
                            window.location.reload();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', data.message || 'Gagal menyimpan catatan alumni.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        this.isSubmitting = false;
                        console.error(err);
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Terjadi kesalahan saat menyimpan data.', 'error');
                        }
                    });
                }
            };
        }
    </script>
</x-admin-layout>
