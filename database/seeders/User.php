<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class User extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = [];
        foreach(config('modules') as $mods){
            $array[$mods[0]] = 1;
        }

        DB::table('users')->insert(array_merge([
            'name' => 'Symple',
            'username' => 'Administrator',
            'email' => 'hello@symple.ch',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('4t&0@9Vu$w0D#wsd'),
            'is_active' => 1,
            'notification' => 0,
            'language' => 'de',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ], $array));

        DB::table('users')->insert(array_merge([
            'name' => 'Aleksandar Jovanovci',
            'username' => 'aco',
            'email' => 'aco@symple.ch',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('aco12345!'),
            'is_active' => 1,
            'notification' => 1,
            'language' => 'de',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ], $array));
    }
}
