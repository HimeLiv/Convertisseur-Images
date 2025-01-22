<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="./images/th.jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://unpkg.com/pdf-lib@1.16.0"></script>
    <title>Convertisseur de format d'images</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            background-color: #f4f4f4;
            background-image: url('./images/dark_night_sky_with_moon_stars_optimized.png');
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 3%;
            border-radius: 0.5rem;
            text-align: center;
            box-shadow: 0 0.4rem 0.8rem rgba(0, 0, 0, 0.2);
            margin-bottom: 15%;
        }
        h1 {
            margin-bottom: 2%;
            font-size: 2.4%;
        }
        input, select {
            width: 100%;
            padding: 1%;
            margin: 1% 0;
            border-radius: 0.5rem;
            border: 0.1rem solid #ccc;
        }
        .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 1% 2%;
            border-radius: 0.5rem;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        #result {
            margin-top: 2%;
        }
        .header-container {
            position: relative;
            text-align: center;
            margin-bottom: 2%;
        }
        .header-container h1 {
            margin: 0;
            font-size: 185%;
            text-align: center;
            width: 100%;
        }
        .btn-secondary {
            position: absolute;
            left: 0;
            top: 0;
            background-color: #6c757d;
            color: white;
            padding: 1% 2%;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        @media (max-width: 1200px) {
            .form-container {
                width: 60%;
            }
            h1 {
                font-size: 2.2%;
            }
        }
        @media (max-width: 768px) {
            .form-container {
                width: 80%;
                padding: 5%;
            }
            h1 {
                font-size: 160%;
            }
            .btn, .btn-secondary {
                padding: 2% 4%;
            }
        }
        @media (max-width: 480px) {
            .form-container {
                width: 95%;
                padding: 8%;
            }
            h1 {
                font-size: 140%;
            }
            input, select {
                font-size: 90%;
            }
            .btn, .btn-secondary {
                padding: 3% 5%;
            }
            .btn-secondary {
                font-size: 80%;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="form-container">
            <div class="header-container">
                <a href="../"><button class="btn-secondary">Revenir au menu principal</button></a>
                <h1>Convertisseur d'images</h1>
            </div>
            <form id="imageForm" action="/convert" method="POST" enctype="multipart/form-data">

                <label for="fileInput">Choisissez une image :</label>
                <input type="file" id="fileInput" name="fileInput" accept="image/*, application/pdf" required>
                
                <label for="format">Convertir au format :</label>
                <select id="format" name="format">
                    <option value="jpg">JPG (Anciennement JPEG)</option>
                    <option value="png">PNG</option>
                    <option value="webp">WEBP</option>
                    <option value="pdf">PDF</option>
                </select>
                
                <button type="submit" class="btn">Convertir</button>
            </form>
            <div id="result"></div>
        </div>
    </div>

        <script>
        document.getElementById('imageForm').addEventListener('submit', async function (e) { 
            e.preventDefault();

            const fileInput = document.getElementById('fileInput').files[0];
            const format = document.getElementById('format').value;

            if (!fileInput) {
                alert('Veuillez sélectionner un fichier.');
                return;
            }

            const reader = new FileReader();
            reader.onload = async function (event) {
                const fileType = fileInput.type;

                const formatTypes = { // Types des formats
                    jpg: 'image/jpeg',
                    png: 'image/png',
                    webp: 'image/webp',
                    pdf: 'application/pdf'
                };

                if (fileType === formatTypes[format]) { // Vérification de format
                    confirm('Le fichier sélectionné est déjà dans le format demandé.');
                } else if (fileType === 'application/pdf' && (format === 'jpg' || format === 'png' || format === 'webp')) {
                    // PDF vers Image
                    await convertPdfToImage(event.target.result, format);
                } else if ((fileType.startsWith('image/') && fileType !== 'application/pdf') && format === 'pdf') {
                    // Image (inclut WEBP) vers PDF
                    await convertImageToPdf(event.target.result);
                } else if (fileType === 'image/webp' && format === 'pdf') {
                    // WEBP vers PDF
                    await convertImageToPdf(event.target.result);
                } else if (fileType.startsWith('image/')) {
                    // Autres conversions Image vers Image
                    await convertImage(event.target.result, format);
                } else {
                    alert("Le type de fichier sélectionné n'est pas supporté.");
                }
            };

            reader.readAsDataURL(fileInput); 
        });

        async function convertPdfToImage(pdfDataUrl, format) {
            const pdfjsLib = window['pdfjs-dist/build/pdf'];

            // Charger le PDF
            const pdf = await pdfjsLib.getDocument({ data: atob(pdfDataUrl.split(',')[1]) }).promise;

            // Récupérer la première page
            const page = await pdf.getPage(1);
            const viewport = page.getViewport({ scale: 2 }); // Ajuster le scale pour une meilleure résolution

            // Créer un canvas pour le rendu
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            const renderContext = {
                canvasContext: context,
                viewport: viewport,
            };

            // Rendre la page dans le canvas
            await page.render(renderContext).promise;

            // Convertir le canvas en image Blob
            const mimeType = `image/${format}`;
            const blob = await new Promise(resolve => canvas.toBlob(resolve, mimeType));

            // Créer un bouton de téléchargement
            createDownloadButton(blob, `converted-image.${format}`);
        }

        async function convertImageToPdf(imageDataUrl) {
            const pdfLib = window.PDFLib;  // Directement accessible depuis window
            const { PDFDocument } = pdfLib;  // Déstructuration de PDFDocument à partir de pdf-lib

            // Créer un nouveau PDF
            const pdfDoc = await PDFDocument.create();

            // Charger l'image à partir du Data URL
            const imgBytes = await fetch(imageDataUrl).then(res => res.arrayBuffer());

            // Vérifier si l'image est au format WEBP
            let pdfImg;
            if (imageDataUrl.startsWith('data:image/webp')) {
                // Convertir WEBP en PNG via un canvas
                const convertedPngDataUrl = await convertWebpToPng(imageDataUrl);
                const pngBytes = await fetch(convertedPngDataUrl).then(res => res.arrayBuffer());
                pdfImg = await pdfDoc.embedPng(pngBytes); // Embed l'image PNG convertie
            } else {
                pdfImg = await pdfDoc.embedPng(imgBytes); // Embed l'image directement si ce n'est pas WEBP
            }

            const { width, height } = pdfImg.scale(1); // Ajuster les dimensions de l'image

            // Ajouter une page avec les dimensions de l'image
            const page = pdfDoc.addPage([width, height]);
            page.drawImage(pdfImg, { x: 0, y: 0, width, height }); // Dessiner l'image sur la page

            // Sauvegarder le PDF
            const pdfBytes = await pdfDoc.save();

            // Créer un Blob pour le téléchargement
            const pdfBlob = new Blob([pdfBytes], { type: 'application/pdf' });
            
            // Créer un bouton pour télécharger le fichier PDF
            createDownloadButton(pdfBlob, 'converted-image.pdf');
        }

        // Fonction pour convertir une image WEBP en PNG
        async function convertWebpToPng(webpDataUrl) {
            return new Promise((resolve) => {
                const img = new Image();
                img.src = webpDataUrl;

                img.onload = function () {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);

                    // Convertir en PNG
                    const pngDataUrl = canvas.toDataURL('image/png');
                    resolve(pngDataUrl);
                };
            });
        }
        // Fonction pour convertir une image WEBP en PNG


        async function convertImage(imageDataUrl, format) {
            const img = new Image();
            img.src = imageDataUrl;

            await new Promise(resolve => img.onload = resolve); // Attendre le chargement de l'image

            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            const mimeType = `image/${format}`;
            const blob = await new Promise(resolve => canvas.toBlob(resolve, mimeType));
            createDownloadButton(blob, `converted-image.${format}`);
        }

        function createDownloadButton(blob, filename) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '';

            const downloadButton = document.createElement('button');
            downloadButton.className = 'btn';
            downloadButton.innerText = 'Télécharger le fichier converti';
            downloadButton.style.marginTop = '2%';
            downloadButton.style.marginLeft = '-6%';
            downloadButton.onclick = function () {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                link.click();
            };

            resultDiv.appendChild(downloadButton);

            const resetButton = document.createElement('button');
            resetButton.className = 'btn';
            resetButton.style.marginTop = '2%';
            resetButton.style.marginLeft = '5%';
            resetButton.innerText = 'Nouvelle conversion';
            resetButton.onclick = function () {
                document.getElementById('imageForm').reset();
                resultDiv.innerHTML = '';
            };

            resultDiv.appendChild(resetButton);
        }
    </script>

</body>
</html>
