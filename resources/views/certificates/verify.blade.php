<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Certificado — Contigo Terapia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            max-width: 520px;
            width: 100%;
            padding: 40px 36px;
            text-align: center;
        }
        .brand {
            font-size: 14px;
            color: #888;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .valid-badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            border-radius: 999px;
            padding: 8px 20px;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 28px;
        }
        .invalid-badge {
            display: inline-block;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 999px;
            padding: 8px 20px;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 28px;
        }
        .field-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .field-value {
            font-size: 17px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 20px;
        }
        .cert-number {
            font-family: monospace;
            font-size: 13px;
            color: #555;
            background: #f5f5f5;
            border-radius: 6px;
            padding: 8px 14px;
            display: inline-block;
            margin-top: 8px;
        }
        .not-found-text {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">Contigo Terapia</div>

        @if($certificate)
            <div class="valid-badge">✓ Certificado Válido</div>

            <div class="field-label">Doctor(a)</div>
            <div class="field-value">{{ $certificate->user->name }}</div>

            <div class="field-label">Curso completado</div>
            <div class="field-value">{{ $certificate->course->title }}</div>

            <div class="field-label">Fecha de emisión</div>
            <div class="field-value">
                {{ \Carbon\Carbon::parse($certificate->issued_at)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
            </div>

            <hr>
            <div class="field-label">Número de certificado</div>
            <div class="cert-number">{{ $certificate->certificate_number }}</div>
        @else
            <div class="invalid-badge">✗ Certificado no encontrado</div>
            <p class="not-found-text">
                No encontramos ningún certificado con el número:
            </p>
            <div class="cert-number">{{ $certificateNumber }}</div>
            <hr>
            <p class="not-found-text" style="font-size:13px; color:#aaa;">
                Si crees que esto es un error, contacta a soporte@contigoterapia.mx
            </p>
        @endif
    </div>
</body>
</html>
