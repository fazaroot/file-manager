# Hex string hasil gabungan kamu
hex_data = "2F2F1F2122222222202121292323282722292621211C1C212821212923221D1D"

# Ubah ke binary
binary_string = ""
for i in range(0, len(hex_data), 2):
    # Ambil per 2 digit hex (1 byte)
    byte = int(hex_data[i:i+2], 16)
    # Ubah ke 8 digit biner
    binary_string += f"{byte:08b}"

print(f"Total Bit: {len(binary_string)}")
print("Mencoba menampilkan gambar (Hitam = 1, Putih = 0)...\n")

# Kita coba lebar 16 (karena total 256 bit, akar kuadratnya 16)
width = 16 

print(f"=== GAMBAR (Lebar {width} px) ===")
for i in range(0, len(binary_string), width):
    row = binary_string[i:i+width]
    # Ganti 1 dengan kotak, 0 dengan spasi agar terlihat
    print(row.replace('1', '█').replace('0', ' '))
print("==============================")
