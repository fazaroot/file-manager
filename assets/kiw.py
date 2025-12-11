import librosa
import numpy as np
import os
from pydub import AudioSegment

# Force pydub menggunakan ffmpeg
AudioSegment.converter = "ffmpeg"
AudioSegment.ffmpeg = "ffmpeg"
AudioSegment.ffprobe = "ffprobe"

# ---------------------------
# Opsional: Hz → Note
# ---------------------------
def hz_to_note(hz):
    return librosa.hz_to_note(hz)

# ---------------------------
# Convert MP3 → WAV (Fix Case Sensitivity)
# ---------------------------
def convert_mp3_to_wav(src):
    src = os.path.abspath(src)

    # Buat nama output WAV
    base = os.path.splitext(src)[0]
    dst = base + ".wav"

    if not os.path.exists(dst):
        print(f"[Convert] {os.path.basename(src)} → {os.path.basename(dst)}")
        try:
            audio = AudioSegment.from_file(src, format="mp3")
            audio.export(dst, format="wav")
        except Exception as e:
            print(f"[ERROR] Gagal convert MP3 → WAV: {e}")
            return None

    return dst

# ---------------------------
# Proses Audio → HEX
# ---------------------------
def process_audio_to_hex(filepath):
    print(f"[Load] {filepath}")

    try:
        y, sr = librosa.load(filepath, sr=None)
    except Exception as e:
        print(f"[ERROR] Gagal load audio: {e}")
        return ""

    # Deteksi onset
    onset_frames = librosa.onset.onset_detect(y=y, sr=sr, backtrack=True)

    # F0 detection (pyin)
    f0, voiced_flag, voiced_probs = librosa.pyin(
        y,
        fmin=librosa.note_to_hz("C1"),
        fmax=librosa.note_to_hz("C8"),
    )

    hex_results = []

    for frame_idx in onset_frames:
        if frame_idx < len(f0):
            freq = f0[frame_idx]
            if not np.isnan(freq):
                midi_note = librosa.hz_to_midi(freq)
                midi_int = int(round(midi_note))
                hex_val = "{:02X}".format(midi_int)
                hex_results.append(hex_val)

    return "".join(hex_results)

# ---------------------------
# MAIN PROGRAM (Fix Path)
# ---------------------------

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# List semua file audio (case insensitive)
files = [
    f for f in os.listdir(BASE_DIR)
    if f.lower().endswith(".mp3") or f.lower().endswith(".wav")
]

files = sorted(files)

if not files:
    print("❌ Tidak ada file audio ditemukan dalam folder ini!")
    print(f"Folder: {BASE_DIR}")
else:
    print(f"✓ Ditemukan {len(files)} file audio.\n")

    all_hex_combined = ""

    for audio_file in files:
        full_path = os.path.join(BASE_DIR, audio_file)

        # Convert MP3 jika perlu
        if audio_file.lower().endswith(".mp3"):
            wav_path = convert_mp3_to_wav(full_path)
            if wav_path is None:
                continue
            full_path = wav_path

        # Proses WAV final
        hex_string = process_audio_to_hex(full_path)

        print(f"\nFile : {audio_file}")
        print(f"HEX  : {hex_string}")
        print("-" * 30)

        all_hex_combined += hex_string

    print("\n====== SEMUA HEX DIGABUNG ======")
    print(all_hex_combined)
    print("================================")
