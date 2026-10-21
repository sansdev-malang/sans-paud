@php
    $student = $student ?? new \App\Models\Student();
    $fd = old('family_data', $student->family_data ?? []);
    $hh = old('health_habits', $student->health_habits ?? []);
    $dp = old('development_psychology', $student->development_psychology ?? []);
    $docs = old('documents', $student->documents ?? []);

    // Helper to get agreed follow up options as array
    $rawFollowups = old('development_psychology.commitments.agreed_followup_types', $dp['commitments']['agreed_followup_types'] ?? []);
    if (is_string($rawFollowups)) {
        $agreedFollowupList = array_map('trim', explode(',', $rawFollowups));
    } elseif (is_array($rawFollowups)) {
        $agreedFollowupList = $rawFollowups;
    } else {
        $agreedFollowupList = [];
    }

    $followupOptions = [
        'a. Wawancara orangtua secara detail tentang ananda' => 'a. Wawancara orang tua secara detail tentang ananda',
        'b. Observasi lebih lanjut oleh tim ahli' => 'b. Observasi lebih lanjut oleh tim ahli / psikolog',
        'c. Penyediaan Guru Pembimbing Khusus (GPK) jika diperlukan' => 'c. Penyediaan Guru Pembimbing Khusus (GPK) jika diperlukan',
        'd. Mengikuti progam terapi' => 'd. Mengikuti program terapi tumbuh kembang',
    ];
@endphp

<div x-data="{
    activeTab: '{{ $errors->any() ? ($errors->hasAny(["address", "dusun", "rt", "rw", "kelurahan", "kecamatan", "city", "province", "postal_code", "living_with", "transportation"]) ? "domisili" : ($errors->hasAny(["father_name", "father_phone", "mother_name", "mother_phone", "guardian_name", "guardian_phone", "parent_phone", "parent_email"]) ? "keluarga" : ($errors->hasAny(["height", "weight", "head_circumference", "arm_circumference"]) ? "kesehatan" : "identitas"))) : "identitas" }}',
    isSaving: false,
    toastMessage: '',
    toastType: 'success',
    showToast: false,
    errorList: [],
    showErrorBox: false,
    selectedYear: '{{ old('academic_year_id', $student->academic_year_id ?? $activeAcademicYear?->id ?? '') }}',
    selectedJenjang: '{{ old('jenjang_id', $student->jenjang_id ?? '') }}',
    selectedLevel: '{{ old('class_level_id', $student->class_level_id ?? '') }}',
    selectedClassroom: '{{ old('classroom_id', $student->classroom_id ?? '') }}',
    isSpecialNeeds: '{{ old('special_needs', str_contains($student->special_needs ?? '', 'Ya') ? 'Ya' : 'Tidak') }}',
    hasGuardian: {{ old('guardian_name', $student->guardian_name) ? 'true' : 'false' }},
    isDaycare: {{ old('daycare_classroom_id', $student->daycare_classroom_id) ? 'true' : 'false' }},
    isTpq: {{ old('is_tpq', $student->is_tpq || $student->tpq_classroom_id) ? 'true' : 'false' }},
    photoPreview: '{{ $student->student_photo_url ?? '' }}',
    classrooms: {{ json_encode($classrooms->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'jenjang_id' => $c->jenjang_id ?? $c->classLevel?->jenjang_id,
        'class_level_id' => $c->class_level_id,
        'academic_year_id' => $c->academic_year_id,
        'sub_unit' => $c->sub_unit
    ])) }},

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
    },

    onYearChange() {
        const currentClass = this.classrooms.find(c => c.id == this.selectedClassroom);
        if (currentClass && currentClass.academic_year_id != this.selectedYear) {
            this.selectedClassroom = '';
        }
    },

    async saveAndNext(nextTab, isFinal = false) {
        if (this.isSaving) return;
        this.isSaving = true;
        this.showErrorBox = false;
        this.errorList = [];

        const form = this.$el.closest('form');
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json().catch(() => null);

            if (!response.ok) {
                if (response.status === 422 && result && result.errors) {
                    this.errorList = Object.values(result.errors).flat();
                    this.showErrorBox = true;
                    this.triggerToast('Terdapat kesalahan validasi. Membuka tab yang perlu diperbaiki...', 'error');

                    // Intelligent Tab Auto-Switch ke tab tempat kolom error berada
                    const tabFieldMap = {
                        identitas: ['full_name', 'gender', 'academic_year_id', 'status', 'jenjang_id', 'classroom_id', 'daycare_classroom_id', 'tpq_classroom_id', 'nis', 'nisn', 'nik', 'no_kk', 'no_akta', 'birth_place', 'birth_date', 'religion', 'citizenship', 'child_order', 'siblings_count', 'blood_type', 'special_needs', 'special_needs_detail', 'pin_access', 'notes'],
                        domisili: ['address', 'dusun', 'rt', 'rw', 'kelurahan', 'kecamatan', 'city', 'province', 'postal_code', 'living_with', 'transportation', 'distance_to_school', 'travel_time_minutes'],
                        keluarga: ['father_name', 'father_phone', 'father_job', 'father_nik', 'father_birth_place', 'father_birth_date', 'father_education', 'father_company', 'father_income', 'mother_name', 'mother_phone', 'mother_job', 'mother_nik', 'mother_birth_place', 'mother_birth_date', 'mother_education', 'mother_company', 'mother_income', 'guardian_name', 'guardian_phone', 'guardian_job', 'guardian_nik', 'guardian_relation', 'emergency_contact_name', 'emergency_contact_phone', 'parent_phone', 'parent_email'],
                        kesehatan: ['height', 'weight', 'head_circumference', 'arm_circumference', 'health_habits'],
                        psikologi: ['development_psychology', 'student_hobby', 'student_aspiration', 'parent_aspiration', 'planned_school_after', 'is_paud_formal_before'],
                        berkas: ['student_photo', 'documents']
                    };

                    const errorKeys = Object.keys(result.errors);
                    for (const [tabKey, fields] of Object.entries(tabFieldMap)) {
                        if (errorKeys.some(k => fields.some(f => k === f || k.startsWith(f + '.') || k.startsWith(f + '_')))) {
                            this.activeTab = tabKey;
                            this.$nextTick(() => {
                                const btn = this.$refs.tabScroll?.querySelector(`button[data-tab='${this.activeTab}']`);
                                if (btn) btn.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                            });
                            break;
                        }
                    }
                } else {
                    this.triggerToast((result && result.message) || 'Gagal menyimpan data murid. Silakan coba lagi.', 'error');
                }
                this.isSaving = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // Success handling
            if (isFinal) {
                this.triggerToast('Seluruh data murid berhasil disimpan! Mengalihkan ke profil...', 'success');
                setTimeout(() => {
                    if (result && result.redirect_url) {
                        window.location.href = result.redirect_url;
                    } else if (result && result.student && result.student.id) {
                        window.location.href = `/students/${result.student.id}`;
                    } else {
                        window.location.href = form.action;
                    }
                }, 700);
            } else {
                // If this was create mode and student was newly created, update form action to PUT update
                if (result && result.student && result.student.id) {
                    form.action = `/students/${result.student.id}`;
                    let methodInput = form.querySelector('input[name=\'_method\']');
                    if (!methodInput) {
                        methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'PUT';
                        form.appendChild(methodInput);
                    }
                }

                this.triggerToast('Data tab berhasil disimpan! Beralih ke langkah berikutnya...', 'success');
                if (nextTab) {
                    this.activeTab = nextTab;
                    this.$nextTick(() => {
                        const btn = this.$refs.tabScroll?.querySelector(`button[data-tab='${this.activeTab}']`);
                        if (btn) btn.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                    });
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    // Refresh lucide icons in the new view
                    setTimeout(() => {
                        if (window.lucide) window.lucide.createIcons();
                    }, 50);
                }
                this.isSaving = false;
            }
        } catch (err) {
            console.error('Save error:', err);
            this.triggerToast('Terjadi kendala jaringan saat menyimpan. Silakan coba lagi.', 'error');
            this.isSaving = false;
        }
    },

    triggerToast(msg, type = 'success') {
        this.toastMessage = msg;
        this.toastType = type;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 4000);
    }
}" x-init="$nextTick(() => { checkTabScroll(); setTimeout(() => checkTabScroll(), 100); setTimeout(() => checkTabScroll(), 400); }); window.addEventListener('resize', () => checkTabScroll())" class="space-y-6">

    <!-- HORIZONTAL STEPPER / TAB NAVIGATION (Clean Full-Width with Hover Scroll Indicators) -->
    <div class="sticky top-0 z-30 bg-slate-50/95 dark:bg-slate-950/95 backdrop-blur-md pt-2.5 pb-1 border-b border-slate-200 dark:border-slate-800 -mx-3.5 sm:-mx-5 lg:-mx-6 px-3.5 sm:px-5 lg:px-6 shadow-xs tab-scroll-container group relative">
        
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
            <button type="button" data-tab="identitas" @click="activeTab = 'identitas'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                :class="activeTab === 'identitas' 
                    ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="activeTab === 'identitas' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">1</span>
                Identitas & Rombel
            </button>

            <button type="button" data-tab="domisili" @click="activeTab = 'domisili'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                :class="activeTab === 'domisili' 
                    ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="activeTab === 'domisili' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">2</span>
                Domisili & Lingkungan
            </button>

            <button type="button" data-tab="keluarga" @click="activeTab = 'keluarga'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                :class="activeTab === 'keluarga' 
                    ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="activeTab === 'keluarga' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">3</span>
                Orang Tua & Keluarga
            </button>

            <button type="button" data-tab="kesehatan" @click="activeTab = 'kesehatan'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                :class="activeTab === 'kesehatan' 
                    ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="activeTab === 'kesehatan' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">4</span>
                Fisik & Medis
            </button>

            <button type="button" data-tab="psikologi" @click="activeTab = 'psikologi'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                :class="activeTab === 'psikologi' 
                    ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="activeTab === 'psikologi' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">5</span>
                Tumbuh Kembang
            </button>

            <button type="button" data-tab="dokumen" @click="activeTab = 'dokumen'; $el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' })"
                :class="activeTab === 'dokumen' 
                    ? 'bg-indigo-600 text-white shadow-xs font-semibold' 
                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs whitespace-nowrap transition-all border border-slate-200/80 dark:border-slate-800/80 cursor-pointer">
                <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="activeTab === 'dokumen' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">6</span>
                Berkas & Dokumen Fisik
            </button>
        </div>
    </div>

    <!-- FLOATING REALTIME TOAST NOTIFICATION -->
    <div x-show="showToast" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl backdrop-blur-md text-xs font-semibold"
        :class="toastType === 'success' ? 'bg-emerald-600/95 text-white shadow-emerald-500/20' : 'bg-rose-600/95 text-white shadow-rose-500/20'">
        <template x-if="toastType === 'success'">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-200 shrink-0"></i>
        </template>
        <template x-if="toastType === 'error'">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-200 shrink-0"></i>
        </template>
        <span x-text="toastMessage"></span>
        <button type="button" @click="showToast = false" class="ml-2 opacity-70 hover:opacity-100 cursor-pointer">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    </div>

    <!-- DYNAMIC VALIDATION ERRORS DISPLAY -->
    <div x-show="showErrorBox && errorList.length > 0" x-cloak
        class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 p-4 rounded-2xl text-xs">
        <div class="flex items-center justify-between font-bold mb-2 text-sm text-rose-700 dark:text-rose-400">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                Terdapat beberapa kesalahan pengisian formulir:
            </div>
            <button type="button" @click="showErrorBox = false" class="text-rose-400 hover:text-rose-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <ul class="list-disc list-inside space-y-1 opacity-90 pl-1">
            <template x-for="(err, idx) in errorList" :key="idx">
                <li x-text="err"></li>
            </template>
        </ul>
    </div>

    <!-- SERVER-SIDE VALIDATION ERRORS DISPLAY -->
    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 p-4 rounded-2xl text-xs">
            <div class="flex items-center gap-2 font-bold mb-2 text-sm text-rose-700 dark:text-rose-400">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                Terdapat beberapa kesalahan pengisian formulir:
            </div>
            <ul class="list-disc list-inside space-y-1 opacity-90 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- TAB 1: IDENTITAS & ROMBEL -->
    <div x-show="activeTab === 'identitas'" x-cloak class="space-y-6">
        <!-- 1.1 Penempatan Akademik & Program Layanan -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="school" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Penempatan Akademik & Program Layanan</h3>
                    <p class="text-[11px] text-slate-400">Pilih tahun ajaran, program inti (KB / TK / Daycare Murni), serta program layanan tambahan (Daycare & TPQ).</p>
                </div>
            </div>

            <!-- Bagian 1: Pengaturan Wajib (Tahun Ajaran & Status) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mb-5 pb-5 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                    <select name="academic_year_id" x-model="selectedYear" @change="onYearChange()" required class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ old('academic_year_id', $student->academic_year_id ?? $activeAcademicYear?->id) == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }}{{ $ay->is_active ? ' ★ Aktif' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Murid <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        <option value="aktif" {{ old('status', $student->status ?? 'aktif') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="lulus" {{ old('status', $student->status) == 'lulus' ? 'selected' : '' }}>Lulus</option>
                        <option value="mutasi" {{ old('status', $student->status) == 'mutasi' ? 'selected' : '' }}>Mutasi</option>
                        <option value="keluar" {{ old('status', $student->status) == 'keluar' ? 'selected' : '' }}>Keluar</option>
                        <option value="nonaktif" {{ old('status', $student->status) == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Bagian 2: Program Pendidikan Utama -->
            <div class="mb-5 p-4 rounded-xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-5 h-5 rounded-md bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold">1</span>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Program Pendidikan Utama</h4>
                    <span class="text-[10px] text-slate-400">(Pilih jenjang utama anak atau pilih Daycare bila belum sekolah formal)</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenjang Utama <span class="text-rose-500">*</span></label>
                        <select name="jenjang_id" x-model="selectedJenjang" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-medium">
                            <option value="">-- Pilih Jenjang Utama --</option>
                            @foreach($jenjangs as $j)
                                <option value="{{ $j->id }}">{{ $j->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelompok Belajar Utama (Rombel)</label>
                        <select name="classroom_id" x-model="selectedClassroom" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            <option value="">-- Pilih Kelompok Belajar --</option>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}"
                                    x-show="(!selectedJenjang || selectedJenjang == '{{ $c->jenjang_id ?? $c->classLevel?->jenjang_id }}') && (!selectedYear || selectedYear == '{{ $c->academic_year_id }}')"
                                    {{ old('classroom_id', $student->classroom_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->classLevel?->name ?? 'Level' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Layanan Tambahan (Multi-Program / Layanan Bersamaan) -->
            <div class="p-4 rounded-xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-5 h-5 rounded-md bg-amber-500 text-white flex items-center justify-center text-[10px] font-bold">2</span>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Layanan Tambahan / Multi-Program (Opsional)</h4>
                    <span class="text-[10px] text-slate-400">(Bisa diambil bersamaan dengan program utama)</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <!-- Daycare Card -->
                    <div class="p-3.5 rounded-xl border transition-all"
                        :class="isDaycare ? 'bg-amber-50/60 dark:bg-amber-950/20 border-amber-300 dark:border-amber-700/60' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-base">👶</span>
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 block">Layanan Daycare (TPA)</span>
                                    <span class="text-[10px] text-slate-400">Penitipan anak siang / full-day</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="isDaycare" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-amber-500"></div>
                            </label>
                        </div>
                        <div x-show="isDaycare" class="mt-2.5 pt-2.5 border-t border-amber-200/60 dark:border-amber-900/40">
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1 text-[11px]">Pilih Kelompok Daycare:</label>
                            <select name="daycare_classroom_id" :disabled="!isDaycare" class="w-full rounded-xl border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-amber-500">
                                <option value="">-- Pilih Kelompok Daycare --</option>
                                @foreach($daycareClassrooms as $dc)
                                    <option value="{{ $dc->id }}"
                                        x-show="!selectedYear || selectedYear == '{{ $dc->academic_year_id }}'"
                                        {{ old('daycare_classroom_id', $student->daycareClassroom?->id) == $dc->id ? 'selected' : '' }}>
                                        {{ $dc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- TPQ Card -->
                    <div class="p-3.5 rounded-xl border transition-all"
                        :class="isTpq ? 'bg-teal-50/60 dark:bg-teal-950/20 border-teal-300 dark:border-teal-700/60' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-base">📖</span>
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 block">Program Mengaji TPQ</span>
                                    <span class="text-[10px] text-slate-400">Pembelajaran Al-Qur'an sore</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_tpq" value="1" x-model="isTpq" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-teal-600"></div>
                            </label>
                        </div>
                        <div x-show="isTpq" class="mt-2.5 pt-2.5 border-t border-teal-200/60 dark:border-teal-900/40">
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1 text-[11px]">Pilih Kelompok TPQ:</label>
                            <select name="tpq_classroom_id" :disabled="!isTpq" class="w-full rounded-xl border-teal-200 dark:border-teal-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-teal-500">
                                <option value="">-- Pilih Kelompok TPQ --</option>
                                @foreach($tpqClassrooms as $tpq)
                                    <option value="{{ $tpq->id }}"
                                        x-show="!selectedYear || selectedYear == '{{ $tpq->academic_year_id }}'"
                                        {{ old('tpq_classroom_id', $student->tpqClassroom?->id) == $tpq->id ? 'selected' : '' }}>
                                        {{ $tpq->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1.2 Legalitas & Biodata Pokok Murid (Termasuk Upload Foto Profil) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="user-check" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Biodata Pokok & Legalitas Kependudukan</h3>
                    <p class="text-[11px] text-slate-400">Identitas resmi murid sesuai Akta Kelahiran dan Kartu Keluarga (KK).</p>
                </div>
            </div>

            <!-- Bagian Foto Profil Ananda (Physical Upload with Auto-Optimize) -->
            <div class="mb-5 p-4 rounded-xl bg-slate-50/60 dark:bg-slate-800/30 border border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-4 text-xs">
                <div class="relative w-20 h-20 rounded-2xl overflow-hidden border-2 border-indigo-200 dark:border-indigo-900 shrink-0 bg-slate-100 dark:bg-slate-800 flex items-center justify-center shadow-inner">
                    <template x-if="photoPreview">
                        <img :src="photoPreview" alt="Foto Murid" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!photoPreview">
                        <div class="text-slate-400 flex flex-col items-center">
                            <i data-lucide="camera" class="w-6 h-6 mb-1"></i>
                            <span class="text-[9px]">Belum Ada</span>
                        </div>
                    </template>
                </div>
                <div class="space-y-1.5 flex-1 text-center sm:text-left">
                    <label class="block font-bold text-slate-700 dark:text-slate-300">Foto Resmi Ananda</label>
                    <p class="text-[11px] text-slate-400">Format JPG, PNG, atau WebP. Foto akan otomatis di-resize (maks. 1600px) dan dikompresi ke WebP kualitas optimal saat disimpan.</p>
                    <div class="pt-1">
                        <input type="file" name="student_photo" accept="image/jpeg,image/png,image/webp"
                            @change="const file = $event.target.files[0]; if (file) { photoPreview = URL.createObjectURL(file); }"
                            class="block w-full text-[11px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950/60 dark:file:text-indigo-400 cursor-pointer">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Murid <span class="text-rose-500">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" required placeholder="Nama lengkap sesuai akta kelahiran"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-bold">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Panggilan</label>
                    <input type="text" name="nickname" value="{{ old('nickname', $student->nickname) }}" placeholder="Panggilan akrab"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                    <select name="gender" required class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        <option value="L" {{ old('gender', $student->gender ?? 'L') == 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                        <option value="P" {{ old('gender', $student->gender) == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIS (Nomor Induk Siswa)</label>
                    <input type="text" name="nis" value="{{ old('nis', $student->nis) }}" placeholder="Contoh: 26871029"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NISN (Kemdikbud)</label>
                    <input type="text" name="nisn" value="{{ old('nisn', $student->nisn) }}" placeholder="10 digit NISN"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Siswa (No KTP Ananda)</label>
                    <input type="text" name="nik" value="{{ old('nik', $student->nik) }}" placeholder="16 digit NIK"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Kartu Keluarga (KK)</label>
                    <input type="text" name="no_kk" value="{{ old('no_kk', $student->no_kk) }}" placeholder="16 digit No. KK"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Akta Kelahiran</label>
                    <input type="text" name="no_akta" value="{{ old('no_akta', $student->no_akta) }}" placeholder="Nomor registrasi akta"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                    <input type="text" name="birth_place" value="{{ old('birth_place', $student->birth_place) }}" placeholder="Kota kelahiran"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $student->birth_date ? $student->birth_date->format('Y-m-d') : '') }}"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Agama</label>
                    <select name="religion" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        <option value="Islam" {{ old('religion', $student->religion ?? 'Islam') == 'Islam' ? 'selected' : '' }}>Islam</option>
                        <option value="Kristen" {{ old('religion', $student->religion) == 'Kristen' ? 'selected' : '' }}>Kristen</option>
                        <option value="Katolik" {{ old('religion', $student->religion) == 'Katolik' ? 'selected' : '' }}>Katolik</option>
                        <option value="Hindu" {{ old('religion', $student->religion) == 'Hindu' ? 'selected' : '' }}>Hindu</option>
                        <option value="Buddha" {{ old('religion', $student->religion) == 'Buddha' ? 'selected' : '' }}>Buddha</option>
                        <option value="Konghucu" {{ old('religion', $student->religion) == 'Konghucu' ? 'selected' : '' }}>Konghucu</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kewarganegaraan</label>
                    <select name="citizenship" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        <option value="Indonesia (WNI)" {{ old('citizenship', $student->citizenship ?? 'Indonesia (WNI)') == 'Indonesia (WNI)' || old('citizenship', $student->citizenship) == 'WNI' ? 'selected' : '' }}>Indonesia (WNI)</option>
                        <option value="Asing (WNA)" {{ old('citizenship', $student->citizenship) == 'Asing (WNA)' || old('citizenship', $student->citizenship) == 'WNA' ? 'selected' : '' }}>Asing (WNA)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Anak Ke-</label>
                    <input type="number" name="child_order" min="1" max="25" value="{{ old('child_order', $student->child_order) }}" placeholder="1"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jumlah Saudara Kandung</label>
                    <input type="number" name="siblings_count" min="0" max="25" value="{{ old('siblings_count', $student->siblings_count) }}" placeholder="0"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Golongan Darah</label>
                    <select name="blood_type" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        <option value="">-- Belum Tahu --</option>
                        <option value="A" {{ old('blood_type', $student->blood_type) == 'A' ? 'selected' : '' }}>A</option>
                        <option value="B" {{ old('blood_type', $student->blood_type) == 'B' ? 'selected' : '' }}>B</option>
                        <option value="AB" {{ old('blood_type', $student->blood_type) == 'AB' ? 'selected' : '' }}>AB</option>
                        <option value="O" {{ old('blood_type', $student->blood_type) == 'O' ? 'selected' : '' }}>O</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Berkebutuhan Khusus</label>
                    <select name="special_needs" x-model="isSpecialNeeds" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-medium">
                        <option value="Tidak">Tidak</option>
                        <option value="Ya">Ya</option>
                    </select>
                </div>

                <div x-show="isSpecialNeeds === 'Ya'" x-cloak class="transition-all">
                    <label class="block font-semibold text-amber-700 dark:text-amber-400 mb-1">
                        Ragam / Jenis Kebutuhan Khusus <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="special_needs_detail" list="special_needs_options"
                        value="{{ old('special_needs_detail', $student->special_needs_detail ?? ($student->special_needs && !in_array($student->special_needs, ['Tidak', 'Ya', '01) Tidak', '02) Ya']) ? $student->special_needs : '')) }}"
                        placeholder="Ketik atau pilih jenis (cth: Autis, Wicara, Hiperaktif...)"
                        class="w-full rounded-xl border-amber-300 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-950/20 text-slate-800 dark:text-slate-200 text-xs focus:ring-amber-500 font-medium">
                    <datalist id="special_needs_options">
                        <option value="Autis">
                        <option value="Wicara / Speech Delay">
                        <option value="Hiperaktif (ADHD)">
                        <option value="ASD (Autism Spectrum Disorder)">
                        <option value="Down Syndrome">
                        <option value="Tuna Rungu">
                        <option value="Tuna Netra">
                        <option value="Tuna Daksa">
                        <option value="Global Developmental Delay (GDD)">
                    </datalist>
                    <p class="text-[10px] text-slate-400 mt-1">Pilih dari rekomendasi atau ketik diagnosa khusus ananda.</p>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">PIN Akses Kios / Presensi</label>
                    <input type="text" name="pin_access" value="{{ old('pin_access', $student->pin_access ?? '1234') }}" placeholder="1234"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                </div>

                <div class="sm:col-span-3">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan Admin</label>
                    <input type="text" name="notes" value="{{ old('notes', $student->notes) }}" placeholder="Catatan internal perihal ananda..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="button" @click="saveAndNext('domisili')" :disabled="isSaving"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-semibold rounded-xl transition-all shadow-xs disabled:opacity-50 cursor-pointer">
                <template x-if="!isSaving">
                    <span class="inline-flex items-center gap-2">
                        <span>Simpan & Lanjut ke Domisili</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </span>
                </template>
                <template x-if="isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Menyimpan Tab 1...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- TAB 2: DOMISILI & TRANSPORTASI -->
    <div x-show="activeTab === 'domisili'" x-cloak class="space-y-6">
        <!-- 2.1 Alamat Rumah -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="map-pin" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Alamat Tempat Tinggal</h3>
                    <p class="text-[11px] text-slate-400">Alamat domisili saat ini tempat ananda tinggal dan diasuh.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="sm:col-span-4">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Lengkap (Jalan, No Rumah, Blok)</label>
                    <textarea name="address" rows="2" placeholder="Nama jalan, nomor rumah, perumahan, gang..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">{{ old('address', $student->address) }}</textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">RT</label>
                    <input type="text" name="rt" value="{{ old('rt', $student->rt) }}" placeholder="01"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">RW</label>
                    <input type="text" name="rw" value="{{ old('rw', $student->rw) }}" placeholder="02"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Dusun / Lingkungan</label>
                    <input type="text" name="dusun" value="{{ old('dusun', $student->dusun) }}" placeholder="Nama Dusun / RW"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kelurahan / Desa</label>
                    <input type="text" name="kelurahan" value="{{ old('kelurahan', $student->kelurahan) }}" placeholder="Nama Kelurahan"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kecamatan</label>
                    <input type="text" name="kecamatan" value="{{ old('kecamatan', $student->kecamatan) }}" placeholder="Nama Kecamatan"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kota / Kabupaten</label>
                    <input type="text" name="city" value="{{ old('city', $student->city ?? 'Kota Malang') }}" placeholder="Kota Malang / Kota Batu"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Pos</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $student->postal_code) }}" placeholder="65141"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tinggal Bersama</label>
                    <select name="living_with" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $lw = old('living_with', $student->living_with ?? 'Bersama Orang Tua'); @endphp
                        <option value="Bersama Orang Tua" {{ $lw == 'Bersama Orang Tua' || $lw == 'Orangtua' ? 'selected' : '' }}>Bersama Orang Tua</option>
                        <option value="Bersama Kakek dan Nenek" {{ $lw == 'Bersama Kakek dan Nenek' || $lw == 'Kakek-nenek' ? 'selected' : '' }}>Bersama Kakek dan Nenek</option>
                        <option value="Bersama orang tua dan kakek nenek" {{ $lw == 'Bersama orang tua dan kakek nenek' || $lw == 'Ortu dan kakek nenek' ? 'selected' : '' }}>Bersama Orang Tua dan Kakek Nenek</option>
                        <option value="Bersama Wali" {{ $lw == 'Bersama Wali' ? 'selected' : '' }}>Bersama Wali</option>
                        <option value="KONTRAKAN / SENDIRI" {{ $lw == 'KONTRAKAN' || $lw == 'SENDIRI' ? 'selected' : '' }}>Kontrakan / Sendiri</option>
                        <option value="Lainnya" {{ !in_array($lw, ['Bersama Orang Tua', 'Orangtua', 'Bersama Kakek dan Nenek', 'Kakek-nenek', 'Bersama orang tua dan kakek nenek', 'Ortu dan kakek nenek', 'Bersama Wali', 'KONTRAKAN', 'SENDIRI']) ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2.2 Transportasi & Lingkungan Hunian -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="navigation" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Transportasi & Karakteristik Lingkungan</h3>
                    <p class="text-[11px] text-slate-400">Jarak tempuh ke sekolah dan gambaran tempat tinggal ananda.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Moda Transportasi</label>
                    <select name="transportation" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $trans = old('transportation', $student->transportation ?? 'Sepeda Motor'); @endphp
                        <option value="Sepeda Motor" {{ $trans == 'Sepeda Motor' ? 'selected' : '' }}>Sepeda Motor</option>
                        <option value="Mobil Pribadi" {{ $trans == 'Mobil Pribadi' ? 'selected' : '' }}>Mobil Pribadi</option>
                        <option value="Angkutan Umum/Ojek Online" {{ $trans == 'Angkutan Umum/Ojek Online' ? 'selected' : '' }}>Angkutan Umum / Ojek Online</option>
                        <option value="Mobil/bus Jemput" {{ $trans == 'Mobil/bus Jemput' ? 'selected' : '' }}>Mobil / Bus Jemput</option>
                        <option value="Jalan Kaki" {{ $trans == 'Jalan Kaki' ? 'selected' : '' }}>Jalan Kaki</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jarak ke Sekolah</label>
                    <input type="text" name="distance_to_school" value="{{ old('distance_to_school', $student->distance_to_school) }}" placeholder="Contoh: 3 km atau < 1 km"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Waktu Tempuh (Menit)</label>
                    <input type="number" name="travel_time_minutes" min="0" max="300" value="{{ old('travel_time_minutes', $student->travel_time_minutes) }}" placeholder="15"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pengantar / Penjemput Terpercaya</label>
                    <input type="text" name="family_data[relationships][trusted_pickup]" value="{{ old('family_data.relationships.trusted_pickup', $fd['relationships']['trusted_pickup'] ?? 'Orang Tua') }}" placeholder="Orang Tua / Kakek / Supir"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Kepemilikan Rumah</label>
                    <select name="family_data[environment][house_ownership]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $ho = old('family_data.environment.house_ownership', $fd['environment']['house_ownership'] ?? 'Milik sendiri'); @endphp
                        <option value="Milik sendiri" {{ strtolower($ho) == 'milik sendiri' ? 'selected' : '' }}>Milik Sendiri</option>
                        <option value="Milik Orang Tua" {{ str_contains(strtolower($ho), 'orang tua') || strtolower($ho) == 'mertua' || strtolower($ho) == 'ortu' ? 'selected' : '' }}>Milik Orang Tua / Mertua</option>
                        <option value="Kontrak" {{ str_contains(strtolower($ho), 'kontrak') || str_contains(strtolower($ho), 'sewa') ? 'selected' : '' }}>Kontrak / Sewa</option>
                        <option value="Rumah dinas" {{ strtolower($ho) == 'rumah dinas' ? 'selected' : '' }}>Rumah Dinas</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lingkungan Hunian</label>
                    <select name="family_data[environment][housing_environment]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $he = old('family_data.environment.housing_environment', $fd['environment']['housing_environment'] ?? 'Perumahan'); @endphp
                        <option value="Perumahan" {{ strtolower($he) == 'perumahan' ? 'selected' : '' }}>Perumahan</option>
                        <option value="Perkampungan" {{ strtolower($he) == 'perkampungan' ? 'selected' : '' }}>Perkampungan</option>
                        <option value="Rumah Pinggir Jalan Raya" {{ str_contains(strtolower($he), 'jalan raya') ? 'selected' : '' }}>Pinggir Jalan Raya</option>
                        <option value="Pesantren" {{ strtolower($he) == 'pesantren' ? 'selected' : '' }}>Pesantren</option>
                        <option value="Industri" {{ strtolower($he) == 'industri' ? 'selected' : '' }}>Industri</option>
                        <option value="Lainnya" {{ !in_array(strtolower($he), ['perumahan', 'perkampungan', 'pesantren', 'industri']) && !str_contains(strtolower($he), 'jalan raya') ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Tinggal Keluarga</label>
                    <select name="family_data[environment][house_sharing]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $hs = old('family_data.environment.house_sharing', $fd['environment']['house_sharing'] ?? 'Sendiri'); @endphp
                        <option value="Sendiri" {{ $hs == 'Sendiri' ? 'selected' : '' }}>Sendiri</option>
                        <option value="Bersama-sama dengan keluarga yang lain" {{ $hs == 'Bersama-sama dengan keluarga yang lain' ? 'selected' : '' }}>Bersama Keluarga Lain</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Halaman Bermain di Luar</label>
                    <select name="family_data[environment][has_playyard]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $hpy = old('family_data.environment.has_playyard', $fd['environment']['has_playyard'] ?? 'Ada'); @endphp
                        <option value="Ada" {{ $hpy == 'Ada' ? 'selected' : '' }}>Ada</option>
                        <option value="Tidak ada" {{ $hpy == 'Tidak ada' || $hpy == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kondisi Keamanan Halaman</label>
                    <select name="family_data[environment][playyard_state]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $pys = old('family_data.environment.playyard_state', $fd['environment']['playyard_state'] ?? 'Aman'); @endphp
                        <option value="Aman" {{ $pys == 'Aman' ? 'selected' : '' }}>Aman / Terawat</option>
                        <option value="Cukup Aman" {{ $pys == 'Cukup Aman' ? 'selected' : '' }}>Cukup Aman</option>
                        <option value="Kurang Aman" {{ $pys == 'Kurang Aman' || $pys == 'Tidak Ada' ? 'selected' : '' }}>Kurang Aman / Tidak Ada</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kamar Tidur Sendiri</label>
                    <select name="family_data[environment][has_own_bedroom]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $hob = old('family_data.environment.has_own_bedroom', $fd['environment']['has_own_bedroom'] ?? 'Tidak memiliki'); @endphp
                        <option value="Memiliki" {{ $hob == 'Memiliki' ? 'selected' : '' }}>Memiliki Kamar Sendiri</option>
                        <option value="Tidak memiliki" {{ $hob == 'Tidak memiliki' ? 'selected' : '' }}>Tidak Memiliki</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penghuni Dewasa di Rumah</label>
                    <input type="text" name="family_data[environment][adult_residents]" value="{{ old('family_data.environment.adult_residents', $fd['environment']['adult_residents'] ?? '2') }}" placeholder="Contoh: 2 orang"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penghuni Anak di Rumah</label>
                    <input type="text" name="family_data[environment][child_residents]" value="{{ old('family_data.environment.child_residents', $fd['environment']['child_residents'] ?? '1') }}" placeholder="Contoh: 1 orang"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-2">
            <button type="button" @click="activeTab = 'identitas'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Sebelumnya
            </button>
            <button type="button" @click="saveAndNext('keluarga')" :disabled="isSaving"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-semibold rounded-xl transition-all shadow-xs disabled:opacity-50 cursor-pointer">
                <template x-if="!isSaving">
                    <span class="inline-flex items-center gap-2">
                        <span>Simpan & Lanjut ke Orang Tua & Keluarga</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </span>
                </template>
                <template x-if="isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Menyimpan Tab 2...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- TAB 3: ORANG TUA & KELUARGA -->
    <div x-show="activeTab === 'keluarga'" x-cloak class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 3.1 Profil Ayah Kandung -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                    <i data-lucide="user" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Data Ayah Kandung</h3>
                        <p class="text-[11px] text-slate-400">Profil lengkap ayah untuk keperluan administrasi dan pelaporan.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Depan</label>
                        <input type="text" name="father_title_prefix" value="{{ old('father_title_prefix', $student->father_title_prefix) }}" placeholder="Dr. / Ir. / Drs."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ayah Kandung</label>
                        <input type="text" name="father_name" value="{{ old('father_name', $student->father_name) }}" placeholder="Nama lengkap tanpa gelar"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-bold">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Belakang</label>
                        <input type="text" name="father_title_suffix" value="{{ old('father_title_suffix', $student->father_title_suffix) }}" placeholder="S.Kom., M.M., S.T."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Ayah</label>
                        <input type="text" name="father_nik" value="{{ old('father_nik', $student->father_nik) }}" placeholder="16 digit NIK"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pendidikan Terakhir</label>
                        <select name="father_education" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $fe = old('father_education', $student->father_education ?? ''); @endphp
                            <option value="">-- Pilih --</option>
                            @foreach(['S3', 'S2', 'S1', 'Diploma 4', 'Diploma 3', 'Diploma 1', 'SMA/Sederajat', 'SMP/Sederajat', 'SD/Sederajat', 'Tidak Sekolah'] as $edu)
                                <option value="{{ $edu }}" {{ $fe == $edu ? 'selected' : '' }}>{{ $edu }}</option>
                            @endforeach
                            @if($fe && !in_array($fe, ['S3', 'S2', 'S1', 'Diploma 4', 'Diploma 3', 'Diploma 1', 'SMA/Sederajat', 'SMP/Sederajat', 'SD/Sederajat', 'Tidak Sekolah']))
                                <option value="{{ $fe }}" selected>{{ $fe }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                        <input type="text" name="father_birth_place" value="{{ old('father_birth_place', $student->father_birth_place) }}" placeholder="Kota kelahiran"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                        <input type="date" name="father_birth_date" value="{{ old('father_birth_date', $student->father_birth_date ? $student->father_birth_date->format('Y-m-d') : '') }}"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan</label>
                        <select name="father_job" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $fj = old('father_job', $student->father_job ?? ''); @endphp
                            <option value="">-- Pilih Pekerjaan --</option>
                            @foreach(['Karyawan Swasta', 'Wiraswasta', 'Wirausaha', 'PNS', 'TNI/Polri', 'Guru/Dosen', 'Dokter', 'Pegawai BUMN/BUMD', 'Tidak Bekerja', 'Pensiunan', 'Lainnya'] as $job)
                                <option value="{{ $job }}" {{ $fj == $job ? 'selected' : '' }}>{{ $job }}</option>
                            @endforeach
                            @if($fj && !in_array($fj, ['Karyawan Swasta', 'Wiraswasta', 'Wirausaha', 'PNS', 'TNI/Polri', 'Guru/Dosen', 'Dokter', 'Pegawai BUMN/BUMD', 'Tidak Bekerja', 'Pensiunan', 'Lainnya']))
                                <option value="{{ $fj }}" selected>{{ $fj }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Perusahaan Tempat Kerja</label>
                        <input type="text" name="father_company" value="{{ old('father_company', $student->father_company) }}" placeholder="Nama kantor / instansi"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penghasilan Rata-rata</label>
                        <input type="text" name="father_income" value="{{ old('father_income', $student->father_income) }}" placeholder="Rentang pendapatan"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="father_phone" value="{{ old('father_phone', $student->father_phone) }}" placeholder="08xxxxxxxxxx"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                    </div>
                </div>
            </div>

            <!-- 3.2 Profil Ibu Kandung -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                    <i data-lucide="user" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Data Ibu Kandung</h3>
                        <p class="text-[11px] text-slate-400">Profil lengkap ibu kandung untuk keperluan komunikasi harian.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Depan</label>
                        <input type="text" name="mother_title_prefix" value="{{ old('mother_title_prefix', $student->mother_title_prefix) }}" placeholder="Dr. / Dra."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ibu Kandung</label>
                        <input type="text" name="mother_name" value="{{ old('mother_name', $student->mother_name) }}" placeholder="Nama lengkap tanpa gelar"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-bold">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Belakang</label>
                        <input type="text" name="mother_title_suffix" value="{{ old('mother_title_suffix', $student->mother_title_suffix) }}" placeholder="S.Pd., M.Si."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Ibu</label>
                        <input type="text" name="mother_nik" value="{{ old('mother_nik', $student->mother_nik) }}" placeholder="16 digit NIK"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pendidikan Terakhir</label>
                        <select name="mother_education" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $me = old('mother_education', $student->mother_education ?? ''); @endphp
                            <option value="">-- Pilih --</option>
                            @foreach(['S3', 'S2', 'S1', 'Diploma 4', 'Diploma 3', 'Diploma 1', 'SMA/Sederajat', 'SMP/Sederajat', 'SD/Sederajat', 'Tidak Sekolah'] as $edu)
                                <option value="{{ $edu }}" {{ $me == $edu ? 'selected' : '' }}>{{ $edu }}</option>
                            @endforeach
                            @if($me && !in_array($me, ['S3', 'S2', 'S1', 'Diploma 4', 'Diploma 3', 'Diploma 1', 'SMA/Sederajat', 'SMP/Sederajat', 'SD/Sederajat', 'Tidak Sekolah']))
                                <option value="{{ $me }}" selected>{{ $me }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                        <input type="text" name="mother_birth_place" value="{{ old('mother_birth_place', $student->mother_birth_place) }}" placeholder="Kota kelahiran"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                        <input type="date" name="mother_birth_date" value="{{ old('mother_birth_date', $student->mother_birth_date ? $student->mother_birth_date->format('Y-m-d') : '') }}"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan</label>
                        <select name="mother_job" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $mj = old('mother_job', $student->mother_job ?? ''); @endphp
                            <option value="">-- Pilih Pekerjaan --</option>
                            @foreach(['Ibu Rumah Tangga', 'Guru/Dosen', 'Karyawan Swasta', 'Wiraswasta', 'Wirausaha', 'PNS', 'Dokter', 'TNI/Polri', 'Pegawai BUMN/BUMD', 'Tidak Bekerja', 'Lainnya'] as $job)
                                <option value="{{ $job }}" {{ $mj == $job ? 'selected' : '' }}>{{ $job }}</option>
                            @endforeach
                            @if($mj && !in_array($mj, ['Ibu Rumah Tangga', 'Guru/Dosen', 'Karyawan Swasta', 'Wiraswasta', 'Wirausaha', 'PNS', 'Dokter', 'TNI/Polri', 'Pegawai BUMN/BUMD', 'Tidak Bekerja', 'Lainnya']))
                                <option value="{{ $mj }}" selected>{{ $mj }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Perusahaan</label>
                        <input type="text" name="mother_company" value="{{ old('mother_company', $student->mother_company) }}" placeholder="Nama instansi / institusi"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penghasilan Rata-rata</label>
                        <input type="text" name="mother_income" value="{{ old('mother_income', $student->mother_income) }}" placeholder="Rentang pendapatan"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="mother_phone" value="{{ old('mother_phone', $student->mother_phone) }}" placeholder="08xxxxxxxxxx"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3.3 Data Wali & Kontak Darurat -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <div class="flex items-center gap-2.5">
                        <i data-lucide="shield" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Data Wali Murid (Bila Ada)</h3>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs">
                        <input type="checkbox" x-model="hasGuardian" class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Memiliki Wali</span>
                    </label>
                </div>

                <div x-show="hasGuardian" class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Depan</label>
                        <input type="text" name="guardian_title_prefix" value="{{ old('guardian_title_prefix', $student->guardian_title_prefix) }}" placeholder="H. / Dr."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Wali <span class="text-rose-500">*</span></label>
                        <input type="text" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}" placeholder="Nama wali lengkap"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-bold">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gelar Belakang</label>
                        <input type="text" name="guardian_title_suffix" value="{{ old('guardian_title_suffix', $student->guardian_title_suffix) }}" placeholder="S.E. / M.M."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan Keluarga</label>
                        <select name="guardian_relation" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $gr = old('guardian_relation', $student->guardian_relation ?? ''); @endphp
                            <option value="">-- Pilih Hubungan --</option>
                            @foreach(['Kakek', 'Nenek', 'Paman', 'Bibi', 'Kakak', 'Pengasuh / Pembantu', 'Ayah', 'Ibu', 'Lainnya'] as $rel)
                                <option value="{{ $rel }}" {{ $gr == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                            @endforeach
                            @if($gr && !in_array($gr, ['Kakek', 'Nenek', 'Paman', 'Bibi', 'Kakak', 'Pengasuh / Pembantu', 'Ayah', 'Ibu', 'Lainnya']))
                                <option value="{{ $gr }}" selected>{{ $gr }}</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NIK Wali</label>
                        <input type="text" name="guardian_nik" value="{{ old('guardian_nik', $student->guardian_nik) }}" placeholder="16 digit NIK"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pendidikan Terakhir</label>
                        <select name="guardian_education" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $ge = old('guardian_education', $student->guardian_education ?? ''); @endphp
                            <option value="">-- Pilih --</option>
                            @foreach(['S3', 'S2', 'S1', 'Diploma 4', 'Diploma 3', 'Diploma 1', 'SMA/Sederajat', 'SMP/Sederajat', 'SD/Sederajat', 'Tidak Sekolah'] as $edu)
                                <option value="{{ $edu }}" {{ $ge == $edu ? 'selected' : '' }}>{{ $edu }}</option>
                            @endforeach
                            @if($ge && !in_array($ge, ['S3', 'S2', 'S1', 'Diploma 4', 'Diploma 3', 'Diploma 1', 'SMA/Sederajat', 'SMP/Sederajat', 'SD/Sederajat', 'Tidak Sekolah']))
                                <option value="{{ $ge }}" selected>{{ $ge }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                        <input type="text" name="guardian_birth_place" value="{{ old('guardian_birth_place', $student->guardian_birth_place) }}" placeholder="Kota kelahiran"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                        <input type="date" name="guardian_birth_date" value="{{ old('guardian_birth_date', $student->guardian_birth_date ? $student->guardian_birth_date->format('Y-m-d') : '') }}"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. HP / WA Wali</label>
                        <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone) }}" placeholder="08xxxxxxxxxx"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan</label>
                        <select name="guardian_job" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $gj = old('guardian_job', $student->guardian_job ?? ''); @endphp
                            <option value="">-- Pilih Pekerjaan --</option>
                            @foreach(['Wiraswasta', 'Karyawan Swasta', 'PNS', 'Pensiunan', 'Tidak Bekerja', 'Lainnya'] as $job)
                                <option value="{{ $job }}" {{ $gj == $job ? 'selected' : '' }}>{{ $job }}</option>
                            @endforeach
                            @if($gj && !in_array($gj, ['Wiraswasta', 'Karyawan Swasta', 'PNS', 'Pensiunan', 'Tidak Bekerja', 'Lainnya']))
                                <option value="{{ $gj }}" selected>{{ $gj }}</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Perusahaan</label>
                        <input type="text" name="guardian_company" value="{{ old('guardian_company', $student->guardian_company) }}" placeholder="Nama instansi"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penghasilan Rata-rata</label>
                        <input type="text" name="guardian_income" value="{{ old('guardian_income', $student->guardian_income) }}" placeholder="Rentang penghasilan"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Didampingi Wali?</label>
                        <select name="is_accompanied_by_guardian" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            <option value="0" {{ old('is_accompanied_by_guardian', $student->is_accompanied_by_guardian) ? '' : 'selected' }}>Tidak, ananda tinggal dengan orang tua</option>
                            <option value="1" {{ old('is_accompanied_by_guardian', $student->is_accompanied_by_guardian) ? 'selected' : '' }}>Ya, ananda tinggal bersama wali</option>
                        </select>
                    </div>
                </div>

                <div x-show="!hasGuardian" class="text-center py-6 text-slate-400 text-xs italic">
                    Centang pilihan "Memiliki Wali" di atas apabila murid diasuh oleh wali resmi.
                </div>
            </div>

            <!-- Kontak Darurat -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                    <i data-lucide="phone-call" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Kontak Darurat (Emergency)</h3>
                        <p class="text-[11px] text-slate-400">Pihak yang dapat dihubungi segera bila orang tua tidak merespons.</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kontak Darurat</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $student->emergency_contact_name) }}" placeholder="Nama kerabat / orang terdekat"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Telepon Darurat</label>
                            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone) }}" placeholder="08xxxxxxxxxx"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan</label>
                            <input type="text" name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $student->emergency_contact_relation) }}" placeholder="Kakek / Nenek / Tante"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3.4 Dinamika Relasi & Iklim Agama -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="heart-handshake" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Kelekatan Hubungan & Iklim Agama Keluarga</h3>
                    <p class="text-[11px] text-slate-400">Kuesioner pola asuh dan interaksi sosial keluarga inti.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Ikatan Paling Dekat</label>
                    <select name="family_data[relationships][closest_bond]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $cb = old('family_data.relationships.closest_bond', $fd['relationships']['closest_bond'] ?? 'Bunda'); @endphp
                        <option value="Bunda" {{ $cb == 'Bunda' ? 'selected' : '' }}>Bunda</option>
                        <option value="Ayah" {{ $cb == 'Ayah' ? 'selected' : '' }}>Ayah</option>
                        <option value="Nenek" {{ $cb == 'Nenek' || str_contains($cb, 'Nenek') ? 'selected' : '' }}>Nenek / Kakek</option>
                        <option value="Bibi" {{ $cb == 'Bibi' ? 'selected' : '' }}>Bibi / Paman</option>
                        <option value="Baby Sitter" {{ $cb == 'Baby Sitter' || $cb == 'Baby Sister' ? 'selected' : '' }}>Pengasuh / Baby Sitter</option>
                        <option value="Lainnya" {{ !in_array($cb, ['Bunda', 'Ayah', 'Nenek', 'Bibi', 'Baby Sitter', 'Baby Sister']) ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan dengan Ayah</label>
                    <select name="family_data[relationships][rel_father]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $rf = old('family_data.relationships.rel_father', $fd['relationships']['rel_father'] ?? 'Erat'); @endphp
                        <option value="Erat" {{ $rf == 'Erat' ? 'selected' : '' }}>Erat</option>
                        <option value="Cukup" {{ $rf == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $rf == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        <option value="Tidak pernah" {{ $rf == 'Tidak pernah' ? 'selected' : '' }}>Tidak Pernah</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan dengan Ibu</label>
                    <select name="family_data[relationships][rel_mother]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $rm = old('family_data.relationships.rel_mother', $fd['relationships']['rel_mother'] ?? 'Erat'); @endphp
                        <option value="Erat" {{ $rm == 'Erat' ? 'selected' : '' }}>Erat</option>
                        <option value="Cukup" {{ $rm == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $rm == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan dgn Saudara</label>
                    <select name="family_data[relationships][rel_siblings]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $rs = old('family_data.relationships.rel_siblings', $fd['relationships']['rel_siblings'] ?? 'Erat'); @endphp
                        <option value="Erat" {{ $rs == 'Erat' ? 'selected' : '' }}>Erat</option>
                        <option value="Cukup" {{ $rs == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $rs == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        <option value="Belum punya saudara" {{ str_contains(strtolower($rs), 'belum') || str_contains(strtolower($rs), 'tidak ada') || str_contains(strtolower($rs), 'tunggal') ? 'selected' : '' }}>Belum Punya Saudara / Anak Tunggal</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan dgn Kakek & Nenek</label>
                    <select name="family_data[relationships][rel_grandparents]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $rgp = old('family_data.relationships.rel_grandparents', $fd['relationships']['rel_grandparents'] ?? 'Erat'); @endphp
                        <option value="Erat" {{ $rgp == 'Erat' ? 'selected' : '' }}>Erat</option>
                        <option value="Cukup" {{ $rgp == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $rgp == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        <option value="Sudah meninggal dunia" {{ str_contains(strtolower($rgp), 'meninggal') ? 'selected' : '' }}>Sudah Meninggal Dunia</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Komunikasi Ibu & Anak</label>
                    <select name="family_data[relationships][comm_mother]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $cm = old('family_data.relationships.comm_mother', $fd['relationships']['comm_mother'] ?? 'Sering'); @endphp
                        <option value="Sering" {{ $cm == 'Sering' ? 'selected' : '' }}>Sering</option>
                        <option value="Cukup" {{ $cm == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Jarang" {{ $cm == 'Jarang' ? 'selected' : '' }}>Jarang</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hubungan Teman Sebaya</label>
                    <select name="family_data[relationships][rel_peers]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $rp = old('family_data.relationships.rel_peers', $fd['relationships']['rel_peers'] ?? 'Erat'); @endphp
                        <option value="Erat" {{ $rp == 'Erat' ? 'selected' : '' }}>Erat</option>
                        <option value="Cukup" {{ $rp == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $rp == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        <option value="Jarang punya teman sebaya" {{ str_contains(strtolower($rp), 'jarang') ? 'selected' : '' }}>Jarang Ada Teman Sebaya</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kesempatan Bergaul Sebaya</label>
                    <select name="family_data[relationships][peer_opportunity]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $po = old('family_data.relationships.peer_opportunity', $fd['relationships']['peer_opportunity'] ?? 'Banyak'); @endphp
                        <option value="Banyak" {{ $po == 'Banyak' ? 'selected' : '' }}>Banyak</option>
                        <option value="Kurang" {{ $po == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        <option value="Sama sekali tidak ada" {{ $po == 'Sama sekali tidak ada' ? 'selected' : '' }}>Sama Sekali Tidak Ada</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Iklim Keagamaan Rumah</label>
                    <select name="family_data[religious_climate][climate]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $rc = old('family_data.religious_climate.climate', $fd['religious_climate']['climate'] ?? 'Sangat sehat'); @endphp
                        <option value="Sangat sehat" {{ $rc == 'Sangat sehat' ? 'selected' : '' }}>Sangat Sehat</option>
                        <option value="Cukup" {{ $rc == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $rc == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Peran Ibadah Keluarga</label>
                    <select name="family_data[religious_climate][family_worship]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $fw = old('family_data.religious_climate.family_worship', $fd['religious_climate']['family_worship'] ?? 'Tinggi'); @endphp
                        <option value="Tinggi" {{ $fw == 'Tinggi' ? 'selected' : '' }}>Tinggi</option>
                        <option value="Cukup" {{ $fw == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $fw == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Masjid Terdekat</label>
                    <select name="family_data[religious_climate][nearby_mosque]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $nm = old('family_data.religious_climate.nearby_mosque', $fd['religious_climate']['nearby_mosque'] ?? 'Ada'); @endphp
                        <option value="Ada" {{ $nm == 'Ada' ? 'selected' : '' }}>Ada</option>
                        <option value="Tidak ada" {{ $nm == 'Tidak ada' || $nm == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">TPQ Radius 300m</label>
                    <select name="family_data[religious_climate][nearby_tpq]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $ntpq = old('family_data.religious_climate.nearby_tpq', $fd['religious_climate']['nearby_tpq'] ?? 'Ada'); @endphp
                        <option value="Ada" {{ $ntpq == 'Ada' ? 'selected' : '' }}>Ada</option>
                        <option value="Tidak ada" {{ $ntpq == 'Tidak ada' || $ntpq == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-2">
            <button type="button" @click="activeTab = 'domisili'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Sebelumnya
            </button>
            <button type="button" @click="saveAndNext('kesehatan')" :disabled="isSaving"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-semibold rounded-xl transition-all shadow-xs disabled:opacity-50 cursor-pointer">
                <template x-if="!isSaving">
                    <span class="inline-flex items-center gap-2">
                        <span>Simpan & Lanjut ke Fisik & Kebiasaan</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </span>
                </template>
                <template x-if="isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Menyimpan Tab 3...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- TAB 4: FISIK, MEDIS & KEBIASAAN -->
    <div x-show="activeTab === 'kesehatan'" x-cloak class="space-y-6">
        <!-- 4.1 Antropometri Fisik -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="activity" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Antropometri & Ukuran Fisik Murid</h3>
                    <p class="text-[11px] text-slate-400">Parameter pertumbuhan fisik murid saat ini.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tinggi Badan (cm)</label>
                    <input type="number" step="0.1" name="height" value="{{ old('height', $student->height) }}" placeholder="105"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-bold">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Berat Badan (kg)</label>
                    <input type="number" step="0.1" name="weight" value="{{ old('weight', $student->weight) }}" placeholder="16.5"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-bold">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lingkar Kepala (cm)</label>
                    <input type="number" step="0.1" name="head_circumference" value="{{ old('head_circumference', $student->head_circumference) }}" placeholder="48"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lingkar Lengan / LILA (cm)</label>
                    <input type="number" step="0.1" name="arm_circumference" value="{{ old('arm_circumference', $student->arm_circumference) }}" placeholder="15"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- 4.2 Riwayat Kelahiran & Perkembangan Awal -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="baby" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Riwayat Kelahiran</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kondisi Waktu Hamil</label>
                        <select name="health_habits[birth_history][prenatal_condition]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $pnc = old('health_habits.birth_history.prenatal_condition', $hh['birth_history']['prenatal_condition'] ?? 'Normal'); @endphp
                            <option value="Normal" {{ $pnc == 'Normal' ? 'selected' : '' }}>Normal</option>
                            <option value="Ada gangguan" {{ $pnc == 'Ada gangguan' || str_contains(strtolower($pnc), 'gangguan') ? 'selected' : '' }}>Ada Gangguan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Usia Kandungan Kelahiran</label>
                        <select name="health_habits[birth_history][gestational_age]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $ga = old('health_habits.birth_history.gestational_age', $hh['birth_history']['gestational_age'] ?? 'Cukup bulan'); @endphp
                            <option value="Cukup bulan" {{ $ga == 'Cukup bulan' ? 'selected' : '' }}>Cukup Bulan</option>
                            <option value="Lewat bulan" {{ $ga == 'Lewat bulan' ? 'selected' : '' }}>Lewat Bulan</option>
                            <option value="Premature" {{ $ga == 'Premature' || $ga == 'Prematur' ? 'selected' : '' }}>Premature</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Cara Persalinan</label>
                        <select name="health_habits[birth_history][delivery_method]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $dm = old('health_habits.birth_history.delivery_method', $hh['birth_history']['delivery_method'] ?? 'Normal (spontan)'); @endphp
                            <option value="Normal (spontan)" {{ str_contains(strtolower($dm), 'spontan') || $dm == 'Normal' ? 'selected' : '' }}>Normal (Spontan)</option>
                            <option value="Normal dengan vacuum" {{ str_contains(strtolower($dm), 'vacuum') ? 'selected' : '' }}>Normal dengan Vacuum</option>
                            <option value="Forcep/Sectio Caesar" {{ str_contains(strtolower($dm), 'caesar') || str_contains(strtolower($dm), 'sc') ? 'selected' : '' }}>Forcep / Sectio Caesar (SC)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alasan Tindakan Medis</label>
                        <input type="text" name="health_habits[birth_history][delivery_reason]" value="{{ old('health_habits.birth_history.delivery_reason', $hh['birth_history']['delivery_reason'] ?? '') }}" placeholder="Alasan SC bila ada"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Berat & Panjang Lahir</label>
                        <input type="text" name="health_habits[birth_history][birth_weight_length]" value="{{ old('health_habits.birth_history.birth_weight_length', $hh['birth_history']['birth_weight_length'] ?? '') }}" placeholder="Contoh: 3.2 kg / 50 cm"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Milestone -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="milestone" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Milestone Pertumbuhan</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Ananda Disusui Oleh</label>
                        <select name="health_habits[milestones][breastfed_by]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $bfb = old('health_habits.milestones.breastfed_by', $hh['milestones']['breastfed_by'] ?? 'Ibu kandung'); @endphp
                            <option value="Ibu kandung" {{ strtolower($bfb) == 'ibu kandung' ? 'selected' : '' }}>Ibu Kandung (ASI)</option>
                            <option value="susu formula" {{ strtolower($bfb) == 'susu formula' ? 'selected' : '' }}>Susu Formula</option>
                            <option value="ASI & Susu Formula" {{ str_contains(strtolower($bfb), 'asi') && str_contains(strtolower($bfb), 'formula') ? 'selected' : '' }}>ASI & Susu Formula</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi ASI Eksklusif</label>
                        <input type="text" name="health_habits[milestones][breastfed_duration]" value="{{ old('health_habits.milestones.breastfed_duration', $hh['milestones']['breastfed_duration'] ?? '') }}" placeholder="2 tahun / 6 bulan"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mulai Susu Formula</label>
                        <input type="text" name="health_habits[milestones][formula_start]" value="{{ old('health_habits.milestones.formula_start', $hh['milestones']['formula_start'] ?? '') }}" placeholder="Usia pemberian sufor"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Awal MPASI & Menu</label>
                        <input type="text" name="health_habits[milestones][mpasi_start]" value="{{ old('health_habits.milestones.mpasi_start', $hh['milestones']['mpasi_start'] ?? '') }}" placeholder="6 bulan / bubur buah"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tumbuh Gigi Pertama</label>
                        <input type="text" name="health_habits[milestones][teeth_age]" value="{{ old('health_habits.milestones.teeth_age', $hh['milestones']['teeth_age'] ?? '') }}" placeholder="8 bulan"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mulai Berjalan</label>
                        <input type="text" name="health_habits[milestones][walking_age]" value="{{ old('health_habits.milestones.walking_age', $hh['milestones']['walking_age'] ?? '') }}" placeholder="12 bulan"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mulai Berbicara</label>
                        <input type="text" name="health_habits[milestones][talking_age]" value="{{ old('health_habits.milestones.talking_age', $hh['milestones']['talking_age'] ?? '') }}" placeholder="10 bulan"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Gangguan Perkembangan</label>
                        <input type="text" name="health_habits[milestones][developmental_issues]" value="{{ old('health_habits.milestones.developmental_issues', $hh['milestones']['developmental_issues'] ?? '') }}" placeholder="Bila ada"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- 4.3 Medis & Alergi -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="shield-alert" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Riwayat Medis, Imunisasi & Alergi</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="sm:col-span-4">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Imunisasi yang Pernah Dilakukan</label>
                    <input type="text" name="health_habits[medical_history][immunizations]" value="{{ old('health_habits.medical_history.immunizations', $hh['medical_history']['immunizations'] ?? '') }}" placeholder="BCG, DPT, Polio, Campak, Hepatitis, PCV, dll"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-semibold">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penyakit Berat Pernah Diderita</label>
                    <input type="text" name="health_habits[medical_history][severe_diseases]" value="{{ old('health_habits.medical_history.severe_diseases', $hh['medical_history']['severe_diseases'] ?? '') }}" placeholder="Typus / DB / Pneumonia / Tidak"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Penyakit Sering Dialami</label>
                    <input type="text" name="health_habits[medical_history][frequent_diseases]" value="{{ old('health_habits.medical_history.frequent_diseases', $hh['medical_history']['frequent_diseases'] ?? '') }}" placeholder="Batuk / Pilek / Demam"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alergi Makanan</label>
                    <input type="text" name="health_habits[medical_history][food_allergy]" value="{{ old('health_habits.medical_history.food_allergy', $hh['medical_history']['food_allergy'] ?? '') }}" placeholder="Udang / Telur / Kacang / Tidak ada"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Reaksi & Penanganan Alergi</label>
                    <input type="text" name="health_habits[medical_history][allergy_reaction]" value="{{ old('health_habits.medical_history.allergy_reaction', $hh['medical_history']['allergy_reaction'] ?? '') }}" placeholder="Gatal-gatal / Diberi antihistamin"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alergi Minuman</label>
                    <input type="text" name="health_habits[medical_history][drink_allergy]" value="{{ old('health_habits.medical_history.drink_allergy', $hh['medical_history']['drink_allergy'] ?? '') }}" placeholder="Susu sapi / Dingin / Tidak ada"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Dokter / RS Rujukan</label>
                    <input type="text" name="health_habits[medical_history][preferred_doctor]" value="{{ old('health_habits.medical_history.preferred_doctor', $hh['medical_history']['preferred_doctor'] ?? '') }}" placeholder="Dokter spesialis anak / RS"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alternatif Pengobatan</label>
                    <input type="text" name="health_habits[medical_history][treatment_alternative]" value="{{ old('health_habits.medical_history.treatment_alternative', $hh['medical_history']['treatment_alternative'] ?? '') }}" placeholder="Madu / Herbal / Istirahat"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div class="sm:col-span-4">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pesan Khusus Kesehatan untuk Pihak Sekolah</label>
                    <textarea name="health_habits[medical_history][school_health_notes]" rows="2" placeholder="Catatan pengawasan saat beraktivitas fisik, tangga, atau istirahat..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">{{ old('health_habits.medical_history.school_health_notes', $hh['medical_history']['school_health_notes'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 4.4 Toilet Training, Makan & Tidur -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Toilet Training -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-3 text-xs">
                <h4 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <i data-lucide="bath" class="w-4 h-4 text-indigo-500"></i>
                    Toilet & Higienitas
                </h4>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Masih Pakai Diapers?</label>
                    <select name="health_habits[toilet_training][uses_diapers]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $ud = old('health_habits.toilet_training.uses_diapers', $hh['toilet_training']['uses_diapers'] ?? 'Tidak'); @endphp
                        <option value="Tidak" {{ str_contains(strtolower($ud), 'tidak') ? 'selected' : '' }}>Tidak</option>
                        <option value="Hanya saat tidur" {{ str_contains(strtolower($ud), 'tidur') ? 'selected' : '' }}>Hanya Saat Tidur</option>
                        <option value="Ya" {{ strtolower($ud) == 'ya' ? 'selected' : '' }}>Ya (Sepanjang Hari)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Apakah Mengompol dalam Tidur?</label>
                    <select name="health_habits[toilet_training][bedwetting]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $bw = old('health_habits.toilet_training.bedwetting', $hh['toilet_training']['bedwetting'] ?? 'Tidak Pernah'); @endphp
                        <option value="Tidak Pernah" {{ $bw == 'Tidak Pernah' ? 'selected' : '' }}>Tidak Pernah</option>
                        <option value="Jarang" {{ $bw == 'Jarang' ? 'selected' : '' }}>Jarang</option>
                        <option value="Sering" {{ $bw == 'Sering' ? 'selected' : '' }}>Sering</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Kebiasaan Menggosok Gigi</label>
                    <select name="health_habits[toilet_training][brush_habit]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $bh = old('health_habits.toilet_training.brush_habit', $hh['toilet_training']['brush_habit'] ?? 'Terbiasa'); @endphp
                        <option value="Terbiasa" {{ $bh == 'Terbiasa' ? 'selected' : '' }}>Terbiasa</option>
                        <option value="Jarang" {{ $bh == 'Jarang' ? 'selected' : '' }}>Jarang</option>
                        <option value="Belum terbiasa" {{ $bh == 'Belum terbiasa' ? 'selected' : '' }}>Belum Terbiasa</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Frekuensi Gosok Gigi Sehari</label>
                    <select name="health_habits[toilet_training][brush_frequency]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $bfreq = old('health_habits.toilet_training.brush_frequency', $hh['toilet_training']['brush_frequency'] ?? '2 kali'); @endphp
                        <option value="2 kali" {{ $bfreq == '2 kali' ? 'selected' : '' }}>2 kali sehari</option>
                        <option value="3 kali" {{ $bfreq == '3 kali' ? 'selected' : '' }}>3 kali sehari</option>
                        <option value="1 kali" {{ $bfreq == '1 kali' ? 'selected' : '' }}>1 kali sehari</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Sewaktu Gosok Gigi Apakah Dibantu?</label>
                    <select name="health_habits[toilet_training][brush_assistance]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $bass = old('health_habits.toilet_training.brush_assistance', $hh['toilet_training']['brush_assistance'] ?? 'Sudah mandiri'); @endphp
                        <option value="Sudah mandiri" {{ $bass == 'Sudah mandiri' ? 'selected' : '' }}>Sudah Mandiri</option>
                        <option value="Jarang" {{ $bass == 'Jarang' ? 'selected' : '' }}>Jarang Dibantu</option>
                        <option value="Selalu" {{ $bass == 'Selalu' ? 'selected' : '' }}>Selalu Dibantu</option>
                    </select>
                </div>
            </div>

            <!-- Pola Makan -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-3 text-xs">
                <h4 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <i data-lucide="utensils" class="w-4 h-4 text-emerald-500"></i>
                    Kebiasaan Makan & Minum
                </h4>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Pagi</label>
                        <select name="health_habits[meals][breakfast_appetite]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $bfa = old('health_habits.meals.breakfast_appetite', $hh['meals']['breakfast_appetite'] ?? 'Baik'); @endphp
                            <option value="Baik" {{ $bfa == 'Baik' ? 'selected' : '' }}>Baik</option>
                            <option value="Cukup" {{ $bfa == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                            <option value="Kurang" {{ $bfa == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Siang</label>
                        <select name="health_habits[meals][lunch_appetite]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $lna = old('health_habits.meals.lunch_appetite', $hh['meals']['lunch_appetite'] ?? 'Baik'); @endphp
                            <option value="Baik" {{ $lna == 'Baik' ? 'selected' : '' }}>Baik</option>
                            <option value="Cukup" {{ $lna == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                            <option value="Kurang" {{ $lna == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Malam</label>
                        <select name="health_habits[meals][dinner_appetite]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $dna = old('health_habits.meals.dinner_appetite', $hh['meals']['dinner_appetite'] ?? 'Baik'); @endphp
                            <option value="Baik" {{ $dna == 'Baik' ? 'selected' : '' }}>Baik</option>
                            <option value="Cukup" {{ $dna == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                            <option value="Kurang" {{ $dna == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Waktu Makan Dilakukan Sambil</label>
                    <select name="health_habits[meals][eating_manner]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $em = old('health_habits.meals.eating_manner', $hh['meals']['eating_manner'] ?? 'Duduk'); @endphp
                        <option value="Duduk" {{ $em == 'Duduk' ? 'selected' : '' }}>Duduk Tenang</option>
                        <option value="Bermain" {{ $em == 'Bermain' ? 'selected' : '' }}>Bermain</option>
                        <option value="Berdiri" {{ $em == 'Berdiri' ? 'selected' : '' }}>Berdiri</option>
                        <option value="Jalan - jalan" {{ $em == 'Jalan - jalan' ? 'selected' : '' }}>Jalan-jalan</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Berdoa Sebelum & Sesudah Makan</label>
                    <select name="health_habits[meals][meal_prayer]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $mp = old('health_habits.meals.meal_prayer', $hh['meals']['meal_prayer'] ?? 'Senantiasa'); @endphp
                        <option value="Senantiasa" {{ $mp == 'Senantiasa' ? 'selected' : '' }}>Senantiasa</option>
                        <option value="Jarang" {{ $mp == 'Jarang' ? 'selected' : '' }}>Jarang</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Camilan / Makan Di Antara Waktu di Atas</label>
                    <select name="health_habits[meals][snack_appetite]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $sa = old('health_habits.meals.snack_appetite', $hh['meals']['snack_appetite'] ?? 'Cukup'); @endphp
                        <option value="Sedikit" {{ $sa == 'Sedikit' ? 'selected' : '' }}>Sedikit</option>
                        <option value="Cukup" {{ $sa == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Banyak" {{ $sa == 'Banyak' ? 'selected' : '' }}>Banyak</option>
                        <option value="Sulit makan" {{ $sa == 'Sulit makan' ? 'selected' : '' }}>Sulit Makan</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Makanan Favorit</label>
                    <input type="text" name="health_habits[meals][favorite]" value="{{ old('health_habits.meals.favorite', $hh['meals']['favorite'] ?? '') }}" placeholder="Ayam goreng, sup sayur..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>
            </div>

            <!-- Pola Tidur -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-3 text-xs">
                <h4 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <i data-lucide="moon" class="w-4 h-4 text-purple-500"></i>
                    Pola & Kebiasaan Tidur
                </h4>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Tidur Bersama</label>
                    <select name="health_habits[sleeping][sleeping_with]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $sw = old('health_habits.sleeping.sleeping_with', $hh['sleeping']['sleeping_with'] ?? 'Bersama orang tua'); @endphp
                        <option value="Bersama orang tua" {{ $sw == 'Bersama orang tua' ? 'selected' : '' }}>Bersama Orang Tua</option>
                        <option value="Bersama saudara" {{ $sw == 'Bersama saudara' ? 'selected' : '' }}>Bersama Saudara</option>
                        <option value="Sendirian" {{ $sw == 'Sendirian' ? 'selected' : '' }}>Sendirian</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Apakah Mengigau dalam Tidur?</label>
                    <select name="health_habits[sleeping][night_terrors]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $nt = old('health_habits.sleeping.night_terrors', $hh['sleeping']['night_terrors'] ?? 'Tidak Pernah'); @endphp
                        <option value="Tidak Pernah" {{ $nt == 'Tidak Pernah' ? 'selected' : '' }}>Tidak Pernah</option>
                        <option value="Jarang" {{ $nt == 'Jarang' ? 'selected' : '' }}>Jarang</option>
                        <option value="Sering" {{ $nt == 'Sering' ? 'selected' : '' }}>Sering</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Doa Sebelum - Sesudah Tidur</label>
                    <select name="health_habits[sleeping][sleep_prayer]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $sp = old('health_habits.sleeping.sleep_prayer', $hh['sleeping']['sleep_prayer'] ?? 'Selalu'); @endphp
                        <option value="Selalu" {{ $sp == 'Selalu' ? 'selected' : '' }}>Selalu</option>
                        <option value="Jarang" {{ $sp == 'Jarang' ? 'selected' : '' }}>Jarang</option>
                        <option value="Belum" {{ $sp == 'Belum' ? 'selected' : '' }}>Belum</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Jam Tidur Malam / Bangun Pagi</label>
                    <input type="text" name="health_habits[sleeping][bedtime]" value="{{ old('health_habits.sleeping.bedtime', $hh['sleeping']['bedtime'] ?? '') }}" placeholder="Pukul 20.30 / Bangun 06.00"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-medium text-slate-600 dark:text-slate-400 mb-1">Kebiasaan Tidur Siang</label>
                    <input type="text" name="health_habits[sleeping][nap_time]" value="{{ old('health_habits.sleeping.nap_time', $hh['sleeping']['nap_time'] ?? '') }}" placeholder="1-2 jam di siang hari"
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-2">
            <button type="button" @click="activeTab = 'keluarga'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Sebelumnya
            </button>
            <button type="button" @click="saveAndNext('psikologi')" :disabled="isSaving"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-semibold rounded-xl transition-all shadow-xs disabled:opacity-50 cursor-pointer">
                <template x-if="!isSaving">
                    <span class="inline-flex items-center gap-2">
                        <span>Simpan & Lanjut ke Tumbuh Kembang</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </span>
                </template>
                <template x-if="isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Menyimpan Tab 4...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- TAB 5: TUMBUH KEMBANG, MINAT & KOMITMEN -->
    <div x-show="activeTab === 'psikologi'" x-cloak class="space-y-6">
        <!-- 5.1 Karakter & Tingkah Laku -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="smile" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Karakter, Tingkah Laku & Kepatuhan</h3>
                    <p class="text-[11px] text-slate-400">Observasi kepribadian ananda saat berinteraksi di lingkungan sosial.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Karakter / Sifat Utama Ananda</label>
                    <input type="text" name="development_psychology[traits][character_traits]" value="{{ old('development_psychology.traits.character_traits', $dp['traits']['character_traits'] ?? '') }}" placeholder="Periang, ceria, agak pemalu di awal..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-medium">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kepatuhan Ananda di Rumah</label>
                    <select name="development_psychology[traits][obedience]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        @php $ob = old('development_psychology.traits.obedience', $dp['traits']['obedience'] ?? 'Baik'); @endphp
                        <option value="Baik" {{ $ob == 'Baik' ? 'selected' : '' }}>Baik</option>
                        <option value="Cukup" {{ $ob == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                        <option value="Kurang" {{ $ob == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tingkah Laku (Kelebihan & Hal yang Perlu Perhatian)</label>
                    <textarea name="development_psychology[traits][behavior_notes]" rows="2" placeholder="Catatan kebiasaan positif dan tantangan perilaku yang dihadapi orang tua..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">{{ old('development_psychology.traits.behavior_notes', $dp['traits']['behavior_notes'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- 5.2 Kebiasaan Bermain & Mainan -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="gamepad-2" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Kebiasaan Bermain & Preferensi Mainan</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kebiasaan Bermain</label>
                    <input type="text" name="development_psychology[play][play_habits]" value="{{ old('development_psychology.play.play_habits', $dp['play']['play_habits'] ?? '') }}" placeholder="Menyusun balok, mewarnai, bermain peran..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Bermain</label>
                    <input type="text" name="development_psychology[play][play_location]" value="{{ old('development_psychology.play.play_location', $dp['play']['play_location'] ?? '') }}" placeholder="Di dalam rumah, teras, taman..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Teman Bermain</label>
                    <input type="text" name="development_psychology[play][playmates]" value="{{ old('development_psychology.play.playmates', $dp['play']['playmates'] ?? '') }}" placeholder="Saudara kandung, anak tetangga sebaya..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mainan yang Disenangi</label>
                    <input type="text" name="development_psychology[play][favorite_toys]" value="{{ old('development_psychology.play.favorite_toys', $dp['play']['favorite_toys'] ?? '') }}" placeholder="Balok susun, mobil-mobilan..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mainan yang Tidak Disukai</label>
                    <input type="text" name="development_psychology[play][disliked_toys]" value="{{ old('development_psychology.play.disliked_toys', $dp['play']['disliked_toys'] ?? '') }}" placeholder="Suara keras, topeng seram..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mainan Dinilai Berbahaya</label>
                    <input type="text" name="development_psychology[play][dangerous_toys]" value="{{ old('development_psychology.play.dangerous_toys', $dp['play']['dangerous_toys'] ?? '') }}" placeholder="Benda tajam, partikel kecil..."
                        class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- 5.3 Screen Time, Gadget & Cita-cita -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="tv" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Screen Time & Televisi</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Frekuensi Melihat Televisi</label>
                        <select name="development_psychology[screen_time][tv_frequency]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $tvf = old('development_psychology.screen_time.tv_frequency', $dp['screen_time']['tv_frequency'] ?? 'Jarang'); @endphp
                            <option value="Tidak pernah" {{ $tvf == 'Tidak pernah' ? 'selected' : '' }}>Tidak Pernah</option>
                            <option value="Jarang" {{ $tvf == 'Jarang' ? 'selected' : '' }}>Jarang</option>
                            <option value="Sering" {{ $tvf == 'Sering' ? 'selected' : '' }}>Sering</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Apakah Didampingi Saat Nonton?</label>
                        <select name="development_psychology[screen_time][tv_accompanied]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $tva = old('development_psychology.screen_time.tv_accompanied', $dp['screen_time']['tv_accompanied'] ?? 'Didampingi'); @endphp
                            <option value="Didampingi" {{ $tva == 'Didampingi' ? 'selected' : '' }}>Didampingi</option>
                            <option value="Tidak didampingi" {{ $tva == 'Tidak didampingi' ? 'selected' : '' }}>Tidak Didampingi</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Konsentrasi Saat Menonton TV</label>
                        <select name="development_psychology[screen_time][tv_concentration]" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $tvc = old('development_psychology.screen_time.tv_concentration', $dp['screen_time']['tv_concentration'] ?? 'Fokus'); @endphp
                            <option value="Fokus" {{ $tvc == 'Fokus' ? 'selected' : '' }}>Fokus</option>
                            <option value="Kurang fokus" {{ $tvc == 'Kurang fokus' ? 'selected' : '' }}>Kurang Fokus</option>
                            <option value="Tidak fokus" {{ $tvc == 'Tidak fokus' ? 'selected' : '' }}>Tidak Fokus</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pendamping Utama</label>
                        <input type="text" name="development_psychology[screen_time][tv_companion]" value="{{ old('development_psychology.screen_time.tv_companion', $dp['screen_time']['tv_companion'] ?? '') }}" placeholder="Bunda / Ayah / ART"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Fasilitas Game & Sejenisnya</label>
                        <input type="text" name="development_psychology[screen_time][game_facility]" value="{{ old('development_psychology.screen_time.game_facility', $dp['screen_time']['game_facility'] ?? '') }}" placeholder="Playstation, Game HP, Nintendo..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Akses Nonton YouTube</label>
                        <input type="text" name="development_psychology[screen_time][youtube_access]" value="{{ old('development_psychology.screen_time.youtube_access', $dp['screen_time']['youtube_access'] ?? '') }}" placeholder="Bersama orang tua / Dibatasi..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Acara TV / Tayangan Favorit</label>
                        <input type="text" name="development_psychology[screen_time][favorite_tv]" value="{{ old('development_psychology.screen_time.favorite_tv', $dp['screen_time']['favorite_tv'] ?? '') }}" placeholder="Upin Ipin, Tayo, Ms Rachel"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Acara TV Tidak Disukai</label>
                        <input type="text" name="development_psychology[screen_time][disliked_tv]" value="{{ old('development_psychology.screen_time.disliked_tv', $dp['screen_time']['disliked_tv'] ?? '') }}" placeholder="Berita keras, adegan tegang"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Cita-cita & Minat -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <i data-lucide="compass" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Cita-cita, Minat & Riwayat Sekolah</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Cita-cita Ananda</label>
                        <select name="student_aspiration" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-medium">
                            @php $asp = old('student_aspiration', $student->student_aspiration ?? ''); @endphp
                            <option value="">-- Pilih Cita-cita --</option>
                            @foreach(['Dokter', 'Guru/Dosen', 'TNI/Polri', 'Wiraswasta', 'Seni/Lukis/Artis/Sejenis', 'Pilot', 'Arsitek', 'Lainnya'] as $cita)
                                <option value="{{ $cita }}" {{ $asp == $cita ? 'selected' : '' }}>{{ $cita }}</option>
                            @endforeach
                            @if($asp && !in_array($asp, ['Dokter', 'Guru/Dosen', 'TNI/Polri', 'Wiraswasta', 'Seni/Lukis/Artis/Sejenis', 'Pilot', 'Arsitek', 'Lainnya']))
                                <option value="{{ $asp }}" selected>{{ $asp }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hobi Siswa</label>
                        <select name="student_hobby" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            @php $hb = old('student_hobby', $student->student_hobby ?? ''); @endphp
                            <option value="">-- Pilih Hobi --</option>
                            @foreach(['Olah Raga', 'Kesenian', 'Membaca', 'Menulis', 'Traveling', 'Lainnya'] as $hobby)
                                <option value="{{ $hobby }}" {{ $hb == $hobby ? 'selected' : '' }}>{{ $hobby }}</option>
                            @endforeach
                            @if($hb && !in_array($hb, ['Olah Raga', 'Kesenian', 'Membaca', 'Menulis', 'Traveling', 'Lainnya']))
                                <option value="{{ $hb }}" selected>{{ $hb }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Sekolah Asal Sebelumnya</label>
                        <input type="text" name="previous_school" value="{{ old('previous_school', $student->previous_school) }}" placeholder="Nama KB / Daycare / Belum Pernah"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pernah PAUD Non-Formal?</label>
                        <select name="is_paud_formal_before" class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                            <option value="0" {{ old('is_paud_formal_before', $student->is_paud_formal_before) ? '' : 'selected' }}>Tidak</option>
                            <option value="1" {{ old('is_paud_formal_before', $student->is_paud_formal_before) ? 'selected' : '' }}>Ya</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Rencana Sekolah Lanjutan (Setelah Lulus)</label>
                        <input type="text" name="planned_school_after" value="{{ old('planned_school_after', $student->planned_school_after) }}" placeholder="SD Anak Saleh / SD Negeri / MI"
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Harapan / Cita-cita Orang Tua Terhadap Ananda</label>
                        <input type="text" name="parent_aspiration" value="{{ old('parent_aspiration', $student->parent_aspiration) }}" placeholder="Menjadi anak sholeh/sholehah, mandiri, dan berakhlak mulia..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- 5.4 Komitmen & Bentuk Tindak Lanjut Asesmen (Checkbox List) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <i data-lucide="check-square" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Komitmen Asesmen & Aspirasi Orang Tua</h3>
                    <p class="text-[11px] text-slate-400">Persetujuan tindak lanjut jika pada asesmen 3 bulan awal ditemukan indikasi hambatan tumbuh kembang.</p>
                </div>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kesediaan Menindaklanjuti Asesmen Perkembangan</label>
                    <select name="development_psychology[commitments][assessment_agreement]" class="w-full sm:w-80 rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">
                        <option value="Bersedia" {{ old('development_psychology.commitments.assessment_agreement', $dp['commitments']['assessment_agreement'] ?? 'Bersedia') == 'Bersedia' ? 'selected' : '' }}>Bersedia</option>
                        <option value="Tidak Bersedia" {{ old('development_psychology.commitments.assessment_agreement', $dp['commitments']['assessment_agreement'] ?? '') == 'Tidak Bersedia' ? 'selected' : '' }}>Tidak Bersedia</option>
                    </select>
                </div>

                <!-- Checkbox List: Bentuk Tindak Lanjut yang Disetujui -->
                <div class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 space-y-2.5">
                    <label class="block font-bold text-slate-800 dark:text-slate-200">
                        Bentuk Tindak Lanjut yang Ayah-Bunda Setujui: <span class="text-[11px] font-normal text-slate-400">(Bisa pilih lebih dari satu)</span>
                    </label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 pt-1">
                        @foreach($followupOptions as $key => $label)
                            @php
                                $isChecked = in_array($key, $agreedFollowupList) || in_array($label, $agreedFollowupList);
                                if (!$isChecked) {
                                    // fuzzy check for prefix
                                    foreach ($agreedFollowupList as $af) {
                                        if (str_starts_with($af, substr($key, 0, 2))) {
                                            $isChecked = true;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <label class="flex items-start gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-850 cursor-pointer transition-colors">
                                <input type="checkbox" name="development_psychology[commitments][agreed_followup_types][]" value="{{ $key }}"
                                    {{ $isChecked ? 'checked' : '' }}
                                    class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 mt-0.5">
                                <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Harapan Kemampuan Setelah Lulus PAUD</label>
                        <textarea name="development_psychology[commitments][paud_outcome_expectation]" rows="2" placeholder="Harapan kemampuan kemandirian, ibadah, sosialisasi..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">{{ old('development_psychology.commitments.paud_outcome_expectation', $dp['commitments']['paud_outcome_expectation'] ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Saran & Masukan Orang Tua untuk Sekolah</label>
                        <textarea name="development_psychology[commitments][parent_suggestions]" rows="2" placeholder="Masukan dan harapan orang tua untuk fasilitas dan kegiatan..."
                            class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500">{{ old('development_psychology.commitments.parent_suggestions', $dp['commitments']['parent_suggestions'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-2">
            <button type="button" @click="activeTab = 'kesehatan'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Sebelumnya
            </button>
            <button type="button" @click="saveAndNext('dokumen')" :disabled="isSaving"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-semibold rounded-xl transition-all shadow-xs disabled:opacity-50 cursor-pointer">
                <template x-if="!isSaving">
                    <span class="inline-flex items-center gap-2">
                        <span>Simpan & Lanjut ke Berkas & Dokumen</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </span>
                </template>
                <template x-if="isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Menyimpan Tab 5...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- TAB 6: BERKAS & DOKUMEN DIGITAL (Physical Upload with Interception & Auto-Compression) -->
    <div x-show="activeTab === 'dokumen'" x-cloak class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-5">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="folder-archive" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Upload Berkas & Dokumen Fisik / Scan</h3>
                        <p class="text-[11px] text-slate-400">Upload langsung berkas pendaftaran. Sistem menerapkan kompresi otomatis (resize & WebP 80) agar ukuran file hemat dan tidak membebani storage.</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/50">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    Auto-Compress Active
                </span>
            </div>

            @php
                $documentItems = [
                    'nisn_scan' => [
                        'title' => 'Scan Kartu / Bukti NISN',
                        'desc' => 'File gambar (JPG, PNG) atau PDF kartu NISN atau bukti verifikasi Kemdikbud.',
                        'icon' => 'file-text',
                        'color' => 'indigo',
                    ],
                    'previous_school_cert' => [
                        'title' => 'Scan Surat Pindah / Ijazah Lama',
                        'desc' => 'Scan surat mutasi atau sertifikat KB / jenjang sekolah sebelumnya bila ada.',
                        'icon' => 'school',
                        'color' => 'amber',
                    ],
                    'kia_scan' => [
                        'title' => 'Scan Kartu Identitas Anak (KIA)',
                        'desc' => 'Foto atau scan KIA resmi anak dari Disdukcapil.',
                        'icon' => 'id-card',
                        'color' => 'blue',
                    ],
                    'last_report_card' => [
                        'title' => 'Scan Rapot Terakhir',
                        'desc' => 'Buku laporan perkembangan anak semester terakhir (bila pindahan).',
                        'icon' => 'book-marked',
                        'color' => 'emerald',
                    ],
                    'assessment_file' => [
                        'title' => 'Scan Lembar Asesmen / DDTK / Psikolog',
                        'desc' => 'Catatan deteksi dini tumbuh kembang atau rekomendasi dokter/psikolog.',
                        'icon' => 'clipboard-check',
                        'color' => 'purple',
                    ],
                    'kms_book' => [
                        'title' => 'Scan Buku KMS / Kartu Posyandu',
                        'desc' => 'Scan halaman grafik pertumbuhan & catatan imunisasi anak.',
                        'icon' => 'activity',
                        'color' => 'rose',
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                @foreach($documentItems as $docKey => $meta)
                    @php
                        $existingDocUrl = $docs[$docKey] ?? null;
                        $isPdf = $existingDocUrl && str_ends_with(strtolower(parse_url($existingDocUrl, PHP_URL_PATH) ?? ''), '.pdf');
                        $isLocalStorageImage = $existingDocUrl && str_starts_with($existingDocUrl, '/storage/') && !$isPdf;
                        $isExternalLink = $existingDocUrl && str_starts_with($existingDocUrl, 'http');
                    @endphp
                    <div x-data="{
                        fileName: '',
                        previewSrc: '{{ $isLocalStorageImage ? $existingDocUrl : '' }}',
                        isExisting: {{ $existingDocUrl ? 'true' : 'false' }},
                        isExternal: {{ $isExternalLink ? 'true' : 'false' }},
                        existingUrl: '{{ $existingDocUrl ?? '' }}',
                        fileType: '{{ $isPdf ? 'pdf' : ($isLocalStorageImage ? 'image' : ($isExternalLink ? 'link' : '')) }}'
                    }" class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <i data-lucide="{{ $meta['icon'] }}" class="w-4 h-4 text-{{ $meta['color'] }}-500"></i>
                                {{ $meta['title'] }}
                            </label>
                            <template x-if="isExisting && existingUrl">
                                <a :href="existingUrl" target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 hover:underline">
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                    Lihat Berkas
                                </a>
                            </template>
                        </div>

                        <p class="text-[11px] text-slate-400">{{ $meta['desc'] }}</p>

                        <!-- Preview Container -->
                        <div x-show="previewSrc || fileName || (isExisting && existingUrl)" class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                            <template x-if="previewSrc">
                                <img :src="previewSrc" alt="Preview Dokumen" class="w-12 h-12 object-cover rounded-lg border border-slate-200 dark:border-slate-700 shrink-0">
                            </template>
                            <template x-if="!previewSrc && (fileType === 'pdf' || (fileName && fileName.endsWith('.pdf')))">
                                <div class="w-12 h-12 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="file-text" class="w-6 h-6"></i>
                                </div>
                            </template>
                            <template x-if="!previewSrc && fileType === 'link' && !fileName">
                                <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-950/60 border border-blue-200 text-blue-600 flex items-center justify-center shrink-0">
                                    <i data-lucide="cloud" class="w-6 h-6"></i>
                                </div>
                            </template>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-700 dark:text-slate-300 block truncate" x-text="fileName ? fileName : (isExisting ? (isExternal ? 'Tersimpan (Google Drive Cloud)' : 'Berkas Fisik Tersimpan') : 'File')"></span>
                                <span class="text-[10px] text-slate-400" x-text="fileName ? 'Siap diupload & dioptimasi' : (isExisting ? (isExternal ? 'Tautan cloud aktif' : 'Tersimpan di server lokal') : '')"></span>
                            </div>
                        </div>

                        <!-- File Input -->
                        <div>
                            <input type="file" name="document_files[{{ $docKey }}]" accept="image/jpeg,image/png,image/webp,application/pdf"
                                @change="
                                    const f = $event.target.files[0];
                                    if (f) {
                                        fileName = f.name;
                                        if (f.type.startsWith('image/')) {
                                            fileType = 'image';
                                            previewSrc = URL.createObjectURL(f);
                                        } else {
                                            fileType = 'pdf';
                                            previewSrc = '';
                                        }
                                    }
                                "
                                class="block w-full text-[11px] text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300 cursor-pointer">
                        </div>

                        <!-- Fallback / External link field (collapsible) -->
                        <details class="text-[10.5px] text-slate-400 pt-1">
                            <summary class="cursor-pointer hover:text-slate-600 dark:hover:text-slate-300">Tautan Dokumen Eksternal / Google Drive (Opsional)</summary>
                            <div class="mt-2">
                                <input type="text" name="documents[{{ $docKey }}]" value="{{ old("documents.$docKey", $docs[$docKey] ?? '') }}" placeholder="https://drive.google.com/..."
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:ring-indigo-500 font-mono">
                            </div>
                        </details>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-between items-center pt-2">
            <button type="button" @click="activeTab = 'psikologi'"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Sebelumnya
            </button>
            <button type="button" @click="saveAndNext(null, true)" :disabled="isSaving"
                class="inline-flex items-center gap-2.5 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-semibold rounded-xl shadow-md transition-all disabled:opacity-50 cursor-pointer">
                <template x-if="!isSaving">
                    <span class="inline-flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Simpan Seluruh Data Murid</span>
                    </span>
                </template>
                <template x-if="isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Menyimpan Seluruh Data...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

</div>
