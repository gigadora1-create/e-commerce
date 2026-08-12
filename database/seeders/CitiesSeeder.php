<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    public function run()
    {
        $cities = [
            ['city_name' => 'BOGOTA', 'city_store' => 'BOGOTA PRINCIPAL'],
            ['city_name' => 'BOGOTA', 'city_store' => 'BOGOTA SECUNDARIA'],
            ['city_name' => 'BOGOTA', 'city_store' => 'BOGOTA'],
            ['city_name' => 'CALI', 'city_store' => 'CALI'],
            ['city_name' => 'MEDELLIN', 'city_store' => 'MEDELLIN'],
            ['city_name' => 'BARRANQUILLA', 'city_store' => 'BARRANQUILLA'],
            ['city_name' => 'QUIBDO', 'city_store' => 'QUIBDO'],
            ['city_name' => 'CARTAGENA', 'city_store' => 'CARTAGENA'],
            ['city_name' => 'SAN ANDRES', 'city_store' => 'SAN ANDRES'],
        ];

        foreach ($cities as $city) {
            City::firstOrCreate(['city_store' => $city['city_store']], $city);
        }
    }
}
