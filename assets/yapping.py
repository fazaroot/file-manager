import librosa
import numpy as np
import requests
import os

# --- URL AUDIO ---
# Urutan sesuai temuan discord
URLS = [
    "https://cache.pusatkode.com/5h0jyu.MP3",
    "https://cache.pusatkode.com/f1952q.MP3",
    "https://cache.pusatkode.com/10379p.MP3",
    "https://cache.pusatkode.com/ydyf2y.MP3",
    "https://cache.pusatkode.com/hyg2dc.MP3",
    "https://cache.pusatkode.com/zp1l09.MP3"
]

def download_file(url, filename):
    if not os.path.exists(filename):
        print(f"DL: {filename}")
        r = requests.get(url)
        with open(filename, 'wb') as f:
            f.write(r.content)

def get_hex_stream(filename):
    # 1. Load Audio (Tanpa compress, raw mono)
    y, sr = librosa.load(filename, sr=None)
    
    # 2. Pitch Tracking (Mendeteksi nada frame-by-frame)
    # fmin/fmax diset ke range Piano standar (C2 - C7)
    pitches, magnitudes = librosa.piptrack(y=y, sr=sr, fmin=65, fmax=2093)
    
    hex_stream = []
    
    # 3. Iterasi setiap frame waktu
    # Kita ambil pitch dengan magnitude terbesar di setiap frame
    for t in range(pitches.shape[1]):
        index = magnitudes[:, t].argmax()
        pitch = pitches[index, t]
        
        # Filter: Jika pitch > 0 (bukan silence)
        if pitch > 0:
            # Convert Hz ke MIDI Note Number (0-127)
            midi_note = librosa.hz_to_midi(pitch)
            
            # Rounding ke integer terdekat
            note_int = int(round(midi_note))
            
            # --- LOGIKA DECODE: MIDI to HEX ---
            # Kita pakai Modulo 16. 
            # Apapun nadanya, kita ambil sisa bagi 16 untuk dapet 0-F.
            # Ini teknik standar CTF audio-to-hex.
            hex_val = hex(note_int % 16)[2:].upper()
            
            # Hindari duplikasi berlebihan (Debouncing sederhana)
            # Kalau hex-nya sama persis kayak frame sebelumnya, skip (kecuali durasi panjang)
            # Tapi buat raw data, kita ambil aja dulu.
            hex_stream.append(hex_val)

    # Gabungkan jadi string
    # Kita compress: kalau ada 100x angka '2' berturut-turut, kita ambil secukupnya
    # biar gak kepanjangan outputnya
    raw_str = "".join(hex_stream)
    
    # Simple compression buat display: Ambil tiap karakter ke-5
    # (Karena 1 nada piano itu panjangnya puluhan milidetik = puluhan frame)
    compressed_str = raw_str[::5] 
    
    return compressed_str

# --- EKSEKUSI ---
if __name__ == "__main__":
    print("--- STARTING BRUTE FORCE EXTRACTION ---\n")
    
    full_combined_hex = ""
    
    for i, url in enumerate(URLS):
        fname = f"raw_audio_{i+1}.mp3"
        download_file(url, fname)
        
        print(f"[*] Processing {fname}...")
        hex_res = get_hex_stream(fname)
        
        print(f"    -> Hasil Hex (Potongan): {hex_res[:50]}... [Total {len(hex_res)} chars]")
        full_combined_hex += hex_res

    print("\n" + "="*50)
    print("FULL HEX STRING DARI 6 AUDIO:")
    print("="*50)
    # Print 500 karakter pertama aja biar terminal gak crash, sisanya simpen file
    print(full_combined_hex[:500] + "...") 
    
    with open("FULL_HEX_DUMP.txt", "w") as f:
        f.write(full_combined_hex)
        
    print(f"\n[DONE] Data lengkap disimpan di 'FULL_HEX_DUMP.txt'")
