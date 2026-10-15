<x-admin-layout>
    <div class="p-3.5 sm:p-5 lg:p-6 space-y-4 sm:space-y-5" x-data="guestBookApp()">

        <!-- ========================================================================= -->
        <!-- HEADER SECTION                                                            -->
        <!-- ========================================================================= -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center p-1.5 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 border border-teal-200 dark:border-teal-800">
                        <i data-lucide="book-user" class="w-5 h-5"></i>
                    </span>
                    <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Buku Tamu Lembaga</h2>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                    Buku registrasi kunjungan tamu kedinasan, Puskesmas/Dinkes, calon wali murid SPMB, dan tamu umum PAUD.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <!-- Kiosk Public Link -->
                <a href="{{ route('buku-tamu.public') }}" target="_blank"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] sm:text-xs font-semibold rounded-lg transition-colors border border-slate-200 dark:border-slate-700">
                    <i data-lucide="tablet" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
                    <span>Mode Kiosk Mandiri</span>
                    <i data-lucide="external-link" class="w-3 h-3 text-slate-400"></i>
                </a>

                <!-- Ekspor Dropdown (Excel & Unduh PDF) -->
                <div x-data="{ exportOpen: false }" class="relative">
                    <button type="button" @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        id="btn_export_guest_book"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-[11px] sm:text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Ekspor</span>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': exportOpen }"></i>
                    </button>
                    
                    <div x-show="exportOpen" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-1.5 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1 overflow-hidden">
                        <a href="{{ route('guest-books.export.excel', ['month' => $selectedMonth, 'year' => $selectedYear, 'category' => $selectedCategory]) }}"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                            <span>Excel (.xlsx)</span>
                        </a>
                        <a href="{{ route('guest-books.download.pdf', ['month' => $selectedMonth, 'year' => $selectedYear, 'category' => $selectedCategory]) }}"
                            class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-700 dark:hover:text-rose-400 transition-colors border-t border-slate-100 dark:border-slate-800">
                            <i data-lucide="file-text" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0"></i>
                            <span>PDF (.pdf)</span>
                        </a>
                    </div>
                </div>

                <!-- Print -->
                <a href="{{ route('guest-books.print', ['month' => $selectedMonth, 'year' => $selectedYear, 'category' => $selectedCategory]) }}" target="_blank"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] sm:text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 transition-colors">
                    <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Cetak</span>
                </a>

                <!-- Tambah Tamu -->
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-teal-600 hover:bg-teal-700 text-white text-[11px] sm:text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Catat Tamu Baru</span>
                </button>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- FLASH NOTIFICATION                                                        -->
        <!-- ========================================================================= -->
        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 p-3 sm:p-3.5 rounded-xl flex items-center justify-between gap-3 text-xs shadow-xs transition-all">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                    <span class="font-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 p-3 sm:p-3.5 rounded-xl flex items-start gap-2.5 text-xs shadow-xs">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5"></i>
                <div>
                    <span class="font-bold">Terjadi kesalahan validasi:</span>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- STATS CARDS                                                               -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Total -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Tamu</span>
                    <span class="p-1.5 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400">
                        <i data-lucide="users" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-50">
                    {{ $stats['total'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Periode {{ $monthNames[$selectedMonth] }} {{ $selectedYear }}
                </div>
            </div>

            <!-- SPMB -->
            <div class="bg-white dark:bg-slate-900 border border-amber-200/80 dark:border-amber-900/60 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Layanan SPMB</span>
                    <span class="p-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                    {{ $stats['spmb'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Calon Wali Murid
                </div>
            </div>

            <!-- Dinas -->
            <div class="bg-white dark:bg-slate-900 border border-indigo-200/80 dark:border-indigo-900/60 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Kedinasan</span>
                    <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="landmark" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400">
                    {{ $stats['dinas'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Disdik / Kemenag / Pemda
                </div>
            </div>

            <!-- Puskesmas/Dinkes -->
            <div class="bg-white dark:bg-slate-900 border border-emerald-200/80 dark:border-emerald-900/60 rounded-xl p-3 sm:p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Puskesmas / Dinkes</span>
                    <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
                    {{ $stats['puskesmas'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Kesehatan & Skrining
                </div>
            </div>

            <!-- Umum -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Tamu Umum</span>
                    <span class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-2 text-xl sm:text-2xl font-black text-slate-700 dark:text-slate-300">
                    {{ $stats['umum'] }}
                </div>
                <div class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">
                    Mitra & Kunjungan Lain
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- FILTER BAR: BULAN, TAHUN, KATEGORI LAYANAN, PENCARIAN                      -->
        <!-- ========================================================================= -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-4 shadow-xs">
            <form method="GET" action="{{ route('guest-books.index') }}" id="guestFilterForm" class="flex flex-col lg:flex-row lg:items-end justify-between gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 flex-1">
                    <!-- Filter Bulan -->
                    <div>
                        <label for="month" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Bulan <span class="text-rose-500">*</span>
                        </label>
                        <select name="month" id="month" onchange="document.getElementById('guestFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors cursor-pointer">
                            @foreach($monthNames as $mNum => $mLabel)
                                <option value="{{ $mNum }}" {{ $selectedMonth == $mNum ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Tahun -->
                    <div>
                        <label for="year" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Tahun <span class="text-rose-500">*</span>
                        </label>
                        <select name="year" id="year" onchange="document.getElementById('guestFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors cursor-pointer">
                            @foreach($yearsList as $yr)
                                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>
                                    {{ $yr }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Kategori Layanan -->
                    <div>
                        <label for="category" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Kategori Layanan
                        </label>
                        <select name="category" id="category" onchange="document.getElementById('guestFilterForm').submit()"
                            class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors cursor-pointer">
                            <option value="all" {{ $selectedCategory === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Pencarian -->
                    <div>
                        <label for="search" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Cari Tamu / Instansi
                        </label>
                        <div class="relative">
                            <input type="text" name="search" id="search" value="{{ $search }}"
                                placeholder="Nama, No. HP, Instansi, Kota..."
                                class="w-full pl-8 pr-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <!-- Submit / Reset Buttons -->
                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit"
                        class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors cursor-pointer">
                        Filter
                    </button>
                    @if($search || $selectedCategory !== 'all')
                        <a href="{{ route('guest-books.index', ['month' => $selectedMonth, 'year' => $selectedYear]) }}"
                            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- ========================================================================= -->
        <!-- TABLE OF GUESTS                                                           -->
        <!-- ========================================================================= -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 uppercase tracking-wider font-bold text-[10px]">
                            <th class="py-3 px-3.5 text-center w-10">No</th>
                            <th class="py-3 px-3.5 w-32">Waktu & Tanggal</th>
                            <th class="py-3 px-3.5 min-w-[200px]">Nama Tamu & Kontak</th>
                            <th class="py-3 px-3.5 w-28">Kategori</th>
                            <th class="py-3 px-3.5 min-w-[180px]">Institusi / Pekerjaan</th>
                            <th class="py-3 px-3.5 min-w-[140px]">Kota / Domisili</th>
                            <th class="py-3 px-3.5 min-w-[200px]">Keperluan</th>
                            <th class="py-3 px-3.5 min-w-[130px]">Penerima Tamu</th>
                            <th class="py-3 px-3.5 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($guests as $index => $g)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- No -->
                                <td class="py-3 px-3.5 text-center text-slate-400 font-bold">
                                    {{ $index + 1 }}
                                </td>

                                <!-- Tanggal & Waktu -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ \Carbon\Carbon::parse($g->date)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-[11px] text-teal-600 dark:text-teal-400 font-mono flex items-center gap-1 mt-0.5">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <span>{{ $g->formatted_time }}</span>
                                    </div>
                                </td>

                                <!-- Nama & Kontak -->
                                <td class="py-3 px-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 font-bold flex items-center justify-center text-xs shrink-0 mt-0.5">
                                            {{ strtoupper(substr($g->name, 0, 1)) }}
                                        </div>
                                        <div class="space-y-0.5">
                                            <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                                {{ $g->name }}
                                            </div>
                                            <div class="flex items-center gap-1.5 text-[11px]">
                                                <span class="text-slate-500 font-mono">{{ $g->phone }}</span>
                                                @if($g->whatsapp_url)
                                                    <a href="{{ $g->whatsapp_url }}" target="_blank" title="Hubungi via WhatsApp"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-[10px] font-semibold border border-emerald-200 dark:border-emerald-800 transition-colors">
                                                        <i data-lucide="message-circle" class="w-2.5 h-2.5 text-emerald-600 dark:text-emerald-400"></i>
                                                        <span>WA</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kategori -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $g->category_badge_class }}">
                                        {{ $g->category }}
                                    </span>
                                </td>

                                <!-- Institusi & Jabatan -->
                                <td class="py-3 px-3.5">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $g->institution ?: '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $g->position ?: ($g->occupation ?: 'Tamu') }}
                                    </div>
                                </td>

                                <!-- Kota / Domisili -->
                                <td class="py-3 px-3.5">
                                    <div class="text-slate-800 dark:text-slate-200">
                                        {{ $g->city ?: '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-[130px]">
                                        {{ $g->province ?: '' }}
                                    </div>
                                </td>

                                <!-- Keperluan -->
                                <td class="py-3 px-3.5">
                                    <p class="text-slate-700 dark:text-slate-300 line-clamp-2 max-w-[220px]" title="{{ $g->purpose }}">
                                        {{ $g->purpose }}
                                    </p>
                                    @if($g->feedback)
                                        <div class="text-[10px] text-teal-600 dark:text-teal-400 italic line-clamp-1 mt-0.5" title="Kesan: {{ $g->feedback }}">
                                            "{{ $g->feedback }}"
                                        </div>
                                    @endif
                                </td>

                                <!-- Penerima Tamu -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $g->host_recipient ?: '-' }}</span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Detail Modal -->
                                        <button type="button" @click="openViewModal({{ json_encode($g) }})" title="Lihat Detail Tamu"
                                            class="p-1.5 text-slate-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Edit Modal -->
                                        <button type="button" @click="openEditModal({{ json_encode($g) }})" title="Edit Data Tamu"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('guest-books.destroy', $g->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan tamu ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Catatan"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="book-x" class="w-8 h-8 text-slate-300 dark:text-slate-600"></i>
                                        <p class="font-medium text-xs">Belum ada kunjungan tamu tercatat pada filter ini.</p>
                                        <button type="button" @click="openCreateModal()" class="mt-2 text-teal-600 dark:text-teal-400 text-xs font-semibold hover:underline">
                                            + Catat Tamu Baru
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Menampilkan <strong>{{ $guests->count() }}</strong> catatan tamu</span>
                <span>Periode: <strong>{{ $monthNames[$selectedMonth] }} {{ $selectedYear }}</strong></span>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL FORM: TAMBAH / EDIT TAMU                                            -->
        <!-- ========================================================================= -->
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            
            <div @click.outside="modalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-4 sm:p-6 text-left space-y-4">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400">
                            <i data-lucide="book-open-check" class="w-4 h-4"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100"
                            x-text="isEdit ? 'Edit Catatan Buku Tamu' : 'Catat Tamu Baru'">
                        </h3>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form :action="formAction" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Row 1: Tanggal, Waktu & Kategori Layanan -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Tanggal -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Tanggal Kunjungan <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="date" x-model="formData.date" required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Waktu -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Waktu / Jam Datang
                            </label>
                            <input type="time" name="time" x-model="formData.time"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Kategori Layanan -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Kategori Layanan <span class="text-rose-500">*</span>
                            </label>
                            <select name="category" x-model="formData.category" required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Nama & No HP -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Nama Lengkap Tamu <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" x-model="formData.name" required
                                placeholder="Contoh: Dra. Hj. Siti Aminah, M.Pd"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- No HP / WhatsApp -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                No. Handphone / WhatsApp <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="phone" x-model="formData.phone" required
                                placeholder="Contoh: 081234567890"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <!-- Row 3: Institusi, Jabatan & Pekerjaan -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Institusi -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Institusi / Lembaga / Asal
                            </label>
                            <input type="text" name="institution" x-model="formData.institution"
                                placeholder="Contoh: Puskesmas Dinoyo / Disdik"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Jabatan -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Jabatan
                            </label>
                            <input type="text" name="position" x-model="formData.position"
                                placeholder="Contoh: Pengawas / Dokter / Staf"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Pekerjaan -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Pekerjaan
                            </label>
                            <input type="text" name="occupation" x-model="formData.occupation"
                                placeholder="Contoh: ASN / Wiraswasta / Dokter"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <!-- Row 4: Kota, Provinsi & Alamat -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Kota -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Kota / Kabupaten
                            </label>
                            <input type="text" name="city" x-model="formData.city"
                                placeholder="Contoh: Kota Malang"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Provinsi -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Provinsi
                            </label>
                            <input type="text" name="province" x-model="formData.province"
                                placeholder="Contoh: Jawa Timur"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Alamat Lengkap -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Alamat Domisili
                            </label>
                            <input type="text" name="address" x-model="formData.address"
                                placeholder="Contoh: Jl. Jakarta No. 12"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <!-- Row 5: Penerima Tamu -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Penerima Tamu / Petugas Piket Yang Menemui
                        </label>
                        <input type="text" name="host_recipient" x-model="formData.host_recipient"
                            placeholder="Contoh: Ustadzah Fatimah / Kepala Sekolah / Staf TU"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <!-- Row 6: Keperluan (Textarea) -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Keperluan / Agenda Kunjungan <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="purpose" x-model="formData.purpose" rows="2" required
                            placeholder="Tuliskan tujuan dan maksud kunjungan tamu secara spesifik..."
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 leading-relaxed"></textarea>
                    </div>

                    <!-- Row 7: Kesan & Pesan (Textarea) -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            Kesan & Pesan / Catatan Tindak Lanjut
                        </label>
                        <textarea name="feedback" x-model="formData.feedback" rows="2"
                            placeholder="Kesan tamu terhadap pelayanan PAUD atau catatan tindak lanjut..."
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 leading-relaxed"></textarea>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer">
                            <span x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Catatan'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL DETAIL: LIHAT RINCIAN 14 FIELD BUKU TAMU                           -->
        <!-- ========================================================================= -->
        <div x-show="viewModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            
            <div @click.outside="viewModalOpen = false"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-xl w-full p-4 sm:p-6 text-left space-y-4">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100">
                            Rincian Data Kunjungan Tamu
                        </h3>
                    </div>
                    <button type="button" @click="viewModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Modal Body (14 Fields Neatly Grouped) -->
                <div class="space-y-4 text-xs" x-show="viewData">
                    <!-- Guest Header Card -->
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nama Tamu</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-0.5" x-text="viewData?.name"></div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-slate-600 dark:text-slate-400 font-mono" x-text="viewData?.phone"></span>
                                <template x-if="viewData?.whatsapp_url">
                                    <a :href="viewData?.whatsapp_url" target="_blank"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 font-bold text-[10px]">
                                        <i data-lucide="message-circle" class="w-3 h-3"></i> Chat WA
                                    </a>
                                </template>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-md font-bold text-[11px] border"
                            :class="viewData?.category_badge_class"
                            x-text="viewData?.category"></span>
                    </div>

                    <!-- 2 Column Details -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Tanggal & Waktu</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="viewData?.date ? new Date(viewData.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : '-'"></span>
                            <span class="text-slate-500 font-mono block text-[11px]" x-text="viewData?.formatted_time || '-'"></span>
                        </div>
                        <div class="p-3 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Penerima Tamu</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="viewData?.host_recipient || '-'"></span>
                            <span class="text-[11px] text-slate-400 block">Petugas / Pendidik</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Institusi / Lembaga</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="viewData?.institution || '-'"></span>
                        </div>
                        <div class="p-3 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Jabatan / Pekerjaan</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(viewData?.position || '-') + ' / ' + (viewData?.occupation || '-')"></span>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-slate-50/50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Domisili & Alamat</span>
                        <div class="font-semibold text-slate-800 dark:text-slate-200" x-text="(viewData?.city || '-') + ', ' + (viewData?.province || '-')"></div>
                        <div class="text-slate-500 text-[11px] mt-0.5" x-text="viewData?.address || '-'"></div>
                    </div>

                    <!-- Keperluan -->
                    <div class="p-3 rounded-lg bg-teal-50/50 dark:bg-teal-950/20 border border-teal-100 dark:border-teal-900/40">
                        <span class="text-[10px] font-bold uppercase text-teal-700 dark:text-teal-400 block">Keperluan / Agenda Kunjungan</span>
                        <p class="text-slate-800 dark:text-slate-200 whitespace-pre-line mt-1 leading-relaxed font-medium" x-text="viewData?.purpose || '-'"></p>
                    </div>

                    <!-- Kesan & Pesan -->
                    <template x-if="viewData?.feedback">
                        <div class="p-3 rounded-lg bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/40">
                            <span class="text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400 block">Kesan & Pesan / Tindak Lanjut</span>
                            <p class="text-slate-800 dark:text-slate-200 whitespace-pre-line mt-1 leading-relaxed italic" x-text="viewData?.feedback"></p>
                        </div>
                    </template>
                </div>

                <!-- Footer Action -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openEditModal(viewData); viewModalOpen = false"
                        class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
                        Edit Data Ini
                    </button>
                    <button type="button" @click="viewModalOpen = false"
                        class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- ALPINE.JS SCRIPT -->
    <script>
        function guestBookApp() {
            return {
                modalOpen: false,
                viewModalOpen: false,
                isEdit: false,
                formAction: "{{ route('guest-books.store') }}",
                viewData: null,
                formData: {
                    id: null,
                    date: "{{ \Carbon\Carbon::now()->format('Y-m-d') }}",
                    time: "{{ \Carbon\Carbon::now()->format('H:i') }}",
                    category: "Umum",
                    name: "",
                    phone: "",
                    institution: "",
                    position: "",
                    occupation: "",
                    city: "Kota Malang",
                    province: "Jawa Timur",
                    address: "",
                    purpose: "",
                    host_recipient: "",
                    feedback: ""
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formAction = "{{ route('guest-books.store') }}";
                    this.formData = {
                        id: null,
                        date: "{{ \Carbon\Carbon::now()->format('Y-m-d') }}",
                        time: "{{ \Carbon\Carbon::now()->format('H:i') }}",
                        category: "{{ $selectedCategory !== 'all' ? $selectedCategory : 'Umum' }}",
                        name: "",
                        phone: "",
                        institution: "",
                        position: "",
                        occupation: "",
                        city: "Kota Malang",
                        province: "Jawa Timur",
                        address: "",
                        purpose: "",
                        host_recipient: "",
                        feedback: ""
                    };
                    this.modalOpen = true;
                },

                openEditModal(guest) {
                    this.isEdit = true;
                    this.formAction = "/guest-books/" + guest.id;
                    this.formData = {
                        id: guest.id,
                        date: guest.date ? guest.date.split('T')[0] : "",
                        time: guest.time ? guest.time.substring(0, 5) : "",
                        category: guest.category || "Umum",
                        name: guest.name || "",
                        phone: guest.phone || "",
                        institution: guest.institution || "",
                        position: guest.position || "",
                        occupation: guest.occupation || "",
                        city: guest.city || "",
                        province: guest.province || "",
                        address: guest.address || "",
                        purpose: guest.purpose || "",
                        host_recipient: guest.host_recipient || "",
                        feedback: guest.feedback || ""
                    };
                    this.modalOpen = true;
                },

                openViewModal(guest) {
                    this.viewData = guest;
                    this.viewModalOpen = true;
                }
            };
        }
    </script>
</x-admin-layout>
