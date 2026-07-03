# TSEMOU OS Architecture Lock v0.9.7

## Stable content model

- `company` = Company Intelligence profile.
- `story` = TSEMOU File / investigation workspace. This is an internal CPT slug only.
- `tsemou_proof` = Proof / Evidence object.
- `post` = Public article / news article.

## Rules

1. Related Articles must query only WordPress `post`.
2. Latest Evidence must query Proof / Evidence objects.
3. TSEMOU Files must not be treated as public articles.
4. Do not add plugins or snippets for core behavior.
5. All core behavior stays inside TSEMOU Core.
6. Test only on tsemoulab.com before production.
