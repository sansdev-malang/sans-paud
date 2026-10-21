<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-5" x-data="{ 
        activeTab: 'identitas',
        canScrollLeft: false,
        canScrollRight: false,
        checkTabScroll() {
            const el = this.$refs.tabScroll;
            if (!el) return;
            this.canScrollLeft = el.scrollLeft > 10;
            this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 10);
        },
        scrollTabsLeft() {
            if (this.$refs.tabScroll) {
                this.$refs.tabScroll.scrollBy({ left: -220, behavior: 'smooth' });
            }
        },
        scrollTabsRight() {
            if (this.$refs.tabScroll) {
                this.$refs.tabScroll.scrollBy({ left: 220, behavior: 'smooth' });
            }
        }
    }" x-init="$nextTick(() => { checkTabScroll(); setTimeout(() => checkTabScroll(), 100); setTimeout(() => checkTabScroll(), 400); }); window.addEventListener('resize', () => checkTabScroll())">

        <!-- BREADCRUMBS & TOP BAR -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                <a href="{{ route('students.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Data Murid</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                <span class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[200px] sm:max-w-xs">{{ $student->full_name }}</span>
            </nav>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('students.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Kembali
                </a>
                <!-- DROPDOWN UNDUH / CETAK DOSSIER -->
                <div x-data="{ exportOpen: false }" class="relative">
                    <button type="button" @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all cursor-pointer">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Unduh / Cetak</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': exportOpen }"></i>
                    </button>

                    <div x-show="exportOpen" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-1.5 w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1.5 overflow-hidden">
                        
                        <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                            Pilihan Berkas Dossier
                        </div>

                        <!-- 1. Download PDF Dossier -->
                        <a href="{{ route('students.dossier.pdf', $student->id) }}"
                            @click="exportOpen = false"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-400 transition-colors">
                            <i data-lucide="file-text" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
                            <div>
                                <div class="font-semibold text-slate-800 dark:text-slate-100">Unduh PDF (.pdf)</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">Lembar Dossier Resmi / Buku Induk</div>
                            </div>
                        </a>

                        <!-- 2. Download Excel -->
                        <a href="{{ route('students.dossier.excel', $student->id) }}"
                            @click="exportOpen = false"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                            <div>
                                <div class="font-semibold text-slate-800 dark:text-slate-100">Unduh Excel (.xlsx)</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">Rincian Lengkap Data Murid</div>
                            </div>
                        </a>

                        <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                        <!-- 3. Cetak Langsung (Print) -->
                        <a href="{{ route('students.dossier.print', $student->id) }}"
                            target="_blank"
                            @click="exportOpen = false"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer">
                            <i data-lucide="printer" class="w-4 h-4 text-slate-500 shrink-0"></i>
                            <div>
                                <div class="font-semibold text-slate-800 dark:text-slate-100">Cetak Dossier (Print)</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">Lembar resmi A4 (tanpa tampilan web)</div>
                            </div>
                        </a>
                    </div>
                </div>
                <a href="{{ route('students.edit', $student->id) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                    Edit Data Murid
                </a>
            </div>
        </div>

        <!-- FLASH NOTIFICATION -->
        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                    <span class="text-xs sm:text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        <!-- HERO PROFILE HEADER -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-xs relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none -mr-20 -mt-20"></div>

            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-5 relative z-10">
                <!-- Avatar & Identity -->
                <div class="flex items-center gap-4 sm:gap-5 min-w-0">
                    <div class="relative shrink-0">
                        @if($student->student_photo_url)
                            <img src="{{ $student->student_photo_url }}" alt="{{ $student->full_name }}"
                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover shadow-md border-2 border-indigo-200 dark:border-indigo-800">
                        @else
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center font-black text-xl sm:text-2xl shadow-md border-2
                                {{ $student->gender === 'P' 
                                    ? 'bg-rose-50 dark:bg-rose-950/60 border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-400' 
                                    : 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-300 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400' }}">
                                {{ $student->avatar_initials ?? strtoupper(substr($student->full_name, 0, 2)) }}
                            </div>
                        @endif
                        <span class="absolute -bottom-1 -right-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold border shadow-xs
                            {{ $student->gender === 'P' ? 'bg-pink-100 text-pink-700 border-pink-300' : 'bg-blue-100 text-blue-700 border-blue-300' }}">
                            {{ $student->gender === 'P' ? 'P' : 'L' }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-50 truncate">
                                {{ $student->full_name }}
                            </h1>
                            @if($student->nickname)
                                <span class="text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    "{{ $student->nickname }}"
                                </span>
                            @endif

                            <!-- Status Badge (only for non-active states, e.g. Lulus / Mutasi) -->
                            @if($student->status && $student->status !== 'aktif')
                                @if($student->status === 'lulus')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                        Lulus
                                    </span>
                                @elseif($student->status === 'mutasi')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                        Mutasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        {{ ucfirst($student->status) }}
                                    </span>
                                @endif
                            @endif
                        </div>

                        <!-- Sub-details: NIS, NISN, Status, Rombel, Jenjang -->
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="font-medium text-slate-400">NIS:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-200">{{ $student->nis ?? '-' }}</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5">
                                <span class="font-medium text-slate-400">NISN:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-200">{{ $student->nisn ?? '-' }}</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5">
                                <span class="font-medium text-slate-400">Status:</span>
                                <span class="font-bold {{ $student->status === 'aktif' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ ucfirst($student->status ?? 'Aktif') }}
                                </span>
                            </div>
                            <div class="inline-flex items-center gap-1.5">
                                <span class="font-medium text-slate-400">Rombel:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $student->classroom?->name ?? 'Belum Ada Rombel' }}
                                </span>
                            </div>
                            <div class="inline-flex items-center gap-1.5">
                                <span class="font-medium text-slate-400">T.A:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $student->academicYear?->name ?? '2026/2027' }}</span>
                            </div>
                        </div>

                        <!-- Special Program Badges -->
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <i data-lucide="school" class="w-3.5 h-3.5 text-indigo-500"></i>
                                Jenjang {{ $student->jenjang?->name ?? $student->sub_unit ?? 'TK' }}
                            </span>

                            @if($student->daycareClassroom)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                    <i data-lucide="sun" class="w-3.5 h-3.5 text-amber-500"></i>
                                    Daycare: {{ $student->daycareClassroom->name }}
                                </span>
                            @endif

                            @if($student->is_tpq)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-400 border border-teal-200 dark:border-teal-800">
                                    <i data-lucide="book-open" class="w-3.5 h-3.5 text-teal-500"></i>
                                    {{ $student->tpqClassroom && $student->tpqClassroom->name !== 'TPQ' ? 'TPQ: ' . $student->tpqClassroom->name : 'Program TPQ' }}
                                </span>
                            @endif

                            @if($student->parent_phone)
                                <a href="{{ $student->whatsapp_url }}" target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors">
                                    <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    WA: {{ $student->parent_phone }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Box -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 w-full md:w-auto shrink-0 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col text-center px-3 py-1">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Usia</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $student->age ?? '-' }}</span>
                    </div>
                    <div class="flex flex-col text-center px-3 py-1 border-x border-slate-200 dark:border-slate-700">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Gol. Darah</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $student->blood_type ?? '-' }}</span>
                    </div>
                    <div class="flex flex-col text-center px-3 py-1 col-span-2 sm:col-span-1">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Fisik (TB/BB)</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                            {{ $student->height ? $student->height . ' cm' : '-' }} / {{ $student->weight ? $student->weight . ' kg' : '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- HORIZONTAL TAB NAVIGATION (STICKY WITH HOVER SCROLL INDICATORS) -->
        <div class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950/90 backdrop-blur-md pt-2 pb-1 border-b border-slate-200 dark:border-slate-800 -mx-3.5 sm:-mx-5 lg:-mx-6 px-3.5 sm:px-5 lg:px-6 tab-scroll-container group relative">
            
            <!-- Left Arrow Indicator (muncul saat hover jika bisa di-scroll ke kiri) -->
            <button type="button" 
                x-show="canScrollLeft"
                x-cloak
                @click="scrollTabsLeft()"
                class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-30 w-7 h-7 flex items-center justify-center rounded-full bg-white/95 dark:bg-slate-900/95 shadow-md border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                title="Geser tab ke kiri">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </button>

            <!-- Right Arrow Indicator (muncul saat hover jika bisa di-scroll ke kanan) -->
            <button type="button" 
                x-show="canScrollRight"
                x-cloak
                @click="scrollTabsRight()"
                class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-30 w-7 h-7 flex items-center justify-center rounded-full bg-white/95 dark:bg-slate-900/95 shadow-md border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                title="Geser tab ke kanan">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </button>

            <div x-ref="tabScroll" 
                @scroll.passive="checkTabScroll()"
                class="flex items-center gap-1.5 custom-horizontal-scroll pb-1 scroll-smooth">
                <button type="button" @click="activeTab = 'identitas'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                    :class="activeTab === 'identitas' 
                        ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    1. Identitas & Legalitas
                </button>

                <button type="button" @click="activeTab = 'domisili'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                    :class="activeTab === 'domisili' 
                        ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                    2. Domisili & Lingkungan
                </button>

                <button type="button" @click="activeTab = 'keluarga'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                    :class="activeTab === 'keluarga' 
                        ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    3. Orang Tua & Keluarga
                </button>

                <button type="button" @click="activeTab = 'kesehatan'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                    :class="activeTab === 'kesehatan' 
                        ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                    <i data-lucide="heart-pulse" class="w-3.5 h-3.5"></i>
                    4. Fisik & Medis
                </button>

                <button type="button" @click="activeTab = 'psikologi'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                    :class="activeTab === 'psikologi' 
                        ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    5. Tumbuh Kembang
                </button>

                <button type="button" @click="activeTab = 'riwayat'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                    :class="activeTab === 'riwayat' 
                        ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                    6. Riwayat Kelas
                </button>

                <button type="button" @click="activeTab = 'dokumen'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                    :class="activeTab === 'dokumen' 
                        ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                    <i data-lucide="folder-archive" class="w-3.5 h-3.5"></i>
                    7. Berkas & Dokumen
                </button>
            </div>
        </div>

        @php
            $fd = $student->family_data ?? [];
            $hh = $student->health_habits ?? [];
            $dp = $student->development_psychology ?? [];
            $docs = $student->documents ?? [];
        @endphp

        <!-- TAB 1: IDENTITAS & LEGALITAS -->
        <div x-show="activeTab === 'identitas'" x-cloak class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Card 1: Biodata Murid -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="user-check" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Biodata Pokok Murid</h3>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3.5 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Nama Lengkap</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-bold mt-0.5">{{ $student->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Nama Panggilan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->nickname ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Jenis Kelamin</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->formatted_gender }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Tempat, Tanggal Lahir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ $student->birth_place ?? '-' }}, {{ $student->birth_date ? $student->birth_date->translatedFormat('d F Y') : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Agama</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->religion ?? 'Islam' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Kewarganegaraan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->citizenship ?? 'WNI' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Anak Ke-</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ $student->child_order ? 'Anak ke-' . $student->child_order : '-' }}
                                @if($student->siblings_count !== null)
                                    (dari {{ $student->siblings_count }} bersaudara)
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Kebutuhan Khusus</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ in_array($student->special_needs, ['Ya', '02) Ya']) || !empty($student->special_needs_detail) ? 'Ya' : 'Tidak' }}
                                @if((in_array($student->special_needs, ['Ya', '02) Ya']) || !empty($student->special_needs_detail)) && $student->special_needs_detail)
                                    <span class="text-amber-600 dark:text-amber-400 font-medium">({{ $student->special_needs_detail }})</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Card 2: Legalitas Kependudukan & Akses -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="file-badge" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Legalitas & Kependudukan (Dapodik)</h3>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3.5 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Nomor Induk Murid (NIS)</dt>
                            <dd class="text-indigo-600 dark:text-indigo-400 font-bold mt-0.5 font-mono">{{ $student->nis ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">NISN</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-bold mt-0.5 font-mono">{{ $student->nisn ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">NIK Murid</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-bold mt-0.5 font-mono">{{ $student->nik ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Nomor Kartu Keluarga (KK)</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-bold mt-0.5 font-mono">{{ $student->no_kk ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">No. Akta Kelahiran</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5 font-mono">{{ $student->no_akta ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">PIN Akses Kios / Presensi</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-mono font-bold mt-0.5">{{ $student->pin_access ?? '1234' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-slate-400 font-medium">Catatan Khusus Admin</dt>
                            <dd class="text-slate-800 dark:text-slate-200 mt-0.5 italic">{{ $student->notes ?? 'Tidak ada catatan khusus.' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Card 3: Penempatan Akademik & Program Layanan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="school" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Penempatan Akademik & Program Layanan</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block text-[11px]">Tahun Ajaran</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">
                            {{ $student->academicYear?->name ?? '2026/2027' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block text-[11px]">Kelompok Belajar (Rombel)</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400 mt-0.5 block">
                            {{ $student->classroom?->name ?? 'Belum Ditentukan' }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">
                            Wali Kelas: {{ $student->classroom?->homeroomTeacher?->name ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block text-[11px]">Program Daycare</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">
                            {{ $student->daycareClassroom?->name ?? 'Tidak Mengikuti' }}
                        </span>
                        @if($student->daycareClassroom?->homeroomTeacher)
                            <span class="text-[10px] text-slate-400 mt-0.5 block">
                                Pendamping: {{ $student->daycareClassroom->homeroomTeacher->name }}
                            </span>
                        @endif
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block text-[11px]">Program TPQ Sekolah</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">
                            {{ $student->is_tpq ? ($student->tpqClassroom?->name ?? 'Mengikuti TPQ') : 'Tidak Mengikuti' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: DOMISILI & LINGKUNGAN -->
        <div x-show="activeTab === 'domisili'" x-cloak class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Card 1: Alamat Lengkap & Wilayah -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="map-pin" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Alamat Tempat Tinggal</h3>
                    </div>

                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Alamat Jalan / Komplek</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5 leading-relaxed">{{ $student->address ?? '-' }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-slate-400 font-medium">Dusun / Lingkungan</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->dusun ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">RT / RW</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                    {{ $student->rt ? 'RT ' . $student->rt : '-' }} / {{ $student->rw ? 'RW ' . $student->rw : '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Kelurahan / Desa</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->kelurahan ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Kecamatan</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->kecamatan ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Kota / Kabupaten</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->city ?? 'Malang' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Kode Pos</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-mono font-semibold mt-0.5">{{ $student->postal_code ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Tinggal Bersama</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->living_with ?? 'Bersama Orang Tua' }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>

                <!-- Card 2: Akses & Transportasi ke Sekolah -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="navigation" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Transportasi & Akses ke Sekolah</h3>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3.5 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Alat Transportasi</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->transportation ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Jarak ke Sekolah</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->distance_to_school ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Waktu Tempuh</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ $student->travel_time_minutes ? $student->travel_time_minutes . ' Menit' : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Pengantar / Penjemput Terpercaya</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ $fd['relationships']['trusted_pickup'] ?? 'Orang Tua' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Card 3: Lingkungan Rumah & Hunian -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="home" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Kondisi Tempat Tinggal & Lingkungan Bermain</h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Kepemilikan Rumah</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['environment']['house_ownership'] ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Lingkungan Hunian</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['environment']['housing_environment'] ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Halaman Bermain</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['environment']['has_playyard'] ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Keamanan Halaman</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['environment']['playyard_state'] ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Penghuni Dewasa</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['environment']['adult_residents'] ?? '-' }} Orang
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Penghuni Anak</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['environment']['child_residents'] ?? '-' }} Anak
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: ORANG TUA & KELUARGA -->
        <div x-show="activeTab === 'keluarga'" x-cloak class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Card 1: Ayah Kandung -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="user" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Data Ayah Kandung</h3>
                        </div>
                        @if($student->father_phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $student->father_phone) }}" target="_blank"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                WhatsApp
                            </a>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-xs">
                        <div class="sm:col-span-2">
                            <dt class="text-slate-400 font-medium">Nama Lengkap & Gelar</dt>
                            <dd class="text-slate-800 dark:text-slate-100 font-bold mt-0.5 text-sm">
                                {{ trim(($student->father_title_prefix ? $student->father_title_prefix . ' ' : '') . $student->father_name . ($student->father_title_suffix ? ', ' . $student->father_title_suffix : '')) ?: '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">NIK Ayah</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-mono font-semibold mt-0.5">{{ $student->father_nik ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Tempat, Tanggal Lahir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ $student->father_birth_place ?? '-' }}, {{ $student->father_birth_date ? $student->father_birth_date->translatedFormat('d F Y') : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Pendidikan Terakhir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->father_education ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Pekerjaan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->father_job ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Instansi / Perusahaan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->father_company ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Penghasilan Rata-rata</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->father_income ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-slate-400 font-medium">Nomor Telepon / HP</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-mono font-bold mt-0.5">{{ $student->father_phone ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Card 2: Ibu Kandung -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="user" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Data Ibu Kandung</h3>
                        </div>
                        @if($student->mother_phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $student->mother_phone) }}" target="_blank"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                WhatsApp
                            </a>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-xs">
                        <div class="sm:col-span-2">
                            <dt class="text-slate-400 font-medium">Nama Lengkap & Gelar</dt>
                            <dd class="text-slate-800 dark:text-slate-100 font-bold mt-0.5 text-sm">
                                {{ trim(($student->mother_title_prefix ? $student->mother_title_prefix . ' ' : '') . $student->mother_name . ($student->mother_title_suffix ? ', ' . $student->mother_title_suffix : '')) ?: '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">NIK Ibu</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-mono font-semibold mt-0.5">{{ $student->mother_nik ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Tempat, Tanggal Lahir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ $student->mother_birth_place ?? '-' }}, {{ $student->mother_birth_date ? $student->mother_birth_date->translatedFormat('d F Y') : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Pendidikan Terakhir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->mother_education ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Pekerjaan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->mother_job ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Instansi / Perusahaan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->mother_company ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Penghasilan Rata-rata</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->mother_income ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-slate-400 font-medium">Nomor Telepon / HP</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-mono font-bold mt-0.5">{{ $student->mother_phone ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Card 3: Wali & Kontak Darurat -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="shield" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Data Wali Murid (Bila Ada)</h3>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-xs">
                        <div class="sm:col-span-2">
                            <dt class="text-slate-400 font-medium">Nama Lengkap & Gelar Wali</dt>
                            <dd class="text-slate-800 dark:text-slate-100 font-bold mt-0.5 text-sm">
                                {{ trim(($student->guardian_title_prefix ? $student->guardian_title_prefix . ' ' : '') . $student->guardian_name . ($student->guardian_title_suffix ? ', ' . $student->guardian_title_suffix : '')) ?: '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">NIK Wali</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-mono font-semibold mt-0.5">{{ $student->guardian_nik ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Hubungan Keluarga</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->guardian_relation ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Tempat, Tanggal Lahir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                {{ $student->guardian_birth_place ?? '-' }}, {{ $student->guardian_birth_date ? $student->guardian_birth_date->translatedFormat('d F Y') : '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Pendidikan Terakhir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->guardian_education ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Pekerjaan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->guardian_job ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Instansi / Perusahaan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->guardian_company ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Penghasilan Rata-rata</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->guardian_income ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Nomor HP</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-mono font-bold mt-0.5">{{ $student->guardian_phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Tinggal Bersama Murid</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->is_accompanied_by_guardian ? 'Ya' : 'Tidak' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="phone-call" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Kontak Darurat (Emergency)</h3>
                    </div>

                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Nama Kontak Darurat</dt>
                            <dd class="text-slate-800 dark:text-slate-100 font-bold mt-0.5">{{ $student->emergency_contact_name ?? '-' }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-slate-400 font-medium">Nomor Telepon Darurat</dt>
                                <dd class="text-rose-600 dark:text-rose-400 font-mono font-bold mt-0.5">{{ $student->emergency_contact_phone ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Hubungan</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->emergency_contact_relation ?? '-' }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Card 4: Dinamika Kelekatan & Iklim Keagamaan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="heart-handshake" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Dinamika Hubungan Keluarga & Iklim Keagamaan</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Ikatan Paling Dekat</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['relationships']['closest_bond'] ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Hubungan dengan Ayah</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['relationships']['rel_father'] ?? '-' }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Komunikasi: {{ $fd['relationships']['comm_father'] ?? '-' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Hubungan dengan Ibu</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            {{ $fd['relationships']['rel_mother'] ?? '-' }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Komunikasi: {{ $fd['relationships']['comm_mother'] ?? '-' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <span class="text-slate-400 block text-[11px]">Hubungan Saudara & Teman</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">
                            Saudara: {{ $fd['relationships']['rel_siblings'] ?? '-' }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Sebaya: {{ $fd['relationships']['rel_peers'] ?? '-' }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Iklim Agama Keluarga</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $fd['religious_climate']['climate'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Pelaksanaan Ibadah</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $fd['religious_climate']['family_worship'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Masjid Terdekat</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $fd['religious_climate']['nearby_mosque'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">TPQ Sekitar Rumah</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $fd['religious_climate']['nearby_tpq'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: KESEHATAN, FISIK & KEBIASAAN -->
        <div x-show="activeTab === 'kesehatan'" x-cloak class="space-y-5">
            <!-- 1. Antropometri Fisik -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="activity" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Antropometri & Ukuran Fisik Terkini</h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-100 dark:border-indigo-900/40 text-center">
                        <span class="text-slate-400 block text-[11px]">Tinggi Badan</span>
                        <span class="text-base font-black text-indigo-600 dark:text-indigo-400 mt-1 block">
                            {{ $student->height ? $student->height . ' cm' : '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-100 dark:border-emerald-900/40 text-center">
                        <span class="text-slate-400 block text-[11px]">Berat Badan</span>
                        <span class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-1 block">
                            {{ $student->weight ? $student->weight . ' kg' : '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-100 dark:border-amber-900/40 text-center">
                        <span class="text-slate-400 block text-[11px]">Lingkar Kepala (LK)</span>
                        <span class="text-base font-black text-amber-600 dark:text-amber-400 mt-1 block">
                            {{ $student->head_circumference ? $student->head_circumference . ' cm' : '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-sky-50/50 dark:bg-sky-950/20 rounded-xl border border-sky-100 dark:border-sky-900/40 text-center">
                        <span class="text-slate-400 block text-[11px]">Lingkar Lengan (LILA)</span>
                        <span class="text-base font-black text-sky-600 dark:text-sky-400 mt-1 block">
                            {{ $student->arm_circumference ? $student->arm_circumference . ' cm' : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. Riwayat Kelahiran & Perkembangan Awal -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="baby" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Riwayat Kelahiran</h3>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Usia Kandungan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['birth_history']['gestational_age'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Kondisi Saat Hamil</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['birth_history']['prenatal_condition'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Metode Persalinan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['birth_history']['delivery_method'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Alasan Tindakan Medis</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['birth_history']['delivery_reason'] ?? '-' }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-slate-400 font-medium">Berat & Panjang Badan Lahir</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-bold mt-0.5">{{ $hh['birth_history']['birth_weight_length'] ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="milestone" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Milestone Tumbuh Kembang Awal</h3>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">ASI Diberikan Oleh</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['breastfed_by'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Durasi ASI Eksklusif</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['breastfed_duration'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Awal Susu Formula</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['formula_start'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Awal MPASI & Makanan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['mpasi_start'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Tumbuh Gigi Pertama</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['teeth_age'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Mulai Berjalan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['walking_age'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Mulai Berbicara</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['talking_age'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Gangguan Perkembangan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $hh['milestones']['developmental_issues'] ?? 'Tidak ada' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- 3. Riwayat Medis, Imunisasi & Alergi -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Riwayat Medis, Imunisasi, & Catatan Alergi</h3>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <span class="text-slate-400 block font-medium">Imunisasi yang Pernah Dilakukan:</span>
                        <div class="mt-1 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl font-semibold text-slate-800 dark:text-slate-200">
                            {{ $hh['medical_history']['immunizations'] ?? '-' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <span class="text-slate-400 block text-[11px]">Penyakit Berat Pernah Diderita</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">{{ $hh['medical_history']['severe_diseases'] ?? '-' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <span class="text-slate-400 block text-[11px]">Penyakit Sering Dialami</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">{{ $hh['medical_history']['frequent_diseases'] ?? '-' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <span class="text-slate-400 block text-[11px]">Alergi Makanan & Reaksi</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400 mt-1 block">{{ $hh['medical_history']['food_allergy'] ?? 'Tidak ada' }}</span>
                            @if(!empty($hh['medical_history']['allergy_reaction']))
                                <span class="text-[10px] text-slate-400 mt-0.5 block">Reaksi: {{ $hh['medical_history']['allergy_reaction'] }}</span>
                            @endif
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <span class="text-slate-400 block text-[11px]">Alergi Minuman</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 mt-1 block">{{ $hh['medical_history']['drink_allergy'] ?? 'Tidak ada' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <span class="text-slate-400 block text-[11px]">Dokter / RS Rujukan Utama</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $hh['medical_history']['preferred_doctor'] ?? '-' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <span class="text-slate-400 block text-[11px]">Alternatif Pengobatan</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $hh['medical_history']['treatment_alternative'] ?? '-' }}</span>
                        </div>
                    </div>

                    @if(!empty($hh['medical_history']['school_health_notes']))
                        <div class="p-3.5 bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-xl">
                            <span class="text-amber-800 dark:text-amber-400 font-bold block text-[11px]">Pesan Khusus Orang Tua atas Kesehatan Ananda di Sekolah:</span>
                            <p class="text-slate-700 dark:text-slate-300 mt-1 italic leading-relaxed">{{ $hh['medical_history']['school_health_notes'] }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 4. Toilet Training, Pola Makan & Pola Tidur -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Toilet Training -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                        <i data-lucide="bath" class="w-4 h-4 text-indigo-500"></i>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Toilet & Higienitas</h4>
                    </div>
                    <dl class="space-y-2.5 text-xs">
                        <div>
                            <dt class="text-slate-400">Masih Pakai Diapers?</dt>
                            <dd class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $hh['toilet_training']['uses_diapers'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Kebiasaan Buang Air</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ $hh['toilet_training']['toilet_habit'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Kebiasaan Mengompol</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ $hh['toilet_training']['bedwetting'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Sikat Gigi (Frekuensi / Dampingan)</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                {{ $hh['toilet_training']['toothbrush_habit'] ?? '-' }} ({{ $hh['toilet_training']['toothbrush_frequency'] ?? '-' }}, {{ $hh['toilet_training']['toothbrush_assisted'] ?? '-' }})
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Pola Makan -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                        <i data-lucide="utensils" class="w-4 h-4 text-emerald-500"></i>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Kebiasaan Makan & Minum</h4>
                    </div>
                    <dl class="space-y-2.5 text-xs">
                        <div>
                            <dt class="text-slate-400">Selera Makan (Pagi / Siang / Malam)</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                {{ $hh['meals']['breakfast'] ?? '-' }} / {{ $hh['meals']['lunch'] ?? '-' }} / {{ $hh['meals']['dinner'] ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Cara Makan / Doa Makan</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                {{ $hh['meals']['eating_manner'] ?? '-' }} (Doa: {{ $hh['meals']['prays_before_after'] ?? '-' }})
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Makanan Favorit / Dihindari</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                Suka: <span class="text-emerald-600 dark:text-emerald-400">{{ $hh['meals']['favorite'] ?? '-' }}</span> | Hindari: <span class="text-rose-500">{{ $hh['meals']['avoided'] ?? '-' }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Minuman Favorit / Dihindari</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                Suka: {{ $hh['meals']['favorite_drink'] ?? '-' }} | Hindari: {{ $hh['meals']['avoided_drink'] ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Pola Tidur -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                        <i data-lucide="moon" class="w-4 h-4 text-purple-500"></i>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Kebiasaan Tidur</h4>
                    </div>
                    <dl class="space-y-2.5 text-xs">
                        <div>
                            <dt class="text-slate-400">Tidur Bersama</dt>
                            <dd class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $hh['sleeping']['sleeping_with'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Jam Tidur Malam / Bangun Pagi</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                Tidur: {{ $hh['sleeping']['bedtime'] ?? '-' }} | Bangun: {{ $hh['sleeping']['wake_morning_time'] ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Tidur Siang / Bangun Siang</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                {{ $hh['sleeping']['nap_time'] ?? '-' }} (Bangun: {{ $hh['sleeping']['wake_nap_time'] ?? '-' }})
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Kamar Sendiri / Mengigau / Doa</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                Kamar: {{ $hh['sleeping']['has_own_bedroom'] ?? '-' }} | Mengigau: {{ $hh['sleeping']['sleep_talking'] ?? '-' }} | Doa: {{ $hh['sleeping']['sleep_prayers'] ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- TAB 5: PERKEMBANGAN, PSIKOLOGI, MINAT & KOMITMEN -->
        <div x-show="activeTab === 'psikologi'" x-cloak class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- 1. Karakter & Perilaku -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="smile" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Karakter, Sifat & Kepatuhan</h3>
                    </div>

                    <dl class="space-y-3.5 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Karakter / Sifat Utama Ananda</dt>
                            <dd class="text-slate-800 dark:text-slate-100 font-bold mt-0.5 leading-relaxed">{{ $dp['traits']['character_traits'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Tingkat Kepatuhan</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['traits']['obedience'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Catatan Tingkah Laku (Hal Baik & Yang Perlu Perhatian)</dt>
                            <dd class="text-slate-800 dark:text-slate-200 mt-0.5 leading-relaxed p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl italic">
                                {{ $dp['traits']['behavior_notes'] ?? '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- 2. Kebiasaan Bermain -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="gamepad-2" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Kebiasaan Bermain & Mainan</h3>
                    </div>

                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Aktivitas / Kebiasaan Bermain</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['play']['play_habits'] ?? '-' }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-slate-400 font-medium">Tempat Bermain</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['play']['play_location'] ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Teman Bermain</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['play']['playmates'] ?? '-' }}</dd>
                            </div>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Mainan yang Disenangi / Dihindari</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                Disenangi: <span class="text-indigo-600 dark:text-indigo-400">{{ $dp['play']['favorite_toys'] ?? '-' }}</span><br>
                                Dihindari: <span class="text-slate-500">{{ $dp['play']['disliked_toys'] ?? '-' }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Mainan Dinilai Berbahaya oleh Orang Tua</dt>
                            <dd class="text-rose-600 dark:text-rose-400 font-semibold mt-0.5">{{ $dp['play']['dangerous_toys'] ?? 'Tidak ada' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- 3. Screen Time & Minat / Cita-cita -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="tv" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Screen Time, Gadget & YouTube</h3>
                    </div>

                    <dl class="space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-slate-400 font-medium">Fasilitas Game & Sejenisnya</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['screen_time']['game_facility'] ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Akses Nonton YouTube</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['screen_time']['youtube_access'] ?? '-' }}</dd>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-slate-400 font-medium">Frekuensi Nonton TV</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['screen_time']['tv_frequency'] ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Pendamping Saat Nonton</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['screen_time']['tv_companion'] ?? '-' }} ({{ $dp['screen_time']['tv_accompanied'] ?? '-' }})</dd>
                            </div>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Konsentrasi Saat Menonton</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $dp['screen_time']['tv_concentration'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Acara TV Favorit / Tidak Disukai</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">
                                Favorit: <span class="text-indigo-600 dark:text-indigo-400">{{ $dp['screen_time']['favorite_tv'] ?? '-' }}</span> | Dihindari: {{ $dp['screen_time']['disliked_tv'] ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Reaksi Saat Nonton / Buka YouTube</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5 leading-relaxed">{{ $dp['screen_time']['youtube_reaction'] ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                        <i data-lucide="compass" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Cita-cita, Minat & Riwayat Sekolah</h3>
                    </div>

                    <dl class="space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-slate-400 font-medium">Cita-cita Ananda</dt>
                                <dd class="text-indigo-600 dark:text-indigo-400 font-bold mt-0.5">{{ $student->student_aspiration ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Hobi Ananda</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->student_hobby ?? '-' }}</dd>
                            </div>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Harapan / Cita-cita Orang Tua Terhadap Ananda</dt>
                            <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5 leading-relaxed">{{ $student->parent_aspiration ?? '-' }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <dt class="text-slate-400 font-medium">Sekolah Asal Sebelumnya</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->previous_school ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400 font-medium">Rencana Sekolah Lanjutan</dt>
                                <dd class="text-slate-800 dark:text-slate-200 font-semibold mt-0.5">{{ $student->planned_school_after ?? '-' }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- 4. Komitmen & Saran Orang Tua -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="check-square" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Komitmen Pendampingan & Masukan Orang Tua</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
                    <div>
                        <span class="text-slate-400 block font-medium">Kesediaan Asesmen & Tindak Lanjut:</span>
                        <div class="mt-1 font-bold text-slate-800 dark:text-slate-200">
                            {{ $dp['commitments']['assessment_agreement'] ?? '-' }}
                        </div>

                        <span class="text-slate-400 block font-medium mt-3">Bentuk Tindak Lanjut yang Disetujui Orang Tua:</span>
                        <div class="mt-1 font-semibold text-slate-800 dark:text-slate-200 leading-relaxed p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            @php
                                $agreed = $dp['commitments']['agreed_followup_types'] ?? null;
                            @endphp
                            @if(is_array($agreed))
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($agreed as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ $agreed ?: 'Belum ditentukan' }}
                            @endif
                        </div>

                        @if(!empty($dp['commitments']['paud_outcome_expectation']))
                            <span class="text-slate-400 block font-medium mt-3">Harapan Kemampuan Setelah Lulus PAUD:</span>
                            <div class="mt-1 font-medium text-slate-800 dark:text-slate-200 leading-relaxed p-3 bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-xl">
                                {{ $dp['commitments']['paud_outcome_expectation'] }}
                            </div>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 block font-medium">Saran-saran & Harapan Orang Tua kepada Sekolah:</span>
                        <div class="mt-1 font-medium text-slate-700 dark:text-slate-300 leading-relaxed p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl italic">
                            {{ $dp['commitments']['parent_suggestions'] ?? 'Tidak ada saran khusus.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 6: RIWAYAT KELAS -->
        <div x-show="activeTab === 'riwayat'" x-cloak class="space-y-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="history" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Jejak Perjalanan Rombel & Riwayat Akademik</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400">
                                <th class="py-2.5 px-3 font-semibold">Tahun Ajaran</th>
                                <th class="py-2.5 px-3 font-semibold">Kelompok Belajar (Rombel)</th>
                                <th class="py-2.5 px-3 font-semibold">Tingkat Kelas</th>
                                <th class="py-2.5 px-3 font-semibold">Wali Kelas</th>
                                <th class="py-2.5 px-3 font-semibold">Tanggal Masuk</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                                <th class="py-2.5 px-3 font-semibold">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($student->classroomHistories as $hist)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $hist->academicYear?->name ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3 font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ $hist->classroom_name ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3 font-medium text-slate-700 dark:text-slate-300">
                                        {{ $hist->grade_level ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-400">
                                        {{ $hist->homeroom_teacher_name ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 font-mono">
                                        {{ $hist->start_date ? date('d/m/Y', strtotime($hist->start_date)) : '-' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                            {{ $hist->status === 'aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                            {{ $hist->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 italic">
                                        {{ $hist->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-slate-400 italic">
                                        Belum ada rekaman riwayat kelas tersimpan untuk ananda ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 7: BERKAS & DOKUMEN DIGITAL -->
        <div x-show="activeTab === 'dokumen'" x-cloak class="space-y-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                    <div class="flex items-center gap-2.5">
                        <i data-lucide="folder-archive" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Berkas & Tautan Dokumen Digital / Scan</h3>
                            <p class="text-[11px] text-slate-400">Arsip digital scan dokumen murid yang diunggah saat pendaftaran atau pembaruan berkas.</p>
                        </div>
                    </div>
                    <a href="{{ route('students.edit', $student->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        Perbarui Tautan Berkas
                    </a>
                </div>

                @php
                    $docItems = [
                        ['key' => 'nisn_scan', 'label' => 'Scan Kartu / Surat NISN', 'desc' => 'Bukti verifikasi NISN Kemdikbud atau kartu NISN murid.', 'icon' => 'file-text', 'color' => 'indigo'],
                        ['key' => 'previous_school_cert', 'label' => 'Scan Surat Pindah / Ijazah Sekolah Lama', 'desc' => 'Surat keterangan pindah atau ijazah jenjang sebelumnya.', 'icon' => 'school', 'color' => 'amber'],
                        ['key' => 'kia_scan', 'label' => 'Scan Kartu Identitas Anak (KIA)', 'desc' => 'Kartu Identitas Anak resmi dari Disdukcapil.', 'icon' => 'id-card', 'color' => 'blue'],
                        ['key' => 'last_report_card', 'label' => 'Scan Rapot Terakhir', 'desc' => 'Laporan capaian perkembangan anak semester sebelumnya.', 'icon' => 'book-marked', 'color' => 'emerald'],
                        ['key' => 'assessment_file', 'label' => 'Scan Hasil Asesmen / DDTK / Psikolog', 'desc' => 'Lembar asesmen tumbuh kembang atau deteksi dini tumbuh kembang.', 'icon' => 'clipboard-check', 'color' => 'purple'],
                        ['key' => 'kms_book', 'label' => 'Scan Buku KMS / Catatan Kesehatan', 'desc' => 'Grafik pertumbuhan fisik dan catatan imunisasi ananda.', 'icon' => 'activity', 'color' => 'rose'],
                    ];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($docItems as $doc)
                        @php
                            $docVal = $docs[$doc['key']] ?? null;
                            $hasLink = !empty($docVal) && (str_starts_with($docVal, 'http') || str_starts_with($docVal, '/storage/'));
                            $isStorageFile = !empty($docVal) && str_starts_with($docVal, '/storage/');
                            $isPdf = $docVal && str_ends_with(strtolower(parse_url($docVal, PHP_URL_PATH) ?? ''), '.pdf');
                        @endphp
                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                                        <i data-lucide="{{ $doc['icon'] }}" class="w-4 h-4 text-indigo-500"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $doc['label'] }}</h4>
                                        <span class="text-[10px] text-slate-400 block">{{ $doc['desc'] }}</span>
                                    </div>
                                </div>

                                @if($hasLink && !$isPdf && $isStorageFile)
                                    <div class="mt-2 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 max-h-32 flex items-center justify-center">
                                        <img src="{{ $docVal }}" alt="{{ $doc['label'] }}" class="w-full h-28 object-contain">
                                    </div>
                                @endif
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                                @if($hasLink)
                                    <a href="{{ $docVal }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors">
                                        <span>{{ $isStorageFile ? 'Lihat Berkas Dokumen' : 'Buka di Google Drive' }}</span>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                @elseif(!empty($docVal))
                                    <span class="text-xs font-mono text-slate-600 dark:text-slate-400 truncate max-w-[200px]" title="{{ $docVal }}">{{ $docVal }}</span>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Belum diunggah</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
