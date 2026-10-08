<x-admin-layout>
    <div class="p-6 space-y-6" x-data="promotionApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Kenaikan Kelas & Kelulusan</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Promosi kenaikan jenjang (KB-A ke KB-B, TK-A ke TK-B) dan penetapan kelulusan murid.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('students.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all duration-100 cursor-pointer">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Data Murid
                </a>
            </div>
        </section>

        <!-- BANNER INFORMASI TAHAP PENGEMBANGAN -->
        <div class="bg-amber-500/10 dark:bg-amber-500/15 border border-amber-300 dark:border-amber-700/60 rounded-xl p-3.5 sm:p-4 flex items-start sm:items-center gap-3.5 text-amber-900 dark:text-amber-200 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-300/60 dark:border-amber-600/40">
                <i data-lucide="construction" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="text-xs sm:text-sm font-bold tracking-tight text-amber-950 dark:text-amber-200">Fitur Dalam Tahap Pengembangan</h4>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200/70 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 uppercase tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Under Development
                    </span>
                </div>
                <p class="text-[11px] sm:text-xs text-amber-800/90 dark:text-amber-300/80 mt-0.5 leading-relaxed">
                    Halaman Kenaikan Kelas & Kelulusan saat ini masih dalam proses pengembangan dan penyempurnaan alur sistem data. Beberapa fungsi mungkin belum beroperasi secara penuh.
                </p>
            </div>
        </div>

        <!-- MAIN TABS: KENAIKAN KELAS VS KELULUSAN -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 gap-6 text-xs font-bold">
            <button type="button" @click="activeTab = 'promotion'"
                :class="activeTab === 'promotion' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                class="pb-3 border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                Kenaikan Kelas (Promosi Jenjang)
            </button>
            <button type="button" @click="activeTab = 'graduation'"
                :class="activeTab === 'graduation' ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                class="pb-3 border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                Kelulusan (Alumni KB-B & TK-B)
            </button>
        </div>

        <!-- ================= TAB 1: KENAIKAN KELAS ================= -->
        <div x-show="activeTab === 'promotion'" class="space-y-6">
            
            <!-- Filter & Setup Card -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <!-- 1. Kelompok Asal -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 text-xs mb-1.5">
                            1. Pilih Kelompok Asal <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="sourceClassroomId" @change="fetchPromotionStudents()"
                            class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-medium">
                            <option value="">-- Pilih Kelompok Asal --</option>
                            @foreach($sourceClassrooms as $c)
                                <option value="{{ $c->id }}">
                                    [{{ $c->sub_unit }}] {{ $c->name }} ({{ $c->classLevel?->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Kelompok Tujuan -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 text-xs mb-1.5">
                            2. Kelompok Tujuan Kenaikan <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="targetClassroomId"
                            class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-medium">
                            <option value="">-- Pilih Kelompok Tujuan --</option>
                            @foreach($allClassrooms as $c)
                                <option value="{{ $c->id }}">
                                    [{{ $c->sub_unit }}] {{ $c->name }} - T.A. {{ $c->academicYear?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Tahun Ajaran Tujuan -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 text-xs mb-1.5">
                            3. Tahun Ajaran Tujuan <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="targetAcademicYearId"
                            class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-medium">
                            @foreach($academicYears as $y)
                                <option value="{{ $y->id }}">{{ $y->name }} {{ $y->is_active ? '(Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-indigo-500"></i>
                        Aturan Alur: <strong>KB-A ➡️ KB-B</strong> (Playgroup) & <strong>TK-A ➡️ TK-B</strong> (Taman Kanak-Kanak).
                    </span>
                    <span x-text="'Total: ' + promotionStudents.length + ' Murid Terpilih'" class="font-bold text-slate-800 dark:text-slate-200"></span>
                </div>
            </section>

            <!-- Table Siswa Promosi -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 uppercase tracking-wider">
                        <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i>
                        Daftar Murid di Kelompok Asal
                    </h3>

                    <div class="flex items-center gap-2 text-xs">
                        <button type="button" @click="setAllAction('promote')" class="px-2.5 py-1 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 font-semibold hover:bg-indigo-100 transition-colors">
                            Semua Naik Kelas
                        </button>
                        <button type="button" @click="setAllAction('stay')" class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold hover:bg-slate-200 transition-colors">
                            Semua Mengulang
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase w-12">No</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase w-28">NIS</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Nama Ananda</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase w-28">L/P</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase w-44">Status Kenaikan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-if="promotionStudents.length === 0">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                        Silakan pilih <strong>Kelompok Asal</strong> terlebih dahulu untuk menampilkan data murid.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(st, idx) in promotionStudents" :key="st.id">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-5 py-3 text-slate-400 font-mono" x-text="idx + 1"></td>
                                    <td class="px-5 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400" x-text="st.nis"></td>
                                    <td class="px-5 py-3 font-bold text-slate-900 dark:text-slate-100" x-text="st.full_name"></td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-400" x-text="st.gender === 'P' ? '👧 Perempuan' : '👦 Laki-laki'"></td>
                                    <td class="px-5 py-3">
                                        <select x-model="st.action"
                                            class="w-full h-8 px-2.5 text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 font-semibold"
                                            :class="st.action === 'promote' ? 'text-indigo-600 dark:text-indigo-400' : (st.action === 'stay' ? 'text-amber-600' : 'text-rose-600')">
                                            <option value="promote">✅ Naik Kelas</option>
                                            <option value="stay">⚠️ Mengulang di Kelompok Asal</option>
                                            <option value="transfer_out">❌ Mutasi Keluar</option>
                                        </select>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                    <button type="button" @click="submitPromotion()" :disabled="processing || promotionStudents.length === 0"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-2 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span x-text="processing ? 'Memproses...' : 'Eksekusi Kenaikan Kelas Massal'"></span>
                    </button>
                </div>
            </section>
        </div>

        <!-- ================= TAB 2: KELULUSAN (ALUMNI) ================= -->
        <div x-show="activeTab === 'graduation'" class="space-y-6">
            
            <!-- Setup Kelulusan Card -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Pilihan Kelompok Akhir -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 text-xs mb-1.5">
                            Pilih Kelompok Akhir (KB-B / TK-B) <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="gradClassroomId" @change="fetchGraduationStudents()"
                            class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-900 dark:text-slate-50 font-medium">
                            <option value="">-- Pilih Kelompok Akhir --</option>
                            @foreach($graduationClassrooms as $c)
                                <option value="{{ $c->id }}">
                                    [{{ $c->sub_unit }}] {{ $c->name }} ({{ $c->classLevel?->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tanggal Kelulusan -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 text-xs mb-1.5">
                            Tanggal Kelulusan Resmi
                        </label>
                        <input type="date" x-model="graduationDate"
                            class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-900 dark:text-slate-50">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-emerald-500"></i>
                        Murid yang lulus dari <strong>KB-B</strong> menjadi Alumni Playgroup. Murid yang lulus dari <strong>TK-B</strong> menjadi Alumni TK.
                    </span>
                    <span x-text="'Total: ' + graduationStudents.length + ' Calon Lulusan'" class="font-bold text-slate-800 dark:text-slate-200"></span>
                </div>
            </section>

            <!-- Table Siswa Kelulusan -->
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 uppercase tracking-wider">
                        <i data-lucide="graduation-cap" class="w-4 h-4 text-emerald-600"></i>
                        Daftar Calon Lulusan
                    </h3>

                    <div class="flex items-center gap-2 text-xs">
                        <button type="button" @click="toggleAllGraduation(true)" class="px-2.5 py-1 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 font-semibold hover:bg-emerald-100 transition-colors">
                            Pilih Semua
                        </button>
                        <button type="button" @click="toggleAllGraduation(false)" class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold hover:bg-slate-200 transition-colors">
                            Batal Pilih
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                                <th class="px-5 py-3 text-center text-[11px] font-semibold text-slate-500 uppercase w-12">Lulus</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase w-28">NIS</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Nama Ananda</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase w-28">L/P</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase w-44">Status Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-if="graduationStudents.length === 0">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                        Silakan pilih <strong>Kelompok Akhir (KB-B / TK-B)</strong> untuk menampilkan murid yang akan diluluskan.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(st, idx) in graduationStudents" :key="st.id">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-5 py-3 text-center">
                                        <input type="checkbox" x-model="st.is_graduated" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    </td>
                                    <td class="px-5 py-3 font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="st.nis"></td>
                                    <td class="px-5 py-3 font-bold text-slate-900 dark:text-slate-100" x-text="st.full_name"></td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-400" x-text="st.gender === 'P' ? '👧 Perempuan' : '👦 Laki-laki'"></td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                    <button type="button" @click="submitGraduation()" :disabled="processing || graduationStudents.length === 0"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-2 cursor-pointer">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        <span x-text="processing ? 'Memproses...' : 'Resmikan Kelulusan Massal'"></span>
                    </button>
                </div>
            </section>
        </div>

    </div>

    <!-- Alpine.js Application Logic for Promotions -->
    <script>
        function promotionApp() {
            return {
                activeTab: 'promotion',
                processing: false,
                sourceClassroomId: '',
                targetClassroomId: '',
                targetAcademicYearId: '{{ $academicYears->firstWhere("is_active", true)?->id ?? ($academicYears->first()?->id ?? "") }}',
                promotionStudents: [],

                gradClassroomId: '',
                graduationDate: new Date().toISOString().substring(0, 10),
                graduationStudents: [],

                fetchPromotionStudents() {
                    if (!this.sourceClassroomId) {
                        this.promotionStudents = [];
                        return;
                    }

                    fetch(`/class-promotions/students?classroom_id=${this.sourceClassroomId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.promotionStudents = res.students.map(s => ({
                                ...s,
                                action: 'promote'
                            }));
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', res.message || 'Gagal memuat murid.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                        }
                    });
                },

                setAllAction(action) {
                    this.promotionStudents.forEach(s => s.action = action);
                },

                submitPromotion() {
                    if (!this.sourceClassroomId || !this.targetClassroomId || !this.targetAcademicYearId) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Peringatan', 'Mohon lengkapi pilihan kelompok asal, kelompok tujuan, dan tahun ajaran tujuan.', 'warning');
                        }
                        return;
                    }

                    const doProcess = () => {
                        this.processing = true;

                        fetch('/class-promotions/process', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                source_classroom_id: this.sourceClassroomId,
                                target_classroom_id: this.targetClassroomId,
                                target_academic_year_id: this.targetAcademicYearId,
                                effective_date: this.graduationDate,
                                students: this.promotionStudents.map(s => ({
                                    id: s.id,
                                    action: s.action
                                }))
                            })
                        })
                        .then(res => res.json())
                        .then(res => {
                            this.processing = false;
                            if (res.success) {
                                if (typeof window.setPendingToast === 'function') {
                                    window.setPendingToast(res.message || 'Kenaikan kelas berhasil diproses!', 'success');
                                }
                                window.location.reload();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal memproses kenaikan kelas.', 'error');
                                }
                            }
                        })
                        .catch(err => {
                            this.processing = false;
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                            }
                        });
                    };

                    if (typeof showGlobalConfirmModal === 'function') {
                        showGlobalConfirmModal(`Apakah Anda yakin ingin memproses kenaikan kelas untuk ${this.promotionStudents.length} murid?`, doProcess, false);
                    } else if (confirm(`Apakah Anda yakin ingin memproses kenaikan kelas untuk ${this.promotionStudents.length} murid?`)) {
                        doProcess();
                    }
                },

                fetchGraduationStudents() {
                    if (!this.gradClassroomId) {
                        this.graduationStudents = [];
                        return;
                    }

                    fetch(`/class-promotions/students?classroom_id=${this.gradClassroomId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            this.graduationStudents = res.students.map(s => ({
                                ...s,
                                is_graduated: true
                            }));
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', res.message || 'Gagal memuat murid.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                        }
                    });
                },

                toggleAllGraduation(val) {
                    this.graduationStudents.forEach(s => s.is_graduated = val);
                },

                submitGraduation() {
                    const selected = this.graduationStudents.filter(s => s.is_graduated);
                    if (selected.length === 0) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Peringatan', 'Pilih setidaknya 1 murid yang akan diluluskan.', 'warning');
                        }
                        return;
                    }

                    const doGraduate = () => {
                        this.processing = true;

                        fetch('/class-promotions/graduate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                classroom_id: this.gradClassroomId,
                                graduation_date: this.graduationDate,
                                students: this.graduationStudents.map(s => ({
                                    id: s.id,
                                    is_graduated: s.is_graduated
                                }))
                            })
                        })
                        .then(res => res.json())
                        .then(res => {
                            this.processing = false;
                            if (res.success) {
                                if (typeof window.setPendingToast === 'function') {
                                    window.setPendingToast(res.message || 'Kelulusan berhasil diresmikan!', 'success');
                                }
                                window.location.reload();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal meresmikan kelulusan.', 'error');
                                }
                            }
                        })
                        .catch(err => {
                            this.processing = false;
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                            }
                        });
                    };

                    if (typeof showGlobalConfirmModal === 'function') {
                        showGlobalConfirmModal(`Apakah Anda yakin ingin meresmikan kelulusan untuk ${selected.length} murid?`, doGraduate, false);
                    } else if (confirm(`Apakah Anda yakin ingin meresmikan kelulusan untuk ${selected.length} murid?`)) {
                        doGraduate();
                    }
                }
            }
        }
    </script>
</x-admin-layout>
