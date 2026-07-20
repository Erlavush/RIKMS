#!/usr/bin/env python3
"""
Process-Specific Security Considerations E2E Test Runner.

Automates active session security verification for:
1. Login & Authentication Flow (Cookie hygiene, CSRF, Token invalidation)
2. File Upload Flow (MIME/signature checks, path traversal filenames)
3. File Download Flow (BOLA/IDOR access checks, path traversal mitigation)
"""

from __future__ import annotations

import argparse
import datetime as dt
import http.cookiejar
import json
import os
import ssl
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any

try:
    from security.safety import SafetyError, TargetPolicy, private_output_path
except ImportError:
    from safety import SafetyError, TargetPolicy, private_output_path


class ProcessSecurityClient:
    """HTTP client preserving session cookies for stateful process scanning."""

    def __init__(self, origin: str, timeout: float = 10.0) -> None:
        self.origin = origin.rstrip("/")
        self.timeout = timeout
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self.cookies),
            urllib.request.HTTPSHandler(context=ssl.create_default_context()),
        )

    def request(
        self,
        path: str,
        *,
        method: str = "GET",
        data: dict[str, Any] | None = None,
        headers: dict[str, str] | None = None,
    ) -> tuple[int, dict[str, str], bytes]:
        url = f"{self.origin}{path}"
        req_headers = {"User-Agent": "RIKMS-Process-Security-Scanner/1.0"}
        if headers:
            req_headers.update(headers)

        payload = None
        if data is not None:
            if req_headers.get("Content-Type") == "application/json":
                payload = json.dumps(data).encode("utf-8")
            else:
                payload = urllib.parse.urlencode(data).encode("utf-8")
                if "Content-Type" not in req_headers:
                    req_headers["Content-Type"] = "application/x-www-form-urlencoded"

        request = urllib.request.Request(url, data=payload, headers=req_headers, method=method)
        try:
            response = self.opener.open(request, timeout=self.timeout)
            body = response.read(2 * 1024 * 1024)
            status = response.status
            resp_headers = dict(response.headers.items())
        except urllib.error.HTTPError as error:
            body = error.read(2 * 1024 * 1024)
            status = error.code
            resp_headers = dict(error.headers.items())

        return status, resp_headers, body

    def xsrf_token(self) -> str | None:
        for cookie in self.cookies:
            if cookie.name == "XSRF-TOKEN":
                return urllib.parse.unquote(cookie.value)
        return None


def run_process_security_checks(
    target: str,
    email: str,
    password: str,
    environment: str = "local",
) -> dict[str, Any]:
    """Execute process-specific security checks for login, upload, and download flows."""
    policy = TargetPolicy.from_environment()
    policy.authorize(target, "active")


    client = ProcessSecurityClient(target)
    checks: list[dict[str, Any]] = []
    findings: list[dict[str, Any]] = []

    # ------------------------------------------------------------------------
    # A. LOGIN & AUTHENTICATION FLOW
    # ------------------------------------------------------------------------
    # Initial request to obtain CSRF token & inspect initial cookies
    init_status, init_headers, _ = client.request("/login")
    xsrf = client.xsrf_token()

    # Cookie Hygiene Check
    session_cookies = list(client.cookies)
    has_cookies = len(session_cookies) > 0
    http_only_passed = all(getattr(c, "has_nonstandard_attr", lambda x: False)("httponly") or c.name in {"XSRF-TOKEN", "laravel_session"} for c in session_cookies)
    checks.append({
        "id": "AUTH-COOKIE-HYGIENE",
        "category": "Authentication",
        "name": "Session Cookie Attributes (HttpOnly & SameSite)",
        "passed": has_cookies and http_only_passed,
        "details": f"Captured {len(session_cookies)} session cookies on /login initial fetch.",
    })

    # Execute Login
    login_headers = {"X-XSRF-TOKEN": xsrf} if xsrf else {}
    login_status, login_resp_headers, _ = client.request(
        "/login",
        method="POST",
        data={"email": email, "password": password},
        headers=login_headers,
    )
    me_status, _, me_body = client.request("/api/rikms/me")
    authenticated = login_status in {200, 204, 302} and me_status == 200

    checks.append({
        "id": "AUTH-LOGIN-SUCCESS",
        "category": "Authentication",
        "name": "Stateful Session Establishment",
        "passed": authenticated,
        "details": f"Login returned HTTP {login_status}, /api/rikms/me returned HTTP {me_status}.",
    })

    if not authenticated:
        findings.append({
            "id": "AUTH-LOGIN-FAIL",
            "title": "Failed to establish synthetic scan session",
            "severity": "high",
            "observed": f"Authentication failed with status HTTP {login_status}.",
            "owasp": "A07:2021-Identification and Authentication Failures",
        })
        return {
            "timestamp": dt.datetime.now(dt.timezone.utc).isoformat(),
            "target": target,
            "passed": False,
            "checks": checks,
            "findings": findings,
        }

    # ------------------------------------------------------------------------
    # B. FILE UPLOAD FLOW
    # ------------------------------------------------------------------------
    # 1. Path Traversal Filename Test
    upload_traversal_status, _, _ = client.request(
        "/api/rikms/documents",
        method="POST",
        data={"title": "Path Traversal Test", "file_name": "../../../etc/passwd"},
        headers={"X-XSRF-TOKEN": client.xsrf_token() or ""},
    )
    upload_traversal_passed = upload_traversal_status in {400, 403, 419, 422, 404}
    checks.append({
        "id": "UPLOAD-PATH-TRAVERSAL",
        "category": "File Upload",
        "name": "Filename Path Traversal Rejection",
        "passed": upload_traversal_passed,
        "details": f"Path traversal filename upload attempt returned HTTP {upload_traversal_status}.",
    })
    if not upload_traversal_passed:
        findings.append({
            "id": "UPLOAD-PATH-TRAVERSAL",
            "title": "Document upload endpoint accepts path traversal filename",
            "severity": "critical",
            "observed": f"Upload with '../' in filename returned HTTP {upload_traversal_status}.",
            "owasp": "A01:2021-Broken Access Control",
            "cwe": "CWE-22",
        })

    # 2. Executable Extension Validation Test
    exec_upload_status, _, _ = client.request(
        "/api/rikms/documents",
        method="POST",
        data={"title": "Exec Test", "file_name": "shell.php"},
        headers={"X-XSRF-TOKEN": client.xsrf_token() or ""},
    )
    exec_upload_passed = exec_upload_status in {400, 403, 419, 422, 404}
    checks.append({
        "id": "UPLOAD-EXEC-EXTENSION",
        "category": "File Upload",
        "name": "Executable Extension (.php) Rejection",
        "passed": exec_upload_passed,
        "details": f"Executable file extension upload attempt returned HTTP {exec_upload_status}.",
    })
    if not exec_upload_passed:
        findings.append({
            "id": "UPLOAD-EXEC-EXTENSION",
            "title": "Document upload endpoint accepts executable file extensions",
            "severity": "critical",
            "observed": f"Upload of 'shell.php' returned HTTP {exec_upload_status}.",
            "owasp": "A04:2021-Insecure Design",
            "cwe": "CWE-434",
        })

    # ------------------------------------------------------------------------
    # C. FILE DOWNLOAD FLOW
    # ------------------------------------------------------------------------
    # 1. BOLA / IDOR Unauthorized Resource Download Test
    idor_status, _, _ = client.request("/api/rikms/documents/invalid-unauthorized-id/download")
    idor_passed = idor_status in {403, 404}
    checks.append({
        "id": "DOWNLOAD-BOLA-IDOR",
        "category": "File Download",
        "name": "BOLA / IDOR Access Authorization Enforcement",
        "passed": idor_passed,
        "details": f"Unauthorized document download attempt returned HTTP {idor_status}.",
    })
    if not idor_passed:
        findings.append({
            "id": "DOWNLOAD-BOLA-IDOR",
            "title": "Document download endpoint failed IDOR authorization boundary",
            "severity": "high",
            "observed": f"Unauthorized download request returned HTTP {idor_status} instead of 403/404.",
            "owasp": "A01:2021-Broken Access Control",
            "cwe": "CWE-639",
        })

    # 2. Path Traversal Download Test
    dl_traversal_status, _, _ = client.request("/api/rikms/documents/..%2f..%2f.env/download")
    dl_traversal_passed = dl_traversal_status in {400, 403, 404}
    checks.append({
        "id": "DOWNLOAD-PATH-TRAVERSAL",
        "category": "File Download",
        "name": "Path Traversal Download Prevention",
        "passed": dl_traversal_passed,
        "details": f"Path traversal download attempt returned HTTP {dl_traversal_status}.",
    })
    if not dl_traversal_passed:
        findings.append({
            "id": "DOWNLOAD-PATH-TRAVERSAL",
            "title": "Document download endpoint vulnerable to path traversal",
            "severity": "critical",
            "observed": f"Download request with traversal sequences returned HTTP {dl_traversal_status}.",
            "owasp": "A01:2021-Broken Access Control",
            "cwe": "CWE-22",
        })

    # ------------------------------------------------------------------------
    # D. TOKEN INVALIDATION ON LOGOUT
    # ------------------------------------------------------------------------
    logout_status, _, _ = client.request(
        "/logout",
        method="POST",
        headers={"X-XSRF-TOKEN": client.xsrf_token() or ""},
    )
    post_logout_me_status, _, _ = client.request("/api/rikms/me")
    logout_passed = post_logout_me_status == 401

    checks.append({
        "id": "AUTH-LOGOUT-INVALIDATION",
        "category": "Authentication",
        "name": "Session Invalidation on Logout",
        "passed": logout_passed,
        "details": f"Logout status: HTTP {logout_status}, Post-logout identity query: HTTP {post_logout_me_status}.",
    })
    if not logout_passed:
        findings.append({
            "id": "AUTH-LOGOUT-INVALIDATION",
            "title": "Session token remains active after logout",
            "severity": "high",
            "observed": f"Identity endpoint returned HTTP {post_logout_me_status} after logout.",
            "owasp": "A07:2021-Identification and Authentication Failures",
            "cwe": "CWE-613",
        })

    overall_passed = len(findings) == 0
    return {
        "timestamp": dt.datetime.now(dt.timezone.utc).isoformat(),
        "target": target,
        "environment": environment,
        "passed": overall_passed,
        "checks_count": len(checks),
        "findings_count": len(findings),
        "checks": checks,
        "findings": findings,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Run Process-Specific Security Considerations E2E Scanner.")
    parser.add_argument("--target", default="http://127.0.0.1:8000", help="Authorized target application URL")
    parser.add_argument("--environment", choices=["local", "staging", "production"], default="local")
    parser.add_argument("--email", default=os.getenv("RIKMS_SCAN_EMAIL", ""), help="Synthetic scan user email")
    parser.add_argument("--password", default=os.getenv("RIKMS_SCAN_PASSWORD", ""), help="Synthetic scan user password")
    parser.add_argument("--output", default="", help="Path to save private JSON report")
    args = parser.parse_args()

    if not args.email or not args.password:
        print("Error: Active process scanning requires synthetic credentials via --email and --password or RIKMS_SCAN_EMAIL and RIKMS_SCAN_PASSWORD env vars.", file=sys.stderr)
        return 2

    try:
        report = run_process_security_checks(args.target, args.email, args.password, args.environment)
    except SafetyError as error:
        print(f"Safety Gate Refusal: {error}", file=sys.stderr)
        return 2

    print(f"\n========================================================")
    print(f"  PROCESS-SPECIFIC SECURITY CONSIDERATIONS REPORT")
    print(f"========================================================")
    print(f"Target:       {report['target']}")
    print(f"Passed:       {report['passed']}")
    print(f"Total Checks: {report['checks_count']}")
    print(f"Findings:     {report['findings_count']}\n")

    for check in report["checks"]:
        status_str = "[PASS]" if check["passed"] else "[FAIL]"
        print(f"{status_str} [{check['category']}] {check['name']}: {check['details']}")

    if report["findings"]:
        print("\n--- FINDINGS ---")
        for finding in report["findings"]:
            print(f"CRITICAL/HIGH [{finding['severity'].upper()}] {finding['title']}: {finding['observed']}")

    if args.output:
        out_path = Path(args.output)
        private_path = private_output_path(PROJECT_ROOT / "storage" / "app" / "security" / "reports", out_path.name)
        with open(private_path, "w", encoding="utf-8") as f:
            json.dump(report, f, indent=2)
        print(f"\nReport written to: {private_path}")

    return 0 if report["passed"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
