# JARO OS Native-First Execution Policy v1

Status: **owner-approved direction**  
Date: **2026-08-07**

## Purpose

JARO OS must continue to operate when Codex CLI is unavailable, rate-limited, quota-limited, or otherwise unsuitable for a deterministic task.

Codex is an optional execution capability, not the core runtime of JARO OS.

## Single-system rule

Use only the existing execution chain:

`JARO OS Supervisor -> GitHub Bridge -> Transport Worker -> Control Agent -> existing native executor`

Do not create a second Supervisor, Bridge, queue, Control Agent, Local Operator, dashboard, or deployment path to bypass a Codex failure.

## Default routing

For every task, prefer the smallest existing deterministic native action that can complete the requested scope safely.

Examples already supported by the Control Agent include:

- `health`
- `full-check`
- `wp-preflight`
- `wp-backup`
- `control-test`
- `prepare-control-fix`
- `approve-control-fix`
- `agent-self-test`
- `agent-report`
- `pc-list-folder`
- `pc-search-files`
- `pc-create-folder`
- `pc-move-file`
- product and image workflow actions that are already explicitly registered in the Control Agent

Use `codex-run` only when the task genuinely requires local AI reasoning/code generation that cannot be represented safely by an existing native action.

## Native-first decision order

1. **Read-only native action** if the goal can be reached by inspection, preflight, audit, status or file discovery.
2. **Backup action** before any production-changing phase.
3. **Registered deterministic control** (`control-test`, `prepare-control-fix`, `approve-control-fix`) when the requested change maps to an existing control.
4. **Existing PC Agent action** for local file/system work.
5. **Codex fallback** only when no existing deterministic native action is adequate.
6. **BLOCKED** instead of creating a parallel executor when neither native execution nor safe Codex execution is available.

## Codex failure handling

The following conditions must not make JARO OS appear globally offline:

- Codex usage/quota limit
- Codex authentication failure
- missing Codex model capability
- Codex launcher failure
- Codex timeout

When Codex fails:

1. preserve the task evidence and logs without exposing secrets;
2. ensure stale `running` state is not left behind;
3. mark the Codex attempt with an exact blocker;
4. determine whether the remaining scope can be decomposed into registered native actions;
5. continue through those native actions when safe;
6. do not automatically broaden scope or perform a risky production write.

## Production gate

Before any production write, native execution must enforce the same gates as Codex-assisted execution:

- current usable backup;
- exact rollback path;
- affected-object exports where applicable;
- SHA-256 evidence for backup artifacts when required by the task;
- explicit scope and stop conditions;
- verification after the change;
- automatic rollback only where a prepared rollback is technically safe and explicitly within the approved scope.

Secrets must never be printed, committed, uploaded to GitHub, or written into ordinary logs.

## KomArena P0 application

For the active KomArena homepage restoration, the preferred native sequence is:

`wp-preflight -> wp-backup -> control-test / prepare-control-fix (when a registered control applies) -> approve-control-fix -> verification`

The homepage production goal remains unchanged:

- original KomArena homepage becomes authoritative front page;
- ReSmart remains secondary;
- page 3409 is not repurposed as a new homepage;
- the temporary root-only redirect is reversed only from proven evidence;
- no production write occurs before a verified full DB + `wp-content` backup and rollback evidence;
- `/senzory/` remains a KomArena Home Assistant/ESPHome sensor surface and is sanitized only after the same backup gate.

## State semantics

Use precise state labels:

- `RUNTIME_RUNNING_NATIVE_AVAILABLE`
- `CODEX_BLOCKED_NATIVE_AVAILABLE`
- `BLOCKED_NATIVE_ACTION_MISSING`
- `BLOCKED_AUTH_REQUIRED`
- `BLOCKED_BACKUP_REQUIRED`

Do not label the whole JARO OS runtime offline merely because a Codex-specific action is blocked.

## Non-goals

This policy does not:

- install a new runner;
- create a new agent hierarchy;
- replace the existing Control Agent;
- bypass WordPress backup/rollback gates;
- authorize new production scope;
- weaken authentication, WAF, firewall, WordPress security, or GitHub branch protection.
