# Actions — dette technique pré-J5K

Date : **19/06/2026**

## Ordre recommandé

1. Créer une branche courte depuis `main`.
2. Appliquer le bloc `.gitignore` Hodina runtime.
3. Sortir env / uploads / assets du suivi Git avec `git rm --cached`.
4. Garder seulement `public/uploads/products/.gitkeep`.
5. Vérifier `git ls-files`.
6. Mettre à jour les docs.
7. Committer.
8. Déployer en recette avec le script par tag.
9. Vérifier que les fichiers serveur existent toujours.
10. Déployer en prod.

## Branche conseillée

```powershell
git checkout main
git pull
git checkout -b chore/runtime-env-uploads-assets-mailer
```

## Commit conseillé

```powershell
git commit -m "chore(runtime): clean env uploads assets and document mailer dsn"
```

## Tag conseillé après merge main

```powershell
git tag pre-j5k-runtime-mailer-20260619
git push origin pre-j5k-runtime-mailer-20260619
```

## Passage à J5K

J5K peut démarrer uniquement quand :

```text
git ls-files .env.local .env.prod.local prod.env.local public/assets public/uploads/products
```

renvoie seulement :

```text
public/uploads/products/.gitkeep
```
