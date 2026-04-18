# Security Policy

Fleximo handles restaurant menus, customer orders, and payment flows through fincode. We take security seriously and appreciate responsible disclosure from the community.

---

## Supported versions

Fleximo is currently in MVP (`0.x.y`). We only provide security fixes for the **latest minor release** on the `main` branch.

| Version | Supported |
| --- | --- |
| Latest tagged release (`v0.x.y`) | ✅ |
| `main` branch HEAD | ✅ |
| Older tagged releases | ❌ (please upgrade) |

Once Fleximo reaches `1.0.0`, this policy will be revised to cover the current major and the previous major.

---

## Reporting a vulnerability

**Please do NOT open a public GitHub issue, Pull Request, or Discussion** for security vulnerabilities. Public disclosure before a fix is released puts self-hosters at risk.

### Preferred channel

Email the maintainer privately:

- **matsui@ltac.co.jp** — subject line prefix: `[fleximo-oss security]`

Please include as much of the following as possible:

1. **Description** of the vulnerability and its impact
2. **Reproduction steps** (minimal PoC, affected URL/endpoint, request/response samples)
3. **Affected version / commit SHA**
4. **Environment** (PHP version, DB, whether stock `.env.example` was used)
5. **Suggested remediation** if you have one
6. Whether you would like public credit after disclosure (and how you'd like to be named)

If you prefer encrypted communication, mention it in your first message and we will exchange keys.

### Alternative channel

If email is unavailable, use GitHub's private vulnerability reporting:

1. Go to https://github.com/ltac0203-pixel/fleximo-oss/security/advisories/new
2. Submit a private advisory — only maintainers can see it

---

## What to expect

| Milestone | Target timeframe |
| --- | --- |
| Acknowledgement of your report | Within **3 business days** |
| Initial severity assessment | Within **7 business days** |
| Fix or mitigation plan | Depends on severity (see below) |
| Public disclosure | Coordinated with reporter after fix is released |

### Severity guide

| Severity | Examples | Target fix window |
| --- | --- | --- |
| **Critical** | Remote code execution, SQL injection, auth bypass, cross-tenant data leak, payment forgery | 7 days |
| **High** | Privilege escalation within a tenant, IDOR exposing other customers' orders | 14 days |
| **Medium** | Stored XSS, CSRF on sensitive action, information disclosure | 30 days |
| **Low** | Self-XSS, minor info leak with no direct exploit path | 60 days |

These are targets, not guarantees — complex issues may take longer. We will keep you informed of progress.

---

## Disclosure policy

We follow **coordinated disclosure**:

1. Report received → private triage
2. Fix developed and tested on a private branch
3. Patched release published (new tag on `main`)
4. GitHub Security Advisory published, crediting the reporter (if they consent)
5. CHANGELOG / release notes reference the advisory

We aim to disclose publicly within **90 days** of the initial report or immediately after a fix is released, whichever is sooner.

---

## Scope

### In scope

- The `main` branch and latest tagged release of `ltac0203-pixel/fleximo-oss`
- Default configuration from `.env.example`
- Documented deployment paths (Xserver / VPS, as described in `docs/`)
- Official Docker images (once published)

### Out of scope

- Vulnerabilities in **third-party dependencies** — please report those upstream (Laravel, fincode SDK, npm packages, etc.). If a dependency CVE affects Fleximo, we will update and re-release.
- **Self-hosted deployments with custom modifications** — we cannot triage issues introduced by local forks.
- **Denial-of-service** from untuned infrastructure (e.g., no rate limiting at the LB layer) — this is a deployment concern, not a code vulnerability.
- **Social engineering** against maintainers or contributors.
- **Physical attacks** against infrastructure.
- Reports generated solely by automated scanners with no demonstrated impact.
- Best-practice suggestions without a concrete vulnerability (please open a regular issue instead).

### Reference deployment

The reference deployment at `https://fleximo.jp` is operated by the maintainer (L.Tac Inc.) under a separate commercial context. Vulnerabilities found there that also affect the OSS code are in scope; vulnerabilities specific to the hosted service's infrastructure should be reported to the same email but will be triaged separately.

---

## Safe harbor

We consider security research conducted under this policy to be:

- Authorized under applicable anti-hacking laws (we will not pursue civil or criminal action)
- Exempt from DMCA / anti-circumvention claims
- Conducted in good faith

…provided you:

- Make a good-faith effort to avoid privacy violations, data destruction, and service disruption
- Only interact with accounts/tenants you own or have explicit permission to test
- Give us reasonable time to fix issues before any public disclosure
- Do not exploit the vulnerability beyond what's necessary to demonstrate it

If legal action is initiated by a third party against you for activities conducted under this policy, we will make it known that your actions were authorized.

---

## Secrets handling

For self-hosters: never commit real credentials. If you accidentally push secrets (fincode API keys, DB passwords, `APP_KEY`), **rotate them immediately** and force-push a clean history. Fleximo's `.gitignore` excludes `.env` by default, but copy-paste accidents happen.

If you discover **exposed credentials in a public Fleximo repository** (this repo, forks, or a user's deployment), please report it via the channels above.

---

## Acknowledgements

We maintain a list of researchers who have responsibly disclosed vulnerabilities in `SECURITY-HALL-OF-FAME.md` (with consent). Thank you for helping keep Fleximo and its users safe.
