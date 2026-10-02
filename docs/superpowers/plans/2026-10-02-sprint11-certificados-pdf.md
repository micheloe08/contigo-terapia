# Sprint 11 — Certificados PDF Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generar automáticamente un certificado PDF con firma digitalizada y QR de verificación cuando un doctor completa el 100% de un curso, con descarga autenticada y página pública de verificación.

**Architecture:** `CertificateService` centraliza la generación (DomPDF + Blade template). `LessonController@complete` lo invoca cuando el progreso llega a 100%. Los PDFs se guardan en storage privado y se sirven vía controller. Una ruta web pública `/verify/{number}` permite verificar autenticidad.

**Tech Stack:** Laravel 9, DomPDF (`barryvdh/laravel-dompdf`), Simple QR Code (`simplesoftwareio/simple-qrcode`), Blade, React, Tailwind.

**Spec:** `docs/superpowers/specs/2026-10-02-sprint11-certificados-pdf-design.md`

## Global Constraints

- PHP 8.0+, Laravel 9, Sanctum auth en todas las rutas de doctor/admin
- Un certificado por `(user_id, course_id)` — `CertificateService::issue()` es idempotente
- PDFs en `storage/app/private/certificates/` — nunca exponer rutas de filesystem directamente
- Imagen de firma en `storage/app/public/signatures/` — servida vía URL pública
- Número de certificado formato `CT-YYYY-NNNNNN` — inmutable una vez emitido
- Ruta de verificación `/verify/{number}` es ruta web, no API

## Review Focus

- **Certificado duplicado:** Si el doctor completa el curso dos veces (lecciones remarcadas), `issue()` debe retornar el existente sin crear uno nuevo ni regenerar el PDF. → Task 2, paso de test de idempotencia.
- **Descarga de certificado ajeno:** `GET /doctor/certificates/{id}/download` debe retornar 403 si el certificado no pertenece al usuario autenticado. → Task 4, test de autorización.
- **Curso sin lecciones:** `progress_percentage` sería 0/0 = 0, nunca debe emitir certificado. El guard `$percentage === 100` cubre esto, pero verificar explícitamente. → Task 3, test de edge case.
- **Imagen de firma ausente:** Si `certificate_signature_image` no está configurada, el PDF debe generarse igual (sin imagen de firma) sin romper. → Task 2, test de generación sin firma.
- **Número QR apunta a producción:** `APP_URL` en `.env` debe estar configurado correctamente en VPS; si no, el QR apunta a `localhost`. → documentar en deploy notes de Task 6.

---

### Task 1: Migración, Modelo y Seeders de Configuración

**Files:**
- Create: `database/migrations/2026_10_02_000001_create_course_certificates_table.php`
- Create: `app/Models/CourseCertificate.php`
- Create: `database/seeders/CertificateSettingsSeeder.php`

**Interfaces:**
- Produces: `CourseCertificate` model con `$fillable = ['user_id', 'course_id', 'certificate_number', 'issued_at', 'file_path']` y relaciones `user()`, `course()`; constraint unique `(user_id, course_id)`

- [ ] **Step 1: Escribir el test de migración**

```php
// tests/Feature/CourseCertificateModelTest.php
public function test_certificate_has_unique_user_course_constraint(): void
{
    $user   = User::factory()->create();
    $course = Course::factory()->create();

    CourseCertificate::create([
        'user_id'            => $user->id,
        'course_id'          => $course->id,
        'certificate_number' => 'CT-2026-000001',
        'issued_at'          => now(),
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    CourseCertificate::create([
        'user_id'            => $user->id,
        'course_id'          => $course->id,
        'certificate_number' => 'CT-2026-000002',
        'issued_at'          => now(),
    ]);
}
```

- [ ] **Step 2: Correr el test — verificar que falla**

```bash
php artisan test --filter=test_certificate_has_unique_user_course_constraint
```
Expected: FAIL (tabla no existe)

- [ ] **Step 3: Crear la migración**

Tabla `course_certificates`: columnas `id`, `user_id` (FK users, cascade delete), `course_id` (FK courses, cascade delete), `certificate_number` (string, unique), `issued_at` (timestamp), `file_path` (string, nullable), timestamps. Índice único compuesto en `(user_id, course_id)`.

- [ ] **Step 4: Crear `CourseCertificate` model**

`$fillable` con los 5 campos. `$casts = ['issued_at' => 'datetime']`. Relaciones `user()` y `course()`.

- [ ] **Step 5: Crear `CertificateSettingsSeeder`**

Inserta con `Setting::updateOrCreate(['key' => $key], [...])` las 3 claves: `certificate_signer_name` (value: `'Dr. Director Médico'`), `certificate_signer_title` (value: `'Director de Formación'`), `certificate_signature_image` (value: `''`). Todas con `group = 'certificates'`, `type = 'string'`, `is_public = false`.

- [ ] **Step 6: Correr migración y seeder en test**

```bash
php artisan migrate --filter=create_course_certificates_table
php artisan db:seed --class=CertificateSettingsSeeder
php artisan test --filter=test_certificate_has_unique_user_course_constraint
```
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_10_02_000001_create_course_certificates_table.php \
        app/Models/CourseCertificate.php \
        database/seeders/CertificateSettingsSeeder.php \
        tests/Feature/CourseCertificateModelTest.php
git commit -m "feat(sprint11): migración CourseCertificate, model y settings seeder"
```

---

### Task 2: CertificateService — Generación del PDF

**Files:**
- Create: `app/Services/CertificateService.php`
- Create: `resources/views/certificates/template.blade.php`
- Create: `config/dompdf.php` (publish vendor config si no existe)

**Interfaces:**
- Consumes: `CourseCertificate` (Task 1), `Setting::get(string $key)`, `User->name`, `Course->title`
- Produces: `CertificateService::issue(User $user, Course $course): CourseCertificate`

- [ ] **Step 1: Instalar dependencias**

```bash
composer require barryvdh/laravel-dompdf simplesoftwareio/simple-qrcode
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

- [ ] **Step 2: Escribir tests del servicio**

```php
// tests/Feature/CertificateServiceTest.php

public function test_issue_creates_certificate_and_pdf_file(): void
{
    Storage::fake('private');
    $user   = User::factory()->create(['name' => 'Ana García López']);
    $course = Course::factory()->create(['title' => 'Terapia Cognitivo Conductual']);

    $cert = app(CertificateService::class)->issue($user, $course);

    $this->assertDatabaseHas('course_certificates', [
        'user_id'   => $user->id,
        'course_id' => $course->id,
    ]);
    $this->assertStringStartsWith('CT-', $cert->certificate_number);
    Storage::disk('private')->assertExists($cert->file_path);
}

public function test_issue_is_idempotent(): void
{
    Storage::fake('private');
    $user   = User::factory()->create();
    $course = Course::factory()->create();

    $cert1 = app(CertificateService::class)->issue($user, $course);
    $cert2 = app(CertificateService::class)->issue($user, $course);

    $this->assertEquals($cert1->id, $cert2->id);
    $this->assertEquals($cert1->certificate_number, $cert2->certificate_number);
    $this->assertDatabaseCount('course_certificates', 1);
}

public function test_issue_generates_pdf_without_signature_image(): void
{
    Storage::fake('private');
    Setting::set('certificate_signature_image', '');
    $user   = User::factory()->create();
    $course = Course::factory()->create();

    $cert = app(CertificateService::class)->issue($user, $course);

    $this->assertNotNull($cert->file_path);
}
```

- [ ] **Step 3: Correr tests — verificar que fallan**

```bash
php artisan test --filter=CertificateServiceTest
```
Expected: FAIL (clase no existe)

- [ ] **Step 4: Implementar `CertificateService`**

Método `issue(User $user, Course $course): CourseCertificate`:
1. `$existing = CourseCertificate::where(['user_id' => $user->id, 'course_id' => $course->id])->first(); if ($existing) return $existing;`
2. Generar `$number = sprintf('CT-%s-%06d', date('Y'), CourseCertificate::max('id') + 1)`
3. Leer settings: `$signerName`, `$signerTitle`, `$signatureImagePath`
4. Generar QR con `QrCode::format('png')->size(120)->generate(config('app.url') . '/verify/' . $number)` — resultado como base64
5. Renderizar Blade: `$pdf = PDF::loadView('certificates.template', compact(...))`; configurar `setPaper('a4', 'landscape')`
6. `$path = "certificates/{$user->id}/cert-{$course->id}.pdf"`; `Storage::disk('private')->put($path, $pdf->output())`
7. Crear y retornar `CourseCertificate::create([..., 'issued_at' => now(), 'file_path' => $path])`

- [ ] **Step 5: Crear `resources/views/certificates/template.blade.php`**

HTML con CSS inline (DomPDF no procesa hojas externas). Layout A4 landscape. Incluir:
- Header con logo (`public_path('logo-negro.png')`) como `<img src="{{ $logoPath }}">`
- Título centrado: "CERTIFICADO DE FINALIZACIÓN"
- Cuerpo: "Se certifica que **Dr./Dra. {{ $userName }}** ha completado satisfactoriamente el curso **{{ $courseTitle }}**"
- Fecha: `{{ \Carbon\Carbon::parse($issuedAt)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}`
- Número de certificado: `{{ $certificateNumber }}`
- Firma: si `$signatureImagePath` no vacío, `<img src="{{ Storage::disk('public')->path($signatureImagePath) }}">` con altura 60px
- Nombre y cargo del firmante bajo la firma
- QR en esquina inferior derecha: `<img src="data:image/png;base64,{{ $qrBase64 }}">`

- [ ] **Step 6: Correr tests — verificar que pasan**

```bash
php artisan test --filter=CertificateServiceTest
```
Expected: 3 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Services/CertificateService.php \
        resources/views/certificates/template.blade.php \
        tests/Feature/CertificateServiceTest.php
git commit -m "feat(sprint11): CertificateService con generación PDF, QR y firma digitalizada"
```

---

### Task 3: Integrar en LessonController@complete

**Files:**
- Modify: `app/Http/Controllers/Doctor/LessonController.php`

**Interfaces:**
- Consumes: `CertificateService::issue(User, Course): CourseCertificate` (Task 2)
- Produces: respuesta de `complete` incluye `certificate` con `number` y `download_url` cuando `$percentage === 100`

- [ ] **Step 1: Escribir el test de integración**

```php
// tests/Feature/LessonCompleteWithCertificateTest.php
public function test_completing_last_lesson_generates_certificate(): void
{
    Storage::fake('private');
    $user   = User::factory()->create();
    $course = Course::factory()->has(
        CourseModule::factory()->has(Lesson::factory()->count(1), 'lessons'),
        'modules'
    )->create();
    CourseEnrollment::create(['user_id' => $user->id, 'course_id' => $course->id, 'access_type' => 'free']);
    $lesson = $course->modules->first()->lessons->first();

    $response = $this->actingAs($user)
        ->postJson("/api/doctor/lessons/{$lesson->id}/complete");

    $response->assertOk()
             ->assertJsonPath('progress_percentage', 100)
             ->assertJsonStructure(['certificate' => ['number', 'download_url']]);

    $this->assertDatabaseHas('course_certificates', ['user_id' => $user->id, 'course_id' => $course->id]);
}

public function test_completing_non_last_lesson_does_not_generate_certificate(): void
{
    Storage::fake('private');
    $user   = User::factory()->create();
    $course = Course::factory()->has(
        CourseModule::factory()->has(Lesson::factory()->count(2), 'lessons'),
        'modules'
    )->create();
    CourseEnrollment::create(['user_id' => $user->id, 'course_id' => $course->id, 'access_type' => 'free']);
    $lesson = $course->modules->first()->lessons->first();

    $response = $this->actingAs($user)
        ->postJson("/api/doctor/lessons/{$lesson->id}/complete");

    $response->assertOk()->assertJsonMissing(['certificate']);
}
```

- [ ] **Step 2: Correr tests — verificar que fallan**

```bash
php artisan test --filter=LessonCompleteWithCertificateTest
```

- [ ] **Step 3: Modificar `LessonController@complete`**

Después de calcular `$percentage`, agregar:
```php
$certificate = null;
if ($percentage === 100) {
    $cert = app(\App\Services\CertificateService::class)->issue($user, $course);
    $certificate = [
        'number'       => $cert->certificate_number,
        'download_url' => route('doctor.certificates.download', $cert->id),
    ];
}
```
Incluir `'certificate' => $certificate` en el `response()->json(...)` (null si no completó al 100%).

- [ ] **Step 4: Correr tests**

```bash
php artisan test --filter=LessonCompleteWithCertificateTest
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Doctor/LessonController.php \
        tests/Feature/LessonCompleteWithCertificateTest.php
git commit -m "feat(sprint11): emitir certificado automáticamente al completar curso al 100%"
```

---

### Task 4: Endpoints de Consulta y Descarga del Certificado

**Files:**
- Create: `app/Http/Controllers/Doctor/CertificateController.php`
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: `CourseCertificate` (Task 1), `Storage::disk('private')->get($path)`
- Produces:
  - `GET /doctor/courses/{course}/certificate` → JSON `{certificate_number, issued_at, download_url}` o 404
  - `GET /doctor/certificates/{certificate}/download` → stream PDF; 403 si no es del usuario

- [ ] **Step 1: Escribir tests**

```php
// tests/Feature/CertificateDownloadTest.php
public function test_doctor_can_get_certificate_info(): void
{
    Storage::fake('private');
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $cert = CourseCertificate::create([
        'user_id'            => $user->id,
        'course_id'          => $course->id,
        'certificate_number' => 'CT-2026-000001',
        'issued_at'          => now(),
        'file_path'          => 'certificates/1/cert-1.pdf',
    ]);
    Storage::disk('private')->put('certificates/1/cert-1.pdf', 'PDF_CONTENT');

    $response = $this->actingAs($user)
        ->getJson("/api/doctor/courses/{$course->id}/certificate");

    $response->assertOk()
             ->assertJsonPath('certificate_number', 'CT-2026-000001')
             ->assertJsonStructure(['certificate_number', 'issued_at', 'download_url']);
}

public function test_doctor_cannot_download_another_users_certificate(): void
{
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $course = Course::factory()->create();
    $cert = CourseCertificate::create([
        'user_id'            => $owner->id,
        'course_id'          => $course->id,
        'certificate_number' => 'CT-2026-000002',
        'issued_at'          => now(),
    ]);

    $this->actingAs($other)
         ->get("/api/doctor/certificates/{$cert->id}/download")
         ->assertForbidden();
}

public function test_returns_404_when_no_certificate(): void
{
    $user   = User::factory()->create();
    $course = Course::factory()->create();

    $this->actingAs($user)
         ->getJson("/api/doctor/courses/{$course->id}/certificate")
         ->assertNotFound();
}
```

- [ ] **Step 2: Correr tests — verificar que fallan**

```bash
php artisan test --filter=CertificateDownloadTest
```

- [ ] **Step 3: Crear `Doctor\CertificateController`**

Métodos:
- `show(Course $course)`: busca `CourseCertificate::where(['user_id' => auth()->id(), 'course_id' => $course->id])->first()`. Si null → 404. Si existe → JSON con `certificate_number`, `issued_at`, `download_url` (usando `route('doctor.certificates.download', $cert->id)`).
- `download(CourseCertificate $certificate)`: si `$certificate->user_id !== auth()->id()` → 403. `$content = Storage::disk('private')->get($certificate->file_path)`. Retornar `response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="certificado-' . $certificate->certificate_number . '.pdf"'])`.

- [ ] **Step 4: Agregar rutas a `routes/api.php`**

Dentro del grupo `middleware('role:doctor')->prefix('doctor')`:
```php
Route::get('courses/{course}/certificate', [DoctorCertificateController::class, 'show']);
Route::get('certificates/{certificate}/download', [DoctorCertificateController::class, 'download'])
    ->name('doctor.certificates.download');
```

- [ ] **Step 5: Correr tests**

```bash
php artisan test --filter=CertificateDownloadTest
```
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Doctor/CertificateController.php \
        routes/api.php \
        tests/Feature/CertificateDownloadTest.php
git commit -m "feat(sprint11): endpoints consulta y descarga autenticada de certificados"
```

---

### Task 5: Ruta Pública de Verificación

**Files:**
- Create: `app/Http/Controllers/Public/CertificateVerifyController.php`
- Create: `resources/views/certificates/verify.blade.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `CourseCertificate` con relaciones `user`, `course`
- Produces: ruta web `GET /verify/{certificate_number}` → vista HTML con datos o mensaje "no encontrado"

- [ ] **Step 1: Escribir el test**

```php
// tests/Feature/CertificateVerifyPublicTest.php
public function test_public_verify_shows_valid_certificate(): void
{
    $user   = User::factory()->create(['name' => 'Ana García']);
    $course = Course::factory()->create(['title' => 'Terapia TCC']);
    CourseCertificate::create([
        'user_id'            => $user->id,
        'course_id'          => $course->id,
        'certificate_number' => 'CT-2026-999999',
        'issued_at'          => now(),
    ]);

    $this->get('/verify/CT-2026-999999')
         ->assertOk()
         ->assertSee('Ana García')
         ->assertSee('Terapia TCC')
         ->assertSee('CT-2026-999999');
}

public function test_public_verify_returns_not_found_for_invalid_number(): void
{
    $this->get('/verify/CT-0000-000000')
         ->assertOk()
         ->assertSee('no encontrado');
}
```

- [ ] **Step 2: Correr — verificar que falla**

```bash
php artisan test --filter=CertificateVerifyPublicTest
```

- [ ] **Step 3: Crear el controller**

`show(string $certificateNumber)`: busca `CourseCertificate::with(['user', 'course'])->where('certificate_number', $certificateNumber)->first()`. Pasa `$certificate` (puede ser null) a la vista `certificates.verify`.

- [ ] **Step 4: Crear `resources/views/certificates/verify.blade.php`**

Página HTML simple (sin auth). Si `$certificate` existe: muestra nombre del doctor, nombre del curso, fecha de emisión formateada en español, número de certificado, badge verde "✓ Certificado Válido". Si null: mensaje "Certificado no encontrado" con el número buscado.

- [ ] **Step 5: Agregar ruta a `routes/web.php`**

```php
Route::get('/verify/{certificateNumber}', [CertificateVerifyController::class, 'show']);
```
Sin middleware de auth.

- [ ] **Step 6: Correr tests**

```bash
php artisan test --filter=CertificateVerifyPublicTest
```
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Public/CertificateVerifyController.php \
        resources/views/certificates/verify.blade.php \
        routes/web.php \
        tests/Feature/CertificateVerifyPublicTest.php
git commit -m "feat(sprint11): ruta pública /verify/{number} para validar certificados"
```

---

### Task 6: Admin — Configuración de Firma y Firmante

**Files:**
- Create: `app/Http/Controllers/Admin/CertificateSettingsController.php`
- Modify: `routes/api.php`

**Interfaces:**
- Consumes: `Setting::get()`, `Setting::set()`, `Storage::disk('public')`
- Produces:
  - `GET /admin/settings/certificates` → `{signer_name, signer_title, signature_image_url}`
  - `POST /admin/settings/certificates` → guarda nombre y cargo
  - `POST /admin/settings/certificates/signature` → sube imagen, guarda path en setting

- [ ] **Step 1: Escribir tests**

```php
// tests/Feature/Admin/CertificateSettingsTest.php
public function test_admin_can_update_signer_info(): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
         ->postJson('/api/admin/settings/certificates', [
             'signer_name'  => 'Dr. Juan Pérez',
             'signer_title' => 'Director Médico',
         ])
         ->assertOk();

    $this->assertEquals('Dr. Juan Pérez', Setting::get('certificate_signer_name'));
    $this->assertEquals('Director Médico', Setting::get('certificate_signer_title'));
}

public function test_admin_can_upload_signature_image(): void
{
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $file  = UploadedFile::fake()->image('firma.png');

    $this->actingAs($admin)
         ->postJson('/api/admin/settings/certificates/signature', [
             'signature' => $file,
         ])
         ->assertOk()
         ->assertJsonStructure(['signature_image_url']);

    Storage::disk('public')->assertExists('signatures/signature.png');
}
```

- [ ] **Step 2: Correr — verificar que fallan**

```bash
php artisan test --filter=CertificateSettingsTest
```

- [ ] **Step 3: Crear `Admin\CertificateSettingsController`**

Métodos:
- `index()`: retorna JSON con `signer_name`, `signer_title`, `signature_image_url` (usando `Storage::disk('public')->url(Setting::get('certificate_signature_image', ''))` si el path no está vacío, sino `null`).
- `update(Request $request)`: valida `signer_name` (string, max 100), `signer_title` (string, max 100). Llama `Setting::set()` para cada uno.
- `uploadSignature(Request $request)`: valida `signature` (image, mimes:png, max:2048). Guarda con `$request->file('signature')->storeAs('signatures', 'signature.png', 'public')`. Llama `Setting::set('certificate_signature_image', 'signatures/signature.png')`. Retorna `signature_image_url`.

- [ ] **Step 4: Agregar rutas a `routes/api.php`**

Dentro del grupo admin:
```php
Route::get('settings/certificates', [AdminCertificateSettingsController::class, 'index']);
Route::post('settings/certificates', [AdminCertificateSettingsController::class, 'update']);
Route::post('settings/certificates/signature', [AdminCertificateSettingsController::class, 'uploadSignature']);
```

- [ ] **Step 5: Correr tests**

```bash
php artisan test --filter=CertificateSettingsTest
```
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/CertificateSettingsController.php \
        routes/api.php \
        tests/Feature/Admin/CertificateSettingsTest.php
git commit -m "feat(sprint11): endpoints admin para configurar firmante e imagen de firma"
```

---

### Task 7: Frontend — CourseDetail y CourseList

**Files:**
- Modify: `src/pages/doctor/courses/CourseDetail.jsx`
- Modify: `src/pages/doctor/courses/CourseList.jsx`

**Interfaces:**
- Consumes: `GET /doctor/courses/{id}/certificate` (Task 4), `GET /doctor/certificates/{id}/download` (Task 4)
- Produces: botón "Descargar certificado" en CourseDetail cuando `progress_percentage === 100`; badge en CourseList

- [ ] **Step 1: Modificar `CourseDetail.jsx`**

Después del bloque de progreso (`{course.is_enrolled && ...}`), agregar:

```jsx
{course.is_enrolled && course.progress_percentage === 100 && (
  <CertificateButton courseId={id} />
)}
```

Componente `CertificateButton` (dentro del mismo archivo):
```jsx
function CertificateButton({ courseId }) {
  const [loading, setLoading] = useState(false)

  const handleDownload = async () => {
    setLoading(true)
    try {
      const res = await apiClient.get(`/doctor/courses/${courseId}/certificate`)
      window.open(res.data.download_url, '_blank')
    } catch {
      // silencioso
    } finally {
      setLoading(false)
    }
  }

  return (
    <button
      onClick={handleDownload}
      disabled={loading}
      className="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white text-sm font-medium rounded-lg transition-colors"
    >
      {loading
        ? <div className="animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent" />
        : <i className="ti ti-certificate" />}
      {loading ? 'Preparando…' : '🎓 Descargar certificado'}
    </button>
  )
}
```

- [ ] **Step 2: Modificar `CourseList.jsx`**

En el bloque del thumbnail, agregar badge de certificado después del badge de "Inscrito":
```jsx
{course.is_enrolled && course.progress_percentage === 100 && (
  <div className="absolute bottom-2 left-2 bg-emerald-500 text-white text-xs px-2 py-0.5 rounded-full flex items-center gap-1">
    <i className="ti ti-certificate text-xs" />
    Certificado
  </div>
)}
```

- [ ] **Step 3: Verificar visualmente en desarrollo**

```bash
npm run dev
```
Navegar a un curso con 100% completado y verificar que aparece el botón. Verificar que en la lista aparece el badge.

- [ ] **Step 4: Commit**

```bash
git add src/pages/doctor/courses/CourseDetail.jsx \
        src/pages/doctor/courses/CourseList.jsx
git commit -m "feat(sprint11): botón descargar certificado en CourseDetail y badge en CourseList"
```

---

### Task 8: Frontend Admin — Configuración de Certificados

**Files:**
- Create: `src/pages/admin/courses/CertificateSettings.jsx`
- Modify: `src/router.jsx`

**Interfaces:**
- Consumes: `GET/POST /admin/settings/certificates`, `POST /admin/settings/certificates/signature`

- [ ] **Step 1: Crear `CertificateSettings.jsx`**

Formulario con:
- Campo texto "Nombre del firmante" (input, maxLength 100)
- Campo texto "Cargo del firmante" (input, maxLength 100)
- Upload de imagen de firma (input type=file, accept="image/png") con preview via `URL.createObjectURL`
- Botón "Guardar configuración" (PUT los 2 campos de texto)
- Botón separado "Subir firma" (POST multipart la imagen)
- Si `signature_image_url` existe: mostrar la imagen actual como preview

Al montar: `GET /admin/settings/certificates` para cargar valores actuales.

- [ ] **Step 2: Agregar ruta en `router.jsx`**

```jsx
const AdminCertificateSettings = lazy(() => import('./pages/admin/courses/CertificateSettings'))
// en children de AdminLayout:
{ path: '/admin/certificados', element: withSuspense(<AdminCertificateSettings />) }
```

- [ ] **Step 3: Verificar en desarrollo**

Navegar a `/admin/certificados`, subir una imagen PNG y guardar nombre/cargo. Verificar que persiste al recargar.

- [ ] **Step 4: Commit**

```bash
git add src/pages/admin/courses/CertificateSettings.jsx \
        src/router.jsx
git commit -m "feat(sprint11): página admin para configurar firmante y firma digitalizada"
```

---

### Task 9: Push y Branch Cleanup

- [ ] **Step 1: Correr test suite completa**

```bash
php artisan test
```
Expected: todos los tests nuevos PASS, ningún test previo regresado.

- [ ] **Step 2: Build de producción frontend**

```bash
npm run build
```
Expected: sin errores de build.

- [ ] **Step 3: Push del branch**

```bash
git push origin feature/sprint11-certificados
```

- [ ] **Step 4: Crear PR en GitHub**

Título: `feat: Sprint 11 — Certificados PDF con firma digitalizada y QR de verificación`

---

## Deploy Notes (VPS)

Después de hacer merge a master, ejecutar en el VPS:

```bash
# Backend
cd /var/www/vhosts/api.contigo-terapia.com/httpdocs
git pull origin master
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=CertificateSettingsSeeder
php artisan storage:link   # si no está hecho
php artisan config:cache && php artisan route:cache

# Frontend
cd /var/www/vhosts/contigo-terapia.com/httpdocs
git pull origin master
/opt/plesk/node/24/bin/npm ci
/opt/plesk/node/24/bin/npm run build
```

**⚠️ Importante:** Verificar que `APP_URL` en `.env` de producción sea `https://api.contigo-terapia.com` para que el QR apunte a la URL correcta. El endpoint `/verify/{number}` es una ruta web en el backend Laravel, no en el frontend React.
