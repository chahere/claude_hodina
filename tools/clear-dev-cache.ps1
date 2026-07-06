Write-Host "Nettoyage rapide du cache dev Hodina..." -ForegroundColor Cyan

if ((Test-Path ".\bin\console") -and (Test-Path ".\var\cache")) {
    $oldCache = ".\var\cache\dev_old_" + (Get-Date -Format "yyyyMMddHHmmss")

    if (Test-Path ".\var\cache\dev") {
        Move-Item -Path ".\var\cache\dev" -Destination $oldCache -ErrorAction SilentlyContinue
    }

    php bin/console cache:clear
    php bin/console cache:warmup

    if (Test-Path $oldCache) {
        Start-Process powershell -WindowStyle Hidden -ArgumentList "-NoProfile -ExecutionPolicy Bypass -Command `"Remove-Item -Recurse -Force '$oldCache' -ErrorAction SilentlyContinue`""
    }

    Write-Host "Cache dev regenere. Ancien cache supprime en arriere-plan." -ForegroundColor Green
} else {
    Write-Host "Erreur: lance ce script depuis la racine du projet Hodina." -ForegroundColor Red
    exit 1
}