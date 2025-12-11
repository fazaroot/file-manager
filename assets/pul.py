from PIL import Image

# Hex string hasil dari audio kamu
hex_data = "2F2F1F2122222222202121292323282722292621211C1C212821212923221D1D"

# 1. Konversi Hex ke Biner (0 dan 1)
# zfill(256) memastikan totalnya pas 256 bit
binary_string = bin(int(hex_data, 16))[2:].zfill(256)

# 2. Setup Ukuran Gambar
# Karena 256 bit, akar kuadratnya adalah 16. Jadi gambar 16x16.
width = 16
height = 16

# 3. Buat Gambar Baru (Mode '1' artinya 1-bit pixels, hitam putih)
img = Image.new('1', (width, height))

# 4. Masukkan data pixel
# Kita ubah string '1' jadi putih (1) dan '0' jadi hitam (0), atau sebaliknya sesuai standar QR
pixels = [int(b) for b in binary_string]
img.putdata(pixels)

# 5. Perbesar Gambar (Upscale)
# Gambar 16px itu sekecil kutu, kita perbesar 20x lipat jadi 320x320 biar bisa discan
img = img.resize((width * 20, height * 20), Image.NEAREST)

# 6. Simpan File
filename = "flag_qr.png"
img.save(filename)
print(f"Sukses! Gambar disimpan sebagai: {filename}")
