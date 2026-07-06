# README — Dette technique env / uploads / assets / MAILER_DSN avant J5K

Date : **19/06/2026**  
Contexte : **après J5G-B4 v11 validé recette + production, avant J5K GPS livraison**

## Objectif

Traiter une dette courte et peu risquée avant d'ajouter le GPS de livraison :

- empêcher les secrets d'environnement d'entrer dans Git ;
- sortir les fichiers runtime déjà suivis par Git sans les supprimer physiquement ;
- confirmer que les assets Symfony AssetMapper restent générés, non versionnés ;
- documenter clairement `MAILER_DSN` pour recette / production ;
- éviter de confondre `EmailLog = SENT` avec un e-mail réellement reçu.

Cette étape ne doit pas modifier le métier, les entités ou les migrations. Elle prépare le terrain pour J5K.

---

## Audit rapide des sources du 19/06/2026

### Déjà correct / rassurant

Le script :

```text
tools/deploy-hodina-by-tag.sh
```

protège déjà les éléments runtime suivants pendant une MEP :

```text
.env.local
.env.prod.local
prod.env.local
public/uploads/products
```

Il tolère aussi `public/assets` comme dossier généré, puis exécute :

```bash
php bin/console asset-map:compile --env=prod
```

Le mailer Symfony est bien configuré pour lire le DSN depuis l'environnement :

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

### Dette restante

La dette restante n'est pas une dette fonctionnelle. C'est une dette Git / exploitation :

- `prod.env.local` est encore signalé comme suivi par Git ;
- d'anciennes images de `public/uploads/products/*` sont encore signalées comme suivies par Git ;
- `public/assets/` doit être explicitement ignoré ;
- `MAILER_DSN=null://null` est utile en local / sécurité par défaut, mais dangereux si on croit valider des e-mails réels ;
- `public/error_log` est un fichier runtime o2switch et ne doit pas être versionné.

---

## Règles Git à verrouiller

À ajouter ou vérifier dans le `.gitignore` racine :

```gitignore
### Hodina — runtime, secrets et fichiers générés ###
.env.local
.env.*.local
prod.env.local

/public/uploads/products/*
!/public/uploads/products/.gitkeep

/public/assets/
/public/error_log
```

Remarque : `public/uploads/products/.gitkeep` permet de garder le dossier dans Git sans versionner les photos réelles.

---

## Commandes locales PowerShell recommandées

Depuis le poste local :

```powershell
cd E:\hodina\hodina.fr

git status

# Créer le dossier runtime si nécessaire et garder seulement un marqueur Git.
New-Item -ItemType Directory -Force public\uploads\products | Out-Null
New-Item -ItemType File -Force public\uploads\products\.gitkeep | Out-Null

# Ajouter le bloc .gitignore s'il n'existe pas déjà.
$gitignore = ".gitignore"
$block = @'

### Hodina — runtime, secrets et fichiers générés ###
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
if ($content -notmatch "Hodina — runtime") {
    Add-Content -Path $gitignore -Value $block
}

# Sortir les secrets / runtime / assets du suivi Git sans supprimer les fichiers locaux.
git rm --cached --ignore-unmatch .env.local .env.prod.local prod.env.local

git rm --cached -r --ignore-unmatch public\assets

git rm --cached -r --ignore-unmatch public\uploads\products

# Réajouter uniquement le marqueur de dossier upload.
git add .gitignore public\uploads\products\.gitkeep

# Vérification : seuls .gitignore, .gitkeep et les docs doivent rester à committer.
git status --short

# Vérification : aucun secret ni image réelle ne doit rester suivi.
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products
```

Résultat attendu de la dernière commande :

```text
public/uploads/products/.gitkeep
```

Si une vraie image apparaît encore dans `git ls-files public/uploads/products`, ne pas continuer : il faut la ressortir du suivi.

---

## Validation serveur après merge / déploiement

Sur recette puis production, vérifier que les fichiers runtime n'ont pas été supprimés :

```bash
cd /home/vopu3712/hodina.fr
# ou /home/vopu3712/recette.hodina.fr selon la cible

ls -la .env.local .env.prod.local prod.env.local 2>/dev/null || true
ls -la public/uploads/products 2>/dev/null || true
ls -la public/assets 2>/dev/null || true
```

Vérifier que les secrets restent hors Git :

```bash
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products
```

Résultat attendu :

```text
public/uploads/products/.gitkeep
```

Vérifier la configuration mail sans afficher le mot de passe :

```bash
grep -n "MAILER_DSN" .env.local .env.prod.local prod.env.local .env 2>/dev/null \
  | sed -E 's#://([^:]+):[^@]+@#://\1:***@#'
```

Si les e-mails réels sont attendus, la valeur active ne doit pas être uniquement :

```env
MAILER_DSN=null://null
```

Test mail :

```bash
php bin/console mailer:test adresse@test.fr --env=prod
```

Validation complète :

```text
EmailLog = SENT
+ MAILER_DSN réel côté serveur
+ e-mail reçu dans une boîte réelle
```

---

## Point important sur `prod.env.local`

`prod.env.local` est conservé comme fichier historique / fallback opérationnel dans les scripts, mais Symfony charge naturellement :

```text
.env
.env.local
.env.prod
.env.prod.local
```

Pour éviter toute ambiguïté, les secrets réellement utilisés par l'application doivent être dans :

```text
.env.local
```

ou :

```text
.env.prod.local
```

Ne pas compter uniquement sur `prod.env.local` pour faire fonctionner Symfony.

---

## Hors périmètre volontaire de cette étape

Ne pas mélanger avec J5K :

- pas de latitude / longitude ;
- pas de migration ;
- pas de changement panier ;
- pas de changement EasyAdmin métier ;
- pas de refonte de déploiement atomique.

Autres nettoyages à garder pour plus tard si besoin :

- anciens fichiers `.bak` ;
- refonte complète `.env.example` ;
- page maintenance ;
- déploiement atomique ;
- correction des dépréciations Symfony / Doctrine.

---

## Commit conseillé

```bash
git add .gitignore public/uploads/products/.gitkeep docs tools

git commit -m "chore(runtime): clean env uploads assets and document mailer dsn"
```

Puis, après validation :

```bash
git push
```

Cette étape doit être validée avant d'ouvrir J5K GPS livraison.
