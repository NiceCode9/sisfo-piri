# SDD ledger — plan: docs/superpowers/plans/2026-11-13-berita-jurnalistik.md

## Pre-flight scan

| Tasks | Shared file/interface | Finding |
|-------|----------------------|---------|
| 4 & 5 | routes/web.php | Both add routes; ensure no duplicate names. Rule: add public routes in T4, jurnalistik routes in T5, distinct prefixes. |
| 3 & 4 | Berita model scopes | T4 depends on `published()` scope from T3. Ensure T3 defines it. |
| 5 & 6 | Komentar model | T6 depends on Komentar from T3/T5. Ensure migration includes status enum. |
| 7 & 4 | SitemapController | T7 modifies T4-created sitemap; ensure Berita model query uses published scope. |

Scan result: no blocking conflicts. Proceed.

Task 1: complete (commits ea6a283..c9a21e9, review clean)
Task 2: complete (commits c9a21e9..d96a81c, review clean)
Task 3: complete (commits d96a81c..e1e2157, review clean)
Task 4: complete (commits e1e2157..007e9bc, review clean)
Task 5: complete (commit 27f36fd — jurnalistik dashboard & CRUD, role gate, slug, sanitise HTML, thumbnail GD, tests pass)
Task 6: complete (commit 6b6f9a9 — public comments (guest/tamu, pending moderation), likes (login only), sitemap berita entries, tests pass)
Task 7: complete (commits 6b6f9a9 — sitemap SEO integration, rate limiter fix, tests pass)
