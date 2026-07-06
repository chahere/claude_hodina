param(
    [switch]$ResetIds,
    [switch]$AssumeYes,
    [switch]$DryRun,
    [string]$PhpBin = "php"
)

Write-Host "=== Nettoyage des anciennes commandes Hodina ===" -ForegroundColor Cyan
Write-Host ""

# Sécurité : vérifier qu'on est bien dans un projet Symfony
if (-not (Test-Path "bin/console")) {
    Write-Host "ERREUR : bin/console introuvable. Place-toi à la racine du projet Hodina avant de lancer ce script." -ForegroundColor Red
    exit 1
}

function Run-Sql {
    param(
        [string]$Sql,
        [string]$Label
    )

    Write-Host ""
    Write-Host ">>> $Label" -ForegroundColor Yellow
    Write-Host $Sql -ForegroundColor DarkGray

    if ($DryRun) {
        Write-Host "DRY RUN : SQL non exécuté." -ForegroundColor DarkYellow
        return
    }

    & $PhpBin bin/console dbal:run-sql "$Sql"

    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERREUR pendant : $Label" -ForegroundColor Red
        exit $LASTEXITCODE
    }
}

Write-Host "Vérification AVANT suppression..." -ForegroundColor Cyan

Run-Sql "SELECT COUNT(*) AS nb_commandes FROM customer_order;" "Nombre de commandes"
Run-Sql "SELECT COUNT(*) AS nb_lignes_commande FROM order_item;" "Nombre de lignes de commande"
Run-Sql "SELECT COUNT(*) AS nb_sms_lies_commandes FROM sms_log WHERE customer_order_id IS NOT NULL;" "Nombre de SMS liés aux commandes"
Run-Sql "SELECT COUNT(*) AS nb_emails_lies_commandes FROM email_log WHERE customer_order_id IS NOT NULL;" "Nombre d'emails liés aux commandes"

Write-Host ""
Write-Host "ATTENTION : ce script supprime toutes les commandes, lignes de commande, SMS et emails liés aux commandes." -ForegroundColor Red
Write-Host "Tables nettoyées : sms_log liés commandes, email_log liés commandes, order_item, customer_order." -ForegroundColor Red
Write-Host "Tables conservées : clients, vendeurs, produits, communes, zones, réglages Hodina." -ForegroundColor Green

if (-not $AssumeYes) {
    $confirmation = Read-Host "Tape OUI pour confirmer"

    if ($confirmation -ne "OUI") {
        Write-Host "Opération annulée." -ForegroundColor Yellow
        exit 0
    }
} else {
    Write-Host "AssumeYes actif : confirmation automatique." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Suppression en cours..." -ForegroundColor Cyan

Run-Sql "DELETE FROM sms_log WHERE customer_order_id IS NOT NULL;" "Suppression des SMS liés aux commandes"
Run-Sql "DELETE FROM email_log WHERE customer_order_id IS NOT NULL;" "Suppression des emails liés aux commandes"
Run-Sql "DELETE FROM order_item;" "Suppression des lignes de commande"
Run-Sql "DELETE FROM customer_order;" "Suppression des commandes"

if ($ResetIds) {
    Write-Host ""
    Write-Host "Reset des AUTO_INCREMENT..." -ForegroundColor Cyan

    Run-Sql "ALTER TABLE order_item AUTO_INCREMENT = 1;" "Reset ID order_item"
    Run-Sql "ALTER TABLE customer_order AUTO_INCREMENT = 1;" "Reset ID customer_order"
    Run-Sql "ALTER TABLE sms_log AUTO_INCREMENT = 1;" "Reset ID sms_log"
    Run-Sql "ALTER TABLE email_log AUTO_INCREMENT = 1;" "Reset ID email_log"
}

Write-Host ""
Write-Host "Vérification APRÈS suppression..." -ForegroundColor Cyan

Run-Sql "SELECT COUNT(*) AS nb_commandes_restantes FROM customer_order;" "Commandes restantes"
Run-Sql "SELECT COUNT(*) AS nb_lignes_restantes FROM order_item;" "Lignes de commande restantes"
Run-Sql "SELECT COUNT(*) AS nb_sms_lies_commandes_restants FROM sms_log WHERE customer_order_id IS NOT NULL;" "SMS liés aux commandes restants"
Run-Sql "SELECT COUNT(*) AS nb_emails_lies_commandes_restants FROM email_log WHERE customer_order_id IS NOT NULL;" "Emails liés aux commandes restants"

Write-Host ""
if ($DryRun) {
    Write-Host "Simulation terminée. Aucune donnée n'a été supprimée." -ForegroundColor Yellow
} else {
    Write-Host "Nettoyage terminé avec succès." -ForegroundColor Green
}
