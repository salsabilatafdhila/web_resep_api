<?php
use PHPUnit\Framework\TestCase;


class FileTypeTest extends TestCase
{
    private $projectFiles = [
        // Asumsi file proyek Anda yang diuji
        'index.html', // Mengubah ke index.html berdasarkan kode JS Anda sebelumnya
        'process.php'
    ];

    public function test_files_exist()
    {
        foreach ($this->projectFiles as $file) {
            $this->assertFileExists($file, "File $file tidak ditemukan!");
        }
    }

    public function test_php_files_contain_php_code()
    {
        foreach ($this->projectFiles as $file) {
            // Kita hanya ingin menguji file PHP, bukan index.html
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($file);
                $this->assertStringContainsString('<?php', $content, "File $file tidak mengandung kode PHP!");
            }
        }
    }

    public function test_html_files_contain_html_tags()
    {
        foreach ($this->projectFiles as $file) {
            // Uji file HTML (index.html) dan file PHP yang mungkin berisi HTML
            if (pathinfo($file, PATHINFO_EXTENSION) === 'html' || pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($file);

                $this->assertMatchesRegularExpression(
                    '/<html|<head|<body|<div|<p|<span/i',
                    $content,
                    "File $file bukan HTML yang valid!"
                );
            }
        }
    }
    

    /**
     * @test
     * Memastikan koneksi ke Gemini API berhasil (HTTP 200) dan responsnya valid.
     */
    public function test_gemini_api_connection_and_response()
    {
        // Mendapatkan API Key dari Environment Variable (seperti yang diatur di ci.yml atau .env)
        $apiKey = getenv('GEMINI_API_KEY');
        $this->assertNotEmpty($apiKey, "GEMINI_API_KEY tidak ditemukan di environment.");

        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

        // Data dummy (teks sederhana) untuk diuji
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Beri aku satu kata sifat tentang kecerdasan buatan.']
                    ]
                ]
            ]
        ];

        $jsonPayload = json_encode($payload);

        // Panggil API menggunakan cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Test Case 1: Response Code harus 200
        $this->assertSame(200, $httpCode, 
            "Gagal koneksi ke Gemini API. Kode HTTP: $httpCode. Detail: $response"
        );

        $responseData = json_decode($response, true);

        // Test Case 2: Valid JSON Response
        $this->assertIsArray($responseData, 
            "Respons API bukan JSON yang valid. JSON Error: " . json_last_error_msg()
        );
        
        // Test Case 3: Respons Mengandung Teks
        $responseText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
        $this->assertNotEmpty($responseText, 
            "Respons Gemini tidak mengandung teks balasan yang diharapkan."
        );
    }
}