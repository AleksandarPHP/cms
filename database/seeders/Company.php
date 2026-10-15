<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Company extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('companies')->insert([
            'name' => 'Symple',
            'street' => NULL,
            'plz' => NULL,
            'ort' => NULL,
            'telefon' => NULL,
            'email' => 'test@symple.ch',
            'facebook' => NULL,
            'instagram' => NULL,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
