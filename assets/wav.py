import librosa
import numpy as np
import os
from pydub import AudioSegment  # untuk convert MP3 ke WAV otomatis

# Fungsi untuk mengubah Hz ke nama Not (Opsional)
def hz_to_note(hz):
    return librosa.hz_to_note(hz)

# Fungsi convert otomatis (TIDAK mengubah fungsi utama kamu)
def convert_mp3_to_wav(src):
    dst = src.replace(".mp3", ".wav")

    if not os.path.exists(dst):
        print(f"Mengonversi {src} -> {dst}")
        sound = AudioSegment.from_mp3(src)
        sound.export(dst, format="wav")

    return dst

# Fungsi utama TIDAK DIUBAH
def process_audio_to_hex(filepath):
    print(f"Sedang memproses: {filepath} ...")
    
    try:
        y, sr = librosa.load(filepath)
    except Exception as e:
        print(f"Gagal memuat file: {e}")
        return ""

    onset_frames = librosa.onset.onset_detect(y=y, sr=sr, backtrack=True)
    hex_results = []

    f0, voiced_flag, voiced_probs = librosa.pyin(
        y, 
        fmin=librosa.note_to_hz('C1'), 
        fmax=librosa.note_to_hz('C8')
    )
    
    onset_times = librosa.frames_to_time(onset_frames, sr=sr)

    for frame_idx in onset_frames:
        if frame_idx < len(f0):
            freq = f0[frame_idx]
            if not np.isnan(freq):
                midi_note = librosa.hz_to_midi(freq)
                midi_int = int(round(midi_note))
                hex_val = "{:02X}".format(midi_int)
                hex_results.append(hex_val)

    final_string = "".join(hex_results)
    return final_string

# ========================
# BAGIAN UTAMA (FIXED)
# ========================

# Path absolut agar tidak tergantung folder terminal
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

files = [
    f for f in os.listdir(BASE_DIR)
    if f.endswith(".wav") or f.endswith(".mp3")
]

files = sorted(files)

if not files:
    print("Tidak ada file audio ditemukan!")
else:
    print(f"Ditemukan {len(files)} file audio.\n")

    all_hex_combined = ""

    for audio_file in files:
        full_path = os.path.join(BASE_DIR, audio_file)

        # Jika MP3 → auto convert dulu
        if audio_file.endswith(".mp3"):
            full_path = convert_mp3_to_wav(full_path)

        hex_string = process_audio_to_hex(full_path)
        print(f"File: {audio_file}")
        print(f"Hex : {hex_string}")
        print("-" * 20)

        all_hex_combined += hex_string

    print("\n=== GABUNGAN SEMUA HEX ===")
    print(all_hex_combined)
    print("\nCopy hex ke CyberChef → From Hex → Render Image")
