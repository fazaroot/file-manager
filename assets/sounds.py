import requests
import io
import librosa
import numpy as np

# Daftar URL MP3, menggunakan urutan logis (Alfabetis/Numerik)
mp3_urls = [
    "https://cache.pusatkode.com/10379p.MP3", 
    "https://cache.pusatkode.com/5h0jyu.MP3",
    "https://cache.pusatkode.com/f1952q.MP3",
    "https://cache.pusatkode.com/hyg2dc.MP3",
    "https://cache.pusatkode.com/ydyf2y.MP3",
    "https://cache.pusatkode.com/zp1l09.MP3"
]

# Kamus Kode Morse (Hanya untuk huruf)
MORSE_CODE = {
    '.-': 'A', '-...': 'B', '-.-.': 'C', '-..': 'D', '.': 'E', '..-.': 'F', '--.': 'G', 
    '....': 'H', '..': 'I', '.---': 'J', '-.-': 'K', '.-..': 'L', '--': 'M', '-.': 'N', 
    '---': 'O', '.--.': 'P', '--.-': 'Q', '.-.': 'R', '...': 'S', '-': 'T', '..-': 'U', 
    '...-': 'V', '.--': 'W', '-..-': 'X', '-.--': 'Y', '--..': 'Z'
}

def decode_morse(morse_string):
    """Mendekode string Kode Morse menjadi teks."""
    return MORSE_CODE.get(morse_string, '?')

def analyze_audio_morse(url):
    """Mengunduh audio dan menganalisis pola nada Morse."""
    try:
        response = requests.get(url, timeout=15)
        response.raise_for_status() 
        
        # Muat audio dari memori menggunakan librosa
        audio_file = io.BytesIO(response.content)
        audio_data, sr = librosa.load(audio_file, sr=None)
        
        # Normalisasi dan deteksi keberadaan suara (sangat sederhana)
        rms = librosa.feature.rms(y=audio_data)[0]
        # Threshold: 1.5 kali dari rata-rata RMS (untuk membedakan noise dari sinyal)
        rms_threshold = np.mean(rms) * 1.5 
        
        # Sinyal aktif
        active_segments = rms > rms_threshold
        
        morse_signal = ""
        is_playing = False
        start_frame = 0
        
        # Cari durasi sinyal (untuk membedakan Titik dan Garis)
        for i, active in enumerate(active_segments):
            if active and not is_playing:
                # Sinyal mulai
                is_playing = True
                start_frame = i
            elif (not active or i == len(active_segments) - 1) and is_playing:
                # Sinyal berakhir atau mencapai akhir file, hitung durasi
                end_frame = i
                duration_frames = end_frame - start_frame
                
                # Hitung durasi dalam detik
                # librosa.load mengembalikan 2048 frame per rata-rata RMS.
                duration_sec = duration_frames * (librosa.get_duration(y=audio_data, sr=sr) / len(rms))
                
                # Ambang batas Titik dan Garis: Titik < 0.3 detik
                if duration_sec > 0.3: 
                    morse_signal += "-" # Garis
                elif duration_sec > 0.05:
                    morse_signal += "." # Titik
                
                is_playing = False
        
        return morse_signal
        
    except requests.exceptions.RequestException as e:
        print(f"[!] Gagal mengunduh {url.split('/')[-1]}: {e}")
        return ""
    except Exception as e:
        print(f"[!] Error saat memproses {url.split('/')[-1]}: {e}")
        return ""

# --- ALIRAN UTAMA PROGRAM ---
decoded_word = ""

print("--- Memulai Analisis Sinyal Audio Morse ---")
print("Menggunakan Urutan File: 10379p, 5h0jyu, f1952q, hyg2dc, ydyf2y, zp1l09")
print("-" * 40)


for url in mp3_urls:
    file_name = url.split('/')[-1]
    morse_result = analyze_audio_morse(url)
    
    # Dekode morse yang terdeteksi
    decoded_char = decode_morse(morse_result)
    
    print(f"[{file_name}] Sinyal Terdeteksi: {morse_result}  |  Huruf: {decoded_char}")
    
    decoded_word += decoded_char

print("-" * 40)
print(f"Hasil Dekode (Gabungan): {decoded_word}")

if decoded_word == "DALWWE":
    print("\n[VERIFIKASI MANUAL SUKSES]: Hasil kode Morse cocok dengan DALWWE.")
    print("Mencoba Anagram / Kata Kunci Terkait NASA.")
    print("\n[FLAG YANG SANGAT MUNGKIN (Anagram dari DALWWE)]:")
    print("1. WALLDE")
    print("2. ELADW")
    
else:
    print("\n[PERINGATAN]: Hasil kode tidak sesuai dengan DALWWE. Mungkin ambang batas durasi (0.3 detik) perlu disesuaikan.")

print("\n--- Selesai ---")

