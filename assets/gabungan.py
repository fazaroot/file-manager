import librosa
import numpy as np

# --- KONFIGURASI ---
FILENAME = "gabungan.wav"  # Ganti dengan nama file audiomu
HOP_LENGTH = 512           # Resolusi pembacaan sample

def detect_notes(audio_file):
    print(f"[*] Menganalisis Frekuensi Audio: {audio_file}...")
    
    # 1. Load Audio
    # y = gelombang suara, sr = sample rate
    y, sr = librosa.load(audio_file)
    
    # 2. Deteksi Onset (Kapan tuts piano ditekan)
    # Kita mencari 'serangan' awal suara agar tahu kapan nada berganti
    onset_frames = librosa.onset.onset_detect(y=y, sr=sr, hop_length=HOP_LENGTH)
    
    detected_notes = []
    
    print("[*] Mengekstrak Nada (Pitch)...")
    
    # 3. Analisis Pitch pada setiap Onset
    # Kita menggunakan algoritma PYIN (Probabilistic YIN) untuk deteksi nada fundamental (f0)
    f0, voiced_flag, voiced_probs = librosa.pyin(y, fmin=librosa.note_to_hz('C1'), fmax=librosa.note_to_hz('C8'))
    
    # Loop setiap frame onset yang ditemukan
    for frame in onset_frames:
        # Ambil frekuensi di titik waktu tersebut
        # Kita ambil rata-rata frekuensi di sekitar onset agar lebih akurat
        window = f0[frame:frame+5] 
        window = window[~np.isnan(window)] # Buang yang kosong (nan)
        
        if len(window) == 0:
            continue
            
        freq = np.mean(window)
        
        # 4. Konversi Frekuensi (Hz) ke Nama Nada (Note)
        # Contoh: 261.6 Hz -> C4
        note = librosa.hz_to_note(freq)
        detected_notes.append(note)

    return detected_notes

# --- MAPPING NADA KE HEX (CIPHER) ---
# Ini adalah bagian di mana kita harus "menebak" logika pembuat soal.
# Biasanya urutan nada C, D, E, F... dipetakan ke 0, 1, 2, 3...
def map_notes_to_hex(notes):
    # Contoh pemetaan sederhana (Perlu disesuaikan dengan aturan soal sebenarnya)
    # Kita ambil huruf depannya saja sebagai contoh
    hex_string = ""
    print(f"\n[*] Total Nada Terdeteksi: {len(notes)}")
    print(f"[*] Sampel Nada: {notes[:10]} ...")
    
    # Logika Konversi (Simulasi)
    # Di CTF asli, kamu harus mencari pola. Misal: C=0, D=1, E=2, F=3...
    # Disini kita print saja nadanya agar bisa dianalisis manual
    return notes

# --- EKSEKUSI ---
if __name__ == "__main__":
    try:
        notes = detect_notes(FILENAME)
        
        print("\n=== HASIL TRANSKRIPSI ===")
        print("Urutan Nada Piano yang ditemukan:")
        print(" -> ".join(notes))
        
        print("\n[TIPS]")
        print("Sekarang kamu punya daftar nadanya (misal: C4, E4, G4...).")
        print("Gunakan tabel Hex: 0-9, A-F (Total 16 karakter).")
        print("Cocokkan 16 nada dasar dengan 16 karakter Hex untuk mendapat kodenya.")
        
    except Exception as e:
        print(f"[ERROR] Pastikan file '{FILENAME}' ada dan library terinstall.")
        print(f"Detail error: {e}")
