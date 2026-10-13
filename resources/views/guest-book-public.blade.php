<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buku Tamu Digital - {{ setting('app_name', 'PAUD Terpadu Anak Saleh Malang') }}</title>
    
    <!-- Tailwind CSS (Vite / CDN Fallback for Public Standalone) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 via-slate-50 to-indigo-50 min-h-screen text-slate-800 antialiased flex flex-col justify-between py-6 px-3 sm:px-6">

    <!-- Top Navigation / Brand Bar -->
    <header class="max-w-3xl w-full mx-auto flex items-center justify-between pb-4">
        <div class="flex items-center gap-3">
            @if(setting('app_logo'))
                <img src="{{ asset('storage/' . setting('app_logo')) }}" alt="Logo" class="w-10 h-10 rounded-xl object-cover shadow-sm">
            @else
                <div class="w-10 h-10 rounded-xl bg-teal-600 text-white font-bold flex items-center justify-center shadow-md">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                </div>
            @endif
            <div>
                <h1 class="font-extrabold text-sm sm:text-base text-slate-900 leading-tight">
                    {{ setting('app_name', 'PAUD Islam Terpadu Anak Saleh') }}
                </h1>
                <p class="text-[11px] text-teal-700 font-semibold">Buku Tamu & Kunjungan Digital</p>
            </div>
        </div>

        <div class="text-right hidden sm:block">
            <span class="text-xs text-slate-500 font-medium">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-3xl w-full mx-auto my-auto">
        <!-- SUCCESS BANNER -->
        @if(session('success_public'))
            <div class="mb-5 bg-emerald-50 border-2 border-emerald-400 text-emerald-900 p-5 rounded-2xl shadow-lg animate-fade-in flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
                <div class="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md">
                    <i data-lucide="check" class="w-6 h-6 stroke-[3]"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-base text-emerald-950">Alhamdulillah! Data Berhasil Dicatat</h3>
                    <p class="text-xs sm:text-sm text-emerald-800 mt-0.5 leading-relaxed">
                        {{ session('success_public') }}
                    </p>
                </div>
                <a href="{{ route('buku-tamu.public') }}"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all whitespace-nowrap">
                    Catat Tamu Berikutnya
                </a>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl shadow-xs text-xs">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span>Mohon lengkapi data berikut:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 ml-2">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Card Form -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-teal-600 to-teal-700 p-5 sm:p-6 text-white text-left">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-800/40 border border-teal-400/30 text-[11px] font-semibold mb-2">
                    <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                    <span>Formulir Kunjungan Tamu</span>
                </div>
                <h2 class="text-lg sm:text-xl font-bold tracking-tight">Selamat Datang di Lembaga Kami</h2>
                <p class="text-xs text-teal-100 mt-1 leading-relaxed">
                    Silakan mengisi buku tamu digital untuk kelancaran pelayanan dan pencatatan agenda kunjungan Anda.
                </p>
            </div>

            <!-- Form Body -->
            <form action="{{ route('buku-tamu.submit') }}" method="POST" class="p-5 sm:p-7 space-y-6 text-left">
                @csrf

                <!-- SECTION 1: IDENTITAS TAMU -->
                <div class="space-y-3.5">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                        <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-bold text-xs flex items-center justify-center">1</span>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-800">Identitas Diri & Kontak</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Nama Lengkap Tamu <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                placeholder="Tuliskan nama lengkap beserta gelar jika ada"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-semibold text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all">
                        </div>

                        <!-- No. HP / WhatsApp -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                No. Handphone / WhatsApp <span class="text-rose-500">*</span>
                            </label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" required
                                placeholder="Contoh: 081234567890"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-semibold text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all font-mono">
                        </div>
                    </div>

                    <!-- Kategori Layanan (Visual Cards Selection) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">
                            Kategori Layanan / Maksud Kunjungan <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            @foreach($categories as $cat)
                                @php
                                    $isSelected = old('category', 'Umum') === $cat;
                                    $iconName = match($cat) {
                                        'SPMB' => 'user-plus',
                                        'Dinas' => 'landmark',
                                        'Puskesmas/Dinkes' => 'activity',
                                        default => 'briefcase',
                                    };
                                @endphp
                                <label class="cursor-pointer relative flex flex-col items-center text-center p-3 rounded-xl border border-slate-200 hover:border-teal-400 hover:bg-teal-50/40 transition-all has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50 has-[:checked]:ring-2 has-[:checked]:ring-teal-500/20">
                                    <input type="radio" name="category" value="{{ $cat }}" class="sr-only" {{ $isSelected ? 'checked' : '' }}>
                                    <i data-lucide="{{ $iconName }}" class="w-5 h-5 text-teal-600 mb-1.5"></i>
                                    <span class="text-xs font-bold text-slate-800">{{ $cat }}</span>
                                    <span class="text-[10px] text-slate-400 mt-0.5">
                                        @if($cat === 'SPMB') Calon Murid
                                        @elseif($cat === 'Dinas') Kedinasan
                                        @elseif($cat === 'Puskesmas/Dinkes') Puskesmas
                                        @else Umum
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tanggal & Waktu (Hidden or Pre-filled) -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Tanggal Kunjungan</label>
                            <input type="date" name="date" value="{{ old('date', \Carbon\Carbon::now()->format('Y-m-d')) }}" required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Waktu / Jam Datang</label>
                            <input type="time" name="time" value="{{ old('time', \Carbon\Carbon::now()->format('H:i')) }}"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: INSTANSI & DOMISILI -->
                <div class="space-y-3.5">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                        <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-bold text-xs flex items-center justify-center">2</span>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-800">Asal Instansi & Domisili</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Institusi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Institusi / Lembaga / Asal</label>
                            <input type="text" name="institution" value="{{ old('institution') }}"
                                placeholder="Contoh: Puskesmas Dinoyo / Umum"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Jabatan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Jabatan</label>
                            <input type="text" name="position" value="{{ old('position') }}"
                                placeholder="Contoh: Pengawas / Dokter / Wali Murid"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Pekerjaan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pekerjaan</label>
                            <input type="text" name="occupation" value="{{ old('occupation') }}"
                                placeholder="Contoh: ASN / Wiraswasta / Karyawan"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Kota -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Kota / Kabupaten</label>
                            <input type="text" name="city" value="{{ old('city', 'Kota Malang') }}"
                                placeholder="Contoh: Kota Malang"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Provinsi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Provinsi</label>
                            <input type="text" name="province" value="{{ old('province', 'Jawa Timur') }}"
                                placeholder="Contoh: Jawa Timur"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Alamat -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Domisili</label>
                            <input type="text" name="address" value="{{ old('address') }}"
                                placeholder="Jl. / RT / RW / Kelurahan"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: KEPERLUAN & KESAN -->
                <div class="space-y-3.5">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                        <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 font-bold text-xs flex items-center justify-center">3</span>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-800">Keperluan & Catatan</h3>
                    </div>

                    <!-- Keperluan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Maksud & Keperluan Kunjungan <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="purpose" rows="2" required
                            placeholder="Jelaskan maksud dan tujuan kunjungan Anda ke PAUD..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 leading-relaxed">{{ old('purpose') }}</textarea>
                    </div>

                    <!-- Menemui Siapa -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Ingin Menemui Siapa / Penerima Tamu
                        </label>
                        <input type="text" name="host_recipient" value="{{ old('host_recipient') }}"
                            placeholder="Contoh: Kepala Sekolah / Bagian Administrasi / Pendidik"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <!-- Kesan & Pesan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Kesan & Pesan / Saran (Opsional)
                        </label>
                        <textarea name="feedback" rows="2"
                            placeholder="Tuliskan kesan, pesan, atau harapan Anda terhadap layanan kami..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 leading-relaxed">{{ old('feedback') }}</textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <button type="submit"
                        class="w-full sm:w-auto px-8 py-3 bg-teal-600 hover:bg-teal-700 active:scale-98 text-white text-sm font-bold rounded-xl shadow-lg shadow-teal-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim & Catat Kunjungan</span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="max-w-3xl w-full mx-auto text-center pt-6 text-[11px] text-slate-400">
        <p>&copy; {{ date('Y') }} {{ setting('app_name', 'PAUD Islam Terpadu Anak Saleh Malang') }}. Hak Cipta Dilindungi.</p>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
