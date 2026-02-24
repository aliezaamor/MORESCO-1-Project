<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $group = \App\Models\Group::create([
            'name' => 'Test Group',
            'description' => 'A demo group for testing',
        ]);

        $contact1 = \App\Models\Contact::create([
            'name' => 'John Doe',
            'phone_number' => '1234567890',
            'email' => 'john@example.com',
        ]);

        $contact2 = \App\Models\Contact::create([
            'name' => 'Jane Smith',
            'phone_number' => '9876543210',
            'email' => 'jane@example.com',
        ]);

        $group->contacts()->attach([$contact1->id, $contact2->id]);
    }
}
