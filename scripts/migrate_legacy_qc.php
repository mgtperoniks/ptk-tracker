<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PTK;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$oldEmail = 'adminqc@peroniks.com';
$newEmail = 'adminqcflange@peroniks.com';

// Search case-insensitively
$oldUser = User::where('email', 'LIKE', $oldEmail)->first();
$newUser = User::where('email', 'LIKE', $newEmail)->first();

if (!$oldUser) {
    echo "Old user ($oldEmail) not found in DB.\n";
    // List some users to help debug
    echo "Available users: " . User::limit(5)->pluck('email')->implode(', ') . "\n";
    exit;
}

if (!$newUser) {
    echo "New user ($newEmail) not found in DB.\n";
    exit;
}

$oldId = $oldUser->id;
$newId = $newUser->id;

echo "Migrating data from User ID $oldId ({$oldUser->email}) to User ID $newId ({$newUser->email})...\n";

DB::transaction(function () use ($oldId, $newId, $oldUser) {
    // 1. Update PTKs
    // Use proper case for RAW query if needed, better use prepared statements/Eloquent or simple values
    $updatedPtksCount = DB::table('ptks')
        ->where(function($q) use ($oldId) {
            $q->where('pic_user_id', $oldId)
              ->orWhere('created_by', $oldId)
              ->orWhere('approved_stage1_by', $oldId)
              ->orWhere('approved_stage2_by', $oldId)
              ->orWhere('last_reject_by', $oldId);
        })
        ->update([
            'pic_user_id' => DB::raw("IF(pic_user_id = $oldId, $newId, pic_user_id)"),
            'created_by' => DB::raw("IF(created_by = $oldId, $newId, created_by)"),
            'approved_stage1_by' => DB::raw("IF(approved_stage1_by = $oldId, $newId, approved_stage1_by)"),
            'approved_stage2_by' => DB::raw("IF(approved_stage2_by = $oldId, $newId, approved_stage2_by)"),
            'last_reject_by' => DB::raw("IF(last_reject_by = $oldId, $newId, last_reject_by)"),
        ]);
    
    echo "- Updated $updatedPtksCount rows in 'ptks' table.\n";

    // 2. Update Approval Logs (if exists)
    if (Schema::hasTable('approval_logs')) {
        $updatedLogsCount = DB::table('approval_logs')
            ->where('user_id', $oldId)
            ->update(['user_id' => $newId]);
        echo "- Updated $updatedLogsCount rows in 'approval_logs' table.\n";
    }

    // 3. Update Activity Log
    if (Schema::hasTable('activity_log')) {
        $updatedActivityCount = DB::table('activity_log')
            ->where('causer_id', $oldId)
            ->where('causer_type', User::class)
            ->update(['causer_id' => $newId]);
        echo "- Updated $updatedActivityCount rows in 'activity_log' table.\n";
    }

    // 4. Delete the old user
    DB::table('users')->where('id', $oldId)->delete();
    echo "- Deleted legacy user account (ID: $oldId).\n";
});

echo "\nMigration COMPLETED successfully.\n";
