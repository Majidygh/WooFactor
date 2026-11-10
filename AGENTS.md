# Woofactor

Project root: `/root/woofactor`. Telegram project thread: `998420`.

## Workflow

Use the local Spec Kit workflow in `.hermes/commands/speckit.*.md` and templates/scripts in `.specify/`. Read the relevant command file before performing its workflow. Product requirements have not been specified yet; do not invent them.

Use Codebase Memory MCP for structural code inspection. The Hermes server is configured as `codebase`. If native MCP tools are unavailable in the session, use the installed CLI:

- `codebase-memory-mcp cli index_repository --repo-path /root/woofactor --name woofactor`
- `codebase-memory-mcp cli list_projects`
- `codebase-memory-mcp cli get_architecture --help`

Re-index after code changes. Do not treat partial parser coverage as complete.

Ponytail full: smallest complete verified change, no unsolicited features, no progress reports. End with skipped checks and material risks.
