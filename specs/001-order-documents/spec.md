# Woo Factor order documents

## User stories
- P1 US1: Checkout stays unchanged by default. Customer legal fields are an explicit opt-in, independent of seller identity. Historical order metadata remains readable.
- P1 US2: Merchants can edit sender display name, address, postcode and contact details; new defaults come from site/WooCommerce settings without overwriting custom saved values.
- P1 US3: Staff can print invoices and postal labels from classic and HPOS order screens; existing authorized customer invoice access remains.
- P2 US4: Improve Persian admin usability and printable readability while retaining the existing teal, rounded visual language and bundled Persian fonts.

## Acceptance
Real WordPress/WooCommerce integration and ZIP install tests, PHP lint, checkout off/on and access tests pass before release and live installation. Render every changed template plus admin with Chromium and inspect images. Deploy only after recoverable backup; verify file hashes, settings preservation and zero Woo Factor checkout hooks on Motikala. No live order writes, membership changes, new scheduling, history rewrite or release deletion.
