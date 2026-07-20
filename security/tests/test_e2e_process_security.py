from __future__ import annotations

import json
import unittest
from unittest.mock import patch

from security.e2e_process_security import ProcessSecurityClient, run_process_security_checks


class E2EProcessSecurityTest(unittest.TestCase):
    @patch("security.safety.TargetPolicy.authorize")
    def test_run_process_security_checks_executes_all_section_2_checks(self, mock_authorize) -> None:

        with patch.object(ProcessSecurityClient, "request") as mock_request:
            mock_request.side_effect = [
                # 1. GET /login -> init cookies
                (200, {"Set-Cookie": "XSRF-TOKEN=test; HttpOnly; SameSite=Lax"}, b""),
                # 2. POST /login -> authenticated
                (200, {}, b""),
                # 3. GET /api/rikms/me -> me identity
                (200, {}, b'{"data": {"role": "agency_admin"}}'),
                # 4. POST /api/rikms/documents (path traversal) -> rejected 422
                (422, {}, b""),
                # 5. POST /api/rikms/documents (shell.php exec) -> rejected 422
                (422, {}, b""),
                # 6. GET /api/rikms/documents/invalid-unauthorized-id/download (BOLA/IDOR) -> rejected 403
                (403, {}, b""),
                # 7. GET /api/rikms/documents/..%2f..%2f.env/download (path traversal download) -> rejected 404
                (404, {}, b""),
                # 8. POST /logout -> 200
                (200, {}, b""),
                # 9. GET /api/rikms/me post-logout -> 401 unauthenticated
                (401, {}, b""),
            ]

            report = run_process_security_checks(
                "http://127.0.0.1:8000",
                "test@example.com",
                "secret_password",
                "local",
            )

            self.assertTrue(report["passed"])
            self.assertEqual(0, report["findings_count"])

            check_ids = [c["id"] for c in report["checks"]]
            self.assertIn("AUTH-COOKIE-HYGIENE", check_ids)
            self.assertIn("AUTH-LOGIN-SUCCESS", check_ids)
            self.assertIn("UPLOAD-PATH-TRAVERSAL", check_ids)
            self.assertIn("UPLOAD-EXEC-EXTENSION", check_ids)
            self.assertIn("DOWNLOAD-BOLA-IDOR", check_ids)
            self.assertIn("DOWNLOAD-PATH-TRAVERSAL", check_ids)
            self.assertIn("AUTH-LOGOUT-INVALIDATION", check_ids)


if __name__ == "__main__":
    unittest.main()
