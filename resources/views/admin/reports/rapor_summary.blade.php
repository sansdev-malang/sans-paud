<x-admin-layout>
    <div class="flex w-full flex-1 flex-col gap-3.5 sm:gap-4 p-3.5 sm:p-5 md:p-6" x-data="{
        selectedStatusFilter: 'ALL',
        searchQuery: '',
        previewPdfUrl: null,
        previewStudentName: '',
        selectedStudent: null,
        showDetailModal: false,
        
        openPreview(item) {
            this.previewStudentName = item.full_name;
            this.previewPdfUrl = item.print_url;
        },
        openDetail(item) {
            this.selectedStudent = item;
            this.showDetailModal = true;
        },
        matchesFilter(item) {
            if (this.selectedStatusFilter !== 'ALL' && item.status !== this.selectedStatusFilter) {
                return false;
            }
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase().trim();
                const name = (item.full_name || '').toLowerCase();
                const nis = (item.nis || '').toLowerCase();
                const nisn = (item.nisn || '').toLowerCase();
                return name.includes(q) || nis.includes(q) || nisn.includes(q);
            }
            return true;
        }
    }">

        <!-- ========================================================================= -->
        <!-- TOP CONTEXT BANNER: Title, Actions, Rombel Switcher & 4-Semester Tabs    -->
        <!-- ========================================================================= -->
        <div class="space-y-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 sm:p-4 shadow-xs">
            <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                <!-- Title -->
                <div class="flex items-center gap-2">
                    <h1 class="text-sm sm:text-base font-bold tracking-tight text-slate-900 dark:text-slate-50">
                        Data Rapor Murid
                    </h1>
                </div>

                <!-- Top Right Actions & Filters Form -->
                <form method="GET" action="{{ route('rekap-rapor.index') }}" id="filterForm" class="flex shrink-0 flex-wrap items-center gap-2">
                    <input type="hidden" name="semester" value="{{ $selectedSemester }}" id="hiddenSemesterInput">

                    <!-- Academic Year Filter Selector -->
                    @if($academicYears && count($academicYears) > 0)
                    <select name="academic_year_id" onchange="document.getElementById('filterForm').submit()"
                        title="Pilih Tahun Ajaran"
                        class="h-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                        @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                            TA {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Classroom / Rombel Filter Selector -->
                    @if($classrooms && count($classrooms) > 0)
                    <select name="classroom_id" onchange="document.getElementById('filterForm').submit()"
                        title="Pilih Kelompok / Rombel"
                        class="h-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                        @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" {{ $selectedClassroomId == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Leger Nilai Excel -->
                    <a href="{{ $exportLegerUrl }}" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs transition cursor-pointer"
                        title="Unduh Rekap Buku Leger Nilai Kelas (Excel)">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                        <span>Leger Nilai (Excel)</span>
                    </a>

                    <!-- Cetak 1 Rombel PDF -->
                    <a href="{{ $printClassroomUrl }}" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition cursor-pointer {{ count($studentData) === 0 ? 'pointer-events-none opacity-50' : '' }}"
                        title="Cetak Seluruh Lembar Rapor Murid di Kelas Ini Sekaligus">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Cetak 1 Rombel (PDF)</span>
                    </a>
                </form>
            </div>

            <!-- 1-Click 4-Semester Segmented Pill Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/60 p-1">
                @foreach($semesters as $sem)
                <button type="button"
                    onclick="document.getElementById('hiddenSemesterInput').value = '{{ $sem['code'] }}'; document.getElementById('filterForm').submit();"
                    class="flex flex-1 shrink-0 min-w-[130px] sm:min-w-0 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-center text-xs transition-all
                    {{ $selectedSemester === $sem['code'] ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100 font-semibold' }}">
                    <span>{{ $sem['name'] }}</span>
                    @if($sem['is_active'])
                    <span class="rounded px-1.5 py-0.5 text-[9px] font-bold {{ $selectedSemester === $sem['code'] ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400' }}">
                        Aktif
                    </span>
                    @endif
                </button>
                @endforeach
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- FILTER & INTERACTIVE STATUS COUNTER BAR                                   -->
        <!-- ========================================================================= -->
        <div class="space-y-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 sm:p-4 shadow-xs">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <!-- Interactive KPI Status Filter Tabs -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button @click="selectedStatusFilter = 'ALL'" type="button"
                        :class="selectedStatusFilter === 'ALL'
                            ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900 shadow-xs'
                            : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs transition cursor-pointer">
                        Semua ({{ $stats['total_students'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'approved'" type="button"
                        :class="selectedStatusFilter === 'approved'
                            ? 'border-emerald-600 bg-emerald-600 text-white shadow-xs'
                            : 'border-emerald-200 dark:border-emerald-900/50 bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs transition cursor-pointer">
                        ✓ Disetujui ({{ $stats['approved_count'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'submitted'" type="button"
                        :class="selectedStatusFilter === 'submitted'
                            ? 'border-amber-500 bg-amber-500 text-white shadow-xs'
                            : 'border-amber-200 dark:border-amber-900/50 bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs transition cursor-pointer">
                        ⏳ Menunggu ({{ $stats['submitted_count'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'revisi'" type="button"
                        :class="selectedStatusFilter === 'revisi'
                            ? 'border-rose-600 bg-rose-600 text-white shadow-xs'
                            : 'border-rose-200 dark:border-rose-900/50 bg-white dark:bg-slate-800 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs transition cursor-pointer">
                        ⚠️ Revisi ({{ $stats['revisi_count'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'draft'" type="button"
                        :class="selectedStatusFilter === 'draft'
                            ? 'border-blue-600 bg-blue-600 text-white shadow-xs'
                            : 'border-blue-200 dark:border-blue-900/50 bg-white dark:bg-slate-800 text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/30 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs transition cursor-pointer">
                        📝 Draft ({{ $stats['draft_count'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'belum_diisi'" type="button"
                        :class="selectedStatusFilter === 'belum_diisi'
                            ? 'border-slate-700 bg-slate-700 text-white shadow-xs'
                            : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs transition cursor-pointer">
                        ⚪ Belum Diisi ({{ $stats['unfilled_count'] }})
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-60 md:w-64">
                    <i data-lucide="search" class="absolute top-1/2 left-3 w-3.5 h-3.5 -translate-y-1/2 text-slate-400"></i>
                    <input x-model="searchQuery" type="text" placeholder="Cari murid atau NIS..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 pr-7 pl-8 text-xs font-medium text-slate-900 dark:text-slate-100 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <button v-if="searchQuery" @click="searchQuery = ''" x-show="searchQuery" type="button"
                        class="absolute top-1/2 right-2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
           <!-- ========================================================= -->
        <!-- STUDENT REPORT DATA TABLE (Standardized Typography)      -->
        <!-- ========================================================= -->
        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse min-w-[760px]">
                    <thead class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider select-none">
                        <tr>
                            <th class="w-12 px-4 py-3.5 text-center">No</th>
                            <th class="w-36 px-4 py-3.5 text-left">NIS / NISN</th>
                            <th class="px-4 py-3.5 text-left">Peserta Didik</th>
                            <th class="w-44 px-4 py-3.5 text-left">Kelengkapan Data</th>
                            <th class="w-44 px-4 py-3.5 text-center">Status Rapor</th>
                            <th class="w-44 px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @php $counter = 1; @endphp
                        @forelse($studentData as $student)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group"
                            x-show="matchesFilter({{ json_encode($student) }})">
                            <!-- 1. No -->
                            <td class="px-4 py-3.5 text-center font-medium text-slate-400 dark:text-slate-500 text-xs">
                                {{ $counter++ }}
                            </td>

                            <!-- 2. NIS / NISN -->
                            <td class="px-4 py-3.5">
                                <div class="font-mono font-semibold text-xs text-indigo-600 dark:text-indigo-400">
                                    {{ $student->nis }}
                                </div>
                                @if($student->nisn)
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    NISN: {{ $student->nisn }}
                                </div>
                                @endif
                            </td>

                            <!-- 3. Name & Gender -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg text-xs font-bold uppercase shadow-2xs flex items-center justify-center shrink-0 {{ $student->gender === 'P' || $student->gender === 'Perempuan' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-900' : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-900' }}">
                                        {{ substr($student->full_name, 0, 1) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-xs tracking-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                                            {{ $student->full_name }}
                                        </span>
                                        <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                                            <span>{{ $student->gender === 'P' || $student->gender === 'Perempuan' ? 'Perempuan' : 'Laki-laki' }}</span>
                                            <span>•</span>
                                            <span class="font-medium text-slate-600 dark:text-slate-300">{{ $selectedClassroom?->name ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Completeness Indicator -->
                            <td class="px-4 py-3.5">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $student->has_narrative ? 'bg-emerald-500' : 'bg-rose-400' }}"></span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                            Narasi:
                                            <strong class="{{ $student->has_narrative ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                                {{ $student->has_narrative ? 'Terisi' : 'Kosong' }}
                                            </strong>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $student->has_growth ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                            Fisik/Absen:
                                            <strong class="{{ $student->has_growth ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600' }}">
                                                {{ $student->has_growth ? 'Lengkap' : 'Belum' }}
                                            </strong>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 5. Status Badge -->
                            <td class="px-4 py-3.5 text-center">
                                <div class="inline-flex flex-col items-center gap-1">
                                    @if($student->status === 'approved')
                                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300 bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 shadow-2xs dark:border-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300"
                                        title="Rapor telah diperiksa dan disahkan oleh Kepala Sekolah">
                                        <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                                        <span>Disetujui</span>
                                    </span>
                                    @elseif($student->status === 'submitted')
                                    <span class="inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 shadow-2xs dark:border-amber-700 dark:bg-amber-950/80 dark:text-amber-300"
                                        title="Menunggu verifikasi dan pengesahan Kepala Sekolah">
                                        <i data-lucide="clock" class="w-3 h-3 animate-pulse"></i>
                                        <span>Menunggu Review</span>
                                    </span>
                                    @elseif($student->status === 'revisi')
                                    <span class="inline-flex items-center gap-1 rounded-full border border-rose-300 bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 shadow-2xs dark:border-rose-700 dark:bg-rose-950/80 dark:text-rose-300"
                                        title="Rapor dikembalikan oleh Kepala Sekolah untuk direvisi">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                        <span>Perlu Revisi</span>
                                    </span>
                                    @if($student->review_notes)
                                    <span class="text-[10px] text-rose-600 dark:text-rose-400 italic max-w-[130px] truncate" title="{{ $student->review_notes }}">
                                        "{{ $student->review_notes }}"
                                    </span>
                                    @endif
                                    @elseif($student->status === 'draft')
                                    <span class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:border-blue-800 dark:bg-blue-950/80 dark:text-blue-300"
                                        title="Draft tersimpan, belum diajukan ke Kepala Sekolah">
                                        <i data-lucide="file-edit" class="w-3 h-3"></i>
                                        <span>Draft</span>
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                                        <span>Belum Diisi</span>
                                    </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 6. Aksi (Hanya Menampilkan Data & Cetak Rapor) -->
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Tombol Pratinjau -->
                                    <button @click="openPreview({{ json_encode($student) }})" type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs transition cursor-pointer"
                                        title="Lihat Pratinjau Dokumen PDF">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                        <span>Pratinjau</span>
                                    </button>

                                    <!-- Tombol Cetak PDF Langsung -->
                                    <a href="{{ $student->print_url }}" target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white px-2.5 py-1 text-xs font-semibold shadow-2xs transition cursor-pointer"
                                        title="Cetak Dokumen Rapor PDF">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Cetak PDF</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                                <p class="text-xs">Tidak ada data murid pada kelompok belajar ini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- LIVE PDF PREVIEW MODAL                                    -->
        <!-- ========================================================= -->
        <div x-show="previewPdfUrl" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-5 bg-slate-900/80 backdrop-blur-xs"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            
            <div class="relative flex flex-col h-[92vh] w-full max-w-5xl rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl overflow-hidden"
                @click.away="previewPdfUrl = null">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-50">
                            Pratinjau Rapor: <span class="text-indigo-600 dark:text-indigo-400" x-text="previewStudentName"></span>
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <a :href="previewPdfUrl" target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>Buka Tab Baru / Cetak</span>
                        </a>
                        <button @click="previewPdfUrl = null" type="button"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition cursor-pointer">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal PDF Iframe Body -->
                <div class="flex-1 bg-slate-100 dark:bg-slate-950 p-1">
                    <template x-if="previewPdfUrl">
                        <iframe :src="previewPdfUrl" class="h-full w-full rounded-b-xl border-0" title="Pratinjau PDF Rapor"></iframe>
                    </template>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
