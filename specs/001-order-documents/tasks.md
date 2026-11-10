# Tasks
- [x] T001 Merge current upstream into /root/woofactor without overwriting scaffold.
- [x] T002 [US1] Test and fix opt-in registration in tests/test_checkout.php and includes/hooks.php.
- [x] T003 [US2] Test editable sender defaults in woo-factor.php.
- [ ] T004 [US1] Fix unchecked checkbox persistence in includes/helpers.php and admin/settings.php.
- [ ] T005 [US3] Verify order links and access in admin/meta-boxes.php and tests.
- [ ] T006 [US4] Refine admin and print output with Chromium renders.
- [ ] T007 Install release ZIP in real WP/Woo; lint and integration tests.
- [ ] T008 Release and deploy only after backup and green tests; read back live hashes.

Dependencies: T001 before stories, T007 after all stories, T008 last. Environment setup may run parallel to isolated helper tests.
