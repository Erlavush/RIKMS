Fuzz Breakdown & Web App Testing Reference

This document explains what mutations were performed on the PDF and how they test the robustness of the extraction engine and the security of the downstream web application.

────────────────────────────────────────────────────────────────────────────────
1. Deleting Chunks (delete_random_chunk)
────────────────────────────────────────────────────────────────────────────────
• Action: Removes random slices of bytes (from 1 to 1,000 bytes) from BinderOne.pdf.
• Testing Purpose (Parser Robustness):
  - Cross-Reference (xref) Table Desynchronization: PDF files use an 'xref' table storing exact byte offsets for pages/objects. Deleting bytes shifts these offsets. A secure parser must detect misalignment and fail gracefully. A weak parser will jump to incorrect offsets, read garbage bytes, and trigger Null Pointer Dereferences or Type Confusion crashes.
  - Broken Enclosures: Cuts syntax tags in half (e.g., "<< /Type" to "<< /T"). Verifies if the parser hangs or throws unhandled EOF exceptions.

────────────────────────────────────────────────────────────────────────────────
2. Inserting Chunks & Web App Payloads (insert_random_chunk)
────────────────────────────────────────────────────────────────────────────────
• Action: Injects random strings, repeat buffers, or specific safe exploit payloads.
• Testing Purpose:
  - Parser Exploits:
    - Buffer Overflows: Injects long strings (e.g., 'A' * 4096) into metadata fields (Author, Title). Tests if low-level C-bindings (Poppler, MuPDF) enforce size limits to prevent memory corruption.
    - Null Byte Injection: Injects null bytes (\x00). Tests if the parser truncates filenames or bypasses file-extension verifications.
    - Format Strings: Injects '%s%x%n' to test if logging/print statements handle inputs unsafely.
  - Web App & Database Exploits (New):
    - Cross-Site Scripting (XSS): Injects harmless tags (e.g., '<script>console.log("XSS")</script>'). If the extracted metadata is shown on your web repository UI without escaping, the script will execute in the browser console.
    - SQL Injection (SQLi): Injects query markers (e.g., "' OR '1'='1" or "UNION SELECT"). If the web app saves the extracted metadata to a database via string formatting rather than parameterized queries, it will alter SQL syntax and throw errors.
    - Command Injection: Injects shell characters (e.g., "; echo CMD_TEST"). Tests if the web app runs CLI utilities unsafely behind the scenes.
    - PDF JavaScript Actions: Injects "/OpenAction << /S /JavaScript /JS ... >>" to test if the extraction engine or viewer triggers embedded scripts when reading the PDF.

────────────────────────────────────────────────────────────────────────────────
3. Replacing Chunks & Bit Flipping (replace_random_chunk & byte_flip)
────────────────────────────────────────────────────────────────────────────────
• Action: Overwrites random byte segments or flips individual bits.
• Testing Purpose (Decompression Engine):
  - Compressed stream corruption: PDF text/images are compressed (zlib/FlateDecode). Corrupting bytes inside compressed blocks forces decompression libraries to handle malformed zlib structures, testing for memory flaws in the decompression libraries.

────────────────────────────────────────────────────────────────────────────────
4. Swapping Chunks (swap_chunks)
────────────────────────────────────────────────────────────────────────────────
• Action: Swaps two non-overlapping sections of the file.
• Testing Purpose (Cycle Detection):
  - Infinite Recursion (DoS): Scrambles object directories. If Object A is made to point to Object B, which points back to Object A, a parser without cycle checking will enter infinite loops, exhausting stack memory and hanging/crashing the host server.

────────────────────────────────────────────────────────────────────────────────
5. Corrupting ASCII Numbers (mutate_ascii_numbers)
────────────────────────────────────────────────────────────────────────────────
• Action: Overwrites numerical text values with boundary numbers (0, -1, 2147483647, NaN).
• Testing Purpose (Integer Math):
  - Integer Overflow/Underflow: If a parser reads a length of -1 or 4294967295 and allocates memory via malloc(Length + 1), it overflows to 0. A tiny buffer is created, but the parser copies the full stream into it, causing a Heap Buffer Overflow.
  - Math Exception Crashes: Forces layout calculations to divide by zero or compute NaN, checking if rendering libraries crash.

────────────────────────────────────────────────────────────────────────────────
Summary of What to Monitor
────────────────────────────────────────────────────────────────────────────────
When testing the 50 mutated PDFs against your system, look for:
1. Backend Hangs: CPU climbs to 100% indefinitely (indicates infinite loops).
2. Backend Crashes: Segmentation faults or unhandled exceptions in the PDF parser.
3. Web App logs: "CMD_INJECTION_TEST" in shell outputs, SQL syntax errors in DB logs, or console logs in your web browser (verifying XSS/JS injection).