import librosa
import numpy as np
from PIL import Image
from tqdm import tqdm

# --- CONFIG ---
FILENAME = "gabungan.wav" # Pastikan file ini ada di folder yang sama
HOP_LENGTH = 512

# Kunci Mapping (Cipher) berdasarkan pola Discord
# Kita lengkapi mappingnya dari C1 sampai oktaf tinggi
NOTE_TO_HEX = {
    # Octave 1
    "C1": 0x0, "C#1": 0x0, "D1": 0x1, "D#1": 0x1, "E1": 0x2, "F1": 0x3, "F#1": 0x3, "G1": 0x4, "G#1": 0x4, "A1": 0x5, "A#1": 0x5, "B1": 0x6,
    # Octave 2
    "C2": 0x7, "C#2": 0x7, "D2": 0x8, "D#2": 0x8, "E2": 0x9, "F2": 0xA, "F#2": 0xA, "G2": 0xB, "G#2": 0xB, "A2": 0xC, "A#2": 0xC, "B2": 0xD,
    # Octave 3
    "C3": 0xE, "C#3": 0xE, "D3": 0xF, "D#3": 0xF, 
    # Mapping Cadangan (Jika nada meleset dikit)
    "E3": 0x0, "F3": 0x1, "G3": 0x2 
}

def analyze_audio_full():
    print(f"[*] Loading Audio: {FILENAME} (Ini akan memakan waktu)...")
    y, sr = librosa.load(FILENAME)
    
    print("[*] Mendeteksi Onset (Ketukan Tuts)...")
    onset_frames = librosa.onset.onset_detect(y=y, sr=sr, hop_length=HOP_LENGTH)
    
    print(f"[*] Ditemukan {len(onset_frames)} ketukan. Sedang mengekstrak nada...")
    
    detected_hex = []
    
    # Gunakan PYIN untuk akurasi tinggi (tapi agak lambat)
    f0, _, _ = librosa.pyin(y, fmin=librosa.note_to_hz('C1'), fmax=librosa.note_to_hz('C5'), sr=sr)
    
    # Progress Bar biar kelihatan jalan
    for frame in tqdm(onset_frames):
        # Ambil rata-rata frekuensi di sekitar onset
        window = f0[frame:frame+5]
        window = window[~np.isnan(window)]
        
        if len(window) == 0:
            continue
            
        freq = np.mean(window)
        note = librosa.hz_to_note(freq)
        
        # Konversi Note ke Hex (Ambil nilai terakhir / default 0)
        # Kita pakai logika sederhana: Map note ke dictionary
        hex_val = NOTE_TO_HEX.get(note, 0) # Default ke 0 (Hitam/Putih) jika note tidak dikenal
        detected_hex.append(hex_val)

    return detected_hex

def build_qr_image(hex_data):
    print(f"\n[*] Total Data Hex Terkumpul: {len(hex_data)} nibbles")
    print("[*] Menyusun Pixel...")
    
    # QR Code itu kotak. Kita harus menebak lebar (Width) yang pas.
    # Total data audio ~600-900 nada.
    # QR Code Version 2 ukurannya 25x25 (625 pixel).
    # QR Code Version 3 ukurannya 29x29 (841 pixel).
    
    # Mari kita coba susun jadi kotak estimasi 29x29 (Standar QR data kecil)
    est_width = 29 
    est_height = 29
    
    # Siapkan Canvas
    img = Image.new('1', (est_width, est_height), 1) # 1 = Putih
    pixels = img.load()
    
    idx = 0
    # Ubah list Hex menjadi bitstream panjang
    # Tiap 1 Hex (0-F) = 4 bit (0000 - 1111)
    full_binary_string = ""
    for h in hex_data:
        full_binary_string += bin(h)[2:].zfill(4)
        
    print(f"[*] Panjang Bitstream: {len(full_binary_string)} bits")
    
    # Gambar ke canvas
    total_pixels = est_width * est_height
    for i in range(total_pixels):
        if i < len(full_binary_string):
            bit = full_binary_string[i]
            x = i % est_width
            y = i // est_width
            
            # QR Code: 1 = Hitam (0), 0 = Putih (1)
            color = 0 if bit == '1' else 1
            pixels[x, y] = color

    # Resize biar enak dilihat (Zoom 10x)
    final_img = img.resize((est_width * 10, est_height * 10), Image.NEAREST)
    final_img.save("hasil_hardcore.png")
    print(f"[SUCCESS] Gambar disimpan: hasil_hardcore.png")

if __name__ == "__main__":
    try:
        data = analyze_audio_full()
        build_qr_image(data)
    except Exception as e:
        print(f"Error: {e}")
