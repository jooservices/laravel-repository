# Workflow reference

All jobs use GitHub-hosted `ubuntu-latest` runners.

| Workflow | Trigger | Purpose |
| --- | --- | --- |
| `ci.yml` | Pull request on `master` or `develop` | Package quality gate |
| `commitlint.yml` | Pull request commits | Validate Conventional Commits |
| `semantic-pr.yml` | Pull request | Validate pull-request title |
| `scorecard.yml` | Push to `develop`; scheduled; manual | OpenSSF Scorecard |
| `release.yml` | Tag `v*` | GitHub Release and package publication |
