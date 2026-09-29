<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perkembangan Peserta Didik (Rapor PAUD)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
                break-after: page;
            }
            .print-card {
                box-shadow: none !important;
                border-color: #cbd5e1 !important;
            }
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <!-- FLOATING PRINT BUTTON -->
    <div class="no-print fixed top-5 right-5 z-50 flex items-center gap-2 bg-white/95 backdrop-blur-md p-2 rounded-xl shadow-xl border border-slate-200">
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors flex items-center gap-1.5 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer">
            Tutup
        </button>
    </div>

    <!-- MAIN REPORTS LOOP -->
    <div class="max-w-4xl mx-auto space-y-12">
        @foreach($reports as $index => $r)
            @php
                $s = $r->student;
                $cls = $s->classroom;
            @endphp

            <div class="bg-white p-8 md:p-10 rounded-2xl shadow-sm border border-slate-200 print-card space-y-6 {{ !$loop->last ? 'page-break' : '' }}">
                
                <!-- KOP SURAT RESMI SEKOLAH -->
                <div class="border-b-2 border-slate-900 pb-4 text-center relative">
                    <div class="flex items-center justify-center gap-4">
                        @if (setting('app_logo'))
                            <img src="{{ asset('storage/' . setting('app_logo')) }}" alt="Logo" class="w-16 h-16 object-contain shrink-0">
                        @else
                            <div class="w-16 h-16 rounded-xl bg-emerald-700 text-white flex items-center justify-center font-bold text-2xl shrink-0">
                                AS
                            </div>
                        @endif
                        <div class="text-center">
                            <h2 class="text-xs uppercase font-bold tracking-widest text-slate-500">YAYASAN PENDIDIKAN ANAK SALEH MALANG</h2>
                            <h1 class="text-xl font-extrabold uppercase tracking-wide text-slate-900 mt-0.5">
                                KB - TK - DAYCARE - TPQ ANAK SALEH
                            </h1>
                            <p class="text-[11px] text-slate-600 mt-0.5 leading-tight">
                                {{ setting('school_address', 'Jl. Candi Mendut No. 9, Lowokwaru, Kota Malang, Jawa Timur') }} | Telp: {{ setting('school_phone', '(0341) 492020') }}
                            </p>
                            <p class="text-[10px] text-slate-400 font-mono">NPSN: {{ setting('school_npsn', '69901234') }} | Akreditasi A (Unggul)</p>
                        </div>
                    </div>
                </div>

                <!-- TITLE RAPOR -->
                <div class="text-center py-2 bg-emerald-50/70 rounded-xl border border-emerald-100">
                    <h3 class="text-sm font-extrabold text-emerald-950 uppercase tracking-wide">
                        LAPORAN PERKEMBANGAN PESERTA DIDIK (RAPOR PAUD)
                    </h3>
                    <p class="text-xs font-semibold text-emerald-800">
                        KURIKULUM MERDEKA PAUD & ISLAMIC CHARACTER BUILDING
                    </p>
                </div>

                <!-- IDENTITAS PESERTA DIDIK -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs border-b border-slate-200 pb-4">
                    <div class="flex justify-between border-b border-slate-100 py-1">
                        <span class="text-slate-500 font-medium">Nama Peserta Didik</span>
                        <span class="font-bold text-slate-900 uppercase">{{ $s->full_name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 py-1">
                        <span class="text-slate-500 font-medium">Kelompok / Rombel</span>
                        <span class="font-bold text-slate-900">{{ $cls?->name ?? '-' }} ({{ $cls?->sub_unit ?? 'TK' }})</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 py-1">
                        <span class="text-slate-500 font-medium">Nomor Induk Siswa (NIS/NISN)</span>
                        <span class="font-mono font-bold text-slate-900">{{ $s->nis }} / {{ $s->nisn ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 py-1">
                        <span class="text-slate-500 font-medium">Fase / Jenjang</span>
                        <span class="font-medium text-slate-900">Fase Fondasi ({{ $cls?->classLevel?->name ?? 'TK' }})</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 py-1">
                        <span class="text-slate-500 font-medium">Tahun Ajaran / Semester</span>
                        <span class="font-bold text-slate-900">T.A. {{ $r->academicYear?->name ?? '2026/2027' }} ({{ $r->semester_label }})</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 py-1">
                        <span class="text-slate-500 font-medium">Usia Saat Ini</span>
                        <span class="font-medium text-slate-900">{{ $s->age ?? '-' }}</span>
                    </div>
                </div>

                @if($r->entry_mode === 'pdf' && $r->pdf_file)
                    <!-- TAMPILAN MODE PDF RAPOR -->
                    <div class="space-y-4 py-4">
                        <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📄</span>
                                <div>
                                    <h4 class="text-xs font-bold text-purple-950">Rapor Berkas PDF Resmi Terlampir</h4>
                                    <p class="text-[11px] text-purple-700">Dokumen PDF rapor telah diunggah oleh wali kelas.</p>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $r->pdf_file) }}" target="_blank"
                                class="no-print px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Buka Berkas PDF
                            </a>
                        </div>

                        <div class="w-full h-[650px] rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-inner no-print">
                            <iframe src="{{ asset('storage/' . $r->pdf_file) }}" class="w-full h-full border-0"></iframe>
                        </div>
                    </div>
                @else
                    <!-- BAGIAN 1: CAPAIAN PEMBELAJARAN PAUD (KURIKULUM MERDEKA) -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-lg">
                            I. CAPAIAN PEMBELAJARAN (CP) FONDASI
                        </h4>

                    <!-- 1. NABP -->
                    <div class="border border-slate-200 rounded-xl p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[11px] font-bold">1</span>
                            <h5 class="text-xs font-bold text-slate-900">Nilai Agama dan Budi Pekerti (NABP)</h5>
                        </div>
                        <p class="text-xs leading-relaxed text-slate-800 text-justify pl-7">
                            {{ $r->nabp_narrative ?: 'Ananda telah menunjukkan pembiasaan nilai-nilai agama dan budi pekerti islami dengan baik selama semester ini.' }}
                        </p>
                    </div>

                    <!-- 2. Jati Diri -->
                    <div class="border border-slate-200 rounded-xl p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-800 flex items-center justify-center text-[11px] font-bold">2</span>
                            <h5 class="text-xs font-bold text-slate-900">Jati Diri (Sosial Emosional & Fisik Motorik)</h5>
                        </div>
                        <p class="text-xs leading-relaxed text-slate-800 text-justify pl-7">
                            {{ $r->jati_diri_narrative ?: 'Ananda berkembang menjadi pribadi yang mandiri, percaya diri, dan memiliki koordinasi motorik yang aktif.' }}
                        </p>
                    </div>

                    <!-- 3. STEAM -->
                    <div class="border border-slate-200 rounded-xl p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center text-[11px] font-bold">3</span>
                            <h5 class="text-xs font-bold text-slate-900">Dasar-Dasar Literasi, Matematika, Sains, Teknologi, Rekayasa, dan Seni (STEAM)</h5>
                        </div>
                        <p class="text-xs leading-relaxed text-slate-800 text-justify pl-7">
                            {{ $r->steam_narrative ?: 'Ananda memiliki rasa ingin tahu yang tinggi dalam mengeksplorasi buku cerita, berhitung, dan berkarya seni.' }}
                        </p>
                    </div>
                </div>

                <!-- BAGIAN 2: PROJEK P5 -->
                @if($r->p5_narrative || $r->p5_project_name)
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-lg">
                            II. PROJEK PENGUATAN PROFIL PELAJAR PANCASILA (P5)
                        </h4>
                        <div class="border border-slate-200 rounded-xl p-4 space-y-2">
                            <h5 class="text-xs font-bold text-indigo-950">{{ $r->p5_project_name ?: 'Projek P5 Semester' }}</h5>
                            <p class="text-xs leading-relaxed text-slate-800 text-justify">
                                {{ $r->p5_narrative }}
                            </p>
                        </div>
                    </div>
                @endif

                <!-- BAGIAN 3: LAYANAN DAYCARE & TPQ (JIKA ADA) -->
                @if($r->daycare_narrative || $r->tpq_narrative || $r->tpq_jilid || $r->tpq_surah)
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-lg">
                            III. CAPAIAN KHUSUS DAYCARE & TPQ ANAK SALEH
                        </h4>

                        @if($r->daycare_narrative)
                            <div class="border border-slate-200 rounded-xl p-4 space-y-1.5">
                                <h5 class="text-xs font-bold text-purple-950">Layanan Daycare (TPA)</h5>
                                <p class="text-xs leading-relaxed text-slate-800 text-justify">
                                    {{ $r->daycare_narrative }}
                                </p>
                            </div>
                        @endif

                        @if($r->tpq_narrative || $r->tpq_jilid || $r->tpq_surah)
                            <div class="border border-slate-200 rounded-xl p-4 space-y-2">
                                <h5 class="text-xs font-bold text-emerald-950">Layanan TPQ & Tahfidz Al-Qur'an</h5>
                                <div class="grid grid-cols-2 gap-2 text-xs bg-emerald-50/50 p-2.5 rounded-lg">
                                    <div><span class="text-slate-500">Capaian Jilid:</span> <strong class="text-emerald-900">{{ $r->tpq_jilid ?: '-' }}</strong></div>
                                    <div><span class="text-slate-500">Hafalan Surat:</span> <strong class="text-emerald-900">{{ $r->tpq_surah ?: '-' }}</strong></div>
                                    <div class="col-span-2"><span class="text-slate-500">Hadits & Doa:</span> <strong class="text-emerald-900">{{ $r->tpq_hadith_doa ?: '-' }}</strong></div>
                                </div>
                                @if($r->tpq_narrative)
                                    <p class="text-xs leading-relaxed text-slate-800 text-justify pt-1">
                                        {{ $r->tpq_narrative }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <!-- BAGIAN 4: TUMBUH KEMBANG & KEHADIRAN -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-lg">
                        IV. PERTUMBUHAN FISIK & REKAPITULASI KEHADIRAN
                    </h4>
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div class="border border-slate-200 rounded-xl p-3.5 space-y-2">
                            <h5 class="font-bold text-slate-900 border-b border-slate-100 pb-1">Pertumbuhan Fisik</h5>
                            <div class="space-y-1">
                                <div class="flex justify-between"><span class="text-slate-500">Tinggi Badan:</span> <strong class="font-mono">{{ $r->height ? $r->height . ' cm' : '-' }}</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500">Berat Badan:</span> <strong class="font-mono">{{ $r->weight ? $r->weight . ' kg' : '-' }}</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500">Lingkar Kepala:</span> <strong class="font-mono">{{ $r->head_circumference ? $r->head_circumference . ' cm' : '-' }}</strong></div>
                                @if($r->nutritional_status)
                                    <div class="flex justify-between pt-1 border-t border-slate-100"><span class="text-slate-500">Status Gizi:</span> <strong class="text-emerald-700">{{ $r->nutritional_status }}</strong></div>
                                @endif
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-xl p-3.5 space-y-2">
                            <h5 class="font-bold text-slate-900 border-b border-slate-100 pb-1">Ketidakhadiran Semester</h5>
                            <div class="space-y-1">
                                <div class="flex justify-between"><span class="text-slate-500">Sakit (S):</span> <strong class="font-mono">{{ $r->attendance_sick }} hari</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500">Izin (I):</span> <strong class="font-mono">{{ $r->attendance_permission }} hari</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500">Tanpa Keterangan (A):</span> <strong class="font-mono">{{ $r->attendance_unexcused }} hari</strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 5: DOKUMENTASI FOTO KEGIATAN (JIKA ADA) -->
                @if(!empty($r->photos))
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider bg-slate-100 px-3 py-1.5 rounded-lg">
                            V. DOKUMENTASI KEGIATAN ANANDA
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($r->photos as $photo)
                                <div class="rounded-xl overflow-hidden border border-slate-200 aspect-4/3">
                                    <img src="{{ asset('storage/' . $photo) }}" alt="Dokumentasi" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- BAGIAN 6: PESAN WALI KELAS -->
                @if($r->teacher_notes)
                    <div class="space-y-2 border border-emerald-200 bg-emerald-50/40 rounded-xl p-4">
                        <h4 class="text-xs font-bold text-emerald-950 uppercase">Refleksi & Pesan Wali Kelas:</h4>
                        <p class="text-xs leading-relaxed text-slate-800 italic text-justify">
                            "{{ $r->teacher_notes }}"
                        </p>
                    </div>
                @endif
                @endif

                <!-- PENGESAHAN & TANDA TANGAN -->
                <div class="pt-6 border-t border-slate-200">
                    <div class="flex justify-end text-xs mb-4 text-slate-600">
                        {{ $r->place ?? 'Malang' }}, {{ $r->report_date ? $r->report_date->translatedFormat('d F Y') : date('d F Y') }}
                    </div>

                    <div class="grid grid-cols-3 gap-6 text-center text-xs">
                        <!-- Ortu -->
                        <div class="flex flex-col justify-between h-28">
                            <span>Mengetahui,<br>Orang Tua / Wali Murid</span>
                            <div class="font-bold border-b border-slate-900 pb-1 mx-4">
                                {{ $s->father_name ?: ($s->mother_name ?: '..........................................') }}
                            </div>
                        </div>

                        <!-- Wali Kelas -->
                        <div class="flex flex-col justify-between h-28">
                            <span>Wali Kelas / Guru Pendamping</span>
                            <div class="font-bold border-b border-slate-900 pb-1 mx-4">
                                {{ $r->homeroom_teacher_name ?: ($cls?->homeroomTeacher?->name ?? 'Ustadzah Wali Kelas') }}
                            </div>
                        </div>

                        <!-- Kepala Sekolah -->
                        <div class="flex flex-col justify-between h-28">
                            <span>Mengetahui,<br>Kepala Sekolah PAUD</span>
                            <div class="font-bold border-b border-slate-900 pb-1 mx-4">
                                {{ $r->principal_name ?: setting('principal_name', 'Ustadzah Kepala Sekolah, S.Pd') }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</body>
</html>
