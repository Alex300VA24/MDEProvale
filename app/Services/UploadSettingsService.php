<?php

namespace App\Services;

use App\Models\Setting;

class UploadSettingsService
{
    public const KEY = 'uploads.max_attachment_mb';
    public const DEFAULT_MB = 10;
    public const MIN_MB = 1;
    public const MAX_MB = 10;

    public function maxMb(): int
    {
        return max(self::MIN_MB, min(self::MAX_MB, (int) Setting::get(self::KEY, self::DEFAULT_MB)));
    }

    public function maxKilobytes(): int
    {
        return $this->maxMb() * 1024;
    }

    public function update(int $megabytes): int
    {
        $value = max(self::MIN_MB, min(self::MAX_MB, $megabytes));
        Setting::put(self::KEY, $value);

        return $value;
    }
}
