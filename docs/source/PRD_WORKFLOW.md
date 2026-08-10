Phase 1: DISCOVERY (use o3 or DeepSeek R1)
├── Feed it your initial idea, target users, and business goals
├── Ask: "What features are essential vs nice-to-have?"
├── Ask: "What are the biggest risks and unknowns?"
└── Output: Raw analysis, feature priority list, risk register

Phase 2: DRAFTING (use Claude via Claude Code)
├── Feed it the Phase 1 analysis + your PRD template
├── Ask it to write the full PRD with:
│   ├── Executive Summary
│   ├── Problem Statement
│   ├── User Personas & Stories
│   ├── Functional Requirements (with acceptance criteria)
│   ├── Non-Functional Requirements
│   ├── Success Metrics (KPIs)
│   └── Risks & Open Questions
└── Output: Professional, structured PRD document

Phase 3: REVIEW (use Gemini 2.5 Pro or Qwen)
├── Feed it the PRD + any technical architecture docs
├── Ask: "Are there any gaps, contradictions, or ambiguities?"
├── Ask: "Is this technically feasible given our stack?"
└── Output: Gap analysis, suggested improvements
