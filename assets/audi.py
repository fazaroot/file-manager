import librosa
import numpy as np
import requests
import os
from io import BytesIO

# --- 1. CONFIG URL & MAPPING ---
URLS = [
    "https://cache.pusatkode.com/5h0jyu.MP3",
    "https://cache.pusatkode.com/f1952q.MP3",
    "https://cache.pusatkode.com/10379p.MP3",
    "https://cache.pusatkode.com/ydyf2y.MP3",
    "https://cache.pusatkode.com/hyg2dc.MP3",
    "https://cache.pusatkode.com/zp1l09.MP3"
]

# Mapping Nada ke Hex (Berdasarkan pola standar Piano Cipher)
# Kita buat range luas biar nada oktaf berapapun kena.
NOTE_MAP = {
    # Nada C = 0, C# = 1, D = 2 ... sampai B = F (Logic umum)
    # Atau mapping spesifik dari puzzle ini (Vigo/Discord style)
    # Disini kita pakai mapping Logika Audio -> Data
    "C": "0", "C#": "1", "D": "2", "D#": "3", "E": "4", "F": "5", 
    "F#": "6", "G": "7", "G#": "8", "A": "9", "A#": "A", "B": "B"
}

def download_file(url, filename):
    if not os.path.exists(filename):
        print(f"[*] Downloading {filename}...")
        r = requests.get(url)
        with open(filename, 'wb') as f:
            f.write(r.content)

def extract_hex_from_audio(filename):
    print(f"\n[*] Menganalisis FULL Audio: {filename}")
    print("    (Harap sabar, sedang scanning ribuan frame...)")
    
    # 1. Load Audio Full
    y, sr = librosa.load(filename)
    
    # 2. Deteksi Onset (Setiap kali tuts ditekan)
    onset_frames = librosa.onset.onset_detect(y=y, sr=sr, hop_length=512)
    
    # 3. Analisis Frekuensi (Pitch)
    # Kita pakai fmin C1 dan fmax C8 (Range piano penuh)
    f0, _, _ = librosa.pyin(y, fmin=librosa.note_to_hz('C1'), 
                            fmax=librosa.note_to_hz('C8'), sr=sr, frame_length=2048)
    
    hex_string = ""
    
    # Loop semua ketukan yang ditemukan
    for frame in onset_frames:
        # Ambil frekuensi di titik ketukan
        # Kita ambil rata-rata frekuensi di jendela kecil sekitar ketukan
        window = f0[frame:frame+3] # Ambil 3 frame kedepan
        window = window[~np.isnan(window)] # Buang nilai kosong
        
        if len(window) > 0:
            avg_freq = np.mean(window)
            note = librosa.hz_to_note(avg_freq)
            # note output contoh: "C#4", "D5"
            
            # Ambil Huruf Nadanya saja (C, D, E...) buang angkanya
            note_key = note[:-1] 
            
            # Konversi ke Hex pakai Mapping
            hex_char = NOTE_MAP.get(note_key, "?") # ? kalau nada tidak dikenal
            
            # Filter noise (hanya ambil yang valid 0-F)
            if hex_char != "?":
                hex_string += hex_char
                
    return hex_string

# --- MAIN EXECUTION ---
if __name__ == "__main__":
    all_hex_data = []
    
    for i, url in enumerate(URLS):
        fname = f"audio_part_{i+1}.mp3"
        download_file(url, fname)
        
        # EKSTRAK!
        long_hex = extract_hex_from_audio(fname)
        
        print(f"\n[HASIL EXTRAKSI FILE {i+1}]")
        print(f"Panjang String: {len(long_hex)} karakter")
        print(f"Preview (100 char awal): {long_hex[:100]}...")
        
        all_hex_data.append(long_hex)

    print("\n" + "="*40)
    print("BERIKUT ADALAH STRING HEX LENGKAP HASIL EKSTRAKSI:")
    print("="*40)
    for i, h in enumerate(all_hex_data):
        print(f"FILE {i+1} FULL HEX:\n{h}\n")
