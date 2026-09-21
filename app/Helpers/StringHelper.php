<?php

namespace App\Helpers;

class StringHelper
{
    public static function normalizeWarehouseName($name)
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        return preg_replace('/[^a-z0-9]/', '', $name);
    }

    /**
     * Reconstruye texto dañado por importación de Excel donde la pérdida
     * de 2 bytes UTF-8 convirtió vocales acentuadas y ñ en '??'.
     */
    public static function repairAccents(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $map = [
            // items
            'Peque??a' => 'Pequeña',
            'dise??ado' => 'diseñado',
            'dise??ada' => 'diseñada',
            'dise??ar' => 'diseñar',
            'ni??os' => 'niños',
            'energ??a' => 'energía',
            'hidrataci??n' => 'hidratación',
            'amino??cidos' => 'aminoácidos',
            'recuperaci??n' => 'recuperación',
            'probi??ticos' => 'probióticos',
            'prebi??ticos' => 'prebióticos',
            'inmunol??gico' => 'inmunológico',
            'neurol??gico' => 'neurológico',
            'composici??n' => 'composición',
            'esp??ritu' => 'espíritu',
            'prote??na' => 'proteína',
            'prote??ico' => 'proteico',
            'mineral' => 'mineral',
            'alimentaci??n' => 'alimentación',
            'vitam??nico' => 'vitamínico',
            'f??rmula' => 'fórmula',
            'a??adir' => 'añadir',
            // locations
            'Ubicaci??n' => 'Ubicación',
            'recepci??n' => 'recepción',
            'distribuci??n' => 'distribución',
            'retenci??n' => 'retención',
            'producci??n' => 'producción',
            'ubicaci??n' => 'ubicación',
            // nombres y textos de picking
            'Andr??s' => 'Andrés',
            'Degustaci??n' => 'Degustación',
            'DEGUSTACI??N' => 'DEGUSTACIÓN',
            'degustaci??n' => 'degustación',
            'EXPANSI??N' => 'EXPANSIÓN',
            'Jim??nez' => 'Jiménez',
            'Ferm??n' => 'Fermín',
            'Su??rez' => 'Suárez',
            'M??nica' => 'Mónica',
            'Fabi??n' => 'Fabián',
            'Dur??n' => 'Durán',
            'Ram??rez' => 'Ramírez',
            'Jes??s' => 'Jesús',
            'Londo??o' => 'Londoño',
            'LONDO??O' => 'LONDOÑO',
            'QUI??ONEZ' => 'QUIÑONEZ',
            'G??MEZ' => 'GÓMEZ',
            'G??mez' => 'Gómez',
            'L??pez' => 'López',
            'L??PEZ' => 'LÓPEZ',
            'Echavarr??a' => 'Echavarría',
            'V??lez' => 'Vélez',
            'Cede??o' => 'Cedeño',
            'Luc??a' => 'Lucía',
            'Mar??a' => 'María',
            'Jos??' => 'José',
            'Ca??izalez' => 'Cañizalez',
            'Rodr??guez' => 'Rodríguez',
            'Chavarr??a' => 'Chavarría',
            'S??nchez' => 'Sánchez',
            'Mej??a' => 'Mejía',
            'Villamar??n' => 'Villamarín',
            'cu??ntico' => 'cuántico',
            'CHOCONT??' => 'CHOCONTÁ',
            'KAF??' => 'KAFÉ',
        ];

        foreach ($map as $corrupt => $fixed) {
            $value = str_replace($corrupt, $fixed, $value);
        }

        // Fallback: cualquier ejecución de '?' residual (separadores o bordes)
        $chars = preg_split('//u', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return $value;
        }

        $out = [];
        $count = count($chars);
        $isLetter = static fn ($c) => $c !== '' && preg_match('/[\p{L}\p{M}]/u', $c) === 1;

        for ($i = 0; $i < $count; $i++) {
            if ($chars[$i] !== '?') {
                $out[] = $chars[$i];
                continue;
            }
            $j = $i;
            while ($j < $count && $chars[$j] === '?') {
                $j++;
            }
            $before = $i > 0 ? $chars[$i - 1] : '';
            $after = $j < $count ? $chars[$j] : '';
            if ($isLetter($before) && $isLetter($after)) {
                $out[] = ' ';
            }
            $i = $j - 1;
        }

        return implode('', $out);
    }
}
