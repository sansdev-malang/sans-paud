<x-admin-layout>
    <div class="p-6 space-y-6" x-data="spmbCandidateApp()">

        <!-- HEADER / ACTION BAR -->
        <section class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">SPMB</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Data pendaftar dan calon murid yang masuk dari sistem pendaftaran SPMB Pusat.</p>
            </div>

            <!-- ACTION CONTROLS: SYNC BUTTON -->
            <div class="flex items-center gap-2.5 shrink-0">
                <button type="button" @click="syncData()" :disabled="syncing"
                    class="inline-flex items-center justify-center gap-2 px-3.5 sm:px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-xs transition-all duration-150 cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="{ 'animate-spin': syncing }"></i>
                    <span x-text="syncing ? 'Menyinkronkan...' : 'Tarik Data dari SPMB'">Tarik Data dari SPMB</span>
                </button>
            </div>
        </section>

        <!-- COMPACT STATS SUMMARY BAR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-6 text-xs divide-y sm:divide-y-0 sm:divide-x divide-slate-100 dark:divide-slate-800">
                <!-- Stat 1: Total Pendaftar -->
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Total Pendaftar:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-100 text-sm">{{ number_format($stats['total']) }}</span>
                    <span class="text-[10.5px] text-slate-400">({{ $selectedYear === 'all' ? 'Semua TA' : 'TA ' . $selectedYear }})</span>
                </div>

                <!-- Stat 2: Terverifikasi / Diterima -->
                <div class="flex items-center gap-2 sm:pl-6 pt-2 sm:pt-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Terverifikasi:</span>
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-sm">{{ number_format($stats['verified']) }}</span>
                </div>

                <!-- Stat 3: Pembayaran Lunas -->
                <div class="flex items-center gap-2 sm:pl-6 pt-2 sm:pt-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shrink-0"></span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Lunas:</span>
                    <span class="font-bold font-mono text-indigo-600 dark:text-indigo-400 text-sm">{{ number_format($stats['paid']) }}</span>
                </div>

                <!-- Stat 4: Sudah Murid Aktif -->
                <div class="flex items-center gap-2 sm:pl-6 pt-2 sm:pt-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shrink-0"></span>
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Murid Aktif:</span>
                    <span class="font-bold font-mono text-purple-600 dark:text-purple-400 text-sm">{{ number_format($stats['enrolled']) }}</span>
                </div>
            </div>
        </section>

        <!-- SEARCH & FILTER TOOLBAR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 sm:p-4 shadow-xs">
            <form method="GET" action="{{ route('spmb.candidates.index') }}" class="flex flex-col gap-3">
                <div class="flex flex-col xl:flex-row gap-2.5 sm:gap-3 items-stretch xl:items-center justify-between">
                    
                    <!-- Search input -->
                    <div class="relative flex-1 xl:max-w-xs">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari murid, no. reg, NIK, orang tua..."
                            class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>

                    <!-- Dropdowns Filter Sejajar (Urutan: Tahun Ajaran -> Jalur Masuk -> Gelombang -> Jenjang -> Kelas -> Kategori) -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- 1. Tahun Ajaran -->
                        <select name="period" onchange="this.form.submit()" 
                            class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                            <option value="all" {{ $selectedYear === 'all' ? 'selected' : '' }}>Semua Tahun</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>

                        <!-- 2. Jalur Masuk -->
                        <select name="registration_type" onchange="this.form.submit()" 
                            class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                            <option value="all">Semua Jalur</option>
                            @foreach($registrationTypes as $rType)
                                <option value="{{ $rType }}" {{ request('registration_type') === $rType ? 'selected' : '' }}>
                                    {{ $rType }}
                                </option>
                            @endforeach
                        </select>

                        <!-- 3. Gelombang -->
                        <select name="wave" onchange="this.form.submit()" 
                            class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                            <option value="all">Semua Gelombang</option>
                            @foreach($availableWaves as $w)
                                <option value="{{ $w }}" {{ request('wave') === $w ? 'selected' : '' }}>{{ $w }}</option>
                            @endforeach
                        </select>

                        <!-- 4. Jenjang -->
                        <select name="jenjang" onchange="this.form.submit()" 
                            class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                            <option value="all">Semua Jenjang</option>
                            @foreach($availableJenjangs as $jKey => $jLabel)
                                <option value="{{ $jKey }}" {{ request('jenjang') === $jKey ? 'selected' : '' }}>{{ $jLabel }}</option>
                            @endforeach
                        </select>

                        <!-- 5. Kelas -->
                        <select name="admission_level" onchange="this.form.submit()" 
                            class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                            <option value="all">Semua Kelas</option>
                            @foreach($availableAdmissionLevels as $lvl)
                                <option value="{{ $lvl }}" {{ request('admission_level') === $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                            @endforeach
                        </select>

                        <!-- 6. Kategori Murid (Reguler / MBK) -->
                        <select name="category" onchange="this.form.submit()" 
                            class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                            <option value="all">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                    {{ $cat === 'MBK' ? '🌟 MBK' : 'Reguler' }}
                                </option>
                            @endforeach
                        </select>

                        @if(request()->hasAny(['search', 'period', 'registration_type', 'wave', 'jenjang', 'admission_level', 'category', 'status', 'payment_status']))
                            <a href="{{ route('spmb.candidates.index') }}" 
                                class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition-colors inline-flex items-center justify-center"
                                title="Reset Semua Filter">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </section>

        <!-- TABLE CANDIDATES -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden w-full">
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[1050px]">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-950/50 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="px-4 py-3.5 w-40">No. Registrasi</th>
                            <th class="px-4 py-3.5 min-w-[210px]">Calon Murid</th>
                            <th class="px-4 py-3.5 w-44">Jalur & Gelombang</th>
                            <th class="px-4 py-3.5 w-48">Jenjang & Kelas</th>
                            <th class="px-4 py-3.5 w-32 text-center">Kategori</th>
                            <th class="px-4 py-3.5 min-w-[170px]">Orang Tua & WA</th>
                            <th class="px-4 py-3.5 text-center w-36">Status Murid</th>
                            <th class="px-4 py-3.5 text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                        @forelse($candidates as $c)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">
                                <!-- 1. No Registrasi & Periode -->
                                <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                    <div class="font-bold font-mono text-slate-900 dark:text-slate-100">
                                        {{ $c->registration_number }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                            TA {{ $c->academic_year }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        {{ $c->created_at ? $c->created_at->translatedFormat('d M Y, H:i') : '-' }}
                                    </div>
                                </td>

                                <!-- 2. Calon Murid -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="flex items-start gap-2.5">
                                        @if($c->student_photo_url)
                                             <img src="{{ $c->student_photo_url }}" alt="{{ $c->full_name }}" class="w-10 h-10 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700 shrink-0 shadow-2xs">
                                        @else
                                            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                                {{ strtoupper(substr($c->full_name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 dark:text-slate-100 text-xs hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer transition-colors" @click="openDetail({{ $c->id }})">
                                                {{ $c->full_name }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1 flex-wrap">
                                                <span>{{ $c->gender === 'L' ? '👦 Laki-laki' : ($c->gender === 'P' ? '👧 Perempuan' : $c->gender) }}</span>
                                                @if($c->birth_date)
                                                    <span>• {{ \Carbon\Carbon::parse($c->birth_date)->age }} th</span>
                                                @endif
                                            </div>
                                            @if($c->nik)
                                                <div class="text-[10px] font-mono text-slate-400 mt-0.5">NIK: {{ $c->nik }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Jalur Masuk & Gelombang -->
                                <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs flex items-center gap-1">
                                        <i data-lucide="signpost" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                        <span>{{ $c->registration_type ?: 'Murid Baru' }}</span>
                                    </div>
                                    <div class="mt-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50/70 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                            {{ $c->wave ?? 'Gelombang 1' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- 4. Jenjang & Kelas -->
                                <td class="px-4 py-3.5 align-top">
                                    @php
                                        $adm = strtolower($c->admission_level ?? '');
                                        $prog = strtolower($c->class_program ?? '');
                                        $srvs = implode(' ', array_map('strtolower', $c->services_list ?? []));
                                        $comb = $adm . ' ' . $prog . ' ' . $srvs;

                                        $jenjangBadge = 'TK';
                                        $jenjangColor = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
                                        if (str_contains($comb, 'kb') || str_contains($comb, 'bermain')) {
                                            $jenjangBadge = 'KB';
                                            $jenjangColor = 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border-blue-200 dark:border-blue-800';
                                        } elseif (str_contains($comb, 'daycare') || str_contains($comb, 'tpa')) {
                                            $jenjangBadge = 'Daycare';
                                            $jenjangColor = 'bg-pink-50 text-pink-700 dark:bg-pink-950/50 dark:text-pink-300 border-pink-200 dark:border-pink-800';
                                        } elseif (str_contains($comb, 'tpq')) {
                                            $jenjangBadge = 'TPQ';
                                            $jenjangColor = 'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border-purple-200 dark:border-purple-800';
                                        }
                                    @endphp
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $jenjangColor }}">
                                            {{ $jenjangBadge }}
                                        </span>
                                        <span class="font-bold text-slate-800 dark:text-slate-100 text-xs">
                                            {{ $c->admission_level ?: 'TK A' }}
                                        </span>
                                    </div>
                                    @if(!empty($c->services_list))
                                        <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1 flex-wrap">
                                            <span>Layanan: {{ implode(', ', $c->services_list) }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- 5. Kategori Murid -->
                                <td class="px-4 py-3.5 align-top whitespace-nowrap text-center">
                                    @if($c->category === 'MBK')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                            🌟 MBK
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            Reguler
                                        </span>
                                    @endif
                                </td>

                                <!-- 6. Orang Tua & WhatsApp -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                        {{ $c->father_name ?? ($c->mother_name ?? ($c->guardian_name ?? '-')) }}
                                    </div>
                                    @if($c->parent_phone)
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <a href="{{ $c->whatsapp_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-2 py-0.8 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-[10.5px] font-semibold border border-emerald-200 dark:border-emerald-800 transition-colors shadow-2xs" title="Hubungi via WhatsApp">
                                                <i data-lucide="message-circle" class="w-3 h-3 text-emerald-600 dark:text-emerald-400"></i>
                                                <span>{{ $c->parent_phone }}</span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-[10px] text-slate-400 mt-0.5">Tidak ada no. WA</div>
                                    @endif
                                </td>

                                <!-- 7. Status Murid Aktif & Pembayaran -->
                                <td class="px-4 py-3.5 align-top whitespace-nowrap text-center">
                                    @if($c->is_enrolled)
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-200 dark:border-purple-800 shadow-2xs">
                                                <i data-lucide="sparkles" class="w-3 h-3"></i> Murid Aktif
                                            </span>
                                            @if($c->student)
                                                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-bold">NIS: {{ $c->student->nis }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            Belum Terdaftar
                                        </span>
                                    @endif

                                    <div class="mt-1">
                                        @if(in_array(strtolower($c->payment_status), ['paid', 'lunas', 'settlement', 'success']))
                                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">💳 Lunas</span>
                                        @else
                                            <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400">⏳ Belum Lunas</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 8. Aksi -->
                                <td class="px-4 py-3.5 align-top whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Detail -->
                                        <button type="button" @click="openDetail({{ $c->id }})"
                                            class="px-2.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1 shadow-2xs cursor-pointer" title="Lihat Biodata Lengkap">
                                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                                            <span>Detail</span>
                                        </button>

                                        <!-- Tombol Enrollment Murid Aktif -->
                                        <button type="button" @click="openEnrollModal({{ $c->id }})"
                                            class="px-2.5 py-1.5 {{ $c->is_enrolled ? 'bg-purple-100 hover:bg-purple-200 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : 'bg-purple-600 hover:bg-purple-700 text-white' }} rounded-lg text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer shadow-xs"
                                            title="{{ $c->is_enrolled ? 'Kelola / Batalkan Murid Aktif' : 'Daftarkan sebagai Murid Aktif' }}">
                                            <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                                            <span>{{ $c->is_enrolled ? 'Kelola' : 'Daftarkan' }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                            <i data-lucide="inbox" class="w-6 h-6"></i>
                                        </div>
                                        <p class="font-bold text-slate-600 dark:text-slate-300 text-sm">Belum ada data pendaftar SPMB sesuai filter</p>
                                        <p class="text-xs max-w-sm">Coba sesuaikan filter pencarian atau klik tombol <b>"Tarik Data dari SPMB"</b> di atas.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($candidates->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $candidates->links() }}
                </div>
            @endif
        </section>

        <!-- ========================================================================= -->
        <!-- MODAL DETAIL PENDAFTAR LENGKAP -->
        <!-- ========================================================================= -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" 
            style="display: none; margin: 0px !important; margin-top: 0px !important; top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
            
            <div @click.outside="modalOpen = false" 
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col text-left">
                
                <!-- Modal Header -->
                <div class="p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between bg-white dark:bg-slate-900 z-10 shrink-0">
                    <div class="flex items-center gap-3.5 sm:gap-4 overflow-hidden">
                        <template x-if="selectedCandidate?.student_photo_url">
                            <img :src="selectedCandidate.student_photo_url" class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl object-cover ring-2 ring-emerald-500/20 shadow-xs shrink-0">
                        </template>
                        <template x-if="!selectedCandidate?.student_photo_url">
                            <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-bold text-base sm:text-lg flex items-center justify-center shrink-0" 
                                x-text="selectedCandidate?.full_name ? selectedCandidate.full_name.substring(0,2).toUpperCase() : 'PS'">
                            </div>
                        </template>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-50 truncate" x-text="selectedCandidate?.full_name"></h3>
                                
                                <!-- Badge Kategori Murid -->
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                    :class="selectedCandidate?.category === 'MBK' ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-800' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                                    x-text="selectedCandidate?.category === 'MBK' ? '🌟 MBK' : 'Reguler'">
                                </span>

                                <!-- Badge Jalur Masuk -->
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60"
                                    x-text="selectedCandidate?.registration_type || 'Murid Baru'">
                                </span>

                                <!-- Badge Tahun Ajaran -->
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60"
                                    x-text="'TA ' + (selectedCandidate?.academic_year || '-')">
                                </span>
                            </div>
                            
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 font-mono flex items-center gap-1.5 flex-wrap">
                                <span>No. Reg: <strong class="text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.registration_number"></strong></span>
                                <span>•</span>
                                <span><strong x-text="selectedCandidate?.wave || 'Gelombang 1'"></strong></span>
                                <span>•</span>
                                <span>Kelas: <strong x-text="selectedCandidate?.admission_level || selectedCandidate?.class_program || 'TK A'"></strong></span>
                            </p>
                        </div>
                    </div>
                    <button @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer shrink-0">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Detail Navigation Tabs -->
                <div class="flex items-center border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-950/50 text-xs px-5 py-2 gap-2 overflow-x-auto no-scrollbar shrink-0">
                    <button type="button" @click="detailTab = 'bio'"
                        :class="detailTab === 'bio' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        <span>1. Biodata & Pendaftaran</span>
                    </button>
                    <button type="button" @click="detailTab = 'parents'"
                        :class="detailTab === 'parents' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>2. Orang Tua & Kontak</span>
                    </button>
                    <button type="button" @click="detailTab = 'payment'"
                        :class="detailTab === 'payment' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                        <span>3. Pembayaran & SPMB</span>
                    </button>
                    <button type="button" @click="detailTab = 'docs'"
                        :class="detailTab === 'docs' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                        <span>4. Berkas Dokumen (<span x-text="formattedDocuments.length"></span>)</span>
                    </button>
                    <button type="button" @click="detailTab = 'academic'"
                        :class="detailTab === 'academic' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                        class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 shrink-0">
                        <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                        <span>5. Status di SANS PAUD</span>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto max-h-[58vh] space-y-6 text-xs" x-show="selectedCandidate">

                    <!-- ========================================== -->
                    <!-- TAB 1: BIODATA CALON MURID & DATA SPMB -->
                    <!-- ========================================== -->
                    <div x-show="detailTab === 'bio'" class="space-y-4">
                        
                        <!-- CARD RINGKASAN DATA SPMB (6 POIN UTAMA) -->
                        <div class="p-4 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 rounded-xl space-y-3">
                            <h4 class="text-xs font-bold text-emerald-900 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-2 border-b border-emerald-200 dark:border-emerald-800/50 pb-2">
                                <i data-lucide="clipboard-list" class="w-4 h-4 text-emerald-600"></i>
                                Informasi Pendaftaran SPMB
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Tahun Ajaran</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="'TA ' + (selectedCandidate?.academic_year || '-')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Kategori Murid</span>
                                    <span class="font-bold" :class="selectedCandidate?.category === 'MBK' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-200'" x-text="selectedCandidate?.category === 'MBK' ? '🌟 Murid Berkebutuhan Khusus (MBK)' : 'Reguler'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Jalur Masuk</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.registration_type || 'Murid Baru'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Gelombang</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.wave || 'Gelombang 1'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Pilihan Kelas</span>
                                    <span class="font-bold text-purple-700 dark:text-purple-300" x-text="selectedCandidate?.admission_level || selectedCandidate?.class_program || 'TK A'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Layanan Tambahan</span>
                                    <template x-if="selectedCandidate?.services_list && selectedCandidate.services_list.length > 0">
                                        <div class="flex flex-wrap gap-1 mt-0.5">
                                            <template x-for="srv in selectedCandidate.services_list" :key="srv">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200" x-text="srv"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!selectedCandidate?.services_list || selectedCandidate.services_list.length === 0">
                                        <span class="text-slate-400 font-medium">Layanan Standar</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- IDENTITAS LENGKAP -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-4">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-700/60 pb-2.5 text-emerald-700 dark:text-emerald-400">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                Identitas Lengkap Calon Murid
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Nama Lengkap</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs" x-text="selectedCandidate?.full_name"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Nama Panggilan</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.nickname || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Jenis Kelamin</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1 mt-0.5">
                                        <span x-text="selectedCandidate?.gender === 'L' ? '👦 Laki-laki' : (selectedCandidate?.gender === 'P' ? '👧 Perempuan' : selectedCandidate?.gender || '-')"></span>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Tempat, Tanggal Lahir</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(selectedCandidate?.birth_place ? selectedCandidate.birth_place + ', ' : '') + (selectedCandidate?.formatted_birth_date || '-')"></span>
                                    <span class="block text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold" x-text="selectedCandidate?.age_string ? 'Usia: ' + selectedCandidate.age_string : ''"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">NIK (No. Induk Kependudukan)</span>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.nik || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">NISN (Jika Ada)</span>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.nisn || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Agama</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.religion || 'Islam'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Asal Sekolah / PAUD Sebelumnya</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.previous_school || '-'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Nomor Registrasi</span>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.registration_number"></span>
                                </div>
                                <div class="sm:col-span-2 lg:col-span-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <span class="text-slate-400 block text-[11px]">Alamat Domisili Lengkap</span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200 leading-relaxed" x-text="selectedCandidate?.formatted_address || selectedCandidate?.address || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- TAB 2: ORANG TUA & KONTAK -->
                    <!-- ========================================== -->
                    <div x-show="detailTab === 'parents'" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Card Ayah -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                                <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 dark:border-slate-700/60 pb-2">
                                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                    Data Ayah Kandung
                                </h4>
                                <div class="space-y-2.5">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Nama Lengkap Ayah</span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100" x-text="selectedCandidate?.father_name || '-'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">Pekerjaan</span>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.father_job || '-'"></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">Pendidikan</span>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.father_education || '-'"></span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">NIK Ayah</span>
                                            <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.father_nik || '-'"></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">No. Telepon / HP</span>
                                            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.father_phone || '-'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Ibu -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                                <h4 class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 dark:border-slate-700/60 pb-2">
                                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                    Data Ibu Kandung
                                </h4>
                                <div class="space-y-2.5">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Nama Lengkap Ibu</span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100" x-text="selectedCandidate?.mother_name || '-'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">Pekerjaan</span>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.mother_job || '-'"></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">Pendidikan</span>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.mother_education || '-'"></span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">NIK Ibu</span>
                                            <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.mother_nik || '-'"></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">No. Telepon / HP</span>
                                            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.mother_phone || '-'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Kontak Utama & WhatsApp -->
                        <div class="p-4 bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-bold text-emerald-900 dark:text-emerald-300">Kontak Utama Orang Tua</h4>
                                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                                    No. WhatsApp: <strong class="font-mono font-bold" x-text="selectedCandidate?.parent_phone || '-'"></strong>
                                    • Email: <span x-text="selectedCandidate?.parent_email || '-'"></span>
                                </p>
                            </div>
                            <template x-if="modalWaUrl">
                                <a :href="modalWaUrl" target="_blank" 
                                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-xs shrink-0 cursor-pointer">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    Kirim Pesan WhatsApp
                                </a>
                            </template>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- TAB 3: STATUS PEMBAYARAN & SPMB -->
                    <!-- ========================================== -->
                    <div x-show="detailTab === 'payment'" class="space-y-4">
                        <!-- Summary Card -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center shrink-0">
                                    <i data-lucide="wallet" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">Status Pembayaran SPMB</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Status: <span class="font-bold uppercase" :class="selectedCandidate?.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'" x-text="selectedCandidate?.payment_status === 'paid' ? 'Lunas' : 'Belum Lunas'"></span>
                                        • Periode: <span class="font-bold text-slate-700 dark:text-slate-300" x-text="'TA ' + (selectedCandidate?.academic_year || '-')"></span>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold"
                                :class="selectedCandidate?.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300'"
                                x-text="selectedCandidate?.payment_status === 'paid' ? '✔ Pembayaran Selesai' : '⏳ Menunggu Pembayaran'">
                            </span>
                        </div>

                        <!-- Riwayat Transaksi -->
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-2.5 flex items-center gap-2">
                                <i data-lucide="receipt" class="w-4 h-4 text-indigo-600"></i>
                                Rincian Invoice & Tagihan Pembayaran
                            </h4>

                            <template x-if="selectedCandidate?.formatted_payments && selectedCandidate.formatted_payments.length > 0">
                                <div class="space-y-2">
                                    <template x-for="(pay, idx) in selectedCandidate.formatted_payments" :key="idx">
                                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between gap-3 shadow-2xs">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono font-bold text-xs text-slate-900 dark:text-slate-100" x-text="pay.invoice_number"></span>
                                                    <span class="px-2 py-0.2 rounded text-[10px] font-bold uppercase"
                                                        :class="pay.is_paid ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'"
                                                        x-text="pay.status">
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1" x-text="pay.payment_type + ' • Melalui: ' + pay.payment_method"></p>
                                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="pay.paid_at ? 'Dibayar pada: ' + pay.paid_at : 'Belum dibayar'"></p>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm font-mono" x-text="pay.formatted_amount"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!selectedCandidate?.formatted_payments || selectedCandidate.formatted_payments.length === 0">
                                <div class="p-5 rounded-xl bg-slate-50 dark:bg-slate-800/40 text-center text-slate-400 text-xs">
                                    Belum ada rincian riwayat invoice tercatat untuk calon murid ini.
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- TAB 4: BERKAS DOKUMEN -->
                    <!-- ========================================== -->
                    <div x-show="detailTab === 'docs'" class="space-y-4">
                        <template x-if="formattedDocuments.length > 0">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <i data-lucide="paperclip" class="w-4 h-4 text-emerald-600"></i>
                                    Berkas & Lampiran Pendaftaran Calon Murid (<span x-text="formattedDocuments.length"></span>)
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="(doc, idx) in formattedDocuments" :key="idx">
                                        <a :href="doc.url" target="_blank" 
                                            class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 hover:border-emerald-500 hover:bg-emerald-50/20 transition-all group cursor-pointer shadow-2xs">
                                            <div class="flex items-center gap-2.5 overflow-hidden">
                                                <div class="p-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-emerald-600 shrink-0">
                                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                                </div>
                                                <span class="font-semibold text-slate-700 dark:text-slate-300 truncate text-xs" x-text="doc.name"></span>
                                            </div>
                                            <i data-lucide="external-link" class="w-4 h-4 text-slate-400 group-hover:text-emerald-600 shrink-0 ml-2"></i>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="formattedDocuments.length === 0">
                            <div class="p-8 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 text-center text-slate-400 text-xs">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-400 opacity-60"></i>
                                <p class="font-bold text-slate-600 dark:text-slate-300">Tidak ada berkas digital yang dilampirkan</p>
                                <p class="text-[11px] mt-0.5">Calon murid ini tidak mengunggah dokumen pada form pendaftaran SPMB.</p>
                            </div>
                        </template>
                    </div>

                    <!-- ========================================== -->
                    <!-- TAB 5: STATUS DI SANS PAUD -->
                    <!-- ========================================== -->
                    <div x-show="detailTab === 'academic'" class="space-y-4">
                        <template x-if="selectedCandidate?.is_enrolled && selectedCandidate?.student">
                            <div class="p-5 bg-purple-50/70 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/70 rounded-2xl space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                                            <i data-lucide="sparkles" class="w-6 h-6"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-purple-900 dark:text-purple-200 text-sm">Resmi Terdaftar sebagai Murid Aktif PAUD</h4>
                                            <p class="text-[11px] text-purple-700 dark:text-purple-300 mt-0.5">Data murid telah masuk ke database induk SANS PAUD.</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="openEnrollModal(selectedCandidate.id)" 
                                        class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition-colors shrink-0 shadow-xs cursor-pointer">
                                        Kelola Penempatan
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-purple-200/60 dark:border-purple-800/60 text-xs">
                                    <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-purple-100 dark:border-purple-900/50">
                                        <span class="text-slate-400 block text-[11px]">Nomor Induk Murid (NIS)</span>
                                        <span class="font-mono font-bold text-purple-700 dark:text-purple-300 text-sm" x-text="selectedCandidate?.student?.nis"></span>
                                    </div>
                                    <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-purple-100 dark:border-purple-900/50">
                                        <span class="text-slate-400 block text-[11px]">Rombongan Belajar (Kelompok)</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs" x-text="selectedCandidate?.student?.classroom?.name || '-'"></span>
                                        <span class="text-[10px] text-slate-400 block" x-text="selectedCandidate?.student?.classroom?.homeroom_teacher ? 'Wali: ' + selectedCandidate.student.classroom.homeroom_teacher.name : ''"></span>
                                    </div>
                                    <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-purple-100 dark:border-purple-900/50">
                                        <span class="text-slate-400 block text-[11px]">Jenjang & Kelas</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(selectedCandidate?.student?.jenjang?.name || 'PAUD') + ' • ' + (selectedCandidate?.student?.class_level?.name || '-')"></span>
                                    </div>
                                    <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-purple-100 dark:border-purple-900/50">
                                        <span class="text-slate-400 block text-[11px]">Layanan Tambahan</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(selectedCandidate?.student?.daycare_classroom ? '👶 Daycare (' + selectedCandidate.student.daycare_classroom.name + ')' : 'Tidak Daycare') + (selectedCandidate?.student?.is_tpq ? ' • 📖 TPQ' : '')"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="!selectedCandidate?.is_enrolled">
                            <div class="p-6 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl text-center space-y-3">
                                <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-300 flex items-center justify-center mx-auto">
                                    <i data-lucide="user-plus" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm">Belum Dialokasikan ke Rombel</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1">
                                        Calon murid ini siap didaftarkan resmi sebagai Murid Aktif SANS PAUD dan ditempatkan pada kelompok rombel yang sesuai.
                                    </p>
                                </div>
                                <button type="button" @click="openEnrollModal(selectedCandidate.id)" 
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-xs transition-colors shadow-xs cursor-pointer">
                                    <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                                    Daftarkan sebagai Murid Aktif Sekarang
                                </button>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-slate-50/80 dark:bg-slate-950/60 shrink-0">
                    <div class="text-[11px] text-slate-400 font-mono text-center sm:text-left">
                        Sinkron: <span x-text="selectedCandidate?.synced_at || '-'"></span>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="flex-1 sm:flex-initial px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-colors cursor-pointer text-center">
                            Tutup
                        </button>
                        <button type="button" @click="openEnrollModal(selectedCandidate.id)" class="flex-1 sm:flex-initial px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs flex items-center justify-center gap-1.5 cursor-pointer text-center">
                            <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                            <span x-text="selectedCandidate?.is_enrolled ? 'Kelola Murid' : 'Daftarkan Murid'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL ENROLLMENT WIZARD (PENETAPAN / ALOKASI MURID AKTIF) -->
        <!-- ========================================================================= -->
        <div x-show="enrollModalOpen" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4" 
            style="display: none; margin: 0px !important; margin-top: 0px !important; top: 0px !important; left: 0px !important; right: 0px !important; bottom: 0px !important; z-index: 99999 !important; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px);">
            
            <div @click.outside="enrollModalOpen = false" 
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-2xl max-h-[92vh] overflow-hidden shadow-2xl flex flex-col text-left">
                
                <form @submit.prevent="submitEnroll" class="flex flex-col h-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-purple-50/50 dark:bg-purple-950/20 z-10 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                                <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50"
                                    x-text="enrollData.candidate?.is_enrolled ? 'Kelola Penempatan Murid Aktif' : 'Penerimaan & Penetapan Murid Aktif'">
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Penetapan NIS, jenjang, tingkat kelas, rombel, dan layanan tambahan.</p>
                            </div>
                        </div>
                        <button type="button" @click="enrollModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer shrink-0">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-6 space-y-5 text-xs overflow-y-auto max-h-[62vh]" x-show="enrollData.candidate">
                        
                        <!-- Info Card Calon Murid (6 Data SPMB Pilihan) -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 space-y-3">
                            <div class="flex items-center gap-3">
                                <template x-if="enrollData.candidate?.student_photo_url">
                                    <img :src="enrollData.candidate.student_photo_url" class="w-12 h-12 rounded-xl object-cover ring-1 ring-purple-500/30 shrink-0">
                                </template>
                                <template x-if="!enrollData.candidate?.student_photo_url">
                                    <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-bold flex items-center justify-center text-xs shrink-0" 
                                        x-text="enrollData.candidate?.full_name ? enrollData.candidate.full_name.substring(0, 2).toUpperCase() : 'PS'"></div>
                                </template>
                                <div class="overflow-hidden min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 dark:text-slate-50 truncate text-sm" x-text="enrollData.candidate?.full_name"></h4>
                                        <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400" x-text="enrollData.candidate?.age_string ? '(' + enrollData.candidate.age_string + ')' : ''"></span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                                        No. Reg: <strong class="text-slate-700 dark:text-slate-300" x-text="enrollData.candidate?.registration_number"></strong>
                                        • <span x-text="'TA ' + (enrollData.candidate?.academic_year || '-')"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Badges 6 SPMB Fields -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2.5 border-t border-slate-200/70 dark:border-slate-700/60 text-[11px]">
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Kategori</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="enrollData.candidate?.category === 'MBK' ? '🌟 MBK' : 'Reguler'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Jalur</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="enrollData.candidate?.registration_type || 'Murid Baru'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Gelombang & Kelas</span>
                                    <span class="font-bold text-purple-700 dark:text-purple-300" x-text="(enrollData.candidate?.wave || 'Gel. 1') + ' • ' + (enrollData.candidate?.admission_level || enrollData.candidate?.class_program || 'TK A')"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Layanan Tambahan</span>
                                    <span class="font-bold text-emerald-700 dark:text-emerald-400" x-text="enrollData.candidate?.services_list && enrollData.candidate.services_list.length > 0 ? enrollData.candidate.services_list.join(', ') : 'Standar'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 1: PENEMPATAN AKADEMIK & ROMBEL -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-purple-700 dark:text-purple-400">
                                <i data-lucide="shapes" class="w-3.5 h-3.5"></i>
                                Penempatan Akademik & Rombel
                            </h4>

                            <!-- Row 1: Tahun Ajaran & Jenjang -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Tahun Ajaran Masuk <span class="text-rose-500">*</span>
                                    </label>
                                    <select x-model="enrollForm.academic_year_id" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50 font-semibold cursor-pointer">
                                        <template x-for="ay in enrollData.academic_years" :key="ay.id">
                                            <option :value="ay.id" x-text="'TA ' + ay.name + (ay.is_active ? ' (Aktif)' : '')"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Jenjang Pendidikan <span class="text-rose-500">*</span>
                                    </label>
                                    <select x-model="enrollForm.jenjang_id" @change="onJenjangChange()" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50 font-semibold cursor-pointer">
                                        <template x-for="j in enrollData.jenjangs" :key="j.id">
                                            <option :value="j.id" x-text="j.name + ' (' + j.code + ')'"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <!-- Row 2: Kelas & Rombel -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Tingkat Kelas
                                    </label>
                                    <select x-model="enrollForm.class_level_id" @change="onClassLevelChange()"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                        <option value="">-- Semua Tingkat Kelas --</option>
                                        <template x-for="lvl in filteredClassLevels" :key="lvl.id">
                                            <option :value="lvl.id" x-text="lvl.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Rombongan Belajar (Rombel) <span class="text-rose-500">*</span>
                                    </label>
                                    <select x-model="enrollForm.classroom_id" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50 font-bold cursor-pointer">
                                        <option value="">-- Pilih Rombongan Belajar --</option>
                                        <template x-for="r in filteredClassrooms" :key="r.id">
                                            <option :value="r.id" 
                                                x-text="r.name + (r.homeroom_teacher ? ' (Wali: ' + r.homeroom_teacher.name + ')' : '') + ' • ' + (r.active_students_count || 0) + '/' + (r.capacity || 20) + ' murid'">
                                            </option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: IDENTITAS MURID & NIS -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-purple-700 dark:text-purple-400">
                                <i data-lucide="id-card" class="w-3.5 h-3.5"></i>
                                Nomor Induk & Tanggal Terdaftar
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Nomor Induk Murid (NIS) <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="enrollForm.nis" required placeholder="Contoh: 26.PAUD.001"
                                        class="w-full h-9 px-3 text-xs font-mono font-bold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-purple-700 dark:text-purple-300">
                                    <p class="text-[10px] text-slate-400 mt-1">Saran NIS otomatis dibuat berdasarkan urutan pendaftaran tahun masuk.</p>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Tanggal Masuk / Terdaftar <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" x-model="enrollForm.enrolled_date" required
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: LAYANAN TAMBAHAN TERPADU (DAYCARE & TPQ) -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3.5">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-purple-700 dark:text-purple-400">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                Layanan Tambahan Terpadu (Opsional)
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Layanan Daycare (TPA)</label>
                                    <select x-model="enrollForm.daycare_classroom_id"
                                        class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                        <option value="">- Tidak Mengambil Daycare -</option>
                                        <template x-for="dc in enrollData.daycare_classrooms" :key="dc.id">
                                            <option :value="dc.id" x-text="'👶 ' + dc.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Layanan TPQ</label>
                                    <label class="flex items-center gap-2 h-9 px-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer">
                                        <input type="checkbox" x-model="enrollForm.is_tpq" class="rounded text-purple-600 focus:ring-purple-500">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">📖 Mengikuti Program TPQ</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 4: CATATAN -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Pendaftaran</label>
                            <textarea x-model="enrollForm.notes" rows="2" placeholder="Catatan tambahan penempatan murid..."
                                class="w-full p-2.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50"></textarea>
                        </div>

                        <!-- Alert jika sudah enrolled -->
                        <template x-if="enrollData.candidate?.is_enrolled">
                            <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl flex items-start gap-2.5">
                                <i data-lucide="info" class="w-4 h-4 text-amber-600 mt-0.5 shrink-0"></i>
                                <div class="text-[11px] text-amber-800 dark:text-amber-200 leading-relaxed">
                                    Calon murid ini telah berstatus <b>Murid Aktif</b>. Menyimpan formulir ini akan memperbarui penempatan rombel dan informasi murid tersebut.
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/60 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2.5 shrink-0">
                        <div>
                            <template x-if="enrollData.candidate?.is_enrolled">
                                <button type="button" @click="unenrollStudent(enrollData.candidate.id)" 
                                    class="w-full sm:w-auto px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xl text-xs font-bold transition-colors cursor-pointer text-center">
                                    Batalkan Status Murid
                                </button>
                            </template>
                        </div>
                        <div class="flex items-center gap-2 justify-end">
                            <button type="button" @click="enrollModalOpen = false" class="flex-1 sm:flex-initial px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-colors cursor-pointer text-center">
                                Batal
                            </button>
                            <button type="submit" :disabled="enrolling" class="flex-1 sm:flex-initial px-4 sm:px-5 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer text-center">
                                <i data-lucide="check" class="w-3.5 h-3.5" :class="{ 'animate-spin': enrolling }"></i>
                                <span x-text="enrolling ? 'Menyimpan...' : (enrollData.candidate?.is_enrolled ? 'Perbarui Rombel' : 'Resmi Daftarkan Murid')"></span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- ALPINE JS APP LOGIC -->
    <script>
        function spmbCandidateApp() {
            return {
                modalOpen: false,
                enrollModalOpen: false,
                detailTab: 'bio',
                syncing: false,
                enrolling: false,
                selectedCandidate: null,
                modalWaUrl: null,
                enrollData: {
                    candidate: null,
                    student: null,
                    suggested_nis: '',
                    academic_years: [],
                    jenjangs: [],
                    class_levels: [],
                    classrooms: [],
                    daycare_classrooms: [],
                    tpq_classrooms: [],
                    selected_year_id: null,
                },
                enrollForm: {
                    nis: '',
                    academic_year_id: '',
                    jenjang_id: '',
                    class_level_id: '',
                    classroom_id: '',
                    daycare_classroom_id: '',
                    is_tpq: false,
                    tpq_classroom_id: '',
                    enrolled_date: '',
                    notes: '',
                },

                get formattedDocuments() {
                    if (!this.selectedCandidate) return [];
                    if (this.selectedCandidate.formatted_documents && Array.isArray(this.selectedCandidate.formatted_documents) && this.selectedCandidate.formatted_documents.length > 0) {
                        return this.selectedCandidate.formatted_documents;
                    }
                    const docs = this.selectedCandidate.documents;
                    if (!docs) return [];
                    if (Array.isArray(docs)) {
                        return docs.map(d => ({
                            name: d.name || d.label || d.key || 'Berkas Dokumen',
                            url: d.url || '#'
                        }));
                    }
                    if (typeof docs === 'object') {
                        const labelMap = {
                            'student_photo': 'Pas Foto Calon Murid (Foto Formal)',
                            'student_photo_path': 'Pas Foto Calon Murid (Foto Formal)',
                            'birth_certificate': 'Akta Kelahiran',
                            'birth_certificate_path': 'Akta Kelahiran',
                            'family_card': 'Kartu Keluarga (KK)',
                            'family_card_path': 'Kartu Keluarga (KK)',
                            'diploma_certificate': 'Ijazah / Surat Keterangan Aktif Sekolah',
                            'diploma_certificate_path': 'Ijazah / Surat Keterangan Aktif Sekolah',
                            'student_card': 'NISN / KIA / Kartu Pelajar (Opsional)',
                            'student_card_path': 'NISN / KIA / Kartu Pelajar (Opsional)',
                            'special_needs_assessment_path': 'Asesmen Kebutuhan Khusus (Jika Ada)',
                            'payment_receipt_path': 'Bukti Pembayaran Pendaftaran',
                        };
                        return Object.entries(docs).filter(([k, v]) => v && typeof v === 'string' && v.trim() !== '').map(([k, v]) => ({
                            name: labelMap[k] || (k.includes('_') ? k.replace(/_/g, ' ') : k),
                            url: v
                        }));
                    }
                    return [];
                },

                get filteredClassLevels() {
                    if (!this.enrollData.class_levels || !this.enrollForm.jenjang_id) {
                        return this.enrollData.class_levels || [];
                    }
                    return this.enrollData.class_levels.filter(l => l.jenjang_id == this.enrollForm.jenjang_id);
                },

                get filteredClassrooms() {
                    if (!this.enrollData.classrooms) return [];
                    return this.enrollData.classrooms.filter(r => {
                        if (this.enrollForm.jenjang_id && r.jenjang_id != this.enrollForm.jenjang_id) {
                            return false;
                        }
                        if (this.enrollForm.class_level_id && r.class_level_id != this.enrollForm.class_level_id) {
                            return false;
                        }
                        return true;
                    });
                },

                onJenjangChange() {
                    const levels = this.filteredClassLevels;
                    if (levels.length > 0) {
                        if (!levels.some(l => l.id == this.enrollForm.class_level_id)) {
                            this.enrollForm.class_level_id = levels[0].id;
                        }
                    } else {
                        this.enrollForm.class_level_id = '';
                    }

                    this.onClassLevelChange();
                },

                onClassLevelChange() {
                    const rooms = this.filteredClassrooms;
                    if (rooms.length > 0) {
                        if (!rooms.some(r => r.id == this.enrollForm.classroom_id)) {
                            this.enrollForm.classroom_id = rooms[0].id;
                        }
                    } else {
                        this.enrollForm.classroom_id = '';
                    }
                },

                openDetail(id) {
                    this.detailTab = 'bio';
                    fetch(`/spmb/candidates/${id}`, {
                        headers: {
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.selectedCandidate = res.candidate;
                            this.modalWaUrl = res.wa_url;
                            this.modalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal memuat detail pendaftar: " + err.message, 'error');
                        }
                    });
                },

                openEnrollModal(id) {
                    this.modalOpen = false;
                    fetch(`/spmb/candidates/${id}/enroll-data`, {
                        headers: {
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.enrollData = res;

                            // Tentukan Jenjang Awal
                            let initialJenjangId = '';
                            let initialClassLevelId = '';
                            let initialClassroomId = '';
                            let initialDaycareId = '';
                            let initialIsTpq = false;

                            if (res.student) {
                                initialJenjangId = res.student.jenjang_id || (res.student.classroom?.jenjang_id || '');
                                initialClassLevelId = res.student.class_level_id || (res.student.classroom?.class_level_id || '');
                                initialClassroomId = res.student.classroom_id || '';
                                initialDaycareId = res.student.daycare_classroom_id || '';
                                initialIsTpq = !!res.student.is_tpq;
                            } else {
                                // Tebak Jenjang dari pilihan kelas & program SPMB
                                const admLvl = (res.candidate.admission_level || '').toLowerCase();
                                const prog = (res.candidate.class_program || '').toLowerCase();
                                const combinedClass = admLvl + ' ' + prog;
                                const candUnit = (res.candidate.unit_code || '').toLowerCase();

                                if (combinedClass.includes('kb') || combinedClass.includes('bermain') || candUnit.includes('kb')) {
                                    const kbJenjang = res.jenjangs.find(j => j.code === 'KB' || j.code === 'PG');
                                    initialJenjangId = kbJenjang ? kbJenjang.id : (res.jenjangs[0]?.id || '');
                                } else if (combinedClass.includes('tk') || candUnit.includes('tk')) {
                                    const tkJenjang = res.jenjangs.find(j => j.code === 'TK');
                                    initialJenjangId = tkJenjang ? tkJenjang.id : (res.jenjangs[0]?.id || '');
                                } else {
                                    initialJenjangId = res.jenjangs[0]?.id || '';
                                }

                                // Cari class level yang cocok
                                const matchingLevels = res.class_levels.filter(l => l.jenjang_id == initialJenjangId);
                                if (matchingLevels.length > 0) {
                                    if (combinedClass.includes('tk-a') || combinedClass.includes('tk a') || combinedClass.includes(' a')) {
                                        const lvlA = matchingLevels.find(l => l.name.toLowerCase().includes('a'));
                                        initialClassLevelId = lvlA ? lvlA.id : matchingLevels[0].id;
                                    } else if (combinedClass.includes('tk-b') || combinedClass.includes('tk b') || combinedClass.includes(' b')) {
                                        const lvlB = matchingLevels.find(l => l.name.toLowerCase().includes('b'));
                                        initialClassLevelId = lvlB ? lvlB.id : matchingLevels[0].id;
                                    } else {
                                        initialClassLevelId = matchingLevels[0].id;
                                    }
                                }

                                // Cari rombel yang cocok
                                const matchingRooms = res.classrooms.filter(r => r.jenjang_id == initialJenjangId && (!initialClassLevelId || r.class_level_id == initialClassLevelId));
                                initialClassroomId = matchingRooms[0]?.id || (res.classrooms[0]?.id || '');

                                // Deteksi Layanan Tambahan (Daycare / TPQ)
                                const services = Array.isArray(res.candidate.extra_services) 
                                    ? res.candidate.extra_services.join(' ').toLowerCase() 
                                    : (typeof res.candidate.extra_services === 'string' ? res.candidate.extra_services.toLowerCase() : '');
                                
                                if (services.includes('daycare') || services.includes('tpa') || combinedClass.includes('daycare') || combinedClass.includes('tpa')) {
                                    if (res.daycare_classrooms && res.daycare_classrooms.length > 0) {
                                        initialDaycareId = res.daycare_classrooms[0].id;
                                    }
                                }
                                if (services.includes('tpq') || services.includes('ngaji') || combinedClass.includes('tpq')) {
                                    initialIsTpq = true;
                                }
                            }

                            this.enrollForm = {
                                nis: res.student ? res.student.nis : res.suggested_nis,
                                academic_year_id: res.student ? res.student.academic_year_id : (res.selected_year_id || (res.academic_years[0]?.id || '')),
                                jenjang_id: initialJenjangId,
                                class_level_id: initialClassLevelId,
                                classroom_id: initialClassroomId,
                                daycare_classroom_id: initialDaycareId,
                                is_tpq: initialIsTpq,
                                tpq_classroom_id: res.student?.tpq_classroom_id || '',
                                enrolled_date: res.student?.enrolled_date ? res.student.enrolled_date.substring(0, 10) : new Date().toISOString().substring(0, 10),
                                notes: res.student?.notes || `Terdaftar via integrasi SPMB (${res.candidate.registration_number})`,
                            };

                            this.enrollModalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal mengambil data persiapan murid aktif: " + err.message, 'error');
                        }
                    });
                },

                submitEnroll() {
                    if (this.enrolling || !this.enrollData.candidate) return;
                    this.enrolling = true;

                    fetch(`/spmb/candidates/${this.enrollData.candidate.id}/enroll`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(this.enrollForm)
                    })
                    .then(res => res.json())
                    .then(res => {
                        this.enrolling = false;
                        if (res.success) {
                            this.enrollModalOpen = false;
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(res.message || "Berhasil mendaftarkan murid aktif!", 'success');
                            }
                            window.location.reload();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', (res.message || "Terjadi kesalahan"), 'error');
                            }
                        }
                    })
                    .catch(err => {
                        this.enrolling = false;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Error: " + err.message, 'error');
                        }
                    });
                },

                unenrollStudent(id) {
                    const doUnenroll = () => {
                        fetch(`/spmb/candidates/${id}/unenroll`, {
                            method: "POST",
                            headers: {
                                "Accept": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            }
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                this.enrollModalOpen = false;
                                if (typeof window.setPendingToast === 'function') {
                                    window.setPendingToast(res.message || "Status murid aktif berhasil dibatalkan.", 'success');
                                }
                                window.location.reload();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', (res.message || "Terjadi kesalahan"), 'error');
                                }
                            }
                        })
                        .catch(err => {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', "Error: " + err.message, 'error');
                            }
                        });
                    };

                    if (typeof showGlobalConfirmModal === 'function') {
                        showGlobalConfirmModal("Apakah Anda yakin ingin membatalkan status murid aktif untuk calon murid ini? Data kemuridannya akan dihapus.", doUnenroll, true);
                    } else if (confirm("Apakah Anda yakin ingin membatalkan status murid aktif untuk calon murid ini? Data kemuridannya akan dihapus.")) {
                        doUnenroll();
                    }
                },

                syncData() {
                    if (this.syncing) return;
                    this.syncing = true;

                    fetch("{{ route('spmb.candidates.sync') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            period: "{{ $selectedYear }}"
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        this.syncing = false;
                        if (res.success) {
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(res.message || "Sinkronisasi berhasil!", 'success');
                            }
                            window.location.reload();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', "Gagal sinkronisasi: " + (res.message || "Unknown error"), 'error');
                            }
                        }
                    })
                    .catch(err => {
                        this.syncing = false;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Terjadi kesalahan koneksi: " + err.message, 'error');
                        }
                    });
                }
            }
        }
    </script>
</x-admin-layout>
