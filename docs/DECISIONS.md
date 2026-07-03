# TSEMOU OS Decisions

## Decision 001
The main public object is the TSEMOU File, not a post/article.

## Decision 002
Public identifiers use human-readable call signs, e.g. AMZ-FIRE, CEO-PAY.

## Decision 003
WordPress is infrastructure. TSEMOU OS is the application.

## Decision 004
ACF and snippets are not the default path for new core functionality.

## Decision 005
TSEMOU OS evolves by adding modules. It is not rewritten per sprint.

## Decision 006
Company Engine uses the existing WordPress `company` CPT. We do not create a duplicate company object.

## Decision 007
Company Engine extends the existing Companies CPT and stores File-specific relationship details: role, status and reason.

## Decision 008
Companies keep global intelligence fields: TSEMOU status, score, last review and notes. Connected Files are calculated from relationships.
