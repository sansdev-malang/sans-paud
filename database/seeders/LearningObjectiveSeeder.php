<?php

namespace Database\Seeders;

use App\Models\LearningObjective;
use Illuminate\Database\Seeder;

class LearningObjectiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // 1. NILAI AGAMA & BUDI PEKERTI (NABP)
            [
                'category' => 'NABP',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Pembiasaan Adab, Sholat, & Doa Harian (Sangat Baik)',
                'sample_narrative' => 'Alhamdulillah, ananda menunjukkan perkembangan yang sangat baik dalam mengenal dan mempraktikkan nilai-nilai Islam. Ananda selalu antusias mengikuti kegiatan sholat dhuha berjamaah, mampu melafalkan doa sebelum dan sesudah makan, serta doa untuk kedua orang tua dengan lancar. Ananda juga terbiasa mengucapkan salam, terima kasih, dan meminta tolong kepada teman dan ustadzah.',
                'order' => 1,
            ],
            [
                'category' => 'NABP',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Menyayangi Ciptaan Allah & Tolong Menolong',
                'sample_narrative' => 'Ananda mampu menunjukkan rasa syukur kepada Allah SWT dengan merawat lingkungan kelas dan menyayangi tanaman serta hewan di sekitar sekolah. Ananda memiliki kepedulian sosial yang tinggi, suka berbagi bekal dan sigap membantu teman yang membutuhkan.',
                'order' => 2,
            ],

            // 2. JATI DIRI (Sosial Emosional & Fisik Motorik)
            [
                'category' => 'JATI_DIRI',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Kemandirian, Percaya Diri & Motorik Aktif',
                'sample_narrative' => 'Ananda berkembang menjadi anak yang mandiri dan percaya diri. Mampu memakai dan merapikan sepatu serta perlengkapan pribadi secara mandiri. Dalam motorik kasar, ananda lincah melompat, berlari seimbang, dan aktif berolahraga senam ceria. Koordinasi motorik halus (menggunting, meronce, dan memegang krayon) semakin terasah dengan baik.',
                'order' => 1,
            ],
            [
                'category' => 'JATI_DIRI',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Pengelolaan Emosi & Kerjasama Kelompok',
                'sample_narrative' => 'Ananda mampu mengekspresikan perasaannya dengan baik dan mulai memahami cara menenangkan diri saat merasa lelah atau kecewa. Ananda dapat bermain bersama dalam kelompok, mematuhi aturan main sederhana, serta terbiasa antre dan bergiliran.',
                'order' => 2,
            ],

            // 3. DASAR LITERASI & STEAM
            [
                'category' => 'STEAM',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Keaksaraan Awal & Eksplorasi Sains Logika',
                'sample_narrative' => 'Ananda memiliki rasa ingin tahu yang tinggi terhadap buku cerita dan simbol huruf/angka. Mampu menceritakan kembali isi cerita bergambar dengan bahasanya sendiri. Dalam konsep matematika dan sains, ananda terampil mengelompokkan benda berdasarkan warna dan bentuk, menghitung benda 1-20, serta gemar bereksperimen dengan media alam (air, pasir, dan balok susun).',
                'order' => 1,
            ],
            [
                'category' => 'STEAM',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Kreativitas Seni & Pemecahan Masalah (Problem Solving)',
                'sample_narrative' => 'Ananda menunjukkan daya imajinasi dan kreasi seni yang kaya saat melukis, membentuk plastisin, dan menyusun loose-parts. Ananda juga menunjukkan kemampuan berpikir kritis saat menyelesaikan tantangan permainan puzzle dan konstruksi bangunan balok bersama teman.',
                'order' => 2,
            ],

            // 4. PROJEK P5
            [
                'category' => 'P5',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Projek: Aku Sayang Bumi (Kebun Cilik Anak Saleh)',
                'sample_narrative' => 'Dalam kegiatan Projek Penguatan Profil Pelajar Pancasila (P5) bertema "Aku Sayang Bumi", ananda aktif berpartisipasi menanam biji tanaman, menyiram setiap pagi, dan mengamati proses tumbuhnya tunas. Ananda memahami pentingnya menjaga kebersihan lingkungan dengan membuang sampah pada tempatnya.',
                'order' => 1,
            ],
            [
                'category' => 'P5',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Projek: Aku Cinta Indonesia / Budaya Nusantara',
                'sample_narrative' => 'Melalui projek "Aku Cinta Indonesia", ananda antusias mengenal ragam makanan tradisional, pakaian adat, dan permainan tradisional nusantara. Ananda berani tampil percaya diri dalam peragaan busana dan panggung gembira anak sholeh.',
                'order' => 2,
            ],

            // 5. DAYCARE / TPA
            [
                'category' => 'DAYCARE',
                'sub_unit' => 'DAYCARE',
                'grade_level' => 'ALL',
                'title' => 'Pola Makan Sehat & Kemandirian Daycare',
                'sample_narrative' => 'Selama berkegiatan di Daycare Anak Saleh, ananda memiliki pola makan yang baik, mau mengonsumsi sayur dan buah, serta terbiasa tidur siang dengan teratur dan nyenyak. Toilet training ananda berkembang sangat baik, sudah mampu mengomunikasikan kebutuhan BAK/BAB kepada bunda pendamping.',
                'order' => 1,
            ],

            // 6. TPQ / KEAGAMAAN
            [
                'category' => 'TPQ',
                'sub_unit' => 'TPQ',
                'grade_level' => 'ALL',
                'title' => 'Kelancaran Tilawati / Mengaji & Tahfidz',
                'sample_narrative' => 'Alhamdulillah, dalam program TPQ, ananda menunjukkan ketertarikan tinggi dalam membaca jilid Tilawati dengan makharijul huruf yang semakin tepat. Hafalan surat-surat pendek (An-Nas s/d Al-Ikhlas) dan doa harian dapat dilantunkan dengan tartil dan merdu.',
                'order' => 1,
            ],

            // 7. PESAN WALI KELAS
            [
                'category' => 'TEACHER_NOTES',
                'sub_unit' => 'ALL',
                'grade_level' => 'ALL',
                'title' => 'Apresiasi & Motivasi Semester',
                'sample_narrative' => 'Barakallahu fiik ananda sholeh/sholehah! Perkembangan ananda di semester ini sangat membanggakan. Teruslah tumbuh menjadi pribadi yang ceria, cerdas, berakhlak mulia, dan gemar belajar. Terima kasih kepada Ayah dan Bunda atas sinergi dan pendampingan yang luar biasa di rumah.',
                'order' => 1,
            ],
        ];

        foreach ($templates as $t) {
            LearningObjective::updateOrCreate(
                [
                    'category' => $t['category'],
                    'title' => $t['title'],
                ],
                $t
            );
        }
    }
}
