<?php

namespace App\Service;

use App\Entity\HodinaSetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Historical name kept to avoid a risky service-wide rename during the pilot.
 *
 * Since J5J this service no longer manages only the first commercial opening.
 * It manages the generic Hodina commerce availability mode:
 * - open: public orders are enabled;
 * - preopening: public orders are blocked before official launch;
 * - maintenance: public orders are temporarily blocked during production updates;
 * - closed: public orders are manually suspended.
 *
 * The old J5I settings are intentionally not used anymore. They are migrated to
 * the commerce_* settings by Version20260613130000 so we do not keep two sets of
 * parameters to maintain later.
 */
final class SalesOpeningService
{
    public const MODE_OPEN = 'open';
    public const MODE_PREOPENING = 'preopening';
    public const MODE_MAINTENANCE = 'maintenance';
    public const MODE_CLOSED = 'closed';

    /** @var list<string> */
    private const VALID_MODES = [
        self::MODE_OPEN,
        self::MODE_PREOPENING,
        self::MODE_MAINTENANCE,
        self::MODE_CLOSED,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {
    }

    /** @return array<string, mixed> */
    public function getState(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        $mode = $this->getMode();
        $reopensAt = $this->getReopensAt();
        $cartLockedSetting = $this->getBool(HodinaSetting::KEY_COMMERCE_CART_LOCKED, true);
        $emailCaptureEnabled = $this->getBool(HodinaSetting::KEY_COMMERCE_EMAIL_CAPTURE_ENABLED, true);
        $allowTesters = $this->getBool(HodinaSetting::KEY_COMMERCE_ALLOW_TESTERS, true);
        $isTester = $allowTesters && $this->isCurrentUserAllowedTester();

        $isRestrictedMode = $mode !== self::MODE_OPEN;
        $hasFutureReopening = $reopensAt instanceof \DateTimeImmutable && $reopensAt > $now;
        $isCartLockedForPublic = $isRestrictedMode && $cartLockedSetting;
        $isCartLocked = $isCartLockedForPublic && !$isTester;
        $isBannerVisible = $isRestrictedMode;

        return [
            // New J5J generic state.
            'mode' => $mode,
            'modeLabel' => $this->getModeLabel($mode),
            'enabled' => $isBannerVisible,
            'reopensAt' => $reopensAt,
            'reopensAtIso' => $reopensAt?->format(\DateTimeInterface::ATOM),
            'hasFutureReopening' => $hasFutureReopening,
            'isRestrictedMode' => $isRestrictedMode,
            'isCartLocked' => $isCartLocked,
            'isCartLockedForPublic' => $isCartLockedForPublic,
            'isTester' => $isTester,
            'allowTesters' => $allowTesters,
            'emailCaptureEnabled' => $emailCaptureEnabled,
            'title' => $this->getText(HodinaSetting::KEY_COMMERCE_BANNER_TITLE, 'Commandes temporairement suspendues'),
            'message' => $this->getText(HodinaSetting::KEY_COMMERCE_BANNER_MESSAGE, 'Le catalogue reste visible, mais les commandes sont temporairement désactivées.'),
            'buttonLabel' => $this->getText(HodinaSetting::KEY_COMMERCE_BANNER_BUTTON_LABEL, 'Me prévenir'),
            'successMessage' => $this->getText(HodinaSetting::KEY_COMMERCE_SUCCESS_MESSAGE, 'Merci, ton e-mail est bien enregistré. On te préviendra dès que les commandes seront disponibles.'),
            'cartLockedMessage' => $this->getCartLockedMessageFor($mode, $reopensAt),

            // Backward-compatible keys kept for templates not yet refactored.
            'openingAt' => $reopensAt,
            'openingAtIso' => $reopensAt?->format(\DateTimeInterface::ATOM),
            'isBeforeOpening' => $isBannerVisible,
        ];
    }

    public function isCartLocked(): bool
    {
        return (bool) $this->getState()['isCartLocked'];
    }

    public function getCartLockedMessage(): string
    {
        $state = $this->getState();

        return (string) $state['cartLockedMessage'];
    }

    public function ensureDefaultSettings(): void
    {
        $defaults = [
            HodinaSetting::KEY_COMMERCE_MODE => [
                'Mode commerce',
                self::MODE_PREOPENING,
                'Mode général du portail : open = commandes ouvertes, preopening = préouverture, maintenance = mise à jour production, closed = fermeture manuelle.',
                HodinaSetting::TYPE_CHOICE,
                HodinaSetting::GROUP_COMMERCE,
                10,
            ],
            HodinaSetting::KEY_COMMERCE_REOPENS_AT => [
                'Date de réactivation des commandes',
                '',
                'Date et heure à laquelle les commandes publiques seront réactivées. Exemple : 2026-06-30 18:00.',
                HodinaSetting::TYPE_TEXT,
                HodinaSetting::GROUP_COMMERCE,
                20,
            ],
            HodinaSetting::KEY_COMMERCE_CART_LOCKED => [
                'Bloquer panier et commandes publiques',
                '1',
                'Bloque côté serveur l’ajout au panier et la validation de commande pour le public. Valeurs : 1 = bloqué, 0 = ouvert.',
                HodinaSetting::TYPE_BOOLEAN,
                HodinaSetting::GROUP_COMMERCE,
                30,
            ],
            HodinaSetting::KEY_COMMERCE_ALLOW_TESTERS => [
                'Autoriser les testeurs pendant le blocage',
                '1',
                'Autorise les comptes avec le rôle ROLE_COMMERCE_TESTER, ainsi que les administrateurs, à utiliser le portail normalement malgré le blocage public.',
                HodinaSetting::TYPE_BOOLEAN,
                HodinaSetting::GROUP_COMMERCE,
                40,
            ],
            HodinaSetting::KEY_COMMERCE_BANNER_TITLE => [
                'Titre bannière commerce',
                'Votre marché en ligne de produits locaux arrive bientôt',
                'Titre affiché dans la bannière de préouverture ou de maintenance commerciale.',
                HodinaSetting::TYPE_TEXT,
                HodinaSetting::GROUP_COMMERCE,
                50,
            ],
            HodinaSetting::KEY_COMMERCE_BANNER_MESSAGE => [
                'Message bannière commerce',
                'Le catalogue est accessible, mais la prise de commande sera possible à la date officielle. Laisse nous ton e-mail pour être informé de l’ouverture.',
                'Texte affiché sous le compte à rebours ou dans la bannière de blocage commercial.',
                HodinaSetting::TYPE_TEXTAREA,
                HodinaSetting::GROUP_COMMERCE,
                60,
            ],
            HodinaSetting::KEY_COMMERCE_BANNER_BUTTON_LABEL => [
                'Bouton capture e-mail commerce',
                'Me faire signe à l’ouverture',
                'Libellé du bouton de capture e-mail affiché dans la bannière.',
                HodinaSetting::TYPE_TEXT,
                HodinaSetting::GROUP_COMMERCE,
                70,
            ],
            HodinaSetting::KEY_COMMERCE_EMAIL_CAPTURE_ENABLED => [
                'Capture e-mail commerce active',
                '1',
                'Active ou désactive le formulaire e-mail dans la bannière. Valeurs : 1 = actif, 0 = inactif.',
                HodinaSetting::TYPE_BOOLEAN,
                HodinaSetting::GROUP_COMMERCE,
                80,
            ],
            HodinaSetting::KEY_COMMERCE_SUCCESS_MESSAGE => [
                'Message succès inscription commerce',
                'Merci, ton e-mail est bien enregistré. On te préviendra dès que les commandes seront disponibles.',
                'Message affiché après capture e-mail réussie.',
                HodinaSetting::TYPE_TEXTAREA,
                HodinaSetting::GROUP_COMMERCE,
                90,
            ],
        ];

        $repo = $this->em->getRepository(HodinaSetting::class);
        foreach ($defaults as $key => [$label, $value, $help, $fieldType, $groupKey, $sortOrder]) {
            if ($repo->findOneBy(['settingKey' => $key]) instanceof HodinaSetting) {
                continue;
            }

            $setting = (new HodinaSetting())
                ->setSettingKey($key)
                ->setLabel($label)
                ->setValue($value)
                ->setHelp($help)
                ->setFieldType($fieldType)
                ->setGroupKey($groupKey)
                ->setSortOrder($sortOrder);

            $this->em->persist($setting);
        }

        $this->em->flush();
    }

    private function getMode(): string
    {
        $mode = mb_strtolower($this->getText(HodinaSetting::KEY_COMMERCE_MODE, self::MODE_OPEN));

        return in_array($mode, self::VALID_MODES, true) ? $mode : self::MODE_OPEN;
    }

    private function getModeLabel(string $mode): string
    {
        return match ($mode) {
            self::MODE_PREOPENING => 'Préouverture Hodina',
            self::MODE_MAINTENANCE => 'Maintenance commerciale',
            self::MODE_CLOSED => 'Commandes suspendues',
            default => 'Commandes ouvertes',
        };
    }

    private function getReopensAt(): ?\DateTimeImmutable
    {
        $value = $this->getText(HodinaSetting::KEY_COMMERCE_REOPENS_AT, '');
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function getCartLockedMessageFor(string $mode, ?\DateTimeImmutable $reopensAt): string
    {
        $prefix = match ($mode) {
            self::MODE_MAINTENANCE => 'Les commandes Hodina sont temporairement suspendues pendant la mise à jour.',
            self::MODE_CLOSED => 'Les commandes Hodina sont temporairement suspendues.',
            self::MODE_PREOPENING => 'Les commandes Hodina ne sont pas encore ouvertes.',
            default => 'Les commandes Hodina sont temporairement indisponibles.',
        };

        if ($reopensAt instanceof \DateTimeImmutable) {
            return sprintf('%s Réactivation prévue le %s à %s.', $prefix, $reopensAt->format('d/m/Y'), $reopensAt->format('H:i'));
        }

        return $prefix;
    }

    private function isCurrentUserAllowedTester(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_COMMERCE_TESTER');
    }

    private function getText(string $key, string $default = ''): string
    {
        $setting = $this->em->getRepository(HodinaSetting::class)->findOneBy(['settingKey' => $key]);
        if (!$setting instanceof HodinaSetting) {
            return $default;
        }

        return $setting->getValueOrDefault($default);
    }

    private function getBool(string $key, bool $default): bool
    {
        $value = mb_strtolower($this->getText($key, $default ? '1' : '0'));

        return in_array($value, ['1', 'true', 'yes', 'oui', 'on', 'actif', 'active'], true);
    }
}
