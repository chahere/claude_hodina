<?php

namespace App\Controller\Admin;

use App\Entity\AiChatbotSetting;
use App\Repository\AiChatbotSettingRepository;
use App\Service\AiChatbotCredentialCipher;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Écran EasyAdmin "Réglages IA" : réglage unique (singleton) du fournisseur,
 * du modèle et de la clé API du chatbot. La clé API suit exactement le
 * pattern write-only de Customer::plainPassword (jamais réaffichée en clair,
 * blanc = on conserve la clé actuelle) et est chiffrée via
 * AiChatbotCredentialCipher (même mécanisme AES-256-GCM que
 * CustomerDeliveryCodeService).
 */
final class AiChatbotSettingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AiChatbotCredentialCipher $credentialCipher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AiChatbotSetting::class;
    }

    /**
     * Point d'entrée utilisé par le menu EasyAdmin : il n'y a qu'un seul
     * réglage IA, donc on redirige toujours directement vers son formulaire
     * d'édition (créé au besoin) plutôt que d'afficher une liste.
     */
    #[Route('/ouegnewe/reglages-ia', name: 'admin_ai_chatbot_setting_entry')]
    public function entry(AiChatbotSettingRepository $repository, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $setting = $repository->getOrCreateSingleton();

        $url = $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($setting->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réglages IA')
            ->setEntityLabelInPlural('Réglages IA')
            ->setPageTitle(Crud::PAGE_EDIT, 'Réglages IA — fournisseur, modèle et clé API');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE)
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn (Action $action): Action => $action->setLabel('Enregistrer'))
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn (Action $action): Action => $action->setLabel('Retour au tableau de bord'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield ChoiceField::new('provider', 'Fournisseur LLM')
            ->setChoices([
                'Mode test (simulé, aucun appel réseau)' => AiChatbotSetting::PROVIDER_MOCK,
                'Anthropic' => AiChatbotSetting::PROVIDER_ANTHROPIC,
                'OpenAI' => AiChatbotSetting::PROVIDER_OPENAI,
            ])
            ->setHelp('Changer de fournisseur ne nécessite aucun redéploiement : le choix est effectif immédiatement.');

        yield TextField::new('model', 'Nom du modèle')
            ->setHelp('Exemple : claude-sonnet-5, gpt-4o. Ignoré en mode test.');

        yield TextField::new('plainApiKey', 'Clé API')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions([
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Laisser vide pour conserver la clé actuelle',
                ],
            ])
            ->setHelp('Ne sera plus jamais réaffichée après enregistrement, y compris ici. Laisse ce champ vide pour ne pas changer la clé existante.')
            ->onlyOnForms();

        yield BooleanField::new('apiKey', 'Clé API enregistrée')
            ->renderAsSwitch(false)
            ->setFormTypeOption('disabled', true)
            ->setHelp('Indicateur en lecture seule : confirme qu’une clé est enregistrée, sans jamais l’afficher.');

        yield DateTimeField::new('updatedAt', 'Dernière mise à jour')->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof AiChatbotSetting) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $this->encryptPlainApiKeyIfProvided($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof AiChatbotSetting) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $this->encryptPlainApiKeyIfProvided($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function encryptPlainApiKeyIfProvided(AiChatbotSetting $setting): void
    {
        $plainApiKey = trim((string) $setting->getPlainApiKey());

        if ($plainApiKey === '') {
            // Champ laissé vide : on conserve la clé chiffrée existante telle quelle.
            return;
        }

        $setting
            ->setApiKeyEncrypted($this->credentialCipher->encrypt($plainApiKey))
            ->setPlainApiKey(null);
    }
}
