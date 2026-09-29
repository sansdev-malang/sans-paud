<x-admin-layout>
    <div class="p-6 space-y-6" x-data="classLevelApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-500/20">
                        <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Jenjang Pendidikan
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-800">
                                Master Akademik
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kelola master jenjang pendidikan di PG - TK - DAYCARE - TPQ Anak Saleh.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="{{ route('classrooms.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-800 shadow-xs transition-all duration-100 cursor-pointer">
                    <i data-lucide="shapes" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                    Kelompok
                </a>
                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-100 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Tambah Jenjang
                </button>
            </div>
        </section>

        <!-- STATS CARDS GRID -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat 1: Total Jenjang -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Jenjang</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">{{ number_format($stats['total_levels']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Jenjang terdaftar di PG-TK-Daycare
                </div>
            </div>

            <!-- Stat 2: Total Kelompok -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Kelompok</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">{{ number_format($stats['total_classrooms']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <i data-lucide="shapes" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Kelompok aktif di semua sub-unit
                </div>
            </div>

            <!-- Stat 3: Total Kapasitas -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Daya Tampung</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">{{ number_format($stats['total_capacity']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="door-open" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Kapasitas daya tampung murid
                </div>
            </div>

            <!-- Stat 4: Total Murid Aktif -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Murid Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">{{ number_format($stats['total_students']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl border border-rose-100 dark:border-rose-900/50">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Murid terdaftar TA 2026/2027
                </div>
            </div>
        </section>

        <!-- FILTER SUB-UNIT TABS -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
            <button type="button" @click="activeSubUnit = 'ALL'"
                :class="activeSubUnit === 'ALL' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer">
                Semua Jenjang
            </button>
            <button type="button" @click="activeSubUnit = 'PG'"
                :class="activeSubUnit === 'PG' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                <span>🧸</span> Playgroup (PG)
            </button>
            <button type="button" @click="activeSubUnit = 'TK'"
                :class="activeSubUnit === 'TK' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                <span>🎒</span> TK (Taman Kanak-Kanak)
            </button>
            <button type="button" @click="activeSubUnit = 'DAYCARE'"
                :class="activeSubUnit === 'DAYCARE' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                <span>👶</span> Daycare (TPA)
            </button>
            <button type="button" @click="activeSubUnit = 'TPQ'"
                :class="activeSubUnit === 'TPQ' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-1.5">
                <span>📖</span> TPQ
            </button>
        </div>

        <!-- TABLE DAFTAR JENJANG -->
        <section class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition-all w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="list-ordered" class="w-4 h-4 text-indigo-600"></i>
                    Daftar Jenjang Pendidikan
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-16">Urutan</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Sub Unit</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Kode</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kelompok Terdaftar</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Murid Aktif</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($classLevels as $lvl)
                            <tr x-show="activeSubUnit === 'ALL' || activeSubUnit === '{{ $lvl->sub_unit }}'" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono font-bold text-slate-700 dark:text-slate-300 text-xs">
                                        {{ $lvl->order }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($lvl->sub_unit === 'PG')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            🧸 PG
                                        </span>
                                    @elseif($lvl->sub_unit === 'TK')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            🎒 TK
                                        </span>
                                    @elseif($lvl->sub_unit === 'DAYCARE')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                            👶 DAYCARE
                                        </span>
                                    @elseif($lvl->sub_unit === 'TPQ')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            📖 TPQ
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            -
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                                {{ $lvl->name }}
                                            </span>
                                            @if($lvl->description)
                                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $lvl->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ $lvl->code }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($lvl->classrooms as $rombel)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                                                {{ $rombel->name }}
                                            </span>
                                        @empty
                                            <span class="text-slate-400 italic text-[11px]">Belum ada kelompok</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400 font-mono text-sm">{{ $lvl->active_students_count }}</span>
                                    <span class="text-slate-400 text-[10px] block">murid</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ $lvl->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Jenjang">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="deleteLevel({{ $lvl->id }}, '{{ $lvl->name }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Jenjang">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    Belum ada data Jenjang Pendidikan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL TAMBAH / EDIT JENJANG -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4" style="display: none; margin-top: 0px !important; z-index: 9999; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div @click.outside="modalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl flex flex-col">
                
                <form @submit.prevent="submitForm">
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Jenjang Pendidikan' : 'Tambah Jenjang Baru'"></h3>
                            <p class="text-xs text-slate-400 mt-0.5">Atur nama jenjang, sub-unit, kode, dan nomor urutan tampil.</p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Sub Unit <span class="text-rose-500">*</span></label>
                                <select x-model="formData.sub_unit" required
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                                    <option value="PG">🧸 Playgroup (PG)</option>
                                    <option value="TK">🎒 TK (Taman Kanak-Kanak)</option>
                                    <option value="DAYCARE">👶 Daycare (TPA)</option>
                                    <option value="TPQ">📖 TPQ</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Urut Tampil <span class="text-rose-500">*</span></label>
                                <input type="number" x-model.number="formData.order" required min="1" max="99" placeholder="1, 2, 3..."
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Jenjang <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.name" required placeholder="Contoh: KB-A / TK-A / TPA 1"
                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Singkatan <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.code" required placeholder="Contoh: KB-A / TK-A / TPA-1"
                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono uppercase">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Keterangan / Sasaran Usia</label>
                            <textarea x-model="formData.description" rows="3" placeholder="Contoh: Kelompok usia 3-4 tahun..."
                                class="w-full p-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                            <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Jenjang')"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function classLevelApp() {
            return {
                modalOpen: false,
                isEdit: false,
                saving: false,
                activeSubUnit: 'ALL',
                formData: {
                    id: null,
                    name: '',
                    code: '',
                    sub_unit: 'TK',
                    order: 1,
                    description: '',
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formData = {
                        id: null,
                        name: '',
                        code: '',
                        sub_unit: this.activeSubUnit !== 'ALL' ? this.activeSubUnit : 'TK',
                        order: 1,
                        description: '',
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
                                name: l.name,
                                code: l.code,
                                sub_unit: l.sub_unit || 'TK',
                                order: l.order || 1,
                                description: l.description || '',
                            };
                            this.modalOpen = true;
                        }
                    })
                    .catch(err => alert("Gagal mengambil data: " + err.message));
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
                            alert(res.message || 'Jenjang berhasil disimpan!');
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

                deleteLevel(id, name) {
                    if (!confirm(`Apakah Anda yakin ingin menghapus Jenjang "${name}"?`)) return;

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
                            alert(res.message || 'Jenjang berhasil dihapus!');
                            window.location.reload();
                        } else {
                            alert(res.message || 'Gagal menghapus jenjang.');
                        }
                    })
                    .catch(err => alert('Error: ' + err.message));
                }
            }
        }
    </script>
</x-admin-layout>
