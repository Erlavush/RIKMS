# RIKMS — Manual Startup Guide

> Open **4 separate Command Prompt / PowerShell windows** in the project folder:
> `C:\Users\USePObrero_User609\Downloads\RIKMS`
>
> Run each command below in its own window. Do **not** close any window while using the app.

---

## Step 0 — One-time: build the frontend (only needed if JS files changed)

```powershell
cmd /c npm run build
del public\hot
```

> `del public\hot` removes the Vite lock file so Laravel uses the compiled build instead of a dead dev server.

---

## Step 1 — Docling server (PDF → Markdown converter, CUDA/GPU)

**Window 1:**
```powershell
cd C:\Users\USePObrero_User609\Downloads\RIKMS
python scripts/docling_server.py
```

Wait until you see:
```
[docling-server] Ready. Listening on http://127.0.0.1:5001
```

> [!IMPORTANT]
> To enable GPU acceleration, ensure **`onnxruntime-gpu`** is installed instead of `onnxruntime` (run `pip uninstall onnxruntime` then `pip install onnxruntime-gpu`). This is required because Docling's layout and OCR models run on ONNX Runtime, which defaults to CPU if the GPU package is not installed.


---

## Step 2 — Laravel web server

**Window 2:**
```powershell
cd C:\Users\USePObrero_User609\Downloads\RIKMS
php -d upload_max_filesize=25M -d post_max_size=27M -S 127.0.0.1:8000 -t public
```

Wait until you see:
```
PHP 8.3.32 Development Server (http://127.0.0.1:8000) started
```

> [!IMPORTANT]
> Do **not** use `php artisan serve` for the web server, as it launches a child process that ignores custom command-line PHP settings (`-d upload_max_filesize` / `-d post_max_size`), causing uploads larger than 8MB to fail. Running the server directly via `php -S` respects the configured file upload limits.


---

## Step 3 — Queue worker (processes AI analysis jobs)

**Window 3:**
```powershell
cd C:\Users\USePObrero_User609\Downloads\RIKMS
php artisan queue:work --queue=ai,default --tries=3 --timeout=400
```

This stays idle when no jobs are queued — that is normal.

---

## Step 4 — Ollama (already runs as a system service)

Ollama starts automatically with Windows. You can verify it is running:
```powershell
ollama list
```
You should see `gemma2:2b` in the list. If not, open the **Ollama** app from the Start menu.

---

## Open in browser

**→ [http://localhost:8000](http://localhost:8000)**

| Account | Email | Password | Notes |
|---|---|---|---|
| Agency Admin | `test@example.com` | `password` | DOST XI workspace |
| Super Admin | `admin@rikms.gov.ph` | `password` | Requires 2FA setup on first login |

---

## 2FA setup for Super Admin (first login only)

1. Go to [http://localhost:8000/admin/login](http://localhost:8000/admin/login)
2. Log in with `admin@rikms.gov.ph` / `password`
3. You will be redirected to `/two-factor/setup`
4. Open **Google Authenticator** (or any TOTP app) and add account manually:
   - **Key:** `AF6KV7VWIPY5BSH5`
5. Enter the 6-digit code to confirm, then save the recovery codes shown

---

## Shutdown order

Just close all 3 windows (Docling, Laravel, Queue worker). Ollama keeps running in the background.

---

## How AI metadata extraction works

1. Agency admin uploads a PDF research document
2. On the research edit page, clicks **"Request AI Analysis"**
3. The Laravel server calls the **Docling server** (`/detect` then `/convert`) to extract text
4. The extracted text is passed to **Ollama (gemma2:2b)** via the metadata extractor script
5. AI suggestions appear in the UI for human review — nothing is auto-applied

> All AI features require the Docling server (Step 1) and Ollama (Step 4) to be running.
> The queue worker (Step 3) processes the job in the background.

---

## Quick health check

Run this in PowerShell to verify all services are up:

```powershell
# Docling
Invoke-RestMethod http://127.0.0.1:5001/health

# Ollama
Invoke-RestMethod http://127.0.0.1:11434/api/tags | Select-Object -ExpandProperty models | Select-Object name

# Laravel
Invoke-WebRequest http://localhost:8000 -UseBasicParsing | Select-Object StatusCode
```
