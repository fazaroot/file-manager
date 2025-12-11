import librosa
import numpy as np
import os

# Fungsi untuk mengubah Hz ke nama Not (Opsional, untuk debug)
def hz_to_note(hz):
    return librosa.hz_to_note(hz)

# Fungsi utama pengolah audio
def process_audio_to_hex(filepath):
    print(f"Sedang memproses: {filepath} ...")
    
    # 1. Load Audio
    try:
        y, sr = librosa.load(filepath)
    except Exception as e:
        print(f"Gagal memuat file: {e}")
        return ""

    # 2. Deteksi Onset (Saat nada pertama kali dibunyikan/dipukul)
    # Ini penting agar nada yang panjang tidak dihitung berkali-kali
    onset_frames = librosa.onset.onset_detect(y=y, sr=sr, backtrack=True)
    
    hex_results = []
    
    # 3. Analisis Pitch pada setiap titik onset
    # Kita menggunakan metode YIN untuk mendeteksi frekuensi dasar (f0)
    f0, voiced_flag, voiced_probs = librosa.pyin(y, fmin=librosa.note_to_hz('C1'), fmax=librosa.note_to_hz('C8'))
    
    # Konversi frame onset ke waktu, lalu ke indeks f0
    onset_times = librosa.frames_to_time(onset_frames, sr=sr)
    
    for frame_idx in onset_frames:
        # Ambil frekuensi pada frame tersebut
        # Kita ambil rata-rata sedikit ke depan dari onset agar stabil
        if frame_idx < len(f0):
            freq = f0[frame_idx]
            
            # Jika freq terdeteksi (bukan nan)
            if not np.isnan(freq):
                # 4. Konversi Frekuensi ke MIDI Number
                midi_note = librosa.hz_to_midi(freq)
                
                # Bulatkan ke integer terdekat (karena MIDI itu bilangan bulat)
                midi_int = int(round(midi_note))
                
                # 5. Konversi MIDI ke Hexadecimal
                # {:02X} artinya jadikan hex huruf besar, minimal 2 digit
                hex_val = "{:02X}".format(midi_int)
                
                hex_results.append(hex_val)

    # Gabungkan semua hex menjadi satu string
    final_string = "".join(hex_results)
    return final_string

# --- BAGIAN UTAMA ---
# Mencari semua file .wav atau .mp3 di folder ini
files = sorted([f for f in os.listdir('.') if f.endswith('.wav') or f.endswith('.mp3')])

if not files:
    print("Tidak ada file audio ditemukan! Pastikan script ini satu folder dengan file audio.")
else:
    print(f"Ditemukan {len(files)} file audio.\n")
    
    all_hex_combined = ""

    for audio_file in files:
        hex_string = process_audio_to_hex(audio_file)
        print(f"File: {audio_file}")
        print(f"Hex : {hex_string}")
        print("-" * 20)
        all_hex_combined += hex_string

    print("\n=== GABUNGAN SEMUA HEX ===")
    print(all_hex_combined)
    print("\nCopy kode hex di atas dan masukkan ke CyberChef (From Hex -> Render Image)")
