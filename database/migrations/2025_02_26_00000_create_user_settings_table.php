<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Get default settings from settings.json file
     */
    private function getDefaultSettings($role = null): array
    {
        $path = base_path('settings.json');
        
        if (!File::exists($path)) {
            throw new \RuntimeException('Settings file not found');
        }

        $settings = json_decode(File::get($path), true)['settings'];
        
        $defaultSettings = $settings['all_users'];
        
        if ($role && isset($settings[strtolower($role)])) {
            $defaultSettings = array_merge_recursive(
                $defaultSettings,
                $settings[strtolower($role)]
            );
        }
        
        return $defaultSettings;
    }

    public function up(): void
    {
        try {
            Schema::create('user_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->json('settings');
                $table->timestamps();
            });

            DB::table('users')->orderBy('id')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('user_settings')->insert([
                        'user_id' => $user->id,
                        'settings' => json_encode($this->getDefaultSettings($user->role)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create user_settings table: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
}; 