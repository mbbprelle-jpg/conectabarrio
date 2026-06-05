<?php

class OrgHelper {

    public const COMUNAS = [
        'PEÑAFLOR',
        'TALAGANTE',
        'EL MONTE',
        'PADRE HURTADO',
        'ISLA DE MAIPO',
    ];

    public static function usesCallesJurisdiccion(?string $tipo): bool {
        return ($tipo ?? 'Junta de Vecinos') === 'Junta de Vecinos';
    }

    public static function normalizeComuna(?string $comuna): string {
        $value = mb_strtoupper(trim((string)$comuna), 'UTF-8');
        return in_array($value, self::COMUNAS, true) ? $value : $value;
    }
}
