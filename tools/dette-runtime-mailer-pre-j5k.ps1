<#
Hodina - dette technique runtime / mailer avant J5K
Date : 2026-06-19

Objectif :
- ajouter les regles .gitignore runtime ;
- sortir env/uploads/assets du suivi Git sans supprimer les fichiers locaux ;
- conserver public/uploads/products/.gitkeep ;
- afficher les verifications a relire avant commit.

A lancer depuis : E:\hodina\hodina.fr
#>

$ErrorActionPreference = "Stop"

Write-Host "== Hodina dette runtime / mailer pre-J5K =="

if (-not (Test-Path ".git")) {
    throw "Ce dossier ne semble pas etre un depot Git. Place-toi dans E:\hodina\hodina.fr."
}

Write-Host ""
Write-Host "[1/5] Statut initial"
git status --short

Write-Host ""
Write-Host "[2/5] Preparation du dossier uploads runtime"
New-Item -ItemType Directory -Force "public\uploads\products" | Out-Null
New-Item -ItemType File -Force "public\uploads\products\.gitkeep" | Out-Null

Write-Host ""
Write-Host "[3/5] Ajout / verification du bloc .gitignore"
$gitignore = ".gitignore"
$marker = "Hodina runtime"
$block = @'

### Hodina runtime, secrets et fichiers generes ###
.env.local
.env.*.local
prod.env.local

/public/uploads/products/*
!/public/uploads/products/.gitkeep

/public/assets/
/public/error_log
'@

if (-not (Test-Path $gitignore)) {
    New-Item -ItemType File -Path $gitignore | Out-Null
}

$content = Get-Content $gitignore -Raw
if ($content -notmatch [regex]::Escape($marker)) {
    Add-Content -Path $gitignore -Value $block
    Write-Host "Bloc .gitignore ajoute."
} else {
    Write-Host "Bloc .gitignore deja present."
}

Write-Host ""
Write-Host "[4/5] Sortie du suivi Git sans suppression locale"
git rm --cached --ignore-unmatch .env.local .env.prod.local prod.env.local

git rm --cached -r --ignore-unmatch public\assets

git rm --cached -r --ignore-unmatch public\uploads\products

git add .gitignore public\uploads\products\.gitkeep

Write-Host ""
Write-Host "[5/5] Verifications"
Write-Host ""
Write-Host "--- git status --short ---"
git status --short

Write-Host ""
Write-Host "--- fichiers sensibles encore suivis ? ---"
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products

Write-Host ""
Write-Host "Resultat attendu ci-dessus : public/uploads/products/.gitkeep uniquement."
Write-Host "Relis le statut avant commit. Ne committe aucun secret ni aucune image uploadee."
