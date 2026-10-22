<?php

namespace Tests\Unit;

use App\Enums\ResponsibleType;
use App\Models\Responsible;
use PHPUnit\Framework\TestCase;

class ResponsibleTypeTest extends TestCase
{
    public function test_labels_cover_every_case(): void
    {
        $labels = ResponsibleType::labels();

        $this->assertSame(['chief', 'storekeeper'], array_keys($labels));
        $this->assertSame('Encargado de PROVALE', ResponsibleType::Storekeeper->label());
    }

    public function test_model_label_falls_back_to_raw_type(): void
    {
        $responsible = new Responsible(['type' => 'chief']);
        $this->assertSame('Subgerente de Programas Sociales', $responsible->type_label);

        $responsible->type = 'otro';
        $this->assertSame('otro', $responsible->type_label);
    }
}
