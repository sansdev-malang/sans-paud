<x-admin-layout>
    <div class="p-6 space-y-6" x-data="jenjangApp()">

        <!-- GREETING / PAGE TITLE -->
        <section class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full text-left">
            <div class="flex flex-col gap-0.5">
                <div class="flex items-center gap-2.5">
                    <div class="p-2.5 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-500/20">
                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Jenjang Pendidikan
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-800">
                                Master Akademik
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Kelola master data jenjang pendidikan di PG - TK - DAYCARE - TPQ Anak Saleh.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
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
                        <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 mt-1">{{ number_format($stats['total_jenjang']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                        <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Jenjang terdaftar di institusi
                </div>
            </div>

            <!-- Stat 2: Jenjang Aktif -->
            <div class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jenjang Aktif</p>
                        <h3 class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['active_jenjang']) }}</h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">
                    Status operasional aktif
                </div>
            </div>
        </section>

        <!-- TABLE DAFTAR JENJANG -->
        <!-- Kolom: jenjang, keterangan, status, aksi -->
        <section class="animate-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden transition-all w-full">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-indigo-600"></i>
                    Daftar Jenjang
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-900/50">
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jenjang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keterangan</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse($jenjangs as $j)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                <!-- 1. Jenjang -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg {{ $j->is_active ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }} flex items-center justify-center font-bold text-xs shrink-0">
                                            @if($j->code === 'KB' || $j->code === 'PG')
                                                <span>🧸</span>
                                            @elseif($j->code === 'TK')
                                                <span>🎒</span>
                                            @elseif($j->code === 'DAYCARE' || $j->code === 'TPA')
                                                <span>👶</span>
                                            @elseif($j->code === 'TPQ')
                                                <span>📖</span>
                                            @else
                                                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                                {{ $j->name }}
                                            </span>
                                            @if($j->code)
                                                <span class="ml-1.5 px-1.5 py-0.2 rounded text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                    {{ $j->code }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 2. Keterangan -->
                                <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">
                                    {{ $j->description ?: '-' }}
                                </td>

                                <!-- 3. Status -->
                                <td class="px-5 py-3.5 text-center">
                                    <button type="button" @click="toggleStatus({{ $j->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold border transition-all cursor-pointer {{ $j->is_active ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-300 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700' }}"
                                        title="Klik untuk mengubah status">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $j->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                        <span>{{ $j->is_active ? 'Aktif' : 'Non-aktif' }}</span>
                                    </button>
                                </td>

                                <!-- 4. Aksi -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ $j->id }})"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Jenjang">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="confirmDelete({{ $j->id }}, '{{ $j->name }}')"
                                            class="p-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Jenjang">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                    Belum ada data Jenjang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MODAL TAMBAH / EDIT JENJANG -->
        <div x-show="modalOpen" x-cloak style="display: none; margin-top: 0px !important; z-index: 9999;"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
            @click.self="modalOpen = false"
            @keydown.escape.window="modalOpen = false">
            
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col relative my-auto text-left"
                @click.stop>
                
                <form @submit.prevent="submitForm">
                    <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-10">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-50" x-text="isEdit ? 'Edit Jenjang' : 'Tambah Jenjang Baru'"></h3>
                            <p class="text-xs text-slate-400 mt-0.5">Konfigurasi nama jenjang, kode, dan keterangan.</p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Jenjang <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.name" required placeholder="Contoh: Playgroup (KB) / Taman Kanak-kanak (TK)"
                                class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Singkatan</label>
                                <input type="text" x-model="formData.code" placeholder="Contoh: KB / TK / DAYCARE / TPQ"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50 font-mono uppercase">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Urut Tampil</label>
                                <input type="number" x-model.number="formData.order" min="1" max="99" placeholder="1, 2, 3, 4"
                                    class="w-full h-9 px-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Keterangan</label>
                            <textarea x-model="formData.description" rows="3" placeholder="Keterangan atau sasaran usia jenjang ini..."
                                class="w-full p-3 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900 dark:text-slate-50"></textarea>
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">Status Aktif</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Aktifkan jenjang ini untuk digunakan pada sistem.</p>
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
                            <span x-text="saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Jenjang')"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function jenjangApp() {
            return {
                modalOpen: false,
                isEdit: false,
                saving: false,
                formData: {
                    id: null,
                    name: '',
                    code: '',
                    order: 1,
                    description: '',
                    is_active: true,
                },

                openCreateModal() {
                    this.isEdit = false;
                    this.formData = {
                        id: null,
                        name: '',
                        code: '',
                        order: 1,
                        description: '',
                        is_active: true,
                    };
                    this.modalOpen = true;
                },

                openEditModal(id) {
                    fetch(`/jenjangs/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const j = res.jenjang;
                            this.isEdit = true;
                            this.formData = {
                                id: j.id,
                                name: j.name,
                                code: j.code || '',
                                order: j.order || 1,
                                description: j.description || '',
                                is_active: !!j.is_active,
                            };
                            this.modalOpen = true;
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', "Gagal mengambil data jenjang: " + err.message, 'error');
                        }
                    });
                },

                submitForm() {
                    if (this.saving) return;
                    this.saving = true;

                    const url = this.isEdit ? `/jenjangs/${this.formData.id}` : '/jenjangs';
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
                                window.setPendingToast(res.message || 'Jenjang berhasil disimpan!', 'success');
                            }
                            window.location.reload();
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

                toggleStatus(id) {
                    fetch(`/jenjangs/${id}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            if (typeof window.setPendingToast === 'function') {
                                window.setPendingToast(res.message || 'Status jenjang berhasil diperbarui!', 'success');
                            }
                            window.location.reload();
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast('Perhatian!', res.message || 'Gagal mengubah status jenjang.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        if (typeof window.showToast === 'function') {
                            window.showToast('Perhatian!', 'Error: ' + err.message, 'error');
                        }
                    });
                },

                confirmDelete(id, name) {
                    const action = () => {
                        fetch(`/jenjangs/${id}`, {
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
                                    window.setPendingToast(res.message || 'Jenjang berhasil dihapus!', 'success');
                                }
                                window.location.reload();
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('Perhatian!', res.message || 'Gagal menghapus jenjang.', 'error');
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
                        showGlobalConfirmModal(`Apakah Anda yakin ingin menghapus Jenjang "${name}"?`, action, true);
                    } else if (confirm(`Apakah Anda yakin ingin menghapus Jenjang "${name}"?`)) {
                        action();
                    }
                }
            };
        }
    </script>
</x-admin-layout>
