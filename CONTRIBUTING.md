# Contributing to Fleximo

Thanks for your interest in contributing! Fleximo is an open-source, multi-tenant mobile ordering platform, and we welcome contributions of all kinds — bug reports, feature proposals, docs, and code.

This document describes the lightweight workflow we follow. The Japanese version is available as [`CONTRIBUTING.ja.md`](./CONTRIBUTING.ja.md) (coming soon) — for now, Japanese/English mixing in issues and PRs is welcome.

---

## Code of Conduct

This project adheres to the [Contributor Covenant Code of Conduct](./CODE_OF_CONDUCT.md). By participating, you agree to uphold it. Report unacceptable behavior to **matsui@ltac.co.jp**.

---

## Ways to contribute

- **Report a bug** — open an issue with reproduction steps, expected vs actual behavior, and environment info.
- **Propose a feature** — open an issue with `[proposal]` in the title. Discuss scope before writing code.
- **Improve documentation** — typo fixes, clearer explanations, new how-to guides.
- **Fix or implement** — pick up an open issue or propose a PR directly (small changes).
- **Translate** — Japanese ↔ English translations of `README.*`, `docs/`, UI strings.

For anything larger than a trivial fix, **please open an issue first** so we can align on the approach before you invest time.

---

## Branch naming

We use [GitHub Flow](https://docs.github.com/en/get-started/using-github/github-flow): `main` is always deployable, and all work happens in short-lived branches with purpose prefixes.

| Prefix | Purpose |
| --- | --- |
| `feat/` | New feature |
| `fix/` | Bug fix |
| `docs/` | Documentation-only change |
| `chore/` | Build config, deps, chores |
| `refactor/` | Internal restructure without behavior change |

Examples: `feat/add-printer-integration`, `fix/webhook-signature-check`, `docs/multi-tenancy-diagram`

Do not push directly to `main`. All changes go through pull requests.

---

## Commit messages

We follow [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/):

```
<type>(<scope>)?(!)?: <subject>

<body: optional, explain *why* — the diff already shows *what*>

<footer: optional, BREAKING CHANGE / Refs>
```

- `type` is one of: `feat`, `fix`, `refactor`, `perf`, `docs`, `test`, `chore`, `ci`, `style`
- `!` after type or `BREAKING CHANGE:` in footer indicates a breaking change
- Subject line ≤ 50 chars, imperative mood, no trailing period
- Mixed Japanese/English is fine; be consistent within the repository
- One commit = one intent

Full guide (Japanese): [`docs/how-to/commit-guidelines.md`](./docs/how-to/commit-guidelines.md)

---

## Development setup

See [`README.md#quick-start-local-development`](./README.md#quick-start-local-development) for the full setup. Quick version:

```bash
git clone https://github.com/ltac0203-pixel/fleximo-oss.git
cd fleximo-oss
composer install
cp .env.example .env
php artisan key:generate
# configure MariaDB/MySQL, Redis, fincode keys in .env
php artisan migrate --seed
npm install
npm run dev
```

In a second shell:

```bash
php artisan serve
php artisan queue:listen
```

> **Note**: Tests run against MariaDB/MySQL. SQLite is **not** supported.

---

## Before you open a PR

Run the full check suite locally:

```bash
composer test               # PHPUnit
npm run test                # Vitest
npm run test:e2e            # Playwright (requires running app)
vendor/bin/phpstan analyse  # Static analysis
vendor/bin/pint             # PHP formatting (auto-fix)
npm run lint                # ESLint
npm run build               # Vite production build
```

All of the above must pass before a PR is ready for review. CI will re-run them.

### Checklist

- [ ] Branch is based on up-to-date `main`
- [ ] Commits follow Conventional Commits
- [ ] Lint, test, build all pass locally
- [ ] New code has tests (unit or feature, whichever is appropriate)
- [ ] Public APIs / schema changes are reflected in `docs/`
- [ ] `CHANGELOG.md` `[Unreleased]` section updated for user-visible changes
- [ ] No secrets, PII, or card data added to logs, fixtures, or config

---

## PR guidelines

- **Keep PRs focused** — one concern per PR. Split refactors from features.
- **Write a clear description** — what changed, why, and how to test. Link the related issue with `Closes #123`.
- **Screenshots for UI changes** — before/after images or short GIFs help reviewers.
- **Mark drafts explicitly** — open as Draft if you want early feedback but aren't ready for merge.
- **Respond to reviews promptly** — if a review is blocked on you, push follow-up commits rather than force-pushing over discussion history.
- **Squash vs merge**: maintainers will squash-merge by default. Keep commit history clean within your branch but don't worry about perfect history — the squashed commit is what lands on `main`.

---

## Security issues

**Do not open a public issue for security vulnerabilities.** See [`SECURITY.md`](./SECURITY.md) for the private disclosure process.

---

## Scope & design principles

Before proposing a large feature, please read:

- [`docs/explanation/design-principles.md`](./docs/explanation/design-principles.md) — what Fleximo is and isn't
- [`docs/explanation/multi-tenancy.md`](./docs/explanation/multi-tenancy.md) — tenant isolation model
- [`docs/reference/architecture.md`](./docs/reference/architecture.md) — layered architecture

Features that **break tenant isolation**, **log sensitive payment/PII data**, or **couple the core to a specific payment provider outside the adapter layer** will not be accepted.

---

## License

By contributing, you agree that your contributions will be licensed under the [Apache License 2.0](./LICENSE), the same license as the project. You retain copyright to your contributions.

---

## Questions

- General questions: open a [GitHub Discussion](https://github.com/ltac0203-pixel/fleximo-oss/discussions)
- Bug / feature: open an [Issue](https://github.com/ltac0203-pixel/fleximo-oss/issues)
- Security: **matsui@ltac.co.jp** (see [`SECURITY.md`](./SECURITY.md))

Thanks for helping make Fleximo better.
