<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Developer;
use App\Models\Auditor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestCredentialSeeder extends Seeder
{
    public function run()
    {
        Auditor::where('username', 'admin_auditor')->update([
            'password' => Hash::make('AuditorPassword123!')
        ]);
        Auditor::where('username', 'lead_auditor')->update([
            'password' => Hash::make('AuditorPassword123!')
        ]);
        Developer::where('username', 'alexm')->update([
            'password' => Hash::make('SecurePassword123!')
        ]);
        Developer::where('username', 'sarahc')->update([
            'password' => Hash::make('SecurePassword123!')
        ]);
        Developer::where('username', 'marcusw')->update([
            'password' => Hash::make('SecurePassword123!')
        ]);

        echo "User passwords updated successfully.\n";
    }
}

