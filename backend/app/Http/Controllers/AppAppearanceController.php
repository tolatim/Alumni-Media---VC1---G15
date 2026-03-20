<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppAppearanceController extends Controller
{
    private const THEME_MODE_KEY = 'theme_mode';
    private const LOGO_PATH_KEY = 'app_logo_path';
    private const REGISTRATION_KEY = 'registration_key';

    public function showPublic(): JsonResponse
    {
        return response()->json([
            'data' => $this->readAppearanceSettings(),
        ]);
    }

    public function showAdmin(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        return response()->json([
            'data' => $this->readAdminSettings(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $validated = $request->validate([
            'theme_mode' => 'nullable|in:light,dark',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:4096',
            'registration_key' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('theme_mode', $validated) && !empty($validated['theme_mode'])) {
            $this->setSetting(self::THEME_MODE_KEY, $validated['theme_mode'], $admin->id);
        }

        if ($request->hasFile('logo')) {
            $oldPath = $this->getSetting(self::LOGO_PATH_KEY);
            $newPath = $request->file('logo')->store('branding', 'public');
            $this->setSetting(self::LOGO_PATH_KEY, $newPath, $admin->id);

            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if (array_key_exists('registration_key', $validated)) {
            $registrationKey = trim((string) $validated['registration_key']);
            $this->setSetting(
                self::REGISTRATION_KEY,
                $registrationKey !== '' ? $registrationKey : null,
                $admin->id
            );
        }

        return response()->json([
            'message' => 'Appearance settings updated successfully.',
            'data' => $this->readAdminSettings(),
        ]);
    }

    private function readAppearanceSettings(): array
    {
        $themeMode = $this->getSetting(self::THEME_MODE_KEY) ?: 'light';
        $logoPath = $this->getSetting(self::LOGO_PATH_KEY);

        return [
            'theme_mode' => in_array($themeMode, ['light', 'dark'], true) ? $themeMode : 'light',
            'logo_path' => $logoPath,
            'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
        ];
    }

    private function readAdminSettings(): array
    {
        $appearance = $this->readAppearanceSettings();
        $appearance['registration_key'] = $this->getSetting(self::REGISTRATION_KEY);

        return $appearance;
    }

    private function getSetting(string $key): ?string
    {
        return AppSetting::query()->where('key', $key)->value('value');
    }

    private function setSetting(string $key, ?string $value, int $adminId): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'updated_by' => $adminId,
            ]
        );
    }

    private function requireAdmin(Request $request): User|JsonResponse
    {
        $user = $request->user()?->loadMissing('role');
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (($user->role->name ?? null) !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        return $user;
    }
}
