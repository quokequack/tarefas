<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $examples = [
            ['title' => 'Estudar arquitetura MVC.', 'completed' => true],
            ['title' => 'Implementar Dependency Injection.', 'completed' => true],
            ['title' => 'Compreender o Eloquent ORM.', 'completed' => false],
            ['title' => 'Investigar as Laravel Facades.', 'completed' => false],
            ['title' => 'Preparar apresentação do IFAL.', 'completed' => false],
        ];

        foreach ($examples as $index => $example) {
            Task::firstOrCreate(
                ['title' => $example['title']],
                Task::factory()->raw([
                    ...$example,
                    'created_at' => now()->subMinutes(count($examples) - $index),
                ]),
            );
        }

        Cache::forget('tasks.total');
    }
}
