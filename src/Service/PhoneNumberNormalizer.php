<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Prépare les numéros client au format international simplifié.
 *
 * Principe MVP : on ne devine pas le pays depuis le numéro saisi par le client.
 * Le formulaire fournit un champ indicatif explicite, puis ce service assemble
 * indicatif + numéro local en nettoyant seulement les espaces et séparateurs.
 *
 * La méthode normalizeLegacy() reste volontairement séparée pour le rattrapage
 * des anciennes données déjà présentes en base, dont le périmètre connu est
 * Mayotte et métropole.
 */
final class PhoneNumberNormalizer
{
    public const DEFAULT_DIAL_CODE = '+262';

    /** @return array<string, string> label => value */
    public static function dialCodeChoices(): array
    {
        return [
            'Mayotte / La Réunion (+262)' => '+262',
            'France métropolitaine (+33)' => '+33',
            'Comores (+269)' => '+269',
            'Madagascar (+261)' => '+261',
        ];
    }

    /**
     * Assemble un indicatif choisi explicitement avec un numéro local.
     */
    public function normalizeWithDialCode(?string $dialCode, ?string $phone): string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return '';
        }

        $phone = str_replace(["\xc2\xa0", ' '], '', $phone);
        $phone = preg_replace('/[^+0-9]/', '', $phone) ?? '';

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        // Si le client colle déjà un numéro international complet, on le nettoie
        // mais on ne le réinterprète pas depuis l'indicatif choisi.
        if (str_starts_with($phone, '+')) {
            return $this->normalizeInternational($phone);
        }

        $dialCode = $this->normalizeDialCode($dialCode);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return '';
        }

        return $dialCode . $digits;
    }

    /**
     * Rattrapage conservateur des anciennes données locales déjà enregistrées.
     */
    public function normalizeLegacy(?string $phone): string
    {
        $value = trim((string) $phone);
        if ($value === '') {
            return '';
        }

        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = preg_replace('/[^+0-9]/', '', $value) ?? '';

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }

        if (str_starts_with($value, '+')) {
            return $this->normalizeInternational($value);
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return '';
        }

        // Mayotte mobile : 0639XXXXXX ou 639XXXXXX.
        if (str_starts_with($digits, '0639')) {
            return '+262' . substr($digits, 1);
        }
        if (str_starts_with($digits, '639')) {
            return '+262' . $digits;
        }

        // Mayotte fixe : 0269XXXXXX ou 269XXXXXX.
        if (str_starts_with($digits, '0269')) {
            return '+262' . substr($digits, 1);
        }
        if (str_starts_with($digits, '269')) {
            return '+262' . $digits;
        }

        // Métropole : fixe/mobile classiques 01 à 05, 06, 07, 09.
        if (preg_match('/^0[1-79][0-9]+$/', $digits) === 1) {
            return '+33' . substr($digits, 1);
        }
        if (preg_match('/^[1-79][0-9]+$/', $digits) === 1) {
            return '+33' . $digits;
        }

        return $digits;
    }

    /** @return array{dialCode:string, localNumber:string} */
    public function splitForForm(?string $phone): array
    {
        $value = $this->normalizeInternational((string) $phone);

        if (str_starts_with($value, '+33')) {
            return [
                'dialCode' => '+33',
                'localNumber' => '0' . substr($value, 3),
            ];
        }

        if (str_starts_with($value, '+262')) {
            return [
                'dialCode' => '+262',
                'localNumber' => '0' . substr($value, 4),
            ];
        }

        if (str_starts_with($value, '+269')) {
            return [
                'dialCode' => '+269',
                'localNumber' => '0' . substr($value, 4),
            ];
        }

        if (str_starts_with($value, '+261')) {
            return [
                'dialCode' => '+261',
                'localNumber' => '0' . substr($value, 4),
            ];
        }

        return [
            'dialCode' => self::DEFAULT_DIAL_CODE,
            'localNumber' => trim((string) $phone),
        ];
    }

    private function normalizeDialCode(?string $dialCode): string
    {
        $value = trim((string) $dialCode);
        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = preg_replace('/[^+0-9]/', '', $value) ?? '';

        if ($value === '') {
            return self::DEFAULT_DIAL_CODE;
        }

        if (!str_starts_with($value, '+')) {
            $value = '+' . ltrim($value, '0');
        }

        $allowed = array_values(self::dialCodeChoices());

        return in_array($value, $allowed, true) ? $value : self::DEFAULT_DIAL_CODE;
    }

    private function normalizeInternational(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = preg_replace('/[^+0-9]/', '', $value) ?? '';

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }

        if (!str_starts_with($value, '+')) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', substr($value, 1)) ?? '';
        if ($digits === '') {
            return '';
        }

        // Supprime le zéro national collé après l'indicatif : +2620639 -> +262639.
        foreach (['262', '33', '269', '261'] as $code) {
            if (str_starts_with($digits, $code . '0')) {
                return '+' . $code . substr($digits, strlen($code) + 1);
            }
        }

        return '+' . $digits;
    }
}
