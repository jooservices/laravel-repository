# Next Release Implementation Plan

**Date:** 2026-07-23  
**Sources:** open PR audit, `audit_260723.md`, plan-item verdict, current `master`/`develop` @ `052e3aa`  
**Verdict:** **Do not implement a broad roadmap as one release.** Ship a narrowed correctness + lifecycle + coverage release after unblocking CI with #49. Keep CI supply-chain hardening as a **separate** security PR. Defer query-expansion, DTOs, and major dev-tool bumps.

---

## 1. Executive validation

| Source claim | Valid? | Evidence |
| --- | --- | --- |
| Release is blocked by open PR / CI state | **Yes** | Local lockfile still pins `guzzlehttp/guzzle` **7.12.3**. CI `composer audit` fails on branches without #49. |
| #49 resolves Guzzle advisories; only PR-title check fails | **Yes** | Checks: Security, Lint matrix, Tests & Coverage (L12/L13), Dependency Review = **success**. **Validate PR title = failure** (subject must start with uppercase). |
| #50 is draft and blocked by old Guzzle | **Yes** | Draft; base `master`. **Security Checks = failure**, Tests & Coverage = failure (needs security job). Title check passes. |
| #50 `newRepositoryQuery()` is a redundant forwarder | **Yes** | Diff only does `return $this->newQueryWithCriteria();`. Keep the **rest** of #50 (generics, PHPStan 7, move hook to base, drop `method_exists`). |
| #48 should stay out of this release | **Yes** | Targets `develop`; major bumps `phpunit` `^11` → `^13.2`, `php_codesniffer` `^3` → `^4`; Laravel 12 test job fails; title check fails. |
| `fields` + root `BelongsTo`/`MorphTo` FK bug is real | **Yes** | `applyFieldClauses()` only auto-retains PK; `with` applied after fields; no FK preservation. Existing field test only covers `UserStub` without includes. |
| Exception hierarchy mostly already present | **Yes** | `RepositoryException` → `InvalidRequestQueryException` with disallowed field/filter/sort/include/scope/relation factories. No `UnsafeFieldProjectionException` today; add **only if** reject-mode is chosen. |
| “More Eloquent queries” bulk work is stale | **Yes** | Request-query already covers `whereIn`, `whereBetween`, null checks, `whereHas*`, scopes, aggregates, includes; `HasRead` has `first`/`firstOrFail`/`exists`/`count`; iteration/cursor pagination exist. |
| Query DTOs not justified | **Yes** | Package is intentionally request-query oriented; no non-HTTP consumer requiring a DTO layer. |
| Query lifecycle terminals use `finally` | **Yes** | Present in `HasFilter`, `HasRead`, `HasIteration`, `HasCursorPagination`, `paginateFromRequest`. Missing: stronger cross-query + exception-reset regressions in places that only assert happy-path reset. |
| Release coverage gate not enforced by `composer ci` | **Yes** | `composer ci` = `lint:all` + `test:coverage` only. Threshold exists **only** as inline PHP in `ci.yml`. `release.yml` step is named “coverage gate” but runs bare `composer ci`. Docs claim the gate exists. |
| CI supply-chain hardening still needed | **Mostly yes** | Mutable action tags (`@v6.0.2`, `@v2`, `@v4`, `@v7`); Gitleaks tarball download without checksum. **Dependabot already exists** (composer + github-actions) — do not re-add. |

**Overall:** The plan-item verdict and `audit_260723.md` are **aligned and valid** after narrowing scope. Next release is viable once PRs are sequenced and work is limited to correctness, lifecycle tests, coverage gate, and (separately) CI hardening.

---

## 2. Open PR status (live)

### 2.1 #49 — Guzzle 7.12.3 → 7.15.1

| Field | Value |
| --- | --- |
| URL | https://github.com/jooservices/laravel-repository/pull/49 |
| Author | dependabot[bot] |
| Base | `master` |
| State | open (ready) |
| Purpose | Security: bump transitive Guzzle; resolves cookie/Referer advisories blocking `composer audit` |
| Substantive CI | **Green** |
| Blocker | Semantic PR title: subject must match `^[A-Z].+$` (`.github/workflows/semantic-pr.yml`) |

**Required action before merge**

1. Retitle to start the subject with uppercase, e.g.  
   `build(deps): Bump guzzlehttp/guzzle from 7.12.3 to 7.15.1`  
   (or `chore(deps): Bump guzzlehttp/guzzle to 7.15.1`).
2. Confirm **Validate PR title** turns green.
3. Merge to `master` (and fast-forward/sync `develop` if both stay aligned).

**Do not** implement app code on this PR. It is the security unblocker only.

---

### 2.2 #50 — Repository typing + query lifecycle (draft)

| Field | Value |
| --- | --- |
| URL | https://github.com/jooservices/laravel-repository/pull/50 |
| Author | soulevilx |
| Base | `master` |
| State | **draft** |
| CI | Security/tests failing because lockfile still has vulnerable Guzzle until rebased onto #49 |

**What is valuable (keep)**

- `@template TModel of Model` on `EloquentRepository`, `HasCrud`, `HasCriteria`
- Concrete `@extends EloquentRepository<UserStub>` on test stubs
- Move criteria-aware fresh builder construction so CRUD does not rely on `method_exists($this, 'newQueryWithCriteria')`
- Prefer applying criteria via `applyCriteria()` / interface check for idempotent criteria tracking
- PHPStan level **6 → 7** (explicit typing work; fits `risks-legacy-and-gaps.md` guidance)
- Regression test: override `newQueryWithCriteria()` and assert CRUD honors the hook

**What is redundant (remove)**

```php
protected function newRepositoryQuery(): Builder
{
    return $this->newQueryWithCriteria();
}
```

`HasCrud::crudQuery()` must call **`newQueryWithCriteria()`** directly (always available once the hook lives on `EloquentRepository` or is otherwise guaranteed).

**Required action after #49**

1. Rebase onto updated `master`.
2. Drop `newRepositoryQuery()`; wire CRUD → `newQueryWithCriteria()`.
3. Keep visibility/signature of `newQueryWithCriteria()` as the public extension point for subclasses (`protected`).
4. Mark ready for review only after green CI.
5. Merge as part of the next release branch sequence (or fold equivalent changes into the release PR — avoid double-shipping).

---

### 2.3 #48 — Dev-dependency major bumps

| Field | Value |
| --- | --- |
| URL | https://github.com/jooservices/laravel-repository/pull/48 |
| Base | **`develop`** |
| Changes | `phpunit/phpunit` ^11 → ^13.2; `squizlabs/php_codesniffer` ^3 → ^4 (+ lock churn) |
| CI | Title fail; Laravel 12 test job fail; overall Tests & Coverage fail |

**Decision:** **Out of next release.** Treat as a later tooling migration with dedicated compatibility work. Do not merge into the correctness/security ship.

---

## 3. Plan-item decisions (authoritative for this release)

| Item | Assessment | Next release? | Implementation notes |
| --- | --- | --- | --- |
| fields + with FK bug | Valid silent correctness bug | **Yes — blocker** | Preserve FKs (preferred) for root `BelongsTo` / `MorphTo`; tests first |
| Remove `newRepositoryQuery()` | Valid cleanup in #50 | **Yes** | Keep `newQueryWithCriteria()`; CRUD calls it directly |
| Exception hierarchy | Mostly done | **Limited** | Only add `UnsafeFieldProjectionException` if reject strategy is chosen |
| More Eloquent queries | Stale bulk agenda | **No bulk** | Optional later: `sole`, `value`, `pluck`, constrained includes — each as its own PR |
| Query DTOs | Not justified | **Defer** | No non-HTTP consumer |
| Query state lifecycle | Valid test hardening | **Yes — small** | Cross-query + exception-reset tests; no redesign of `finally` pattern |
| Release coverage gate | Valid policy gap | **Yes** | Shared script; CI + release + docs single source of truth (90%) |
| CI supply-chain hardening | Valid | **Separate security PR** | SHA-pin actions; verify Gitleaks; explicit permissions; Dependabot already present |

---

## 4. Release scope

### In scope (next release)

1. Merge corrected **#49** (Guzzle security).
2. Land **#50** (rebased, without `newRepositoryQuery()`) or equivalent typing/lifecycle PR.
3. Fix **field projection + eager-load foreign keys**.
4. Add **lifecycle regression tests**.
5. Extract and enforce **shared 90% coverage gate** in CI and release.
6. Docs/changelog updates that match real behavior.

### Out of scope (explicit)

- Full query API expansion (`sole`, `value`, `pluck`, constrained includes as a bulk epic)
- Query DTO introduction
- Large exception-tree redesign
- #48 PHPUnit 13 / PHPCS 4
- CI action SHA-pinning / Gitleaks checksum (track as follow-up security PR)
- Namespace rename `Jooservices` → `JOOservices` (completed in 1.6.0)
- Automatic cache invalidation or query caching beyond `HasCache`

---

## 5. Implementation work packages

### WP0 — Unblock CI with #49

**Goal:** Green `composer audit` on protected branches.

**Steps**

1. Retitle #49 so subject starts with uppercase (required by `subjectPattern: ^[A-Z].+$`).
2. Merge #49 to `master`.
3. Sync `develop` with `master` if they remain release twins.
4. Confirm Security Checks pass on both branches.

**Acceptance**

- [ ] `composer audit` / Security Checks green on post-merge HEAD
- [ ] No application source changes required

**Risk:** Dependabot PR title policy may re-fail if recreated; prefer edit title over recreate when possible.

---

### WP1 — Field projection + eager-load foreign keys (correctness blocker)

**Goal:** `fields` + root `with` never silently nulls relation hydration for relation types that need root FKs.

#### Current defect (code)

- `fromRequest()` applies fields **before** includes (`HasRequestQuery.php`).
- `applyFieldClauses()` auto-includes only the model **primary key**, not foreign keys.
- Root `BelongsTo` (e.g. `PostStub::user()` → `user_id`) cannot match eager-loaded parents if `user_id` was not selected.
- Same class of bug for `MorphTo` (FK + morph type columns).

#### Preferred fix strategy: **preserve required keys** (not reject)

Rejecting `fields`+`with` would break valid sparse-field API use. Prefer:

1. When applying root field projection, inspect **root-level** eager loads that will be applied (parse `with` clauses first, or reorder so includes are known before final `select`).
2. For each root include of type `relation` (not count/exists/sum/… aggregates):
   - Resolve relation method on model.
   - If `BelongsTo`: ensure parent model’s **foreign key** column(s) are in the select list (qualified).
   - If `MorphTo`: ensure **foreign key** and **morph type** columns are in the select list.
3. Keep existing PK auto-include behavior.
4. Nested/dot relation includes: only root owner keys that the root model needs for matching must be forced; do not invent nested select logic unless already supported.
5. Do **not** force FK columns into the public attribute allowlist failure path incorrectly — auto-preserved keys are internal safety, not user-requested fields. Strict mode must still allow the request if the user-requested fields are allowed; preserved FKs should not require explicit allowlisting of the FK name (document this).

**Implementation sketch (suggested location)**

- Extend `applyFieldClauses()` or introduce a private collaborator such as `preserveRelationOwnerKeys(Builder $query, array $selected, array $includes): array`.
- Call after user field resolution and before `$query->select(...)`.
- Use Eloquent relation metadata (`getForeignKeyName()`, morph type name APIs) rather than string conventions.

**Do not** introduce `UnsafeFieldProjectionException` under the preserve strategy.

#### Tests first (failing, then fix)

Add integration-style tests (Feature or Unit with SQLite stubs):

| Case | Setup | Assert |
| --- | --- | --- |
| BelongsTo default FK | `PostStub` + `user`; request `fields: [title]`, `with: [user]` | Post has `user` loaded non-null; `user_id` present on model attributes (or relation resolves correctly) |
| BelongsTo custom FK | Model relation with non-default FK column name | Same |
| MorphTo | New stub + migration columns for morph | Morph parent loaded correctly when fields omit morph columns |
| Permissive allowlist mode | Allowed fields exclude FK name | Still works (auto-preserve) |
| Strict allowlist mode | Allowed fields exclude FK name | Still works; no disallowed-field throw for auto-preserved FK |
| Fields without with | Existing behavior | PK auto-include only; no unexpected columns |
| Aggregate includes only | `withCount` / `withExists` | Do **not** require FK preserve for aggregates that do not hydrate models |

**Fixtures to add**

- `tests/Stubs` morph model(s) and, if needed, custom-FK relation on post or a dedicated stub.
- Migration columns for morph tests (or a third migration).
- Optional `PostRepositoryStub` / allowed-request-query post repository if cleaner than ad-hoc anonymous classes.

#### Docs

- `docs/02-user-guide/request-query.md`: document that sparse `fields` automatically retain PK and owner keys required by requested root eager loads.
- `docs/05-maintenance/risks-legacy-and-gaps.md`: note fixed gap if listed.
- `CHANGELOG.md` under Fixed.

#### Acceptance

- [ ] Failing tests written and observed red before fix (or clear reproduction commit)
- [ ] BelongsTo + custom FK + MorphTo covered
- [ ] Strict and permissive modes covered
- [ ] `composer test` / targeted suite green
- [ ] Docs + changelog updated

---

### WP2 — Query hook cleanup + typing (from #50, narrowed)

**Goal:** Criteria-aware CRUD without fragile `method_exists`, without a redundant second hook.

#### Target design

| Piece | Target |
| --- | --- |
| `EloquentRepository` | Own `protected function newQueryWithCriteria(): Builder` (or equivalent always-present base method) that starts a fresh builder and applies criteria when `CriteriaRepositoryInterface` |
| `HasCriteria` | Keep stack APIs + `applyCriteria()`; **remove** duplicate `newQueryWithCriteria()` if moved to base |
| `HasCrud::crudQuery()` | `return $this->newQueryWithCriteria();` — **no** `newRepositoryQuery()`, **no** `method_exists` |
| Generics | Keep `#50` `TModel` annotations if they land cleanly under PHPStan 7 |
| PHPStan | Level 7 only if the PR’s typing work is complete and green |

#### Tests

- Keep/port #50 test: subclass overrides `newQueryWithCriteria()` and CRUD honors it.
- Existing criteria CRUD test: find/all honor pushed criteria without filter-chain pollution.
- Ensure repositories **without** `HasCriteria` still get a plain `newQuery()` path via base `newQueryWithCriteria()`.

#### Acceptance

- [ ] No `newRepositoryQuery` symbol in tree
- [ ] `newQueryWithCriteria` remains the single extension hook
- [ ] CRUD criteria behavior green
- [ ] PHPStan (level as chosen) green

---

### WP3 — Query state lifecycle tests (small)

**Goal:** Harden guarantees already implemented with `try/finally`; no architecture change.

#### Gaps to close

Existing tests cover many happy-path resets (`HasFilter`, `HasIteration`, parts of `HasRead`/`HasRequestQuery`). Add explicit cases for:

1. **Exception reset on terminals that can throw**  
   - e.g. `firstOrFail` already partially covered via follow-up `count()`; extend pattern to `paginate` / `get` if exceptions can occur mid-chain, and to iteration helpers if practical.
2. **Cross-query isolation**  
   - Filter A terminal → unrelated filter B must not see residual builder state.
   - `fromRequest` failure then successful subsequent query (partially exists for `paginateFromRequest`; extend for `fromRequest()->get()` if applicable).
3. **Criteria + filter chain**  
   - Confirm criteria remain for next CRUD/fresh query while mutable filter state resets after terminal.

Place tests near existing suites:

- `tests/Unit/HasFilterTest.php`
- `tests/Unit/HasReadTest.php`
- `tests/Unit/HasRequestQueryTest.php`
- `tests/Unit/HasCriteriaTest.php` / `HasCrudTest.php` as needed

#### Acceptance

- [ ] At least one cross-query isolation test and one exception-reset test added beyond current coverage
- [ ] No production behavior change unless a real leak is found (if found, fix in same WP and document)

---

### WP4 — Shared coverage gate (policy truth)

**Goal:** One threshold definition enforced by both CI and release; docs match reality.

#### Problem

| Surface | Behavior today |
| --- | --- |
| `ci.yml` | Inline PHP parses `build/coverage/clover.xml`; fails if &lt; `COVERAGE_THRESHOLD` (90) |
| `release.yml` | Runs `composer ci` only; **no** threshold assertion despite step name |
| `composer ci` | Lint + coverage **generation** only |
| Docs | Claim release runs coverage threshold |

#### Implementation

1. Add versioned script, e.g. `bin/check-coverage` or `scripts/check-coverage.php` (or shell + php -r), arguments:
   - clover path default `build/coverage/clover.xml`
   - threshold default `90` (or read from env `COVERAGE_THRESHOLD`)
2. Composer script, e.g.:
   - `"test:coverage:check": "php scripts/check-coverage.php"`
   - Optionally wire into a dedicated script used by CI/release; **avoid** silently changing local `composer ci` semantics without documenting (recommended: make `composer ci` call coverage generation + check so local = CI = release).
3. Replace inline block in `ci.yml` with the script.
4. In `release.yml`, after coverage generation (or via full `composer ci` if it includes the check), run the same script.
5. Keep threshold **90** as single default; document in:
   - `docs/04-development/ci-cd.md`
   - `docs/04-development/testing.md` / release-process if they mention the gate
   - `AGENTS.md` already states 90% — keep aligned

**Recommended composer shape**

```text
test:coverage        → generate clover/html
test:coverage:check  → assert threshold on clover
ci                   → lint:all + test:coverage + test:coverage:check
```

Then both workflows can rely on `composer ci` honestly.

#### Acceptance

- [ ] Threshold not duplicated as divergent hardcodes without a shared default
- [ ] CI and release both fail if coverage &lt; 90%
- [ ] Docs no longer over-claim
- [ ] Manual smoke: lower threshold temporarily or feed a fake clover in unit test of the script if practical

---

### WP5 — Documentation & changelog for the release

**Files typically touched**

- `CHANGELOG.md` — Fixed / Changed / Security entries
- `docs/02-user-guide/request-query.md` — fields + with key preservation
- `docs/04-development/ci-cd.md` — real coverage gate path
- `docs/04-development/release-process.md` — if gate steps listed
- `README.md` only if public behavior bullets mention fields/includes

**Acceptance**

- [ ] No claim of bulk new query operators or DTOs
- [ ] Runtime truth guards in `AGENTS.md` still accurate

---

### WP6 — Follow-up security PR (not blocking next tag if schedule slips)

**Goal:** Supply-chain hardening without delaying correctness release.

| Task | Detail |
| --- | --- |
| Pin GitHub Actions | Replace floating tags with full commit SHAs for third-party actions in `ci.yml`, `release.yml`, `secret-scanning.yml`, `scorecard.yml`, etc. Keep human-readable version in comments. |
| Dependabot | **Already configured** for `composer` and `github-actions` targeting `develop`. Ensure pins stay updateable; optionally improve Dependabot commit subject capitalization so semantic PR title passes. |
| Gitleaks | Prefer official SHA-pinned action **or** curl + checksum/signature verify before `tar`. |
| Permissions | Prefer explicit `permissions:` least privilege on every workflow job. |
| Semantic PR action | Already SHA-pinned — use as pattern. |

Track as separate PR titled e.g. `ci: Pin actions and verify Gitleaks artifact`.

---

## 6. Recommended execution sequence

```text
1. WP0  Merge retitled #49 (Guzzle)                    [security unblock]
2.      Rebase #50 onto master
3. WP2  Finalize #50 without newRepositoryQuery()     [typing + hook]
4. WP1  fields/with FK fix + regression tests         [correctness blocker]
5. WP3  Lifecycle tests                               [small]
6. WP4  Shared coverage gate                          [release policy]
7. WP5  Docs + CHANGELOG                              [same PR or release PR]
8.      Tag release when master is green
9. WP6  CI hardening                                  [separate PR]
```

**Branch strategy suggestion**

- Prefer one release PR on `develop` (or `master` if that is the ship branch) stacking WP1–WP5 after #49/#50 land, **or** sequential small PRs in the order above.
- Keep #48 untouched.
- Do not open a mega-PR that mixes PHPUnit 13, DTO work, or action SHA pins with the FK fix.

---

## 7. Validation commands (required)

Use repository command map only:

```bash
composer lint
composer lint:all
composer test
composer test:coverage
composer ci          # after WP4, should include threshold
composer audit       # after #49
```

Optional focused loops during WP1:

```bash
vendor/bin/phpunit --filter Field   # or specific test class names
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=512M
```

Do **not** invent alternate commands (`composer fix`, etc.).

---

## 8. PR / commit conventions

- Conventional Commits; PR titles: type + **uppercase** subject (`fix: Preserve foreign keys for field projections with includes`).
- Suggested split if multiple PRs:
  1. `build(deps): Bump guzzlehttp/guzzle…` (#49)
  2. `refactor: Strengthen repository typing and criteria query hook` (#50 cleaned)
  3. `fix: Preserve relation owner keys when projecting fields`
  4. `test: Harden query lifecycle reset regressions`
  5. `ci: Share coverage threshold check between CI and release`
  6. later: `ci: Pin actions and verify Gitleaks downloads`

---

## 9. Risk register

| Risk | Mitigation |
| --- | --- |
| Auto-preserving FKs surprises API consumers who expected absolute sparse selects | Document; keys are required for correctness of requested `with` |
| Strict allowlist rejects auto-preserved FK if implemented naively | Preserve after allowlist check on **user-requested** fields only |
| PHPStan 7 raises unrelated issues | Keep typing changes isolated to #50; do not mix with FK fix if noisy |
| Merging #50 before #49 | Always rebase; do not force-merge with failing audit |
| #48 accidental merge | Leave open; ignore for tag |
| Coverage script path differs CI vs local | Use repo-relative paths; run from repository root |

---

## 10. Definition of done (next release)

- [ ] #49 merged; audit green
- [ ] #50 merged without `newRepositoryQuery()` (or equivalent on mainline)
- [ ] #48 **not** merged
- [ ] FK projection bug fixed with BelongsTo / custom FK / MorphTo tests
- [ ] Lifecycle regressions added
- [ ] Coverage threshold enforced by shared mechanism in **both** CI and release
- [ ] Docs + changelog accurate
- [ ] `composer ci` green on release commit
- [ ] CI hardening either shipped separately or explicitly deferred in release notes

---

## 11. Traceability

| Artifact | Role |
| --- | --- |
| `audit_260723.md` | Source audit: FK bug, coverage gate, CI integrity |
| This file (`implementation.md`) | Authoritative narrowed implementation plan for next release |
| PR #49 / #50 / #48 | Open PR constraints and sequencing |
| `AGENTS.md` | Package boundaries and command map |

**Final call:** Proceed to implement **WP0 → WP5** only. Treat WP6 and deferred roadmap items as follow-ups, not release blockers for the correctness/security ship.
