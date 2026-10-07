# HMS Development Workflow

## Protected main branch

The `main` branch is the production integration branch.

All HMS development changes must be made on a feature/fix branch and merged through a pull request. Do not treat a proposed, drafted, or locally prepared change as complete until the repository state has been committed and verified.

## Definition of Done

A change is **Complete** only when all applicable stages are true:

1. **Planned** — the intended change is defined.
2. **Applied** — the actual repository files have been changed on a feature branch.
3. **Committed** — the change exists in a Git commit.
4. **CI Verified** — required GitHub Actions checks pass.
5. **Reviewed** — the pull request diff has been inspected.
6. **Merged** — the approved pull request is merged into `main`.
7. **Post-merge Verified** — the resulting `main` files/commit are confirmed.

If a write, commit, CI run, PR, or merge fails, the work must be reported as incomplete rather than implied to be completed.

## Pull request expectations

Every PR should state:

- What changed
- Why it changed
- Files/modules affected
- Security or permission implications
- Database/migration implications, if any
- How the change was verified
- Any remaining local/UAT testing required

## CI

The repository uses GitHub Actions for PHP syntax validation. A PR targeting `main` must pass the PHP lint check before it is considered merge-ready.

## Local synchronization

After a remote merge, synchronize the local checkout before continuing:

```bash
git fetch origin
git reset --hard origin/main
```

Only use `reset --hard` when local uncommitted tracked work is either committed or intentionally disposable.
