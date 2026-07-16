# Local Ollama Metadata Development

RIKMS can use a loopback Ollama model for local development and testing. This
is the safe integration point for the useful part of Julse's local-model
concept: extract PDF text, request structured metadata, validate it with the
canonical RIKMS schema, and keep every result behind human review.

This work does not deploy a model to Google Cloud. Production continues to
default to the existing Vertex provider unless a separate reviewed deployment
changes that configuration.

## Architecture

```text
private PDF
  -> source signature, MIME, size, hash and malware-state gate
  -> optional Docling/MinerU, or pdftotext, bounded OCR, embedded text, Document AI
  -> loopback Ollama provider
  -> strict shared JSON schema and Laravel validation
  -> versioned AI suggestion record
  -> editable draft and human review
```

The provider never publishes, approves, changes access, or directly overwrites
authoritative metadata. The existing queue job checks the source-safety gate
before and after inference so a replaced document cannot accept stale output.

Docling and MinerU are extractor adapters, not AI metadata providers. Their
Markdown enters the same Ollama/Vertex provider and canonical schema as every
other extraction method. The safe default is `native`, so pulling this code
does not install a large Python model or change existing extraction behavior.

Selection is explicit:

- `native`: current pdftotext, Tesseract, embedded-text and cloud OCR chain;
- `docling`: require Docling and fail honestly if it cannot extract;
- `mineru`: require MinerU and fail honestly if it cannot extract;
- `auto`: try configured Docling, then configured MinerU, then the native chain.

## Windows setup

Install Ollama and confirm it is available from PowerShell:

```powershell
ollama --version
ollama serve
ollama list
```

Install the chosen local model only when the machine owner approves the model
download and disk use:

```powershell
ollama pull qwen3.5:4b
```

For local PDF extraction, install Poppler and Tesseract, then put the exact
executable paths in the local `.env`. Example paths vary by installation:

```env
RIKMS_AI_ENABLED=true
RIKMS_AI_AUTO_QUEUE=true
RIKMS_AI_PROVIDER=ollama
RIKMS_AI_MODEL=qwen3.5:4b

OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen3.5:4b
OLLAMA_NUM_CTX=8192
OLLAMA_MAX_INPUT_CHARACTERS=24000
OLLAMA_KEEP_ALIVE=30m

LOCAL_PDF_TEXT_COMMAND=C:\Tools\poppler\Library\bin\pdftotext.exe
LOCAL_PDF_RENDER_COMMAND=C:\Tools\poppler\Library\bin\pdftoppm.exe
LOCAL_OCR_COMMAND=C:\Program Files\Tesseract-OCR\tesseract.exe
LOCAL_OCR_LANGUAGE=eng
LOCAL_OCR_MAX_PAGES=20
LOCAL_OCR_DPI=180
LOCAL_OCR_PAGE_TIMEOUT_SECONDS=15

LOCAL_DOCUMENT_EXTRACTOR=native
LOCAL_STRUCTURED_EXTRACTOR_TIMEOUT_SECONDS=90
LOCAL_STRUCTURED_EXTRACTOR_MAX_PAGES=20
LOCAL_STRUCTURED_EXTRACTOR_MAX_OUTPUT_BYTES=2500000
DOCLING_PYTHON_COMMAND=
MINERU_COMMAND=
```

Use the real installed paths. The values are passed as process arguments; do
not add shell fragments, pipes, redirects or embedded command options.

Clear cached configuration, start the app and run the AI queue worker:

```powershell
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
php artisan queue:work --queue=default,ai --tries=3
```

When using Jaylord's launcher with `-StartApp`, Laravel, this queue worker, and
Vite are started together from the current repository. Do not separately run
Vite or Laravel from a different clone.

Jaylord can start the local services and open the security workbench with:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\windows\security-dashboard.ps1 `
  -StartApp -StartOllama -AI
```

The dashboard will report the AI lane as `unavailable` when the requested
model is not installed. It never downloads a model automatically.

## Optional Docling extraction

Docling is Mon's layout, table and OCR-aware extraction path, adapted as a
one-shot CLI. RIKMS does not expose a Docling HTTP server, accept arbitrary
paths over a port, or retain converted Markdown. Follow the
[official Docling installation guide](https://docling-project.github.io/docling/getting_started/installation/)
and use a dedicated virtual environment.

Windows PowerShell example:

```powershell
py -3.12 -m venv "$env:USERPROFILE\.rikms\docling"
& "$env:USERPROFILE\.rikms\docling\Scripts\python.exe" -m pip install --upgrade pip docling
```

Then configure the exact executable path:

```env
LOCAL_DOCUMENT_EXTRACTOR=docling
DOCLING_PYTHON_COMMAND=C:\Users\YOUR_NAME\.rikms\docling\Scripts\python.exe
```

Linux example:

```bash
python3 -m venv "$HOME/.rikms/docling"
"$HOME/.rikms/docling/bin/python" -m pip install --upgrade pip docling
```

```env
LOCAL_DOCUMENT_EXTRACTOR=docling
DOCLING_PYTHON_COMMAND=/home/YOUR_NAME/.rikms/docling/bin/python
```

The wrapper rejects non-PDF input and bounds file size, page count, elapsed
time and returned characters. It uses Docling accelerator auto-selection, so
CUDA/MPS is used only when that local Python environment supports it.

## Optional MinerU extraction

MinerU is Julse's PDF-to-Markdown concept, adapted without its separate Ollama
call or machine-specific path. Install it using the
[official MinerU quick start](https://opendatalab.github.io/MinerU/quick_start/),
then find the installed executable:

```powershell
(Get-Command mineru).Source
```

```bash
command -v mineru
```

Configure the exact path reported on that machine:

```env
LOCAL_DOCUMENT_EXTRACTOR=mineru
MINERU_COMMAND=C:\Users\YOUR_NAME\.local\bin\mineru.exe
```

or on Linux:

```env
LOCAL_DOCUMENT_EXTRACTOR=mineru
MINERU_COMMAND=/home/YOUR_NAME/.local/bin/mineru
```

RIKMS invokes MinerU's documented `-p`, `-o`, `-b pipeline` arguments without
a shell. The source is already limited by RIKMS upload policy; execution time,
the complete temporary output tree and returned text are separately bounded.
MinerU may download models during its own installation or first-run setup;
perform that step deliberately before testing, never from a queue job.

## Linux setup

Use executable paths reported by the local machine:

```bash
command -v pdftotext pdftoppm tesseract ollama
ollama list
```

Typical local values are:

```env
LOCAL_PDF_TEXT_COMMAND=/usr/bin/pdftotext
LOCAL_PDF_RENDER_COMMAND=/usr/bin/pdftoppm
LOCAL_OCR_COMMAND=/usr/bin/tesseract
```

Run the focused checks before using real synthetic test documents:

```bash
php artisan test --filter=OllamaDocumentAnalysisTest
python3 -m unittest discover -s security/tests -v
python3 -m security.lab --run ai
```

For interactive RIKMS document testing, start Ollama in one terminal and the
complete Laravel development stack in another:

```bash
ollama serve
composer run dev
```

Open RIKMS on `http://127.0.0.1:8000`. Port 5173 is only Vite. A queued
analysis requires the `default,ai` worker included in `composer run dev`; if
that worker is absent, the edit page now reports the missing local runtime
instead of displaying an indefinite generic processing message.

## Security boundaries

- Ollama is accepted only in `local` or `testing` environments.
- Its configured endpoint must be `localhost`, `127.0.0.1`, or `::1`; remote
  and credential-bearing URLs are rejected before the PDF is read.
- Input length, context, output prediction, OCR pages, DPI and per-page time are
  bounded.
- Enhanced extractors receive one validated private PDF through process argument
  arrays, write only below a new permission-restricted temporary directory, and
  are cleaned in `finally` paths. Generated Markdown is never persisted.
- Docling and MinerU are accepted only in `local` or `testing`; production keeps
  the existing provider and extraction defaults.
- Model JSON is untrusted. Unknown fields, invalid SDGs, invalid confidence,
  malformed arrays and invalid pages are rejected.
- Uploaded papers are not security fixtures. Use the synthetic documents under
  `security/fixtures/ai` for prompt-injection and leakage testing.
- Ollama output and prompts are not written to application logs.

## Verification

The adapter tests do not require the heavy Python packages and therefore run on
both Linux and Windows CI:

```bash
php artisan test --filter='DocumentTextExtractionSelectionTest|DoclingDocumentTextExtractorTest|MineruDocumentTextExtractorTest|OllamaDocumentAnalysisTest'
python3 -m py_compile scripts/docling_extract.py
```

Before choosing an enhanced extractor for a teammate's normal workflow,
benchmark it with synthetic or approved papers against the default native
chain. A successful process means text was collected, not that the extracted
metadata is correct; the normal editable draft and human review remain required.
