<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'hodina_setting')]
#[ORM\UniqueConstraint(name: 'UNIQ_HODINA_SETTING_KEY', columns: ['setting_key'])]
#[ORM\Index(name: 'IDX_HODINA_SETTING_GROUP_SORT', columns: ['group_key', 'sort_order'])]
class HodinaSetting
{
    public const KEY_ORDER_REFERENCE_PREFIX = 'order_reference_prefix';
    public const KEY_DELIVERED_COMMUNES = 'delivered_communes';
    public const KEY_GLOBAL_MARGIN_RATE = 'global_margin_rate';
    public const KEY_GLOBAL_COMMUNE_CROSSING_CUSTOMER_FEE = 'global_commune_crossing_customer_fee';
    public const KEY_GLOBAL_COMMUNE_CROSSING_COURIER_PAYOUT = 'global_commune_crossing_courier_payout';
    public const KEY_GLOBAL_DELIVERY_CUSTOMER_FEE_CAP = 'global_delivery_customer_fee_cap';
    public const KEY_GLOBAL_DELIVERY_COURIER_PAYOUT_CAP = 'global_delivery_courier_payout_cap';
    public const KEY_GLOBAL_MULTI_SELLER_EXTRA_CUSTOMER_FEE = 'global_multi_seller_extra_customer_fee';
    public const KEY_GLOBAL_MULTI_SELLER_EXTRA_CUSTOMER_FEE_CAP = 'global_multi_seller_extra_customer_fee_cap';

    public const KEY_COMMERCE_MODE = 'commerce_mode';
    public const KEY_COMMERCE_REOPENS_AT = 'commerce_reopens_at';
    public const KEY_COMMERCE_CART_LOCKED = 'commerce_cart_locked';
    public const KEY_COMMERCE_ALLOW_TESTERS = 'commerce_allow_testers';
    public const KEY_COMMERCE_BANNER_TITLE = 'commerce_banner_title';
    public const KEY_COMMERCE_BANNER_MESSAGE = 'commerce_banner_message';
    public const KEY_COMMERCE_BANNER_BUTTON_LABEL = 'commerce_banner_button_label';
    public const KEY_COMMERCE_EMAIL_CAPTURE_ENABLED = 'commerce_email_capture_enabled';
    public const KEY_COMMERCE_SUCCESS_MESSAGE = 'commerce_success_message';

    public const KEY_COURIER_PAYOUTS_ENABLED = 'courier_payouts_enabled';
    public const KEY_COURIER_PAYOUT_CRON_ENABLED = 'courier_payout_cron_enabled';
    public const KEY_COURIER_PAYOUT_ADMIN_RECAP_ENABLED = 'courier_payout_admin_recap_enabled';
    public const KEY_COURIER_PAYOUT_FREQUENCY = 'courier_payout_frequency';

    public const KEY_AI_CHATBOT_ENABLED = 'ai_chatbot_enabled';
    public const KEY_SUPPORT_MESSENGER_URL = 'support_messenger_url';

    public const KEY_EMAIL_BRANDING_SUBJECT_PREFIX = 'email_branding_subject_prefix';
    public const KEY_EMAIL_BRANDING_OPENING_FORMULA = 'email_branding_opening_formula';
    public const KEY_EMAIL_BRANDING_CLOSING_FORMULA = 'email_branding_closing_formula';
    public const KEY_EMAIL_BRANDING_SIGNATURE = 'email_branding_signature';
    public const KEY_EMAIL_SENDER_NAME = 'email_sender_name';
    public const KEY_EMAIL_SENDER_EMAIL = 'email_sender_email';
    public const KEY_EMAIL_REPLY_TO_NAME = 'email_reply_to_name';
    public const KEY_EMAIL_REPLY_TO_EMAIL = 'email_reply_to_email';
    public const KEY_EMAIL_ORDER_CREATED_COPY_EMAIL = 'email_order_created_copy_email';

    public const COURIER_PAYOUT_FREQUENCY_SEMI_MONTHLY = 'semi_monthly';

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_CHOICE = 'choice';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_EMAIL = 'email';
    public const TYPE_URL = 'url';

    public const GROUP_GENERAL = 'general';
    public const GROUP_COMMERCE = 'commerce';
    public const GROUP_LOGISTICS = 'logistics';
    public const GROUP_NOTIFICATIONS = 'notifications';
    public const GROUP_EMAIL_BRANDING = 'email_branding';
    public const GROUP_PAYMENTS = 'payments';
    public const GROUP_TECHNICAL = 'technical';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $settingKey = '';

    #[ORM\Column(length: 120)]
    private string $label = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $help = null;

    #[ORM\Column(length: 30)]
    private string $fieldType = self::TYPE_TEXT;

    #[ORM\Column(length: 60, options: ['default' => self::GROUP_GENERAL])]
    private string $groupKey = self::GROUP_GENERAL;

    #[ORM\Column(length: 120, options: ['default' => 'Général'])]
    private string $groupLabel = 'Général';

    #[ORM\Column(options: ['default' => 100])]
    private int $sortOrder = 100;

    #[ORM\Column(options: ['default' => true])]
    private bool $isEditable = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $isSensitive = false;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->label !== '' ? $this->label : 'Réglage Hodina';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): self
    {
        $this->settingKey = strtolower(trim($settingKey));
        $this->touch();

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = trim($label);
        $this->touch();

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value !== null ? trim($value) : null;
        $this->touch();

        return $this;
    }

    public function getValueOrDefault(string $default): string
    {
        $value = $this->value !== null ? trim($this->value) : '';

        return $value !== '' ? $value : $default;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): self
    {
        $this->help = $help !== null ? trim($help) : null;
        $this->touch();

        return $this;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function setFieldType(string $fieldType): self
    {
        $fieldType = trim($fieldType);
        $this->fieldType = $fieldType !== '' ? $fieldType : self::TYPE_TEXT;
        $this->touch();

        return $this;
    }

    public function getGroupKey(): string
    {
        return $this->groupKey;
    }

    public function setGroupKey(string $groupKey): self
    {
        $groupKey = strtolower(trim($groupKey));
        $this->groupKey = $groupKey !== '' ? $groupKey : self::GROUP_GENERAL;
        $this->groupLabel = self::getGroupLabelForKey($this->groupKey);
        $this->touch();

        return $this;
    }

    public function getGroupLabel(): string
    {
        return $this->groupLabel;
    }

    public function setGroupLabel(string $groupLabel): self
    {
        $groupLabel = trim($groupLabel);
        $this->groupLabel = $groupLabel !== '' ? $groupLabel : self::getGroupLabelForKey($this->groupKey);
        $this->touch();

        return $this;
    }

    public function setGroup(string $groupKey, ?string $groupLabel = null): self
    {
        $this->setGroupKey($groupKey);

        if ($groupLabel !== null) {
            $this->setGroupLabel($groupLabel);
        }

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        $this->touch();

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    public function setIsEditable(bool $isEditable): self
    {
        $this->isEditable = $isEditable;
        $this->touch();

        return $this;
    }

    public function isSensitive(): bool
    {
        return $this->isSensitive;
    }

    public function setIsSensitive(bool $isSensitive): self
    {
        $this->isSensitive = $isSensitive;
        $this->touch();

        return $this;
    }

    public function getDisplayValue(): string
    {
        if ($this->isSensitive) {
            return $this->value !== null && trim($this->value) !== '' ? '••••••' : '';
        }

        if ($this->settingKey === self::KEY_DELIVERED_COMMUNES) {
            return implode(', ', $this->getValueList());
        }

        if (in_array($this->settingKey, self::getBooleanSettingKeys(), true)) {
            return $this->getBooleanValue() ? 'Oui' : 'Non';
        }

        if ($this->settingKey === self::KEY_COMMERCE_MODE) {
            return match ($this->value) {
                'open' => 'Ouvert',
                'preopening' => 'Préouverture',
                'maintenance' => 'Maintenance',
                'closed' => 'Fermé',
                default => $this->value ?? '',
            };
        }

        return $this->value ?? '';
    }

    public function getBooleanValue(): bool
    {
        $value = strtolower(trim((string) $this->value));

        return in_array($value, ['1', 'true', 'yes', 'oui', 'on', 'actif', 'active'], true);
    }

    public function setBooleanValue(bool $enabled): self
    {
        $this->value = $enabled ? '1' : '0';
        $this->touch();

        return $this;
    }

    /**
     * @return list<string>
     */
    public static function getBooleanSettingKeys(): array
    {
        return [
            self::KEY_COMMERCE_CART_LOCKED,
            self::KEY_COMMERCE_ALLOW_TESTERS,
            self::KEY_COMMERCE_EMAIL_CAPTURE_ENABLED,
            self::KEY_COURIER_PAYOUTS_ENABLED,
            self::KEY_COURIER_PAYOUT_CRON_ENABLED,
            self::KEY_COURIER_PAYOUT_ADMIN_RECAP_ENABLED,
            self::KEY_AI_CHATBOT_ENABLED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getGroupChoices(): array
    {
        return [
            'Général' => self::GROUP_GENERAL,
            'Commerce & commandes' => self::GROUP_COMMERCE,
            'Livraison & logistique' => self::GROUP_LOGISTICS,
            'Notifications' => self::GROUP_NOTIFICATIONS,
            'Branding e-mail' => self::GROUP_EMAIL_BRANDING,
            'Paiements' => self::GROUP_PAYMENTS,
            'Technique / maintenance' => self::GROUP_TECHNICAL,
        ];
    }

    public static function getGroupLabelForKey(string $groupKey): string
    {
        $labelsByKey = array_flip(self::getGroupChoices());

        return $labelsByKey[$groupKey] ?? 'Général';
    }

    /**
     * @return array<int, string>
     */
    public function getValueList(): array
    {
        if ($this->value === null || trim($this->value) === '') {
            return [];
        }

        $rawValue = trim($this->value);
        $decoded = json_decode($rawValue, true);

        if (is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = preg_split('/[\r\n,;]+/', $rawValue) ?: [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $items
        ))));
    }

    /**
     * @param array<int, string|null> $items
     */
    public function setValueList(array $items): self
    {
        $cleanItems = array_values(array_unique(array_filter(array_map(
            static fn (?string $item): string => trim((string) $item),
            $items
        ))));

        $this->value = $cleanItems === []
            ? null
            : json_encode($cleanItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->touch();

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
