# Phase C.5 - Interactive Citizen Experience

## Purpose
Transform TSEMOU from a knowledge platform into an interactive civic intelligence platform.

This phase allows every citizen to interact with verified knowledge instead of only reading articles.

## Position In Platform Roadmap
Phase C.5 is introduced between Phase C and Phase D.

## Core Principles
- Every answer must originate from verified evidence.
- Every interaction must be traceable.
- No generative answers without evidence.
- Interactive exploration instead of passive reading.

## Initial Capabilities
- Explain this simply
- Why does this matter?
- Who benefits?
- Who is affected?
- Show supporting evidence
- Show opposing evidence
- Show related companies
- Show related events
- Show timeline
- Compare with another case
- What changed?
- Confidence level
- Unknowns / Missing evidence
- Explore causes
- Explore consequences
- Ask follow-up question

## Architectural Intent
Phase C.5 introduces an interaction layer on top of verified intelligence outputs. It does not replace evidence, trust, policy, or traceability engines; it makes their outputs interactively navigable for citizens.

## Guardrails
- Interactions must remain evidence-grounded.
- Responses must preserve source traceability.
- Uncertainty and missing evidence must be explicitly preserved.
- Citizen exploration must not bypass policy and trust constraints.

## Non-Goals For This Documentation Update
- No PHP implementation.
- No JavaScript implementation.
- No database migration.
- No configuration changes.
- No menu, route, or renderer changes.
