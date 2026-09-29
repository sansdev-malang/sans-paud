<x-admin-layout>
    <div class="p-6 space-y-6" x-data="reportEditApp({
        studentName: '{{ addslashes($student->nickname ?: $student->full_name) }}',
        templates: {{ Js::from($templates) }}
    })">

        <!-- HEADER & STUDENT INFO BAR -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 font-bold text-lg flex items-center justify-center shrink-0">
                    {{ $student->avatar_initials }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-50">
                            {{ $student->full_name }}
                        </h2>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/50 dark:border-indigo-800">
                            {{ $student->classroom?->name ?? 'Kelompok Belajar' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        NIS: <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $student->nis }}</span> | 
                        Jenjang: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $student->classroom?->classLevel?->name ?? 'Fase Fondasi' }}</span> | 
                        Periode: <span class="font-bold text-slate-800 dark:text-slate-200">T.A. {{ $reportCard->academicYear?->name ?? '2026/2027' }} (Semester {{ $semester }})</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('report-cards.index', ['classroom_id' => $student->classroom_id, 'academic_year_id' => $academicYearId, 'semester' => $semester]) }}"
                    class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Kembali ke Daftar
                </a>
                @if($reportCard->exists)
                    <a href="{{ route('report-cards.show', $reportCard->id) }}" target="_blank"
                        class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        Pratinjau Cetak Rapor
                    </a>
                @endif
            </div>
        </section>

        <!-- MAIN EDIT FORM -->
        <form method="POST" action="{{ route('report-cards.update', $student->id) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
            <input type="hidden" name="semester" value="{{ $semester }}">
            <input type="hidden" name="entry_mode" :value="entryMode">

            <!-- BANNER CATATAN REVISI KEPALA SEKOLAH (JIKA ADA) -->
            @if($reportCard->status === 'revision' || $reportCard->revision_notes)
                <div class="bg-rose-50/90 dark:bg-rose-950/40 border-2 border-rose-300 dark:border-rose-800/80 rounded-2xl p-5 shadow-xs flex items-start gap-4 animate-card">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    </div>
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-rose-900 dark:text-rose-200">
                                    Catatan Revisi dari Kepala Sekolah
                                </h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-200 text-rose-900 dark:bg-rose-900 dark:text-rose-100">
                                    ⚠️ Perlu Perbaikan
                                </span>
                            </div>
                            @if($reportCard->rejected_at)
                                <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">
                                    Dikembalikan pada: {{ $reportCard->rejected_at->format('d M Y, H:i') }}
                                </span>
                            @endif
                        </div>
                        <div class="p-3.5 bg-white/90 dark:bg-slate-900/90 border border-rose-200 dark:border-rose-900/60 rounded-xl text-xs text-rose-950 dark:text-rose-100 whitespace-pre-line leading-relaxed font-medium">
                            {{ $reportCard->revision_notes }}
                        </div>
                        <p class="text-[11px] text-rose-600 dark:text-rose-400">
                            💡 <strong>Petunjuk untuk Guru / Wali Kelas:</strong> Silakan lengkapi / perbaiki poin catatan di atas, kemudian klik tombol <strong class="text-rose-700 dark:text-rose-300">"Ajukan ke Kepala Sekolah"</strong> di bagian bawah agar direview ulang.
                        </p>
                    </div>
                </div>
            @endif

            <!-- MODE PENGISIAN RAPOR (SWITCHER) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">Pilih Metode Pengisian Rapor</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="entryMode === 'pdf' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'"
                                x-text="entryMode === 'pdf' ? 'Mode Unggah PDF (Bypass)' : 'Mode Formulir Naratif'">
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Guru / Wali Kelas dapat memilih ingin mengetik narasi capaian digital atau langsung mengunggah file PDF rapor yang telah disusun sebelumnya.</p>
                    </div>

                    <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl shrink-0">
                        <button type="button" @click="entryMode = 'form'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
                            :class="entryMode === 'form' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            1. Formulir Naratif Digital
                        </button>

                        <button type="button" @click="entryMode = 'pdf'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
                            :class="entryMode === 'pdf' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'">
                            <i data-lucide="file-up" class="w-3.5 h-3.5"></i>
                            2. Unggah Berkas PDF (Bypass)
                        </button>
                    </div>
                </div>
            </div>

            <!-- SECTION: UNGGAH PDF RAPOR (JIKA MODE PDF DIPILIH) -->
            <div x-show="entryMode === 'pdf'" class="space-y-6" style="display: none;">
                <!-- Upload Dropzone Card -->
                <div class="bg-white dark:bg-slate-900 border-2 border-dashed border-purple-300 dark:border-purple-800/80 rounded-2xl p-6 md:p-8 shadow-xs space-y-4 text-center bg-purple-50/20 dark:bg-purple-950/10">
                    <div class="max-w-md mx-auto space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400 flex items-center justify-center mx-auto shadow-inner">
                            <i data-lucide="file-up" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Unggah Dokumen PDF Rapor Ananda</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Pilih file PDF rapor yang telah siap dibagikan ke wali murid (Maksimal 15 MB).
                            </p>
                        </div>

                        <!-- Dropzone input -->
                        <div class="relative mt-3">
                            <input type="file" name="pdf_file" accept=".pdf,application/pdf" @change="handlePdfFileChange($event)"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="px-5 py-4 bg-white dark:bg-slate-900 border border-purple-200 dark:border-purple-800 rounded-xl shadow-xs flex items-center justify-center gap-2 text-xs font-semibold text-purple-700 dark:text-purple-300 hover:border-purple-400 transition-colors">
                                <i data-lucide="upload" class="w-4 h-4 text-purple-500"></i>
                                <span x-text="uploadedPdfName || 'Pilih Berkas PDF dari Komputer'"></span>
                            </div>
                        </div>

                        @if($reportCard->pdf_file)
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center justify-between text-left">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                    <div>
                                        <p class="text-xs font-bold text-emerald-900 dark:text-emerald-300">File PDF Rapor Aktif Tersedia</p>
                                        <p class="text-[10px] text-emerald-700 dark:text-emerald-400 font-mono">{{ basename($reportCard->pdf_file) }}</p>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $reportCard->pdf_file) }}" target="_blank"
                                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center gap-1">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    Buka File PDF
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Snapshot Pertumbuhan & Data Pelengkap (Opsional saat mode PDF) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50 border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                        <span>Data Fisik & Kehadiran (Pelengkap)</span>
                        <span class="text-[11px] font-normal text-slate-400">Opsional untuk ringkasan portal</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Tinggi Badan (TB)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="height" value="{{ $reportCard->height }}" placeholder="105.5"
                                    class="w-full h-9 pl-3 pr-10 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                                <span class="absolute right-3 top-2 text-[11px] text-slate-400">cm</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Berat Badan (BB)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="weight" value="{{ $reportCard->weight }}" placeholder="17.2"
                                    class="w-full h-9 pl-3 pr-10 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                                <span class="absolute right-3 top-2 text-[11px] text-slate-400">kg</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Lingkar Kepala (LK)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="head_circumference" value="{{ $reportCard->head_circumference }}" placeholder="49.0"
                                    class="w-full h-9 pl-3 pr-10 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                                <span class="absolute right-3 top-2 text-[11px] text-slate-400">cm</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rekap Kehadiran -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        <h4 class="text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2">Rekapitulasi Ketidakhadiran (Hari)</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Sakit (S)</label>
                                <input type="number" name="attendance_sick" value="{{ $reportCard->attendance_sick ?? 0 }}" min="0"
                                    class="w-full h-8 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Izin (I)</label>
                                <input type="number" name="attendance_permission" value="{{ $reportCard->attendance_permission ?? 0 }}" min="0"
                                    class="w-full h-8 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Tanpa Keterangan (A)</label>
                                <input type="number" name="attendance_unexcused" value="{{ $reportCard->attendance_unexcused ?? 0 }}" min="0"
                                    class="w-full h-8 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Snapshot Pengesahan saat PDF -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50 border-b border-slate-100 dark:border-slate-800 pb-3">
                        Data Pengesahan & Tanda Tangan
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat & Tanggal Rapor</label>
                            <div class="flex gap-2">
                                <input type="text" name="place" value="{{ $reportCard->place ?? 'Malang' }}" class="w-1/2 h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                                <input type="date" name="report_date" value="{{ $reportCard->report_date ? $reportCard->report_date->format('Y-m-d') : date('Y-m-d') }}" class="w-1/2 h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Wali Kelas</label>
                            <input type="text" name="homeroom_teacher_name" value="{{ $reportCard->homeroom_teacher_name ?: ($student->classroom?->homeroomTeacher?->name ?? '') }}"
                                class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kepala Sekolah</label>
                            <input type="text" name="principal_name" value="{{ $reportCard->principal_name ?: setting('principal_name', 'Ustadzah Kepala Sekolah, S.Pd') }}"
                                class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: FORMULIR NARATIF DIGITAL (JIKA MODE FORM DIPILIH) -->
            <div x-show="entryMode === 'form'" class="space-y-6">
                <!-- TABS NAVIGATION -->
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                    <button type="button" @click="activeTab = 'cp'"
                        class="px-4 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-2"
                        :class="activeTab === 'cp' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        1. Capaian Pembelajaran (CP)
                    </button>

                    <button type="button" @click="activeTab = 'p5_growth'"
                        class="px-4 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-2"
                        :class="activeTab === 'p5_growth' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                        2. Projek P5 & Tumbuh Kembang
                    </button>

                    <button type="button" @click="activeTab = 'services'"
                        class="px-4 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-2"
                        :class="activeTab === 'services' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'">
                        <i data-lucide="shapes" class="w-4 h-4"></i>
                        3. Layanan Daycare & TPQ
                    </button>

                    <button type="button" @click="activeTab = 'photos_reflection'"
                        class="px-4 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer flex items-center gap-2"
                        :class="activeTab === 'photos_reflection' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        4. Dokumentasi Foto & Pesan Guru
                    </button>
                </div>

                <!-- TAB 1: CAPAIAN PEMBELAJARAN (NABP, JATI DIRI, STEAM) -->
                <div x-show="activeTab === 'cp'" class="space-y-6">
                    <!-- 1. Nilai Agama & Budi Pekerti (NABP) -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs">
                                    1
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Elemen Nilai Agama dan Budi Pekerti (NABP)</h3>
                                <p class="text-[11px] text-slate-400">Praktik ibadah harian, doa, sholat, pembiasaan adab islami, dan kasih sayang sesama ciptaan Allah.</p>
                            </div>
                        </div>
                        <button type="button" @click="openTemplateDrawer('NABP')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 rounded-lg text-xs font-semibold transition-colors border border-emerald-200 dark:border-emerald-800 cursor-pointer">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            Pilih Contoh Narasi
                        </button>
                    </div>

                    <textarea name="nabp_narrative" x-model="form.nabp" rows="4" placeholder="Tuliskan narasi perkembangan capaian Nilai Agama dan Budi Pekerti ananda selama semester ini..."
                        class="w-full p-3.5 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs leading-relaxed text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>

                <!-- 2. Jati Diri -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                                2
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Elemen Jati Diri (Sosial Emosional & Fisik Motorik)</h3>
                                <p class="text-[11px] text-slate-400">Kemandirian diri, regulasi emosi, interaksi sosial, motorik kasar (senam/berlari) dan halus (menggunting/meronce).</p>
                            </div>
                        </div>
                        <button type="button" @click="openTemplateDrawer('JATI_DIRI')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold transition-colors border border-indigo-200 dark:border-indigo-800 cursor-pointer">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            Pilih Contoh Narasi
                        </button>
                    </div>

                    <textarea name="jati_diri_narrative" x-model="form.jati_diri" rows="4" placeholder="Tuliskan narasi perkembangan jati diri, kemandirian, emosi, dan motorik ananda..."
                        class="w-full p-3.5 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs leading-relaxed text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></textarea>
                </div>

                <!-- 3. Dasar-Dasar Literasi & STEAM -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xs">
                                3
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Elemen Dasar-Dasar Literasi, Matematika, Sains, & STEAM</h3>
                                <p class="text-[11px] text-slate-400">Keaksaraan awal, logika berhitung, eksplorasi sains, kreasi seni, loose-parts, dan pemecahan masalah.</p>
                            </div>
                        </div>
                        <button type="button" @click="openTemplateDrawer('STEAM')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-semibold transition-colors border border-amber-200 dark:border-amber-800 cursor-pointer">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            Pilih Contoh Narasi
                        </button>
                    </div>

                    <textarea name="steam_narrative" x-model="form.steam" rows="4" placeholder="Tuliskan narasi perkembangan literasi, matematika awal, sains dan ekspresi seni ananda..."
                        class="w-full p-3.5 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs leading-relaxed text-slate-900 dark:text-slate-50 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"></textarea>
                </div>
            </div>

            <!-- TAB 2: PROJEK P5 & TUMBUH KEMBANG -->
            <div x-show="activeTab === 'p5_growth'" class="space-y-6" style="display: none;">
                <!-- Projek Penguatan Profil Pelajar Pancasila (P5) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Projek Penguatan Profil Pelajar Pancasila (P5)</h3>
                            <p class="text-[11px] text-slate-400">Deskripsi keterlibatan ananda dalam tema projek semester (misal: Aku Sayang Bumi, Aku Cinta Indonesia, dll).</p>
                        </div>
                        <button type="button" @click="openTemplateDrawer('P5')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 rounded-lg text-xs font-semibold transition-colors border border-indigo-200 dark:border-indigo-800">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            Contoh Projek P5
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Tema / Judul Projek P5 Semester Ini</label>
                            <input type="text" name="p5_project_name" x-model="form.p5_title" placeholder="Contoh: Projek: Aku Sayang Bumi (Kebun Cilik Anak Saleh)"
                                class="w-full h-9 px-3 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-900 dark:text-slate-50">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Narasi Capaian Projek P5</label>
                            <textarea name="p5_narrative" x-model="form.p5_narrative" rows="4" placeholder="Tuliskan narasi partisipasi dan dimensi profil pelajar pancasila ananda selama kegiatan projek..."
                                class="w-full p-3 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-slate-50"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Pertumbuhan Fisik & Kesehatan -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50 border-b border-slate-100 dark:border-slate-800 pb-3">
                        Pertumbuhan Fisik & Rekap Kehadiran
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Tinggi Badan (TB)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="height" value="{{ $reportCard->height }}" placeholder="105.5"
                                    class="w-full h-9 pl-3 pr-10 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                                <span class="absolute right-3 top-2 text-[11px] text-slate-400">cm</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Berat Badan (BB)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="weight" value="{{ $reportCard->weight }}" placeholder="17.2"
                                    class="w-full h-9 pl-3 pr-10 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                                <span class="absolute right-3 top-2 text-[11px] text-slate-400">kg</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Lingkar Kepala (LK)</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="head_circumference" value="{{ $reportCard->head_circumference }}" placeholder="49.0"
                                    class="w-full h-9 pl-3 pr-10 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                                <span class="absolute right-3 top-2 text-[11px] text-slate-400">cm</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rekap Kehadiran -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        <h4 class="text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2">Rekapitulasi Ketidakhadiran (Hari)</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Sakit (S)</label>
                                <input type="number" name="attendance_sick" value="{{ $reportCard->attendance_sick ?? 0 }}" min="0"
                                    class="w-full h-8 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Izin (I)</label>
                                <input type="number" name="attendance_permission" value="{{ $reportCard->attendance_permission ?? 0 }}" min="0"
                                    class="w-full h-8 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Tanpa Keterangan (A)</label>
                                <input type="number" name="attendance_unexcused" value="{{ $reportCard->attendance_unexcused ?? 0 }}" min="0"
                                    class="w-full h-8 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono text-slate-900 dark:text-slate-50">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: LAYANAN DAYCARE & TPQ -->
            <div x-show="activeTab === 'services'" class="space-y-6" style="display: none;">
                <!-- Layanan Daycare / TPA -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">👶</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Laporan Tumbuh Kembang & Pola Daycare (TPA)</h3>
                                <p class="text-[11px] text-slate-400">Pola makan, tidur siang, kemandirian toilet training, dan stimulasi sensorik harian.</p>
                            </div>
                        </div>
                        <button type="button" @click="openTemplateDrawer('DAYCARE')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 rounded-lg text-xs font-semibold transition-colors border border-purple-200 dark:border-purple-800">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            Contoh Daycare
                        </button>
                    </div>

                    <textarea name="daycare_narrative" x-model="form.daycare" rows="4" placeholder="Tuliskan catatan pola makan, tidur, dan pembiasaan ananda di Daycare..."
                        class="w-full p-3.5 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-slate-50"></textarea>
                </div>

                <!-- Capaian TPQ / Al-Qur'an -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📖</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Capaian Keagamaan & TPQ Anak Saleh</h3>
                                <p class="text-[11px] text-slate-400">Pencapaian jilid tilawati, hafalan surat pendek, doa harian, dan hadits pilihan.</p>
                            </div>
                        </div>
                        <button type="button" @click="openTemplateDrawer('TPQ')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-lg text-xs font-semibold transition-colors border border-emerald-200 dark:border-emerald-800">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            Contoh TPQ
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Capaian Jilid Mengaji</label>
                            <input type="text" name="tpq_jilid" value="{{ $reportCard->tpq_jilid }}" placeholder="Tilawati Jilid 2 Hal 15"
                                class="w-full h-9 px-3 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-900 dark:text-slate-50">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Hafalan Surat Pendek</label>
                            <input type="text" name="tpq_surah" value="{{ $reportCard->tpq_surah }}" placeholder="An-Nas s/d Al-Kafirun"
                                class="w-full h-9 px-3 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-900 dark:text-slate-50">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Hafalan Doa & Hadits</label>
                            <input type="text" name="tpq_hadith_doa" value="{{ $reportCard->tpq_hadith_doa }}" placeholder="Doa Makan, Hadits Kasih Sayang"
                                class="w-full h-9 px-3 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg text-xs text-slate-900 dark:text-slate-50">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Catatan / Evaluasi Guru TPQ</label>
                        <textarea name="tpq_narrative" x-model="form.tpq" rows="3" placeholder="Tuliskan catatan kemajuan makhraj, kelancaran tajwid, dan motivasi mengaji..."
                            class="w-full p-3 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-slate-50"></textarea>
                    </div>
                </div>
            </div>

            <!-- TAB 4: DOKUMENTASI FOTO & PESAN GURU -->
            <div x-show="activeTab === 'photos_reflection'" class="space-y-6" style="display: none;">
                <!-- Pesan & Motivasi Wali Kelas -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Refleksi & Pesan Wali Kelas</h3>
                            <p class="text-[11px] text-slate-400">Apresiasi dan saran tindak lanjut pendampingan ananda untuk Ayah dan Bunda di rumah.</p>
                        </div>
                        <button type="button" @click="openTemplateDrawer('TEACHER_NOTES')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-lg text-xs font-semibold transition-colors border border-emerald-200 dark:border-emerald-800">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            Contoh Pesan
                        </button>
                    </div>

                    <textarea name="teacher_notes" x-model="form.teacher_notes" rows="4" placeholder="Tuliskan pesan motivasi dan apresiasi untuk ananda serta orang tua..."
                        class="w-full p-3.5 bg-slate-50/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs leading-relaxed text-slate-900 dark:text-slate-50"></textarea>
                </div>

                <!-- Foto Dokumentasi Kegiatan -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50 border-b border-slate-100 dark:border-slate-800 pb-3">
                        Dokumentasi Foto Kegiatan Ananda (Opsional)
                    </h3>
                    <p class="text-xs text-slate-400">Foto kegiatan ananda saat belajar, berkarya, atau beribadah yang akan dicetak pada halaman lampiran rapor.</p>

                    @if(!empty($reportCard->photos))
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($reportCard->photos as $p)
                                <div class="relative group rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 aspect-4/3">
                                    <img src="{{ asset('storage/' . $p) }}" alt="Foto Kegiatan" class="w-full h-full object-cover">
                                    <input type="hidden" name="existing_photos[]" value="{{ $p }}">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1 text-xs">Unggah Foto Baru (Bisa pilih 1-4 foto)</label>
                        <input type="file" name="photos[]" multiple accept="image/*"
                            class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950/50 dark:file:text-emerald-400 cursor-pointer">
                    </div>
                </div>

                <!-- Snapshot Pengesahan -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50 border-b border-slate-100 dark:border-slate-800 pb-3">
                        Data Pengesahan & Tanda Tangan
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat & Tanggal Rapor</label>
                            <div class="flex gap-2">
                                <input type="text" name="place" value="{{ $reportCard->place ?? 'Malang' }}" class="w-1/2 h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                                <input type="date" name="report_date" value="{{ $reportCard->report_date ? $reportCard->report_date->format('Y-m-d') : date('Y-m-d') }}" class="w-1/2 h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Wali Kelas</label>
                            <input type="text" name="homeroom_teacher_name" value="{{ $reportCard->homeroom_teacher_name ?: ($student->classroom?->homeroomTeacher?->name ?? '') }}"
                                class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kepala Sekolah</label>
                            <input type="text" name="principal_name" value="{{ $reportCard->principal_name ?: setting('principal_name', 'Ustadzah Kepala Sekolah, S.Pd') }}"
                                class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTION FOOTER BAR (STICKY) -->
            <div class="sticky bottom-4 z-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">Status Rapor:</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                        :class="{
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400': formStatus === 'published',
                            'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400': formStatus === 'approved',
                            'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-400': formStatus === 'submitted',
                            'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400': formStatus === 'revision' || formStatus === 'rejected',
                            'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400': formStatus === 'draft'
                        }"
                        x-text="formStatus === 'published' ? 'TERBIT (PORTAL ORTU)' : (formStatus === 'approved' ? 'DISETUJUI KEPALA SEKOLAH' : (formStatus === 'submitted' ? 'DIAJUKAN KE KEPALA SEKOLAH' : (formStatus === 'revision' || formStatus === 'rejected' ? 'PERLU REVISI' : 'DRAFT PENGISIAN')))">
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" name="status" value="draft" @click="formStatus = 'draft'"
                        class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                        Simpan Draft
                    </button>

                    <button type="submit" name="status" value="submitted" @click="formStatus = 'submitted'"
                        class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-xl text-xs font-semibold transition-colors cursor-pointer">
                        Ajukan ke Kepala Sekolah
                    </button>

                    <button type="submit" name="status" value="approved" @click="formStatus = 'approved'"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                        Setujui Rapor (KS)
                    </button>

                    <button type="submit" name="status" value="published" @click="formStatus = 'published'"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Terbitkan ke Portal
                    </button>
                </div>
            </div>
        </form>

        <!-- DRAWER MODAL: BANK CONTOH NARASI -->
        <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-[9999] flex justify-end" style="display: none; background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px);">
            <div @click.outside="drawerOpen = false" class="w-full max-w-lg bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 h-full p-6 shadow-2xl flex flex-col justify-between overflow-y-auto">
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4 text-amber-500"></i>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Bank Contoh Narasi Capaian</h3>
                        </div>
                        <button type="button" @click="drawerOpen = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <p class="text-xs text-slate-400">Pilih rekomendasi narasi untuk langsung dimasukkan ke kolom teks dan sesuaikan dengan perkembangan ananda.</p>

                    <!-- Template Items List -->
                    <div class="space-y-3">
                        <template x-for="t in activeTemplates" :key="t.id">
                            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 space-y-2 hover:border-emerald-500 transition-colors">
                                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="t.title"></h4>
                                <p class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-400" x-text="t.sample_narrative"></p>
                                <div class="pt-1 flex justify-end">
                                    <button type="button" @click="useTemplate(t.sample_narrative)"
                                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[11px] font-semibold transition-colors">
                                        Gunakan Narasi Ini
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="drawerOpen = false" class="px-4 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg text-xs font-semibold">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function reportEditApp(config) {
            return {
                entryMode: '{{ $reportCard->entry_mode ?: ($reportCard->pdf_file ? 'pdf' : 'form') }}',
                uploadedPdfName: '{{ $reportCard->pdf_file ? basename($reportCard->pdf_file) : '' }}',
                activeTab: 'cp',
                drawerOpen: false,
                currentCategory: '',
                formStatus: '{{ $reportCard->status ?? "draft" }}',
                studentName: config.studentName || 'Ananda',
                templates: config.templates || {},
                activeTemplates: [],

                form: {
                    nabp: `{{ addslashes($reportCard->nabp_narrative ?? '') }}`,
                    jati_diri: `{{ addslashes($reportCard->jati_diri_narrative ?? '') }}`,
                    steam: `{{ addslashes($reportCard->steam_narrative ?? '') }}`,
                    p5_title: `{{ addslashes($reportCard->p5_project_name ?? '') }}`,
                    p5_narrative: `{{ addslashes($reportCard->p5_narrative ?? '') }}`,
                    daycare: `{{ addslashes($reportCard->daycare_narrative ?? '') }}`,
                    tpq: `{{ addslashes($reportCard->tpq_narrative ?? '') }}`,
                    teacher_notes: `{{ addslashes($reportCard->teacher_notes ?? '') }}`
                },

                handlePdfFileChange(event) {
                    const file = event.target.files[0];
                    this.uploadedPdfName = file ? file.name : '';
                },

                openTemplateDrawer(category) {
                    this.currentCategory = category;
                    this.activeTemplates = this.templates[category] || [];
                    this.drawerOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                useTemplate(text) {
                    const formatted = text.replace(/ananda/gi, this.studentName);
                    
                    if (this.currentCategory === 'NABP') {
                        this.form.nabp = this.form.nabp ? this.form.nabp + "\n\n" + formatted : formatted;
                    } else if (this.currentCategory === 'JATI_DIRI') {
                        this.form.jati_diri = this.form.jati_diri ? this.form.jati_diri + "\n\n" + formatted : formatted;
                    } else if (this.currentCategory === 'STEAM') {
                        this.form.steam = this.form.steam ? this.form.steam + "\n\n" + formatted : formatted;
                    } else if (this.currentCategory === 'P5') {
                        this.form.p5_narrative = this.form.p5_narrative ? this.form.p5_narrative + "\n\n" + formatted : formatted;
                    } else if (this.currentCategory === 'DAYCARE') {
                        this.form.daycare = this.form.daycare ? this.form.daycare + "\n\n" + formatted : formatted;
                    } else if (this.currentCategory === 'TPQ') {
                        this.form.tpq = this.form.tpq ? this.form.tpq + "\n\n" + formatted : formatted;
                    } else if (this.currentCategory === 'TEACHER_NOTES') {
                        this.form.teacher_notes = this.form.teacher_notes ? this.form.teacher_notes + "\n\n" + formatted : formatted;
                    }

                    this.drawerOpen = false;
                }
            };
        }
    </script>
</x-admin-layout>
