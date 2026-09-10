# Council Notes: proposal

## Author Summary

Draft proposes blocking search engine indexing of the B2B Call CRM via two mechanisms: (1) `X-Robots-Tag: noindex, nofollow` HTTP header on all responses via a Symfony event subscriber, and (2) `<meta name="robots" content="noindex, nofollow">` in `base.html.twig`. Single new capability `search-indexing` introduced; no existing capabilities modified. Impact is limited to response layer — no DB, entity, or access model changes.

## Reviewer Challenges

- Adversarial-reviewer subagent was unavailable (API key error). Review performed by primary agent.
- Potential gap: `robots.txt` was not considered as an additional blocking mechanism.
- The capability name `search-indexing` could be ambiguous — it describes the topic but not the action (blocking).

## Resolutions

- Accepted: `robots.txt` is not critical for an authenticated internal app — crawlers cannot reach it without credentials. The `X-Robots-Tag` header approach is more robust and covers all responses. Deferred to design.md if needed.
- Rejected: Renaming capability to `search-indexing-block` — `search-indexing` is clear enough in context and matches the change name.

## Remaining Risks

- None identified. The change is isolated and low-risk.
