<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado de Finalización</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #fff;
            color: #1a1a2e;
            width: 297mm;
            height: 210mm;
            padding: 20mm 25mm;
            position: relative;
        }
        .border-frame {
            position: absolute;
            top: 8mm;
            left: 8mm;
            right: 8mm;
            bottom: 8mm;
            border: 3px solid #1a3a5c;
        }
        .border-inner {
            position: absolute;
            top: 11mm;
            left: 11mm;
            right: 11mm;
            bottom: 11mm;
            border: 1px solid #c9a94a;
        }
        .content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .logo {
            max-height: 50px;
            margin-bottom: 8mm;
        }
        .title {
            font-size: 26pt;
            font-weight: bold;
            color: #1a3a5c;
            letter-spacing: 4px;
            margin-bottom: 6mm;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 11pt;
            color: #666;
            margin-bottom: 8mm;
            letter-spacing: 1px;
        }
        .body-text {
            font-size: 13pt;
            color: #333;
            line-height: 1.8;
            margin-bottom: 4mm;
        }
        .doctor-name {
            font-size: 20pt;
            font-weight: bold;
            color: #1a3a5c;
        }
        .course-name {
            font-size: 16pt;
            font-weight: bold;
            color: #c9a94a;
        }
        .date-text {
            font-size: 11pt;
            color: #555;
            margin-top: 4mm;
            margin-bottom: 8mm;
        }
        .cert-number {
            font-size: 9pt;
            color: #999;
            letter-spacing: 2px;
            margin-bottom: 8mm;
        }
        .signature-section {
            display: inline-block;
            text-align: center;
            margin-top: 4mm;
        }
        .signature-img {
            height: 60px;
            max-width: 200px;
            display: block;
            margin: 0 auto 4px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #1a3a5c;
            margin: 4px auto;
        }
        .signer-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1a3a5c;
        }
        .signer-title {
            font-size: 9pt;
            color: #666;
        }
        .qr-section {
            position: absolute;
            bottom: 15mm;
            right: 20mm;
            text-align: center;
        }
        .qr-label {
            font-size: 7pt;
            color: #999;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="border-frame"></div>
    <div class="border-inner"></div>

    <div class="content">
        @if(file_exists($logoPath))
            <img src="{{ $logoPath }}" class="logo" alt="Contigo Terapia">
        @endif

        <div class="title">Certificado de Finalización</div>
        <div class="subtitle">Contigo Terapia — Formación Continua</div>

        <div class="body-text">Se certifica que</div>
        <div class="doctor-name">{{ $userName }}</div>
        <div class="body-text" style="margin-top: 4mm;">
            ha completado satisfactoriamente el curso
        </div>
        <div class="course-name">&ldquo;{{ $courseTitle }}&rdquo;</div>

        <div class="date-text">
            Culiacán, Sinaloa, a {{ \Carbon\Carbon::parse($issuedAt)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </div>

        <div class="cert-number">Número de certificado: {{ $certificateNumber }}</div>

        <div class="signature-section">
            @if($signatureImagePath && file_exists($signatureImagePath))
                <img src="{{ $signatureImagePath }}" class="signature-img" alt="Firma">
            @else
                <div style="height:64px;"></div>
            @endif
            <div class="signature-line"></div>
            <div class="signer-name">{{ $signerName }}</div>
            <div class="signer-title">{{ $signerTitle }}</div>
        </div>
    </div>

    <div class="qr-section">
        <img src="data:image/png;base64,{{ $qrBase64 }}" width="90" height="90" alt="QR Verificación">
        <div class="qr-label">Escanear para verificar</div>
    </div>
</body>
</html>
