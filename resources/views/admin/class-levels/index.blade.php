<x-admin-layout>
    <div class="p-6 space-y-6" x-data="classLevelApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 sm:gap-3 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-slate-50">Kelas</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Kelola master tingkat/kelas (KB-A, KB-B, TK-A, TK-B, TPA 1, TPQ) berbasis jenjang pendidikan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="{{ route('classrooms.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all duration-100 cursor-pointer">
                    <i data-lucide="shapes" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Rombel / Kelompok
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Kelas
                </button>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-3.5">
            <!-- Stat 1: Total Kelas -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Kelas</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 font-mono">
                            {{ number_format($stats['total_levels']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Seluruh Jenjang</span>
                    </div>
                </div>
            </div>

            <!-- Stat 2: Total Kelompok / Rombel -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/40">
                    <i data-lucide="shapes" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Total Rombel</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-blue-600 dark:text-blue-400 font-mono">
                            {{ number_format($stats['total_classrooms']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Kelompok Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Stat 3: Total Kapasitas -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/40">
                    <i data-lucide="door-open" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Daya Tampung</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ number_format($stats['total_capacity']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Kapasitas Kuota</span>
                    </div>
                </div>
            </div>

            <!-- Stat 4: Total Murid Aktif -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 shadow-xs flex items-center gap-3 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-100 dark:border-rose-900/40">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10.5px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">Murid Aktif</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <h3 class="text-xl font-bold tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                            {{ number_format($stats['total_students']) }}
                        </h3>
                        <span class="text-[10px] text-slate-400 font-medium truncate">Terdaftar</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- FILTER JENJANG TABS -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            @foreach($jenjangs as $j)
                <button type="button" @click="setJenjangTab({{ $j->id }})"
                    :class="activeJenjangId == {{ $j->id }} ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                    @if($j->code === 'KB' || $j->code === 'PG')
                        <span>🧸</span>
                    @elseif($j->code === 'TK')
                        <span>🎒</span>
                    @elseif($j->code === 'DAYCARE' || $j->code === 'TPA')
                        <span>👶</span>
                    @elseif($j->code === 'TPQ')
                        <span>📖</span>
                    @endif
                    <span>{{ $j->name }}</span>
                </button>
            @endforeach
        </div>

        <!-- TABLE DAFTAR KELAS -->
        <!-- Kolom: jenjang, kelas, status, aksi -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition-all w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="layers" class="w-4 h-4 text-indigo-600"></i>
                    Daftar Kelas
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-56">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kelas</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($classLevels as $lvl)
                            <tr x-show="activeJenjangId == {{ $lvl->jenjang_id ?: '0' }}" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                <!-- 1. Jenjang (Relasi dari Table Jenjang) -->
                                <td class="px-5 py-3.5">
                                    @if($lvl->jenjang)
                                        @php
                                            $jCode = $lvl->jenjang->code;
                                            $emoji = ($jCode === 'KB' || $jCode === 'PG') ? '🧸' : (($jCode === 'TK') ? '🎒' : (($jCode === 'DAYCARE' || $jCode === 'TPA') ? '👶' : (($jCode === 'TPQ') ? '📖' : '🎓')));
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-800/60 whitespace-nowrap">
                                            <span>{{ $emoji }}</span>
                                            <span>{{ $lvl->jenjang->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-xs">-</span>
                                    @endif
                                </td>

                                <!-- 2. Kelas -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0 border border-indigo-100 dark:border-indigo-900/40">
                                            <i data-lucide="layers" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                                {{ $lvl->name }}
                                            </span>
                                            @if($lvl->description)
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $lvl->description }}</p>
                                            @elseif($lvl->code)
                                                <p class="text-[10.5px] text-slate-400 font-mono mt-0.5">Kode: {{ $lvl->code }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Status -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($lvl->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Non-aktif
                                        </span>
                                    @endif
                                </td>

                                <!-- 4. Aksi -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ $lvl->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Kelas">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="deleteLevel({{ $lvl->id }}, '{{ $lvl->name }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Kelas">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                    Belum ada data Kelas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL TAMBAH / EDIT KELAS -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display: none; margin-top: 0px !important; z-index: 9999; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col text-left">
                
                <form @submit.prevent="submitForm">
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Kelas' : 'Tambah Kelas Baru'"></h3>
                            <p class="text-xs text-slate-400 mt-0.5">Konfigurasi nama kelas dan relasi jenjang pendidikan.</p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenjang Pendidikan <span class="text-rose-500">*</span></label>
                            <select x-model="formData.jenjang_id" required
                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                <option value="">-- Pilih Jenjang --</option>
                                @foreach($jenjangs as $j)
                                    <option value="{{ $j->id }}">{{ $j->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kelas <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.name" required placeholder="Contoh: KB-A / KB-B / TK-A / TK-B / TPA 1 / TPQ"
                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Singkatan</label>
                                <input type="text" x-model="formData.code" placeholder="Contoh: KB-A / TK-A"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono uppercase">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Urut Tampil</label>
                                <input type="number" x-model.number="formData.order" min="1" max="99" placeholder="1, 2, 3..."
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Keterangan / Sasaran Usia</label>
                            <textarea x-model="formData.description" rows="3" placeholder="Contoh: Kelompok usia 3-4 tahun..."
                                class="w-full p-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">Status Aktif</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Aktifkan kelas ini agar tersedia saat pembuatan kelompok.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="formData.is_active" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                            <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Kelas')"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function classLevelApp() {
            const urlParams = new URLSearchParams(window.location.search);
            const initialJenjangId = urlParams.get('jenjang_id') || '{{ $jenjangs->first()?->id ?? 1 }}';

            return {
                modalOpen: false,
                isEdit: false,
                saving: false,
                activeJenjangId: initialJenjangId,
                formData: {
                    id: null,
                    jenjang_id: '',
                    name: '',
                    code: '',
                    order: 1,
                    description: '',
                    is_active: true,
                },

                setJenjangTab(id) {
                    this.activeJenjangId = id;
                    const url = new URL(window.location);
                    url.searchParams.set('jenjang_id', id);
                    window.history.replaceState({}, '', url);
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formData = {
                        id: null,
                        jenjang_id: this.activeJenjangId || '',
                        name: '',
                        code: '',
                        order: 1,
                        description: '',
                        is_active: true,
                    };
                    this.modalOpen = true;
                },

                openEditModal(id) {
                    fetch(`/class-levels/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const l = res.class_level;
                            this.isEdit = true;
                            this.formData = {
                                id: l.id,
                                jenjang_id: l.jenjang_id || '',
                                name: l.name,
                                code: l.code || '',
                                order: l.order || 1,
                                description: l.description || '',
                                is_active: !!l.is_active,
                            };
                            this.modalOpen = true;
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal mengambil data kelas: " + err.message, 'error');
                        }
                    });
                },

                submitForm() {
                    if (this.saving) return;
                    this.saving = true;

                    const url = this.isEdit ? `/class-levels/${this.formData.id}` : '/class-levels';
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
                            this.modalOpen = false;
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(res.message || 'Kelas berhasil disimpan!', 'success');
                            }
                            const targetJenjang = this.formData.jenjang_id || this.activeJenjangId;
                            const url = new URL(window.location);
                            url.searchParams.set('jenjang_id', targetJenjang);
                            window.location.href = url.toString();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', res.message || 'Terjadi kesalahan saat menyimpan.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        this.saving = false;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                        }
                    });
                },

                deleteLevel(id, name) {
                    const action = () => {
                        fetch(`/class-levels/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                if (typeof window.setPendingToast === 'function') {
                                    window.setPendingToast(res.message || 'Kelas berhasil dihapus!', 'success');
                                }
                                const url = new URL(window.location);
                                url.searchParams.set('jenjang_id', this.activeJenjangId);
                                window.location.href = url.toString();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal menghapus kelas.', 'error');
                                }
                            }
                        })
                        .catch(err => {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                            }
                        });
                    };

                    if (typeof showGlobalConfirmModal === 'function') {
                        showGlobalConfirmModal(`Apakah Anda yakin ingin menghapus Kelas "${name}"?`, action, true);
                    } else if (confirm(`Apakah Anda yakin ingin menghapus Kelas "${name}"?`)) {
                        action();
                    }
                }
            }
        }
    </script>
</x-admin-layout>
