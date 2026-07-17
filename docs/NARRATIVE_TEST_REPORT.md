# RIKMS Vulnerability Assessment & Security Verification Narrative Report

This report documents the security testing experiments conducted on the Regionwide Integrated Knowledge Management System (RIKMS) (`jaylord-edits` branch) in accordance with the adviser's narrative reporting template.

---

## 📝 1. Static Application Security Testing (SAST)
*   **Test Date:** July 15, 2026
*   **Type of Test:** Static Application Security Testing (SAST) / Taint-Flow Semantic Analysis.
*   **Description of Test:** Automated inspection of the application's source code files utilizing Larastan (PHPStan for Laravel) to detect logical syntax errors, configuration cache bugs, type violations, and taint-flow vulnerabilities (such as unsafe SQL queries or injection points).
*   **Scope:** All PHP files under the RIKMS Laravel backend, specifically targeting controllers (`app/Http/Controllers`), service classes (`app/Services`), models (`app/Models`), and Artisan console commands.
*   **Findings / Results:**
    *   **Total Issues Identified:** **466 static analysis warnings**.
    *   **Critical Risk - Null Pointer Dereferencing:** Multiple controllers (e.g., `AccessRequestApiController` and `AgencyApiController`) query properties (like `$id` and `$agency_id`) or execute methods (like `hasPermission()` and `fresh()`) directly on `$request->user()` or `$user` variables without verifying if the user object is null. If an unauthenticated or invalid session reaches these endpoints, the application will crash with a fatal PHP runtime error (HTTP 500).
    *   **Medium Risk - Config Caching Violations:** Direct invocations of the `env()` helper function were found outside Laravel's config directory (e.g., in `GoogleDriveAuthCommand.php`). If configuration caching is enabled in production, these helpers return `null`, rendering external Google Drive storage features silently broken.
    *   **Low Risk - Parameter Mismatches:** Several occurrences where nullable/boolean types are passed to strict native PHP string functions (e.g. `preg_match` or `preg_replace`).
*   **Actions Required / Remediation:**
    *   *Null-Pointer Checks:* Ensure that authentication state checks (e.g. `if (!$user) abort(401);`) are executed in the controller constructor or middleware before performing actions on the active user object.
    *   *Config Encapsulation:* Relocate all `env()` function calls to configuration files under the `config/` directory, and refer to them in classes using `config('services.google.drive_auth')`.

---

## 🔒 2. Dynamic Application Security Testing (DAST)
*   **Test Date:** July 15, 2026
*   **Type of Test:** Dynamic Application Security Testing (DAST) / Web Application Vulnerability Fuzzing.
*   **Description of Test:** Active scanning of the running RIKMS application boundaries using OWASP ZAP (Zed Attack Proxy) and python-based HTTP response header checking to identify runtime misconfigurations, injection vulnerability surfaces, and transport security flaws.
*   **Scope:** Deployed application endpoints, including the public home page, authentication gateways, and the sitemap/robots configurations.
*   **Findings / Results:**
    *   **Medium Risk - Content Security Policy (CSP) Missing:** The target server does not output a `Content-Security-Policy` header on web route responses. This leaves the frontend UI highly exposed to Cross-Site Scripting (XSS) and data injection payloads.
    *   **Low Risk - MIME-Sniffing Protection Missing:** The `X-Content-Type-Options: nosniff` header is missing on dynamic asset paths (like Vite scripts `/resources/js/main.tsx` and text documents like `robots.txt`).
    *   **Low Risk - Information Leakage:** The server outputs `X-Powered-By: PHP/8.3.30` on all web pages, exposing the exact backend version to potential attackers profiling the system.
    *   **Low Risk - HttpOnly Attribute Missing:** The `XSRF-TOKEN` cookie is set without the `HttpOnly` flag. *Note:* While Laravel standardizes this to allow JavaScript-based CSRF protection, security scanners flag it as a potential access path.
*   **Actions Required / Remediation:**
    *   *CSP Implementation:* Install and configure a Laravel-native CSP package (e.g., `spatie/laravel-csp`) to inject CSP headers.
    *   *MIME & Header Hardening:* Configure the backend web server (Nginx/Apache) or Laravel middleware to explicitly inject `X-Content-Type-Options: nosniff` and strip out `X-Powered-By` from response headers.

---

## 🔄 3. Functional & Access Control Regression Testing
*   **Test Date:** July 15, 2026
*   **Type of Test:** Shift-Left Functional Verification & Automated Access Control Testing.
*   **Description of Test:** Automated execution of the full RIKMS test suite using PHPUnit. Tests verify business-logic constraints, state machine transitions, and multi-tenant data isolation.
*   **Scope:** 56 feature test cases including Document Intake, SPA views, Access Control policies (restricted, public, embargoed), and Admin two-factor authentication (TOTP).
*   **Findings / Results:**
    *   **Total Issues Identified:** **0 Failures (100% Pass Rate)**. All 56 tests and 380 assertions completed successfully.
    *   **Access Control Verification:** The system successfully isolates documents across agency boundaries. Attempts by an agency administrator to read or modify another agency's document draft were actively blocked.
    *   **AI Validation Pipeline:** Verified that document uploads made via AI interfaces remain drafts until reviewed, and all AI recommendations require explicit human approval before database insertion.
    *   **Two-Factor Enforcement:** Verified that the system forces administrators to change temporary passwords and enroll a TOTP device before accessing any administrative endpoints.
*   **Actions Required / Remediation:**
    *   *Regression Prevention:* Integrate this PHPUnit test suite into the CI/CD pipeline to ensure that future code commits do not break these critical authorization rules.

---

## 📦 4. Software Supply Chain Analysis (SCA)
*   **Test Date:** July 15, 2026
*   **Type of Test:** Software Composition Analysis (SCA) / Dependency Audit.
*   **Description of Test:** Scanning the application lockfiles (`composer.lock` and `package-lock.json`) against databases of known vulnerabilities (CVEs) to check third-party libraries for security advisories.
*   **Scope:** All 51 production and 349 development dependencies in the PHP (Composer) and Node.js (npm) package lists.
*   **Findings / Results:**
    *   **Total Issues Identified:** **0 Vulnerabilities**. Both `composer audit` and `npm audit` returned clean reports.
    *   **Supply Chain Status:** Excellent. The codebase uses modern, secure versions of React, Vite, PHP packages, and Laravel.
*   **Actions Required / Remediation:**
    *   *Continuous Monitoring:* Set up automated dependency monitoring tools (e.g., Dependabot or Snyk) to scan for newly disclosed vulnerabilities in third-party libraries.
