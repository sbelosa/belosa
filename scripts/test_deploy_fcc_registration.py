"""Custom code: FC-2026-10-07: Verify that deployment preserves unrelated live edits."""
from pathlib import Path
import re
import unittest
from deploy_fcc_registration import ROOT, SHARED, LANGUAGES, block, patch_controller, patch_language


class TargetedReleaseTests(unittest.TestCase):
    def test_controller_patch_preserves_unrelated_live_code_and_is_idempotent(self):
        for path in SHARED:
            source = (ROOT / path).read_text()
            baseline = source.replace(block(source), "")
            live = baseline + "\n/* An unrelated production addition. */\n"
            patched = patch_controller(path, live, source)
            self.assertEqual(patched.replace(block(patched), ""), live)
            self.assertEqual(patch_controller(path, patched, source), patched)

    def test_unrelated_patch_on_same_date_is_preserved(self):
        unrelated = "/* Custom code: FC-2026-10-07: Different production change. */\n// Keep this live code.\n/* /Custom code: FC-2026-10-07 */\n"
        for path in SHARED:
            source = (ROOT / path).read_text()
            live = source.replace(block(source), "") + unrelated
            patched = patch_controller(path, live, source)
            self.assertIn(unrelated, patched)
            self.assertEqual(patch_controller(path, patched, source), patched)

    def test_language_patch_preserves_other_template_changes(self):
        for path in LANGUAGES:
            source = (ROOT / path).read_text()
            live = source.replace("{{WEBSITE_TITLE}}", "{{LIVE_SITE_TITLE}}")
            live += "\n/* Unrelated production translations. */\n"
            patched = patch_language(live, source)
            self.assertIn("/* Unrelated production translations. */", patched)
            self.assertIn("{{LIVE_SITE_TITLE}}", patched)
            self.assertEqual(patch_language(patched, source), patched)

    def test_unknown_registration_contract_fails_before_upload(self):
        source = (ROOT / SHARED[0]).read_text()
        with self.assertRaises(ValueError):
            patch_controller(SHARED[0], "<?php\n", source)


if __name__ == "__main__":
    unittest.main()
# /Custom code: FC-2026-10-07
