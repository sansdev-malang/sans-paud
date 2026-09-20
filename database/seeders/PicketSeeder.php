<?php

namespace Database\Seeders;

use App\Models\PicketArea;
use Illuminate\Database\Seeder;

class PicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areasData = [
            [
                'name' => 'IN FRONT OF PAUD MAIN GATE / DROP OFF',
                'duty_hours' => '06.30 - 07.00',
                'jobs' => "Making sure all teachers are in duty based on schedule (Memastikan seluruh kegiatan piket berjalan sesuai tupoksi)\nGreeting the students with Salam, Smile, Greet, Salim, and Make sure the students surveillance and safety (Menyambut ananda dengan Salam, Senyum, Sapa, Salim, Memastikan pengawasan dan keamanan ananda)",
                'is_active' => true,
            ],
            [
                'name' => 'PAUD PLAYGROUND & LOBBY',
                'duty_hours' => '06.30 - 07.00',
                'jobs' => "Greeting the students with Salam, Smile, Greet, Salim, assisting young learners and ensuring safety around playground (Menyambut ananda dan mendampingi ananda di area bermain/lobby)",
                'is_active' => true,
            ]
        ];

        foreach ($areasData as $areaVal) {
            PicketArea::updateOrCreate(
                ['name' => $areaVal['name']],
                $areaVal
            );
        }
    }
}
