<?php

namespace App\Support;

final class UiLabels
{
    public static function role(?string $name): string
    {
        return match ($name) {
            'Administrator' => 'Администратор',
            'Manager' => 'Менеджер',
            'Curator' => 'Куратор',
            'Student' => 'Учащийся',
            null, '' => 'Не назначена',
            default => $name,
        };
    }
}
