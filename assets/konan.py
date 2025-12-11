import codecs
import os

# --- DATA HEX DARI 6 FILE AUDIO (Sudah Dikonfirmasi) ---
FILE_DATA = {
    "10379p.MP3": {"hex": "2F2F1F21", "dim": (8, 4)},  # 32 bit
    "5h0jyu.MP3": {"hex": "222222222021", "dim": (8, 6)}, # 48 bit
    "f1952q.MP3": {"hex": "21292323282722", "dim": (8, 7)}, # 56 bit
    "hyg2dc.MP3": {"hex": "29262121", "dim": (8, 4)},  # 32 bit
    "ydyf2y.MP3": {"hex": "101C212821", "dim": (8, 5)}, # 40 bit
    "zp1l09.MP3": {"hex": "212923221D1D", "dim": (8, 6)}  # 48 bit
}

WIDTH = 8 
HEIGHT = 32 # Total tinggi (4+6+7+4+5+6)
MAX_COLOR = 255 

def hex_to_binary_string(hex_str):
    """Konversi Hex ke Biner"""
    if len(hex_str) % 2 != 0: hex_str = '0' + hex_str
    binary_data = codecs.decode(hex_str, 'hex')
    binary_string = ''.join(format(byte, '08b') for byte in binary_data)
    return binary_string

def generate_combined_ppm(order_list, filename):
    """Menggabungkan 6 strip biner sesuai urutan dan membuat file PPM 8x32."""
    final_binary_string = ""
    total_height_check = 0
    
    # 1. Gabungkan Biner Sesuai Urutan
    for filename_key in order_list:
        if filename_key in FILE_DATA:
            data = FILE_DATA[filename_key]
            binary_strip = hex_to_binary_string(data["hex"])
            final_binary_string += binary_strip
            total_height_check += data["dim"][1]
        else:
            print(f"[ERROR] File '{filename_key}' tidak ditemukan.")
            return

    if total_height_check != HEIGHT:
        print(f"[ERROR KRITIS] Total tinggi strip tidak sama dengan {HEIGHT}.")
        return

    # 2. Tulis ke file PPM
    try:
        with open(filename, 'w') as f:
            f.write(f"P3\n{WIDTH} {HEIGHT}\n{MAX_COLOR}\n")
            
            for i in range(HEIGHT):
                row_start = i * WIDTH
                row_end = (i + 1) * WIDTH
                row_bin = final_binary_string[row_start:row_end]
                
                for bit in row_bin:
                    if bit == '1': r, g, b = 0, 0, 0 
                    else: r, g, b = 255, 255, 255
                    f.write(f"{r} {g} {b} ")
                f.write("\n")
                
        print(f"[SUKSES] Gambar final '{filename}' (8x32) telah dibuat.")
        print("Silakan konversi dan Pindai!")
    except Exception as e:
        print(f"[!] Gagal membuat file PPM: {e}")

# --- PENGGUNAAN ---

# Setelah Anda menyusun 6 strip secara visual dan mendapatkan urutan file yang benar:
# 1. Ubah variabel di bawah ini (GANTI URUTANNYA SAJA)
# 2. Jalankan skrip

# URUTAN PEMBALIKAN (Percobaan 1: Urutan yang benar adalah kebalikan dari urutan Hex)
# Urutan Hex awal: 10379p, 5h0jyu, f1952q, hyg2dc, ydyf2y, zp1l09
# Urutan Dibalik (Reverse): zp1l09, ydyf2y, hyg2dc, f1952q, 5h0jyu, 10379p

TEST_ORDER = [
    "zp1l09.MP3", 
    "ydyf2y.MP3", 
    "hyg2dc.MP3", 
    "f1952q.MP3", 
    "5h0jyu.MP3", 
    "10379p.MP3"
]

generate_combined_ppm(TEST_ORDER, "qr_final_combined_TEST.ppm")
