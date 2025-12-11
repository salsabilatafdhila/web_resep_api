<?php

// 1. KONFIGURASI API KEY (Mengambil dari Variabel Lingkungan)
// Menggunakan Dotenv atau implementasi sederhana untuk memuat .env
if (file_exists(__DIR__ . '/.env')) {
    // Implementasi sederhana untuk memuat .env
    $lines = file(__DIR__ . '/.env'); 
    
    foreach ($lines as $line) {
        $line = trim($line); // Bersihkan spasi di awal/akhir baris

        // LINGKAP PEMBERSIHAN BARIS:
        // 1. Abaikan baris kosong
        // 2. Abaikan komentar (baris dimulai dengan #)
        if (empty($line) || strpos($line, '#') === 0) {
            continue; 
        }

        // Pastikan baris mengandung '=' sebelum di-explode
        if (strpos($line, '=') === false) {
             continue;
        }

        list($name, $value) = explode('=', $line, 2);
        
        $name = trim($name);
        // Bersihkan spasi dan tanda kutip ganda/tunggal dari value
        $value = trim($value, " \t\n\r\0\x0B\"'"); 
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            // Pasang variabel lingkungan
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Ambil API Key dari Variabel Lingkungan
$apiKey = getenv('GEMINI_API_KEY');

if (!$apiKey) {
    http_response_code(500);
    die("Error Konfigurasi: GEMINI_API_KEY tidak ditemukan. Pastikan file .env sudah diatur.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['image_data'])) {
    http_response_code(400);
    die("Permintaan tidak valid.");
}

$base64Image = $_POST['image_data'];

// 2. PROMPT UNTUK GEMINI
// Kita menggunakan satu prompt multimodal yang meminta tiga hal sekaligus.
$prompt = "Analisis gambar bahan makanan ini. Berikan jawaban Anda dalam format berikut, menggunakan Markdown untuk keterbacaan:

### 🥦 Bahan Makanan yang Teridentifikasi
[Daftar bahan makanan yang ada di gambar]

---

### 🍳 Resep Otomatis
Buatkan satu resep sederhana dan cepat yang menggunakan bahan-bahan di atas. Sertakan:
1. Nama Resep
2. Bahan (Bahan dari gambar + bahan tambahan umum)
3. Langkah-Langkah

---

### ❄️ Saran Penyimpanan
Berikan saran penyimpanan terbaik (suhu, wadah) untuk bahan makanan yang Anda identifikasi untuk memaksimalkan kesegaran.
";

// 3. STRUKTUR PAYLOAD API (Permintaan Multimodal) - BAGIAN YANG HILANG DAN KINI DIKEMBALIKAN
$payload = [
    'contents' => [
        [
            'parts' => [
                // Bagian 1: Data Gambar
                [
                    'inlineData' => [
                        'mimeType' => 'image/jpeg', // Asumsi kita akan mengunggah JPEG
                        'data' => $base64Image 
                    ]
                ],
                // Bagian 2: Teks Prompt
                [
                    'text' => $prompt
                ]
            ]
        ]
    ]
];

// Encoding JSON di sini, sebelum dipanggil oleh cURL
$jsonPayload = json_encode($payload);

// 4. PANGGIL API MENGGUNAKAN cURL
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload); // Variabel $jsonPayload kini terdefinisi
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonPayload) // Variabel $jsonPayload kini terdefinisi
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 5. PROSES RESPON
if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    
    // Cek apakah ada respons yang valid
    $responseText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? "Error: Tidak dapat mengekstrak teks dari respons Gemini.";
    
    // Outputkan respons mentah
    echo $responseText;
    
} else {
    // Tangani error API
    http_response_code(500);
    echo "## ❌ Error saat Memanggil API\n";
    echo "Kode HTTP: " . $httpCode . "\n";
    echo "Detail Error: " . $response;
}
?>