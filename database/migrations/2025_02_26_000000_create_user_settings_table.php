<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    /**
     * Get default settings from settings.json file
     */
    private function getDefaultSettings(): array
    {
        $path = base_path('settings.json');
        
        if (!File::exists($path)) {
            throw new \RuntimeException('Settings file not found');
        }

        $settings = json_decode(File::get($path), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid settings JSON file');
        }

        return $settings['settings'] ?? [];
    }

    public function up(): void
    {
        try {
            $defaultSettings = $this->getDefaultSettings();

            Schema::create('user_settings', function (Blueprint $table) use ($defaultSettings) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->json('settings')->default(json_encode($defaultSettings));
                $table->timestamps();
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