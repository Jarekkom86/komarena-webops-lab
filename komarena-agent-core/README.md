# KomArena Agent Core v0.1

KomArena Agent Core is the WordPress-side execution bridge for **KomArena Nexus**.
It is intentionally small and restrictive: Nexus decides *what* should happen,
while Core only executes explicitly allowlisted WordPress actions.

## v0.1 objective

Prove the first safe closed loop:

1. KomArena Web Auditor detects a real issue.
2. JARO OS / Nexus creates a repair task.
3. Repair Brain selects an allowlisted action.
4. Agent Core authenticates the request and checks replay/idempotency rules.
5. Agent Core snapshots the affected resource before a supported write.
6. Agent Core executes the action.
7. Nexus runs verification and the Auditor checks the site again.
8. The task is closed, retried, rolled back, or escalated.

## Security model

Core v0.1 deliberately does **not** support arbitrary PHP, `eval`, shell commands,
remote file execution, arbitrary ZIP/plugin upload, or module installation.

Every REST request must include:

- `X-KomArena-Site`
- `X-KomArena-Timestamp`
- `X-KomArena-Nonce`
- `X-KomArena-Signature`

The signature is HMAC-SHA256 over this canonical string:

```text
TIMESTAMP\n
NONCE\n
HTTP_METHOD\n
REST_ROUTE\n
RAW_BODY
```

Example route: `/komarena-agent/v1/tasks`.

The shared pairing secret is generated locally when the plugin activates and is
shown only to a WordPress administrator under **Settings → KomArena Agent Core**.
The secret is never committed to Git.

Protections in v0.1:

- HMAC request signing;
- five-minute timestamp window;
- nonce replay protection;
- 24-hour idempotency cache;
- action allowlist;
- autonomy-mode write gate;
- precondition SHA-256 for `post.update`;
- resource snapshot before `post.update`;
- bounded rollback history;
- no arbitrary-code execution.

## REST API

Namespace:

```text
/wp-json/komarena-agent/v1/
```

Endpoints:

- `GET status`
- `GET capabilities`
- `POST tasks`

### Task envelope

```json
{
  "task_id": "repair-123",
  "idempotency_key": "repair-123-attempt-1",
  "action": "site.inspect",
  "payload": {}
}
```

For `post.update`, Nexus can additionally send `expected_before_sha256`. Core
will reject the write with HTTP 409 if the resource changed after the repair
plan was created.

## v0.1 actions

Read-only:

- `site.inspect`
- `health.check`
- `plugin.list`
- `post.read`

Low-risk write actions (disabled in `Audit Only`):

- `post.update` — title/content/excerpt only, resource snapshot first;
- `cache.purge` — WordPress object cache plus extension hook;
- `rollback.execute` — restore a Core-created post snapshot.

`post.update` intentionally cannot change status, author, slug, taxonomy,
metadata, template, plugin files, theme files, checkout, orders, users, payment
configuration, or database schema.

## Autonomy modes

- `audit_only` — all writes blocked;
- `assisted` — writes technically enabled; Nexus is expected to require owner approval;
- `safe_auto` — Nexus may dispatch approved low-risk actions automatically;
- `full_auto` — reserved for a later policy engine; Core v0.1 still enforces the same allowlist.

The mode is a local safety switch. The authoritative approval policy remains in
Nexus / JARO OS.

## Rollback scope

Core v0.1 implements **resource rollback**, not a full site backup. A
`post.update` stores the original post fields and returns a `rollback_id`.

Full database + `wp-content` backup orchestration is intentionally left to the
existing JARO OS / hosting backup path until a provider-independent verified
backup adapter exists. High-risk actions must not be added before that gate is
implemented.

## Nexus adapter

`ops/komarena/nexus-wordpress-agent-client.ps1` implements the same canonical
HMAC signing scheme for the existing PowerShell-based JARO OS / Nexus runtime.
It accepts credentials at runtime and never stores a secret in the repository.

## Next implementation gates

Before expanding write capabilities:

1. Connect the Nexus task producer to the signed REST protocol.
2. Add a deterministic Verification Engine contract.
3. Persist task/audit events centrally in Nexus.
4. Add policy/risk classification and attempt limits.
5. Add a verified full-site backup adapter.
6. Only then add signed Module Manager support.

Module installation must use a trusted manifest, hash/signature verification,
compatibility checks, backup, activation health-check, and automatic rollback.
Arbitrary remote plugin ZIP installation is not acceptable.
