<?php

namespace Database\Seeders;
use App\Models\QuestionType;
use Illuminate\Database\Seeder;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionTypes = [
            ['name' => 'Multiple Choice', 'code' => 'MC'],
            ['name' => 'True/False', 'code' => 'TF'],
            ['name' => 'Essay', 'code' => 'ES'],
        ];

        foreach ($questionTypes as $type) {
            QuestionType::updateOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code']]
            );
        }
    }
}
