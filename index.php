<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Makanan & Resep Gemini AI</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: auto; }
        #loading { display: none; color: blue; }
        #preview { max-width: 100%; max-height: 200px; margin-top: 10px; display: block; }
        #result { white-space: pre-wrap; margin-top: 20px; padding: 15px; border: 1px solid #ccc; background-color: #f9f9f9; }
        input[type="file"], button { padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>🍲 Analisis Makanan & Resep Otomatis</h1>
    <p>Unggah gambar sayuran atau bahan makanan Anda, dan Gemini akan menghasilkan resep dan saran penyimpanan!</p>

    <input type="file" id="imageInput" accept="image/*">
    <img id="preview" src="#" alt="Pratinjau Gambar" style="display:none;">
    <button onclick="submitImage()">Generate Resep</button>

    <div id="loading">Memproses, harap tunggu...</div>
    
    <h2>Hasil Analisis dan Resep</h2>
    <div id="result">Hasil akan muncul di sini...</div>

    <script>
        document.getElementById('imageInput').addEventListener('change', function(event) {
            const preview = document.getElementById('preview');
            const file = event.target.files[0];
            
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });

        function submitImage() {
            const imageInput = document.getElementById('imageInput');
            const resultDiv = document.getElementById('result');
            const loadingDiv = document.getElementById('loading');
            const file = imageInput.files[0];

            if (!file) {
                alert("Mohon unggah gambar bahan makanan terlebih dahulu.");
                return;
            }

            // Tampilkan loading dan reset hasil
            loadingDiv.style.display = 'block';
            resultDiv.innerHTML = 'Memproses...';

            const reader = new FileReader();
            reader.onloadend = function() {
                // Konversi gambar ke Base64 untuk dikirim ke PHP
                const base64Image = reader.result.split(',')[1];
                
                const formData = new FormData();
                formData.append('image_data', base64Image);

                // Kirim data ke process.php
                fetch('process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    resultDiv.innerHTML = data;
                })
                .catch(error => {
                    loadingDiv.style.display = 'none';
                    resultDiv.innerHTML = 'Terjadi kesalahan jaringan: ' + error;
                    console.error('Error:', error);
                });
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>