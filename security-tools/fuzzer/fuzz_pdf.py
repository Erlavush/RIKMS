#!/usr/bin/env python3
import os
import sys
import random
import re
import argparse

class RadamsaMutator:
    def __init__(self, seed=None):
        if seed is None:
            seed = random.randint(0, 2**32 - 1)
        self.seed = seed
        self.rng = random.Random(seed)
        
    def delete_random_chunk(self, data: bytearray) -> bytearray:
        if len(data) < 2:
            return data
        max_chunk = min(len(data) // 2, 1000)
        chunk_size = self.rng.randint(1, max_chunk)
        start = self.rng.randint(0, len(data) - chunk_size)
        del data[start : start + chunk_size]
        return data

    def insert_random_chunk(self, data: bytearray) -> bytearray:
        pos = self.rng.randint(0, len(data))
        insert_type = self.rng.choice(['random', 'special', 'repeat'])
        
        if insert_type == 'random':
            length = self.rng.randint(1, 100)
            chunk = bytearray(self.rng.getrandbits(8) for _ in range(length))
        elif insert_type == 'special':
            specials = [
                b'\x00', b'\xff', b'\x7f', b'\x80', 
                b'%s%d%x%n', b'%s' * 20, 
                b'A' * 256, b'A' * 4096, 
                b'../' * 10, b'/' * 100, 
                b'\n', b'\r\n', b'\x00' * 50,
                b'-1', b'2147483647', b'4294967295', b'NaN', b'Infinity',
                b'<<', b'>>', b'obj', b'endobj', b'stream', b'endstream',
                # Safe XSS (Cross-Site Scripting) Payloads (logs to browser console instead of executing alert)
                b'<script>console.log("XSS_Vulnerability_Detected")</script>',
                b'<img src="x" onerror="console.log(\'XSS_Image_Err_Test\')">',
                # Safe Command Injection Payloads (harmless echo commands)
                b'; echo "CMD_INJECTION_TEST"',
                b'| echo "CMD_INJECTION_TEST"',
                # Safe SQL Injection (SQLi) Payloads
                b'\' OR \'1\'=\'1',
                b'UNION SELECT "SQL_INJECTION_TEST"',
                # Safe PDF JavaScript Execution Payload (harmless console print inside PDF)
                b'/OpenAction << /S /JavaScript /JS (console.show(); console.println("Safe PDF JS Payload Executed!");) >>'
            ]
            chunk = bytearray(self.rng.choice(specials))
        else:  # repeat a byte
            byte = self.rng.randint(0, 255)
            length = self.rng.randint(1, 500)
            chunk = bytearray([byte] * length)
            
        data[pos:pos] = chunk
        return data

    def replace_random_chunk(self, data: bytearray) -> bytearray:
        if len(data) < 2:
            return data
        max_chunk = min(len(data) // 2, 500)
        chunk_size = self.rng.randint(1, max_chunk)
        start = self.rng.randint(0, len(data) - chunk_size)
        
        replace_type = self.rng.choice(['random', 'special'])
        if replace_type == 'random':
            chunk = bytearray(self.rng.getrandbits(8) for _ in range(chunk_size))
        else:
            specials = [b'\x00', b'\xff', b'\x7f', b'\x80', b'A', b'\n', b'\x00' * chunk_size]
            byte_val = self.rng.choice(specials)
            if len(byte_val) == 1:
                chunk = bytearray(byte_val * chunk_size)
            else:
                chunk = bytearray(byte_val[:chunk_size].ljust(chunk_size, b'\x00'))
                
        data[start:start + chunk_size] = chunk
        return data

    def repeat_random_chunk(self, data: bytearray) -> bytearray:
        if len(data) < 2:
            return data
        max_chunk = min(len(data) // 4, 100)
        chunk_size = self.rng.randint(1, max_chunk)
        start = self.rng.randint(0, len(data) - chunk_size)
        chunk = data[start:start + chunk_size]
        
        repeats = self.rng.randint(2, 20)
        data[start + chunk_size:start + chunk_size] = chunk * (repeats - 1)
        return data

    def swap_chunks(self, data: bytearray) -> bytearray:
        if len(data) < 4:
            return data
        max_chunk = min(len(data) // 4, 200)
        size1 = self.rng.randint(1, max_chunk)
        size2 = self.rng.randint(1, max_chunk)
        
        pos1 = self.rng.randint(0, len(data) - size1 - size2)
        pos2 = self.rng.randint(pos1 + size1, len(data) - size2)
        
        chunk1 = data[pos1:pos1 + size1]
        chunk2 = data[pos2:pos2 + size2]
        
        data[pos2:pos2 + size2] = chunk1
        data[pos1:pos1 + size1] = chunk2
        return data

    def byte_flip(self, data: bytearray) -> bytearray:
        if len(data) == 0:
            return data
        num_flips = self.rng.randint(1, min(len(data), 10))
        for _ in range(num_flips):
            pos = self.rng.randint(0, len(data) - 1)
            bit = self.rng.randint(0, 7)
            data[pos] ^= (1 << bit)
        return data

    def mutate_ascii_numbers(self, data: bytearray) -> bytearray:
        # Match sequences of digits in the file
        digit_re = re.compile(b'\\d+')
        matches = list(digit_re.finditer(data))
        if not matches:
            return data
            
        num_to_mutate = self.rng.randint(1, min(len(matches), 5))
        matches_to_mutate = self.rng.sample(matches, num_to_mutate)
        matches_to_mutate.sort(key=lambda m: m.start(), reverse=True)
        
        for match in matches_to_mutate:
            start, end = match.start(), match.end()
            mutations = [
                b'0', b'-1', b'2147483647', b'-2147483648', b'4294967295',
                b'9999999999999999', b'3.14159', b'-0.0000001', b'NaN', 
                b'0xffffffff', b'0000000000000'
            ]
            new_val = self.rng.choice(mutations)
            data[start:end] = new_val
            
        return data

    def mutate(self, data: bytes, density=3, protect_header=True) -> bytes:
        mutated_data = bytearray(data)
        if len(mutated_data) < 10:
            return bytes(mutated_data)
            
        # If protecting header, we keep the first 4 bytes (%PDF) unchanged
        header_bytes = b''
        if protect_header and mutated_data.startswith(b'%PDF'):
            header_bytes = mutated_data[:4]
            mutated_data = mutated_data[4:]
            
        mutations = [
            self.delete_random_chunk,
            self.insert_random_chunk,
            self.replace_random_chunk,
            self.repeat_random_chunk,
            self.swap_chunks,
            self.mutate_ascii_numbers,
            self.byte_flip
        ]
        
        applied_mutations = []
        for _ in range(density):
            mutation_func = self.rng.choice(mutations)
            applied_mutations.append(mutation_func.__name__)
            mutated_data = mutation_func(mutated_data)
            if len(mutated_data) == 0:
                mutated_data = bytearray(b'A')
                
        return header_bytes + bytes(mutated_data)

def main():
    parser = argparse.ArgumentParser(description="Radamsa-like PDF Mutation Fuzzer")
    parser.add_argument("-i", "--input", default="BinderOne.pdf", help="Path to input PDF file")
    parser.add_argument("-o", "--output-dir", default="fuzzed_outputs", help="Directory to save fuzzed files")
    parser.add_argument("-n", "--count", type=int, default=50, help="Number of fuzzed files to generate")
    parser.add_argument("-s", "--seed", type=int, default=None, help="Base seed for random generation")
    parser.add_argument("-d", "--density", type=int, default=3, help="Number of mutations to apply per file")
    parser.add_argument("--no-protect", action="store_true", help="Do not protect the %PDF header")
    
    args = parser.parse_args()
    
    if not os.path.exists(args.input):
        print(f"Error: Input file '{args.input}' not found.")
        sys.exit(1)
        
    os.makedirs(args.output_dir, exist_ok=True)
    
    with open(args.input, "rb") as f:
        original_data = f.read()
        
    base_seed = args.seed if args.seed is not None else random.randint(0, 2**31 - 1)
    print(f"[*] Starting PDF mutation fuzzing...")
    print(f"[*] Input file: {args.input} ({len(original_data)} bytes)")
    print(f"[*] Output directory: {args.output_dir}")
    print(f"[*] Generating {args.count} fuzzed files with base seed: {base_seed}")
    print(f"[*] Mutation density: {args.density} operations per file")
    print(f"[*] Protect header: {'No' if args.no_protect else 'Yes'}")
    
    # Save a CSV log file of the fuzzing run
    log_path = os.path.join(args.output_dir, "fuzz_log.csv")
    with open(log_path, "w") as log_file:
        log_file.write("filename,seed,density,protect_header\n")
        
        # Instantiate a random generator using base_seed to generate seeds for each file
        seed_generator = random.Random(base_seed)
        
        for i in range(1, args.count + 1):
            file_seed = seed_generator.randint(0, 2**32 - 1)
            mutator = RadamsaMutator(file_seed)
            mutated_bytes = mutator.mutate(
                original_data, 
                density=args.density, 
                protect_header=not args.no_protect
            )
            
            out_filename = f"BinderOne_fuzzed_{i:04d}.pdf"
            out_path = os.path.join(args.output_dir, out_filename)
            with open(out_path, "wb") as out_f:
                out_f.write(mutated_bytes)
                
            log_file.write(f"{out_filename},{file_seed},{args.density},{not args.no_protect}\n")
            
            if i % 10 == 0 or i == args.count:
                print(f"    Progress: {i}/{args.count} files generated.")
                
    print(f"[+] Complete! Fuzz log saved to: {log_path}")

if __name__ == "__main__":
    main()
