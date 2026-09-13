<x-admin-layout>
    <div class="p-6 space-y-6" x-data="spmbCandidateApp()">

        <!-- HEADER / ACTION BAR -->
        <section class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-500/20">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            SPMB
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 font-semibold border border-emerald-200 dark:border-emerald-800">
                                Unit PAUD
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Data pendaftar dan calon murid yang masuk dari sistem pendaftaran SPMB Pusat.</p>
                    </div>
                </div>
            </div>

            <!-- ACTION CONTROLS: TAHUN AJARAN & SYNC BUTTON -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <!-- Dropdown Tahun Ajaran -->
                <form id="filter-period-form" method="GET" action="{{ route('spmb.candidates.index') }}" class="flex items-center">
                    <div class="relative">
                        <select name="period" onchange="this.form.submit()" 
                            class="appearance-none pl-8 pr-8 py-2 text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                            <option value="all" {{ $selectedYear === 'all' ? 'selected' : '' }}>Semua Tahun Ajaran</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>
                                    Tahun Ajaran {{ $year }}
                                </option>
                            @endforeach
                            @if(empty($academicYears))
                                <option value="{{ date('Y') . '/' . (date('Y') + 1) }}" selected>
                                    Tahun Ajaran {{ date('Y') . '/' . (date('Y') + 1) }}
                                </option>
                            @endif
                        </select>
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    </div>
                </form>

                <!-- Tombol Tarik Data dari SPMB -->
                <button type="button" @click="syncData()" :disabled="syncing"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold rounded-lg shadow-sm transition-all duration-150 cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="{ 'animate-spin': syncing }"></i>
                    <span x-text="syncing ? 'Menyinkronkan...' : 'Tarik Data dari SPMB'">Tarik Data dari SPMB</span>
                </button>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Total Pendaftar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pendaftar SPMB</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">{{ number_format($stats['total']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Periode: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $selectedYear === 'all' ? 'Semua Periode' : $selectedYear }}</span>
                </div>
            </div>

            <!-- Stat 2: Terverifikasi / Diterima -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Terverifikasi / Diterima</p>
                        <h3 class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['verified']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="badge-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Status berkas & pendaftaran valid
                </div>
            </div>

            <!-- Stat 3: Pembayaran Lunas -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pembayaran Lunas</p>
                        <h3 class="text-2xl font-bold tracking-tight text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($stats['paid']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="wallet" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Formulir / Biaya Pendidikan Lunas
                </div>
            </div>

            <!-- Stat 4: Sudah Siswa Aktif -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sudah Siswa Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400 mt-1">{{ number_format($stats['enrolled']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-xl border border-purple-100 dark:border-purple-900/50">
                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Telah dikonfirmasi masuk PAUD
                </div>
            </div>
        </section>

        <!-- SEARCH & FILTER TOOLBAR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
            <form method="GET" action="{{ route('spmb.candidates.index') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <input type="hidden" name="period" value="{{ $selectedYear }}">

                <!-- Search input -->
                <div class="relative flex-1 md:max-w-md">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa, no. registrasi, NIK, nama orang tua..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>

                <!-- Dropdowns -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Status Filter -->
                    <select name="status" onchange="this.form.submit()" 
                        class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                        <option value="all">Semua Status</option>
                        <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Terverifikasi / Diterima</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending / Menunggu</option>
                    </select>

                    <!-- Payment Filter -->
                    <select name="payment_status" onchange="this.form.submit()" 
                        class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                        <option value="all">Semua Pembayaran</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Lunas</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                    </select>

                    <!-- Wave Filter -->
                    <select name="wave" onchange="this.form.submit()" 
                        class="px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer">
                        <option value="all">Semua Gelombang</option>
                        @foreach($availableWaves as $w)
                            <option value="{{ $w }}" {{ request('wave') === $w ? 'selected' : '' }}>{{ $w }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-slate-200 text-white dark:text-slate-900 rounded-lg text-xs font-bold transition-colors cursor-pointer">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </form>
        </section>

        <!-- TABLE CANDIDATES -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-950/50 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="px-4 py-3.5">No. Registrasi</th>
                            <th class="px-4 py-3.5">Calon Siswa</th>
                            <th class="px-4 py-3.5">Orang Tua & WhatsApp</th>
                            <th class="px-4 py-3.5">Gelombang & Program</th>
                            <th class="px-4 py-3.5">Pembayaran</th>
                            <th class="px-4 py-3.5">Status Siswa</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @forelse($candidates as $c)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <!-- No Registrasi -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-bold font-mono text-slate-900 dark:text-slate-100">
                                        {{ $c->registration_number }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $c->created_at ? $c->created_at->translatedFormat('d M Y, H:i') . ' WIB' : '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-semibold mt-0.5">
                                        TA {{ $c->academic_year }}
                                    </div>
                                </td>

                                <!-- Calon Siswa -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if($c->student_photo_url)
                                            <img src="{{ $c->student_photo_url }}" alt="{{ $c->full_name }}" class="w-9 h-9 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700 shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($c->full_name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-slate-100 text-xs hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer" @click="openDetail({{ $c->id }})">
                                                {{ $c->full_name }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                                <span>{{ $c->gender === 'L' ? '👦 Laki-laki' : ($c->gender === 'P' ? '👧 Perempuan' : $c->gender) }}</span>
                                                @if($c->birth_date)
                                                    <span>• {{ \Carbon\Carbon::parse($c->birth_date)->age }} th ({{ \Carbon\Carbon::parse($c->birth_date)->format('d/m/Y') }})</span>
                                                @endif
                                            </div>
                                            @if($c->nik)
                                                <div class="text-[10px] font-mono text-slate-400 mt-0.5">NIK: {{ $c->nik }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Orang Tua & WhatsApp -->
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-slate-800 dark:text-slate-200 text-xs">
                                        {{ $c->father_name ?? ($c->mother_name ?? ($c->guardian_name ?? '-')) }}
                                    </div>
                                    @if($c->parent_phone)
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <a href="{{ $c->whatsapp_url }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold border border-emerald-200 dark:border-emerald-800 transition-colors" title="Hubungi via WhatsApp">
                                                <i data-lucide="message-circle" class="w-3 h-3 text-emerald-600"></i>
                                                <span>{{ $c->parent_phone }}</span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-[11px] text-slate-400 mt-0.5">Tidak ada no. WA</div>
                                    @endif
                                </td>

                                <!-- Gelombang & Program -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                        {{ $c->wave ?? 'Gelombang 1' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        {{ $c->class_program ?? 'Reguler' }}
                                    </div>
                                    @if($c->previous_school)
                                        <div class="text-[10px] text-slate-400 mt-0.5 truncate max-w-[140px]" title="{{ $c->previous_school }}">
                                            Asal: {{ $c->previous_school }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Status Pembayaran -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if(in_array(strtolower($c->payment_status), ['paid', 'lunas', 'settlement', 'success']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            <i data-lucide="check" class="w-3 h-3"></i> Lunas
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            <i data-lucide="clock" class="w-3 h-3"></i> Belum Lunas
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Siswa Aktif -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($c->is_enrolled)
                                        <div class="flex flex-col gap-0.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                                <i data-lucide="sparkles" class="w-3 h-3"></i> Siswa Aktif
                                            </span>
                                            @if($c->student)
                                                <span class="text-[10px] text-slate-400 font-mono">NIS: {{ $c->student->nis }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            Belum Terdaftar
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Detail -->
                                        <button type="button" @click="openDetail({{ $c->id }})"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1 cursor-pointer" title="Lihat Biodata Lengkap">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Detail</span>
                                        </button>

                                        <!-- Tombol Enrollment Siswa Aktif -->
                                        <button type="button" @click="openEnrollModal({{ $c->id }})"
                                            class="px-2.5 py-1.5 {{ $c->is_enrolled ? 'bg-purple-100 hover:bg-purple-200 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800' : 'bg-purple-600 hover:bg-purple-700 text-white' }} rounded-lg text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer shadow-xs"
                                            title="{{ $c->is_enrolled ? 'Kelola / Batalkan Siswa Aktif' : 'Tandai sebagai Siswa Aktif' }}">
                                            <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                                            <span x-text="{{ $c->is_enrolled ? 'true' : 'false' }} ? 'Terdaftar' : ''"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                            <i data-lucide="inbox" class="w-6 h-6"></i>
                                        </div>
                                        <p class="font-bold text-slate-600 dark:text-slate-300 text-sm">Belum ada data pendaftar SPMB</p>
                                        <p class="text-xs max-w-sm">Klik tombol <b>"Tarik Data dari SPMB"</b> di atas untuk menyinkronkan data pendaftar dari server SPMB.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($candidates->hasPages())
                <div class="p-4 border-t border-slate-100 dark:divide-slate-800">
                    {{ $candidates->links() }}
                </div>
            @endif
        </section>

        <!-- MODAL DETAIL PENDAFTAR -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col">
                
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                    <div class="flex items-center gap-4">
                        <template x-if="selectedCandidate?.student_photo_url">
                            <img :src="selectedCandidate.student_photo_url" class="w-14 h-14 rounded-2xl object-cover ring-2 ring-emerald-500/20 shadow-xs">
                        </template>
                        <template x-if="!selectedCandidate?.student_photo_url">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-bold text-lg flex items-center justify-center" x-text="selectedCandidate?.full_name ? selectedCandidate.full_name.substring(0,2).toUpperCase() : 'PS'">
                            </div>
                        </template>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-50" x-text="selectedCandidate?.full_name"></h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                    :class="selectedCandidate?.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300'"
                                    x-text="selectedCandidate?.payment_status === 'paid' ? 'Lunas' : 'Belum Lunas'">
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5 font-mono">
                                No. Registrasi: <span class="font-bold text-slate-700 dark:text-slate-300" x-text="selectedCandidate?.registration_number"></span>
                                | Gelombang: <span x-text="selectedCandidate?.wave || 'Gelombang 1'"></span>
                            </p>
                        </div>
                    </div>
                    <button @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 text-xs" x-show="selectedCandidate">
                    
                    <!-- Section 1: Biodata Siswa -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <i data-lucide="user" class="w-4 h-4 text-emerald-600"></i>
                            Biodata Calon Murid
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Lengkap</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.full_name"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Panggilan</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.nickname || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Jenis Kelamin</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.gender === 'L' ? 'Laki-laki' : (selectedCandidate?.gender === 'P' ? 'Perempuan' : selectedCandidate?.gender)"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Tempat, Tgl Lahir</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="(selectedCandidate?.birth_place ? selectedCandidate.birth_place + ', ' : '') + (selectedCandidate?.formatted_birth_date || '-')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">NIK</span>
                                <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.nik || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">NISN</span>
                                <span class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.nisn || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Agama</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.religion || 'Islam'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Asal Sekolah</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.previous_school || '-'"></span>
                            </div>
                            <div class="col-span-2 sm:col-span-3">
                                <span class="text-slate-400 block text-[11px]">Alamat Domisili</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.address || '-'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Orang Tua / Kontak -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <i data-lucide="users" class="w-4 h-4 text-emerald-600"></i>
                            Data Orang Tua / Wali
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ayah</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.father_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Pekerjaan Ayah</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.father_job || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Nama Ibu</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.mother_name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Pekerjaan Ibu</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.mother_job || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">WhatsApp Utama</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.parent_phone || '-'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Email</span>
                                <span class="text-slate-800 dark:text-slate-200" x-text="selectedCandidate?.parent_email || '-'"></span>
                            </div>
                        </div>

                        <!-- Tombol Hubungi WA -->
                        <template x-if="modalWaUrl">
                            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-start">
                                <a :href="modalWaUrl" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors shadow-xs">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    Hubungi Orang Tua via WhatsApp
                                </a>
                            </div>
                        </template>
                    </div>

                    <!-- Section 3: Berkas Dokumen -->
                    <template x-if="formattedDocuments.length > 0">
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                                <i data-lucide="paperclip" class="w-4 h-4 text-emerald-600"></i>
                                Berkas & Lampiran Pendaftaran (<span x-text="formattedDocuments.length"></span>)
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <template x-for="(doc, idx) in formattedDocuments" :key="idx">
                                    <a :href="doc.url" target="_blank" 
                                        class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 hover:border-emerald-500 hover:bg-emerald-50/20 transition-all group">
                                        <div class="flex items-center gap-2.5 overflow-hidden">
                                            <div class="p-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-emerald-600 shrink-0">
                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                            </div>
                                            <span class="font-semibold text-slate-700 dark:text-slate-300 truncate" x-text="doc.name"></span>
                                        </div>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 shrink-0 ml-2"></i>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/50">
                    <div class="text-[11px] text-slate-400 font-mono">
                        Sinkron: <span x-text="selectedCandidate?.synced_at || '-'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="modalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg font-bold transition-colors cursor-pointer">
                            Tutup
                        </button>
                        <button type="button" @click="modalOpen = false; openEnrollModal(selectedCandidate.id)" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold transition-colors shadow-xs flex items-center gap-1.5">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                            Kelola Siswa Aktif
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL ENROLLMENT WIZARD (TANDAI / ALOKASI SISWA AKTIF) -->
        <div x-show="enrollModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="enrollModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col">
                
                <form @submit.prevent="submitEnroll">
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-purple-50/50 dark:bg-purple-950/20">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold shadow-xs">
                                <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-50">
                                    Penerimaan Siswa Baru PAUD
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Penetapan NIS dan penempatan rombongan belajar.</p>
                            </div>
                        </div>
                        <button type="button" @click="enrollModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 text-xs" x-show="enrollData.candidate">
                        
                        <!-- Info Card Calon Siswa -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex items-center gap-3">
                            <template x-if="enrollData.candidate?.student_photo_url">
                                <img :src="enrollData.candidate.student_photo_url" class="w-10 h-10 rounded-full object-cover ring-1 ring-purple-500/30">
                            </template>
                            <template x-if="!enrollData.candidate?.student_photo_url">
                                <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-700 font-bold flex items-center justify-center text-xs" x-text="enrollData.candidate?.full_name ? enrollData.candidate.full_name.substring(0, 2).toUpperCase() : 'PS'"></div>
                            </template>
                            <div class="overflow-hidden">
                                <h4 class="font-bold text-slate-900 dark:text-slate-50 truncate" x-text="enrollData.candidate?.full_name"></h4>
                                <p class="text-[11px] text-slate-400 font-mono" x-text="enrollData.candidate?.registration_number + ' • ' + (enrollData.candidate?.wave || 'Gelombang 1')"></p>
                            </div>
                        </div>

                        <!-- NIS Input (Auto-suggested) -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nomor Induk Siswa (NIS) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="enrollForm.nis" required placeholder="Contoh: 27.PAUD.001"
                                class="w-full h-9 px-3 text-xs font-mono font-bold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-purple-700 dark:text-purple-300">
                            <p class="text-[10px] text-slate-400 mt-1">Saran format otomatis berdasarkan tahun masuk dan nomor urut.</p>
                        </div>

                        <!-- Tahun Ajaran & Rombel Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Tahun Ajaran <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="enrollForm.academic_year_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                    <template x-for="ay in enrollData.academic_years" :key="ay.id">
                                        <option :value="ay.id" x-text="'TA ' + ay.name + (ay.is_active ? ' (Aktif)' : '')"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Pilih Rombongan Belajar <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="enrollForm.classroom_id" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50 cursor-pointer">
                                    <option value="">Pilih Rombel...</option>
                                    <template x-for="r in enrollData.classrooms" :key="r.id">
                                        <option :value="r.id" x-text="r.name + ' (' + (r.class_level ? r.class_level.name : '') + ') • ' + r.active_students_count + '/' + r.capacity + ' siswa'"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Tanggal Terdaftar -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Masuk / Terdaftar</label>
                            <input type="date" x-model="enrollForm.enrolled_date"
                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-slate-50">
                        </div>

                        <!-- Status Terdaftar Info jika sudah enrolled -->
                        <template x-if="enrollData.candidate?.is_enrolled">
                            <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl flex items-start gap-2.5">
                                <i data-lucide="info" class="w-4 h-4 text-amber-600 mt-0.5 shrink-0"></i>
                                <div class="text-[11px] text-amber-800 dark:text-amber-200 leading-relaxed">
                                    Calon murid ini telah berstatus <b>Siswa Aktif</b>. Anda dapat mengubah rombel atau membatalkan status siswa aktif melalui tombol di bawah.
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between gap-2">
                        <div>
                            <template x-if="enrollData.candidate?.is_enrolled">
                                <button type="button" @click="unenrollStudent(enrollData.candidate.id)" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-semibold transition-colors">
                                    Batalkan Status Siswa Aktif
                                </button>
                            </template>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="enrollModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="enrolling" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span x-text="enrolling ? 'Menyimpan...' : (enrollData.candidate?.is_enrolled ? 'Perbarui Rombel' : 'Resmi Jadikan Siswa Aktif')"></span>
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
                syncing: false,
                enrolling: false,
                selectedCandidate: null,
                modalWaUrl: null,
                enrollData: {
                    candidate: null,
                    student: null,
                    suggested_nis: '',
                    academic_years: [],
                    classrooms: [],
                    selected_year_id: null,
                },
                enrollForm: {
                    nis: '',
                    classroom_id: '',
                    academic_year_id: '',
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

                openDetail(id) {
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
                    .catch(err => alert("Gagal memuat detail pendaftar: " + err.message));
                },

                openEnrollModal(id) {
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
                            this.enrollForm = {
                                nis: res.student ? res.student.nis : res.suggested_nis,
                                classroom_id: res.student ? res.student.classroom_id : (res.classrooms[0]?.id || ''),
                                academic_year_id: res.selected_year_id || (res.academic_years[0]?.id || ''),
                                enrolled_date: res.student?.enrolled_date ? res.student.enrolled_date.substring(0, 10) : new Date().toISOString().substring(0, 10),
                                notes: res.student?.notes || '',
                            };
                            this.enrollModalOpen = true;
                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });
                        }
                    })
                    .catch(err => alert("Gagal mengambil data persiapan siswa aktif: " + err.message));
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
                            alert(res.message || "Berhasil mendaftarkan siswa aktif!");
                            window.location.reload();
                        } else {
                            alert("Gagal: " + (res.message || "Terjadi kesalahan"));
                        }
                    })
                    .catch(err => {
                        this.enrolling = false;
                        alert("Error: " + err.message);
                    });
                },

                unenrollStudent(id) {
                    if (!confirm("Apakah Anda yakin ingin membatalkan status siswa aktif untuk calon murid ini? Data kesiswaannya akan dihapus.")) return;

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
                            alert(res.message || "Status siswa aktif berhasil dibatalkan.");
                            window.location.reload();
                        } else {
                            alert("Gagal: " + (res.message || "Terjadi kesalahan"));
                        }
                    })
                    .catch(err => alert("Error: " + err.message));
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
                            alert(res.message || "Sinkronisasi berhasil!");
                            window.location.reload();
                        } else {
                            alert("Gagal sinkronisasi: " + (res.message || "Unknown error"));
                        }
                    })
                    .catch(err => {
                        this.syncing = false;
                        alert("Terjadi kesalahan koneksi: " + err.message);
                    });
                }
            }
        }
    </script>
</x-admin-layout>
