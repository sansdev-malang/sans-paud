<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Digital: {{ $student->full_name }} | SANS PAUD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen p-4 md:p-8">

    <div class="max-w-3xl mx-auto space-y-6">

        <!-- TOP BAR -->
        <div class="flex items-center justify-between">
            <a href="{{ route('portal.report-cards.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                &larr; Ganti Periode / Keluar
            </a>

            <a href="{{ route('portal.report-cards.print', ['id' => $reportCard->id, 'token' => request('token')]) }}" target="_blank"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ $reportCard->entry_mode === 'pdf' ? 'Unduh / Buka Dokumen PDF' : 'Unduh / Cetak Versi PDF' }}
            </a>
        </div>

        <!-- PROFILE CARD -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-left">
            <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 font-bold text-2xl flex items-center justify-center shrink-0 shadow-inner">
                {{ $student->avatar_initials }}
            </div>
            <div class="space-y-1">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <h1 class="text-xl font-extrabold text-slate-900 dark:text-slate-50">{{ $student->full_name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:border-emerald-800">
                        {{ $student->classroom?->name ?? 'Kelompok' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    NIS: <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $student->nis }}</span> &bull;
                    Wali Kelas: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $reportCard->homeroom_teacher_name ?: ($student->classroom?->homeroomTeacher?->name ?? '-') }}</span>
                </p>
                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 pt-1">
                    Tahun Ajaran {{ $reportCard->academicYear?->name }} ({{ $reportCard->semester_label }})
                </p>
            </div>
        </div>

        <!-- CAPAIAN PEMBELAJARAN CARDS / PDF VIEWER -->
        @if($reportCard->entry_mode === 'pdf' && $reportCard->pdf_file)
            <!-- MODE BERKAS PDF -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-slate-900 border border-purple-200 dark:border-purple-800/60 rounded-3xl p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400 flex items-center justify-center font-bold text-xl shrink-0 border border-purple-100 dark:border-purple-900/50">
                                📄
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900 dark:text-slate-50">Dokumen Berkas Rapor Resmi (PDF)</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Rapor resmi ananda telah diterbitkan dalam format dokumen PDF.</p>
                            </div>
                        </div>

                        <a href="{{ asset('storage/' . $reportCard->pdf_file) }}" target="_blank"
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-sm transition-colors inline-flex items-center gap-2 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Unduh / Buka Dokumen PDF
                        </a>
                    </div>

                    <!-- PDF Embedded Viewer -->
                    <div class="w-full h-[650px] md:h-[800px] rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 shadow-inner">
                        <iframe src="{{ asset('storage/' . $reportCard->pdf_file) }}" class="w-full h-full border-0">
                            <p class="p-6 text-center text-xs text-slate-500">
                                Browser Anda tidak mendukung pratinjau PDF langsung. Silakan klik tombol di atas untuk mengunduh.
                            </p>
                        </iframe>
                    </div>
                </div>

                <!-- Pertumbuhan & Kehadiran (Jika diisi) -->
                @if($reportCard->height || $reportCard->weight || $reportCard->attendance_sick || $reportCard->attendance_permission || $reportCard->attendance_unexcused)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if($reportCard->height || $reportCard->weight)
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-2">
                                <h3 class="text-xs font-bold text-slate-900 dark:text-slate-50 uppercase tracking-wider">Pertumbuhan Fisik</h3>
                                <div class="space-y-1 text-xs">
                                    <div class="flex justify-between"><span class="text-slate-500">Tinggi Badan:</span> <strong class="font-mono">{{ $reportCard->height ? $reportCard->height . ' cm' : '-' }}</strong></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Berat Badan:</span> <strong class="font-mono">{{ $reportCard->weight ? $reportCard->weight . ' kg' : '-' }}</strong></div>
                                    @if($reportCard->nutritional_status)
                                        <div class="flex justify-between pt-1 border-t border-slate-100 dark:border-slate-800"><span class="text-slate-500">Status Gizi:</span> <strong class="text-emerald-600 dark:text-emerald-400">{{ $reportCard->nutritional_status }}</strong></div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-2">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-slate-50 uppercase tracking-wider">Rekap Kehadiran</h3>
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between"><span class="text-slate-500">Sakit:</span> <strong class="font-mono">{{ $reportCard->attendance_sick }} hari</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500">Izin:</span> <strong class="font-mono">{{ $reportCard->attendance_permission }} hari</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500">Tanpa Keterangan:</span> <strong class="font-mono">{{ $reportCard->attendance_unexcused }} hari</strong></div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Pesan Guru -->
                @if($reportCard->teacher_notes)
                    <div class="bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl p-6 shadow-xs space-y-2">
                        <h3 class="text-xs font-bold text-emerald-900 dark:text-emerald-300 uppercase tracking-wider">Refleksi & Pesan Guru Wali Kelas:</h3>
                        <p class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed italic text-justify">
                            "{{ $reportCard->teacher_notes }}"
                        </p>
                    </div>
                @endif
            </div>
        @else
            <!-- MODE FORMULIR NARATIF DIGITAL -->
            <div class="space-y-4">
                <h2 class="text-xs font-bold text-slate-500 uppercase tracking-widest px-2">Capaian Pembelajaran Ananda</h2>

                <!-- 1. NABP -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold">1</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Nilai Agama dan Budi Pekerti (NABP)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 text-justify pt-1 pl-8">
                        {{ $reportCard->nabp_narrative ?: 'Ananda telah menunjukkan pembiasaan beribadah dan akhlak islami yang baik selama semester ini.' }}
                    </p>
                </div>

                <!-- 2. Jati Diri -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-800 flex items-center justify-center text-xs font-bold">2</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Jati Diri (Kemandirian & Motorik)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 text-justify pt-1 pl-8">
                        {{ $reportCard->jati_diri_narrative ?: 'Ananda berkembang mandiri, aktif bersosialisasi, dan memiliki koordinasi motorik yang lincah.' }}
                    </p>
                </div>

                <!-- 3. STEAM -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center text-xs font-bold">3</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Dasar-Dasar Literasi & STEAM</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 text-justify pt-1 pl-8">
                        {{ $reportCard->steam_narrative ?: 'Ananda memiliki rasa ingin tahu yang tinggi dalam eksplorasi buku cerita, angka, dan kreasi seni.' }}
                    </p>
                </div>

                <!-- P5 -->
                @if($reportCard->p5_narrative)
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-2">
                        <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-400 flex items-center gap-2">
                            <span>🌟</span>
                            {{ $reportCard->p5_project_name ?: 'Projek Penguatan Profil Pelajar Pancasila (P5)' }}
                        </h3>
                        <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-300 text-justify pt-1">
                            {{ $reportCard->p5_narrative }}
                        </p>
                    </div>
                @endif

                <!-- Layanan Daycare & TPQ -->
                @if($reportCard->daycare_narrative || $reportCard->tpq_narrative || $reportCard->tpq_jilid)
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-50">Capaian Layanan Khusus</h3>

                        @if($reportCard->daycare_narrative)
                            <div class="border-l-4 border-purple-500 pl-4 py-1 space-y-1">
                                <h4 class="text-xs font-bold text-purple-900 dark:text-purple-400">👶 Catatan Daycare (TPA)</h4>
                                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reportCard->daycare_narrative }}</p>
                            </div>
                        @endif

                        @if($reportCard->tpq_narrative || $reportCard->tpq_jilid)
                            <div class="border-l-4 border-emerald-500 pl-4 py-1 space-y-2">
                                <h4 class="text-xs font-bold text-emerald-900 dark:text-emerald-400">📖 Capaian TPQ & Mengaji</h4>
                                <div class="grid grid-cols-2 gap-2 text-xs bg-emerald-50/50 dark:bg-emerald-950/30 p-2.5 rounded-xl">
                                    <div><span class="text-slate-400">Capaian Jilid:</span> <strong class="text-emerald-900 dark:text-emerald-300">{{ $reportCard->tpq_jilid ?: '-' }}</strong></div>
                                    <div><span class="text-slate-400">Hafalan Surat:</span> <strong class="text-emerald-900 dark:text-emerald-300">{{ $reportCard->tpq_surah ?: '-' }}</strong></div>
                                </div>
                                @if($reportCard->tpq_narrative)
                                    <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">{{ $reportCard->tpq_narrative }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Pertumbuhan & Kehadiran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-2">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-slate-50 uppercase tracking-wider">Pertumbuhan Fisik</h3>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between"><span class="text-slate-500">Tinggi Badan:</span> <strong class="font-mono">{{ $reportCard->height ? $reportCard->height . ' cm' : '-' }}</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Berat Badan:</span> <strong class="font-mono">{{ $reportCard->weight ? $reportCard->weight . ' kg' : '-' }}</strong></div>
                            @if($reportCard->nutritional_status)
                                <div class="flex justify-between pt-1 border-t border-slate-100 dark:border-slate-800"><span class="text-slate-500">Status Gizi:</span> <strong class="text-emerald-600 dark:text-emerald-400">{{ $reportCard->nutritional_status }}</strong></div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-2">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-slate-50 uppercase tracking-wider">Rekap Kehadiran</h3>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between"><span class="text-slate-500">Sakit:</span> <strong class="font-mono">{{ $reportCard->attendance_sick }} hari</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Izin:</span> <strong class="font-mono">{{ $reportCard->attendance_permission }} hari</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Tanpa Keterangan:</span> <strong class="font-mono">{{ $reportCard->attendance_unexcused }} hari</strong></div>
                        </div>
                    </div>
                </div>

                <!-- Dokumentasi Foto -->
                @if(!empty($reportCard->photos))
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-slate-50 uppercase tracking-wider">Dokumentasi Foto Belajar Ananda</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($reportCard->photos as $p)
                                <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 aspect-4/3 shadow-xs">
                                    <img src="{{ asset('storage/' . $p) }}" alt="Kegiatan" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Pesan Guru -->
                @if($reportCard->teacher_notes)
                    <div class="bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl p-6 shadow-xs space-y-2">
                        <h3 class="text-xs font-bold text-emerald-900 dark:text-emerald-300 uppercase tracking-wider">Refleksi & Pesan Guru Wali Kelas:</h3>
                        <p class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed italic text-justify">
                            "{{ $reportCard->teacher_notes }}"
                        </p>
                    </div>
                @endif
            </div>
        @endif

        <div class="text-center py-4 text-xs text-slate-400">
            &copy; {{ date('Y') }} SANS PAUD Anak Saleh Malang. Laporan Resmi Perkembangan Peserta Didik.
        </div>

    </div>

</body>
</html>
