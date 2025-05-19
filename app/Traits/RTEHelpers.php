<?php

declare(strict_types=1);

namespace App\Traits;

trait RTEHelpers
{
    /**
     * Encode an array of modality names to bit flags
     *
     * @param  array  $modalities  Array of modality names
     * @return int Bit flags representing modalities
     */
    public function encodeModalities(array $modalities): int
    {
        $encoded = 0;
        $valid_modalities = [
            'daily_steps' => 1,
            'run' => 2,
            'walk' => 4,
            'bike' => 8,
            'swim' => 16,
            'other' => 32,
        ];

        foreach ($modalities as $modality) {
            if (array_key_exists($modality, $valid_modalities)) {
                $encoded += $valid_modalities[$modality];
            }
        }

        return $encoded;
    }

    /**
     * Decode modalities from bit flags to an array of modality names
     *
     * @param  int  $sum  Bit flags representing modalities
     * @return array Array of modality names
     */
    public function decodeModalities(int $sum): array
    {
        $decoded = [];
        $valid_modalities = $this->validModalities();

        foreach ($valid_modalities as $key => $value) {
            if (($sum & $value) !== 0) {
                $decoded[] = $key;
            }
        }

        return $decoded;
    }

    private function validModalities(): array
    {
        return [
            'daily_steps' => 1,
            'run' => 2,
            'walk' => 4,
            'bike' => 8,
            'swim' => 16,
            'other' => 32,
        ];
    }
}
