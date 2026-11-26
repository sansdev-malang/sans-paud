<x-admin-layout>
    <div class="flex w-full flex-1 flex-col gap-3.5 sm:gap-4 p-3.5 sm:p-5 md:p-6" x-data="{
        selectedStatusFilter: 'ALL',
        searchQuery: '',
        previewPdfUrl: null,
        previewStudentName: '',
        selectedItem: null,
        showDetailModal: false,
        
        openPreview(item) {
            if (!item || item.status === 'belum_diisi') return;
            this.previewStudentName = item.full_name + ' - ' + item.activity_name;
            this.previewPdfUrl = item.print_url;
        },
        openDetail(item) {
            this.selectedItem = item;
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
                const actName = (item.activity_name || '').toLowerCase();
                const cls = (item.classroom_name || '').toLowerCase();
                return name.includes(q) || nis.includes(q) || actName.includes(q) || cls.includes(q);
            }
            return true;
        }
    }">

        <!-- ========================================================================= -->
        <!-- TOP CONTEXT BANNER: Title, Actions, Rombel Switcher & 2-Semester Tabs    -->
        <!-- ========================================================================= -->
        <div class="space-y-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 sm:p-4 shadow-xs">
            <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                <!-- Title & Context Badge -->
                <div>
                    <div class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="award" class="w-4 h-4"></i>
                        </div>
                        <h1 class="text-sm sm:text-base font-bold tracking-tight text-slate-900 dark:text-slate-50">
                            Data Rapor Ekstrakurikuler
                        </h1>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        @if($isStrictCoordinator)
                            Menampilkan penilaian ekstra yang Anda koordinasikan: 
                            <strong class="text-slate-800 dark:text-slate-200">{{ $selectedActivity?->name ?? 'Ekstrakurikuler' }}</strong>
                        @elseif($isHomeroom && !$isAdmin)
                            Menampilkan seluruh kegiatan ekstra murid pada rombel: 
                            <strong class="text-slate-800 dark:text-slate-200">{{ $selectedClassroom?->name ?? 'Kelas Binaan' }}</strong>
                        @else
                            Rekapitulasi penilaian capaian indikator, presensi, dan berkas rapor ekstrakurikuler PAUD.
                        @endif
                    </p>
                </div>

                <!-- Top Right Actions & Filters Form -->
                <form method="GET" action="{{ route('rekap-rapor-ekstra.index') }}" id="extraFilterForm" class="flex shrink-0 flex-wrap items-center gap-2">
                    <input type="hidden" name="semester" value="{{ $selectedSemester }}" id="hiddenExtraSemesterInput">

                    <!-- Academic Year Filter Selector -->
                    @if($academicYears && count($academicYears) > 0)
                    <select name="academic_year_id" onchange="document.getElementById('extraFilterForm').submit()"
                        title="Pilih Tahun Ajaran"
                        class="h-8.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 pr-7 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                        @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                            TA {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Kegiatan Ekstra Filter Selector -->
                    @if($activities && count($activities) > 0)
                    <select name="activity_id" onchange="document.getElementById('extraFilterForm').submit()"
                        title="Pilih Kegiatan Ekstrakurikuler"
                        class="h-8.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 pr-7 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer min-w-[130px]">
                        @if(!$isStrictCoordinator)
                        <option value="all" {{ $selectedActivityId === 'all' ? 'selected' : '' }}>
                            Semua Kegiatan Ekstra
                        </option>
                        @endif
                        @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ (string)$selectedActivityId === (string)$act->id ? 'selected' : '' }}>
                            {{ $act->name }} {{ $act->type === 'wajib' ? '(Wajib)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Classroom / Rombel Filter Selector -->
                    @if($classrooms && count($classrooms) > 0)
                    <select name="classroom_id" onchange="document.getElementById('extraFilterForm').submit()"
                        title="Pilih Kelompok / Rombel"
                        class="h-8.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 pr-7 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer min-w-[110px]">
                        @if(!$isHomeroom || $isAdmin || $isStrictCoordinator)
                        <option value="all" {{ $selectedClassroomId === 'all' ? 'selected' : '' }}>
                            Semua Rombel
                        </option>
                        @endif
                        @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" {{ (string)$selectedClassroomId === (string)$c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Leger Nilai Excel -->
                    <a href="{{ $exportLegerUrl }}" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs transition whitespace-nowrap cursor-pointer"
                        title="Unduh Rekap Buku Leger Nilai Ekstrakurikuler (Excel)">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                        <span>Leger Ekstra (Excel)</span>
                    </a>

                    <!-- Cetak 1 Rombel PDF -->
                    <a href="{{ $printClassroomUrl }}" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition whitespace-nowrap cursor-pointer {{ count($items) === 0 ? 'pointer-events-none opacity-50' : '' }}"
                        title="Cetak Seluruh Lembar Rapor Ekstrakurikuler Sekaligus">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Cetak Rombel (PDF)</span>
                    </a>
                </form>
            </div>

            <!-- 1-Click 2-Semester Segmented Pill Tabs (Ekstra: Semester 1 & Semester 2) -->
            <div class="flex items-center gap-1 overflow-x-auto rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/60 p-1">
                @foreach($semesters as $sem)
                <button type="button"
                    onclick="document.getElementById('hiddenExtraSemesterInput').value = '{{ $sem['code'] }}'; document.getElementById('extraFilterForm').submit();"
                    class="flex flex-1 shrink-0 min-w-[140px] cursor-pointer items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-center text-xs transition-all
                    {{ $selectedSemester === $sem['code'] ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100 font-semibold' }}">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 {{ $selectedSemester === $sem['code'] ? 'text-indigo-400 dark:text-indigo-600' : 'text-slate-400' }}"></i>
                    <span>{{ $sem['name'] }}</span>
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
                        class="rounded-lg border px-2.5 py-1 text-xs font-semibold shadow-2xs transition cursor-pointer">
                        Semua ({{ $stats['total_items'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'lengkap'" type="button"
                        :class="selectedStatusFilter === 'lengkap'
                            ? 'border-emerald-600 bg-emerald-600 text-white shadow-xs'
                            : 'border-emerald-200 dark:border-emerald-900/50 bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-semibold shadow-2xs transition cursor-pointer">
                        ✓ Lengkap ({{ $stats['lengkap_count'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'belum_lengkap'" type="button"
                        :class="selectedStatusFilter === 'belum_lengkap'
                            ? 'border-amber-500 bg-amber-500 text-white shadow-xs'
                            : 'border-amber-200 dark:border-amber-900/50 bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-semibold shadow-2xs transition cursor-pointer">
                        ⚠️ Belum Lengkap ({{ $stats['belum_lengkap_count'] }})
                    </button>
                    <button @click="selectedStatusFilter = 'belum_diisi'" type="button"
                        :class="selectedStatusFilter === 'belum_diisi'
                            ? 'border-slate-700 bg-slate-700 text-white shadow-xs'
                            : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 font-semibold'"
                        class="rounded-lg border px-2.5 py-1 text-xs font-semibold shadow-2xs transition cursor-pointer">
                        ⚪ Belum Diisi ({{ $stats['belum_diisi_count'] }})
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-64 md:w-72">
                    <span class="absolute top-1/2 left-3 -translate-y-1/2 pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input x-model="searchQuery" type="text" placeholder="Cari murid, NIS, atau ekstra..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 pr-7 pl-8 text-xs font-medium text-slate-900 dark:text-slate-100 shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400">
                    <button x-show="searchQuery.length > 0" @click="searchQuery = ''" type="button"
                        class="absolute top-1/2 right-2.5 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

            <!-- Progres Pengisian Bar -->
            <div class="flex items-center gap-3 pt-1 border-t border-slate-100 dark:border-slate-800/80">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">
                    Kelengkapan Nilai: <strong>{{ $stats['progress_percent'] }}%</strong>
                </span>
                <div class="flex-1 h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                        style="width: {{ $stats['progress_percent'] }}%"></div>
                </div>
                <span class="text-[11px] text-slate-400">
                    {{ $stats['lengkap_count'] }} dari {{ $stats['total_items'] }} terisi lengkap
                </span>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- DATA TABLE CARD                                                           -->
        <!-- ========================================================================= -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/60 text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="w-12 px-3 py-3 text-center">No</th>
                            <th scope="col" class="px-4 py-3">Murid & Rombel</th>
                            <th scope="col" class="px-4 py-3">Kegiatan Ekstra</th>
                            <th scope="col" class="px-4 py-3">Catatan / Saran</th>
                            <th scope="col" class="px-4 py-3 text-center">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                        @forelse($items as $index => $item)
                        <tr x-show="matchesFilter({{ json_encode($item) }})" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- 1. No -->
                            <td class="px-3 py-3 text-center text-slate-400 select-none">
                                {{ $index + 1 }}
                            </td>

                            <!-- 2. Murid & Rombel -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase">
                                        {{ mb_substr($item->full_name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate max-w-[220px]" title="{{ $item->full_name }}">
                                            {{ $item->full_name }}
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                            <span>NIS: {{ $item->nis }}</span>
                                            <span>•</span>
                                            <span class="inline-flex items-center rounded px-1.5 py-0.2 bg-slate-100 dark:bg-slate-800 font-semibold text-slate-700 dark:text-slate-300">
                                                {{ $item->classroom_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. Kegiatan Ekstra -->
                            <td class="px-4 py-3">
                                <div class="space-y-0.5">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ $item->activity_name }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @if($item->activity_type === 'wajib')
                                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold bg-cyan-50 dark:bg-cyan-950/50 text-cyan-700 dark:text-cyan-300 border border-cyan-200/50 dark:border-cyan-800/50">
                                            Wajib
                                        </span>
                                        @else
                                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold bg-violet-50 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 border border-violet-200/50 dark:border-violet-800/50">
                                            Pilihan
                                        </span>
                                        @endif

                                        <span class="text-[11px] text-slate-400 truncate max-w-[130px]" title="Pembina/Pelatih: {{ $item->coach_name }}">
                                            {{ $item->coach_name }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Catatan / Saran -->
                            <td class="px-4 py-3 max-w-[280px]">
                                @if(!empty($item->teacher_advice))
                                    <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2 leading-relaxed" title="{{ $item->teacher_advice }}">
                                        "{{ $item->teacher_advice }}"
                                    </p>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum ada catatan/saran</span>
                                @endif
                            </td>

                            <!-- 5. Status Nilai -->
                            <td class="px-4 py-3 text-center">
                                @if($item->status === 'lengkap')
                                <div class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400 text-xs whitespace-nowrap">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span>Lengkap</span>
                                </div>
                                @elseif($item->status === 'belum_lengkap')
                                <div class="inline-flex items-center gap-1.5 font-semibold text-amber-600 dark:text-amber-400 text-xs whitespace-nowrap">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                                    <span>Belum Lengkap</span>
                                </div>
                                @else
                                <div class="inline-flex items-center gap-1.5 font-medium text-slate-400 dark:text-slate-500 text-xs whitespace-nowrap">
                                    <span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600 shrink-0"></span>
                                    <span>Belum Diisi</span>
                                </div>
                                @endif
                            </td>

                            <!-- 6. Aksi (Pratinjau & Cetak PDF) -->
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    @if($item->status !== 'belum_diisi')
                                    <!-- Tombol Pratinjau Modal -->
                                    <button @click="openPreview({{ json_encode($item) }})" type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs transition whitespace-nowrap cursor-pointer"
                                        title="Lihat Pratinjau Lembar Rapor Ekstra">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0"></i>
                                        <span>Pratinjau</span>
                                    </button>

                                    <!-- Tombol Cetak PDF Langsung -->
                                    <a href="{{ $item->print_url }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white px-2.5 py-1.5 text-xs font-semibold shadow-xs transition whitespace-nowrap cursor-pointer"
                                        title="Cetak Lembar Rapor Ekstra PDF">
                                        <i data-lucide="printer" class="w-3.5 h-3.5 shrink-0"></i>
                                        <span>Cetak PDF</span>
                                    </a>
                                    @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-slate-400 dark:text-slate-500 italic select-none">
                                        Belum tersedia
                                    </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <i data-lucide="award" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Tidak ada data penilaian ekstrakurikuler yang sesuai.</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Silakan sesuaikan pilihan Tahun Ajaran, Kegiatan Ekstra, atau Rombel pada filter di atas.</p>
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
                            Pratinjau Rapor Ekstra: <span class="text-indigo-600 dark:text-indigo-400" x-text="previewStudentName"></span>
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
                        <iframe :src="previewPdfUrl" class="h-full w-full rounded-b-xl border-0" title="Pratinjau PDF Rapor Ekstra"></iframe>
                    </template>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- DETAIL INDIKATOR MODAL                                    -->
        <!-- ========================================================= -->
        <div x-show="showDetailModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-5 bg-slate-900/80 backdrop-blur-xs"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">

            <div class="relative flex flex-col max-h-[85vh] w-full max-w-2xl rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl overflow-hidden"
                @click.away="showDetailModal = false">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 px-5 py-3.5">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            <i data-lucide="award" class="w-4 h-4 text-indigo-600"></i>
                            <span>Rincian Penilaian Ekstrakurikuler</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedItem?.full_name"></span> 
                            • <span x-text="selectedItem?.activity_name"></span> 
                            (<span x-text="selectedItem?.classroom_name"></span>)
                        </p>
                    </div>
                    <button @click="showDetailModal = false" type="button"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body: Indikator & Catatan -->
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <!-- Presensi & Pelatih Banner -->
                    <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 text-xs">
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Kehadiran Pertemuan:</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100" x-text="(selectedItem?.attendance_present ?? 0) + ' dari ' + (selectedItem?.attendance_total ?? 0) + ' Pertemuan'"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Pembina / Pelatih:</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100" x-text="selectedItem?.coach_name || '-'"></span>
                        </div>
                    </div>

                    <!-- Tabel Daftar Indikator Capaian -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-2">
                            Daftar Capaian Indikator
                        </h4>
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase">
                                    <tr>
                                        <th class="w-8 px-3 py-2 text-center">No</th>
                                        <th class="px-3 py-2">Indikator Pembelajaran</th>
                                        <th class="w-24 px-3 py-2 text-center">Predikat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                    <template x-for="(ind, idx) in (selectedItem?.indicators || [])" :key="ind.id || idx">
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                            <td class="px-3 py-2 text-center text-slate-400" x-text="idx + 1"></td>
                                            <td class="px-3 py-2">
                                                <span class="font-medium text-slate-800 dark:text-slate-200" x-text="ind.text"></span>
                                                <template x-if="ind.category">
                                                    <span class="block text-[10px] text-slate-400 font-semibold" x-text="ind.category"></span>
                                                </template>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <template x-if="selectedItem?.indicator_grades?.[ind.id]">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold"
                                                        :class="{
                                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200': selectedItem.indicator_grades[ind.id] === 'BSB',
                                                            'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200': selectedItem.indicator_grades[ind.id] === 'BSH',
                                                            'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200': selectedItem.indicator_grades[ind.id] === 'MB',
                                                            'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200': selectedItem.indicator_grades[ind.id] === 'BB'
                                                        }"
                                                        x-text="selectedItem.indicator_grades[ind.id]">
                                                    </span>
                                                </template>
                                                <template x-if="!selectedItem?.indicator_grades?.[ind.id]">
                                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Catatan & Saran -->
                    <div class="space-y-1">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">
                            Catatan & Saran Perkembangan
                        </h4>
                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 text-xs leading-relaxed text-slate-700 dark:text-slate-300">
                            <template x-if="selectedItem?.teacher_advice">
                                <p x-text="selectedItem.teacher_advice"></p>
                            </template>
                            <template x-if="!selectedItem?.teacher_advice">
                                <p class="text-slate-400 italic">Belum ada saran atau catatan perkembangan.</p>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 px-5 py-3">
                    <button @click="showDetailModal = false" type="button"
                        class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 shadow-2xs transition cursor-pointer">
                        Tutup
                    </button>

                    <div class="flex items-center gap-2">
                        <template x-if="selectedItem?.status !== 'belum_diisi'">
                            <a :href="selectedItem?.print_url" target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition">
                                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                <span>Cetak Dokumen PDF</span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
