# Sprint 11 — Certificados PDF con Firma y QR de Verificación

## Objetivo

Cuando un doctor completa el 100% de un curso, el sistema genera automáticamente un certificado PDF con su nombre, el nombre del curso, la fecha de emisión, la firma digitalizada del firmante configurado y un código QR de verificación pública. El doctor puede descargarlo desde el detalle del curso. Cualquier persona puede verificar la autenticidad del certificado escaneando el QR.

---

## Casos de Uso

1. **Doctor completa última lección** → el sistema detecta progreso = 100% y genera el certificado en background.
2. **Doctor descarga su certificado** → autenticado, recibe el PDF por streaming.
3. **Tercero escanea QR del certificado** → ve una página pública que confirma: nombre del doctor, nombre del curso, fecha de emisión.
4. **Admin configura firmante** → desde el backoffice sube la imagen de firma (PNG fondo transparente), nombre del firmante y cargo.

---

## Modelo de Datos

### Tabla `course_certificates`

| columna               | tipo             | notas                                     |
|-----------------------|------------------|-------------------------------------------|
| `id`                  | bigint PK        |                                           |
| `user_id`             | FK users         |                                           |
| `course_id`           | FK courses       |                                           |
| `certificate_number`  | varchar unique   | formato `CT-YYYY-NNNNNN` (6 dígitos, zfill) |
| `issued_at`           | timestamp        |                                           |
| `file_path`           | varchar nullable | ruta en disco en `private` storage        |
| timestamps            |                  |                                           |

Índice único: `(user_id, course_id)` — un certificado por alumno por curso.

---

## Configuración (tabla `settings`)

Claves nuevas bajo `group = 'certificates'`:

| key                          | type   | label                     | is_public |
|------------------------------|--------|---------------------------|-----------|
| `certificate_signer_name`    | string | Nombre del firmante       | false     |
| `certificate_signer_title`   | string | Cargo del firmante        | false     |
| `certificate_signature_image`| string | Ruta de imagen de firma   | false     |

La imagen se almacena en `storage/app/public/signatures/signature.png` y se referencia por ruta (no base64 en settings).

---

## CertificateService

```php
CertificateService::issue(User $user, Course $course): CourseCertificate
```

- Si ya existe un certificado para `(user_id, course_id)`, devuelve el existente.
- Genera el número de certificado con `sprintf('CT-%s-%06d', date('Y'), $nextId)`.
- Renderiza la vista Blade `certificates.template` con DomPDF.
- Guarda el PDF en `storage/app/private/certificates/{user_id}/cert-{course_id}.pdf`.
- Crea y retorna el registro `CourseCertificate`.

---

## Plantilla PDF (Blade)

Diseño en A4 landscape, con:
- Logo de Contigo Terapia (desde `public/logo-negro.png` o configurable via setting `certificate_logo`)
- Título: "CERTIFICADO DE FINALIZACIÓN"
- Cuerpo: "Se certifica que **Dr./Dra. {nombre}** ha completado satisfactoriamente el curso **{nombre_curso}**"
- Fecha en español: "Culiacán, Sinaloa, a {día} de {mes} de {año}"
- Número de certificado: `CT-2026-000042`
- Sección de firma: imagen PNG + línea + nombre + cargo
- QR code en esquina inferior derecha apuntando a `{APP_URL}/verify/{certificate_number}`

DomPDF se configura con `'default_paper_size' => 'a4'` y orientación landscape.
QR generado con `simplesoftwareio/simple-qrcode` como SVG inline o PNG base64.

---

## Endpoints

### Doctor (autenticado)

```
GET /api/doctor/courses/{course}/certificate
```
Response 200:
```json
{
  "certificate_number": "CT-2026-000001",
  "issued_at": "2026-10-02T07:00:00Z",
  "download_url": "/api/doctor/certificates/1/download"
}
```
Response 404: `{ "message": "Aún no tienes certificado para este curso." }`

```
GET /api/doctor/certificates/{certificate}/download
```
Streams el PDF con `Content-Type: application/pdf` y `Content-Disposition: inline`.
Guard: el certificado debe pertenecer al usuario autenticado (403 si no).

### Público (sin auth)

```
GET /verify/{certificate_number}
```
Ruta web (no API). Devuelve una página HTML simple con los datos del certificado o "Certificado no encontrado".

### Admin (backoffice)

```
GET  /api/admin/settings/certificates       → { signer_name, signer_title, signature_image_url }
POST /api/admin/settings/certificates       → guarda los 3 settings
POST /api/admin/settings/certificates/signature  → sube imagen (multipart), guarda en public storage
```

---

## Integración con LessonController@complete

Cuando `$percentage === 100`:
1. Llamar `CertificateService::issue($user, $course)`.
2. Incluir en la respuesta: `certificate_number` y `certificate_download_url`.

---

## Frontend

### `CourseDetail.jsx`
- Si `progress_percentage === 100` y el usuario está inscrito: mostrar botón "🎓 Descargar certificado" que hace `GET /doctor/courses/{id}/certificate` y abre la URL de descarga en nueva pestaña.
- Mientras carga el certificado: spinner en el botón.

### `CourseList.jsx`
- Si `progress_percentage === 100` y `is_enrolled`: mostrar badge "✓ Certificado disponible" sobre la miniatura.

### Admin — Configuración de Certificados
- Nueva sección en `/admin/configuracion` (o nueva ruta `/admin/certificados`) con formulario: nombre del firmante, cargo, upload de imagen.
- Preview de la imagen cargada.

---

## Dependencias a instalar

```bash
composer require barryvdh/laravel-dompdf simplesoftwareio/simple-qrcode
```

---

## Restricciones Globales

- PHP 8.0+, Laravel 9, Sanctum auth
- Storage privado para los PDFs: nunca exponer rutas directas de filesystem
- Un solo certificado por par `(user_id, course_id)`; idempotente
- Número de certificado inmutable una vez emitido
- La ruta de verificación pública `/verify/{number}` es una ruta web, no API
- La imagen de firma se sirve desde `public` storage; los PDFs desde `private` storage
