from PIL import Image

# Hex string hasil gabungan kamu
hex_data = "2F2F1F2122222222202121292323282722292621211C1C212821212923221D1D"

# 1. Ubah Hex ke List Bit (0 dan 1)
# Total 256 bit
bits = [int(b) for b in bin(int(hex_data, 16))[2:].zfill(256)]

# Ukuran Grid (256 bit = 16x16 pixel)
size = 16

# 2. Buat Gambar Baru
img = Image.new('1', (size, size))
pixels = []

# 3. TRANSPOSE DATA (Kunci Perbaikan!)
# Kita baca data seolah-olah itu Kolom, lalu kita susun jadi Baris untuk gambar
# Loop Baris dulu, baru Kolom
for y in range(size):
    for x in range(size):
        # Rumus ini mengambil bit secara vertikal (kolom)
        # index = (x * 16) + y
        src_idx = (x * size) + y
        
        # Ambil bitnya
        bit = bits[src_idx]
        
        # 4. Invert Warna (0 jadi Putih, 1 jadi Hitam) sesuai standar QR
        # Jika bit 1 -> Hitam (0), Jika bit 0 -> Putih (1)
        color = 0 if bit == 1 else 1
        pixels.append(color)

img.putdata(pixels)

# 5. Perbesar Gambar (Upscale) agar mudah discan
img = img.resize((320, 320), Image.NEAREST)

# Simpan
img.save("flag_fixed.png")
print("Gambar berhasil diperbaiki! Silakan download 'flag_fixed.png'")
