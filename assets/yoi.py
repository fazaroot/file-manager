from PIL import Image
import math

def render_dump_to_image():
    # 1. BACA FILE YANG UDAH LU BIKIN
    filename = "FULL_HEX_DUMP.txt"
    try:
        with open(filename, "r") as f:
            hex_data = f.read().strip()
    except FileNotFoundError:
        print("ERROR: File FULL_HEX_DUMP.txt gak ketemu! Jalanin script extracting tadi dulu.")
        return

    print(f"[*] Membaca data dari {filename}...")
    print(f"[*] Panjang Data: {len(hex_data)} karakter Hex")

    # 2. KONVERSI HEX KE BINER (010101...)
    # Ini mengubah kode "2D B9..." lu jadi urutan bit (titik pixel)
    binary_string = ""
    for char in hex_data:
        try:
            # Hex char -> 4 digit binary
            val = int(char, 16)
            binary_string += f"{val:04b}"
        except ValueError:
            continue # Skip kalau ada karakter aneh

    total_pixels = len(binary_string)
    print(f"[*] Total Titik Pixel: {total_pixels}")

    # 3. TENTUKAN UKURAN CANVAS (OTOMATIS)
    # Kita cari akar kuadrat buat bikin kotak persegi yang pas
    # Misal ada 400 pixel, berarti ukurannya 20x20.
    dimension = int(math.sqrt(total_pixels)) + 1
    
    print(f"[*] Membuat Canvas ukuran: {dimension}x{dimension}")

    img = Image.new('1', (dimension, dimension), 1) # 1 = Putih
    pixels = img.load()

    # 4. GAMBAR PIXEL DEMI PIXEL
    idx = 0
    for y in range(dimension):
        for x in range(dimension):
            if idx < total_pixels:
                bit = binary_string[idx]
                # Logika: 1 = Hitam, 0 = Putih
                color = 0 if bit == '1' else 1
                pixels[x, y] = color
                idx += 1

    # 5. SAVE & RESIZE
    # Kita perbesar 20x biar lu gampang liatnya
    final_img = img.resize((dimension * 20, dimension * 20), Image.NEAREST)
    output_name = "HASIL_VISUAL_DARI_AUDIO_LU.png"
    final_img.save(output_name)

    print("\n" + "="*40)
    print(f"[SUCCESS] Gambar jadi: {output_name}")
    print("="*40)
    print("CATATAN PENTING:")
    print("Gambar ini adalah representasi JUJUR dari audio yang lu ekstrak.")
    print("Jika gambarnya terlihat seperti 'TV Rusak' atau 'Semut',")
    print("itu berarti ekstraksi audionya masih mengandung noise/gangguan.")
    print("TAPI, inilah wujud visual dari string Hex yang lu dapetin.")

if __name__ == "__main__":
    render_dump_to_image()
