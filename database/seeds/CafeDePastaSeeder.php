<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CafeDePastaSeeder extends Seeder
{
    /**
     * Seed the Cafe De Pasta product catalog, categories, uploads, stocks, and site branding.
     *
     * @return void
     */
    public function run()
    {
        $sqlPath = database_path('sql/cafe_de_pasta_catalog.sql');
        if (File::exists($sqlPath)) {
            $sql = File::get($sqlPath);
            DB::unprepared($sql);
            $this->command->info('Cafe De Pasta product catalog seeded successfully from SQL file.');
        } else {
            $this->command->error('cafe_de_pasta_catalog.sql file not found at: ' . $sqlPath);
        }
    }
}
