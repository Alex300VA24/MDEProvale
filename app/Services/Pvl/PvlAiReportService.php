<?php

namespace App\Services\Pvl;

class PvlAiReportService extends PvlGeminiReportService
{
    public function modelIdentifier(): ?string
    {
        return $this->ai->modelIdentifier();
    }
}
