<?php

use Illuminate\Database\Seeder;

// composer require laracasts/testdummy
use Laracasts\TestDummy\Factory as TestDummy;

class MenuItemsTableSeeder extends Seeder
{
    public function run()
    {
        Eloquent::unguard();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('ussd_menu_items')->truncate();

        DB::table('ussd_menu_items')->delete();

        DB::table('ussd_menu_items')->insert(array(
            array(
                'menu_id' => 1,
                'description' => 'Enter File Number',
                'next_menu_id' => 0,
                'step' => 1,
                'confirmation_phrase' => 'File Number',
            ),
              array(
                'menu_id' => 1,
                'description' => 'Enter Name',
                'next_menu_id' => 0,
                'step' => 2,
                'confirmation_phrase' => 'Name',
            ),
               array(
                'menu_id' => 1,
                'description' => 'Stream',
                'next_menu_id' => 0,
                'step' => 3,
                'confirmation_phrase' => 'Stream',
            ),
                array(
                'menu_id' => 1,
                'description' => 'House',
                'next_menu_id' => 0,
                'step' => 4,
                'confirmation_phrase' => 'House',
            ),
                     array(
                'menu_id' => 1,
                'description' => 'Profession',
                'next_menu_id' => 0,
                'step' => 5,
                'confirmation_phrase' => 'Profession',
            ),
        ));
    }
}
