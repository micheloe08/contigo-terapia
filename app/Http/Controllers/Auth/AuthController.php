<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterDoctorRequest;
use App\Http\Requests\Auth\RegisterPatientRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registerPatient(RegisterPatientRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'phone'    => $request->phone,
                'role' => 'patient',
            ]);

            Patient::create([
                'user_id' => $user->id,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Registro exitoso.',
                'user'    => $user->load('patient'),
                'token'   => $token,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el paciente.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function registerDoctor(RegisterDoctorRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => $request->password,
                'phone'    => $request->phone,
                'role'     => 'doctor',
            ]);

            $licenseDocument = null;
            if ($request->hasFile('license_document')) {
                $licenseDocument = $request->file('license_document')
                    ->store('licenses', 'public');
            }

            Doctor::create([
                'user_id'            => $user->id,
                'specialty_id'       => $request->specialty_id,
                'license_number'     => $request->license_number,
                'license_document'   => $licenseDocument,
                'bio'                => $request->bio,
                'education'          => $request->education,
                'experience_years'   => $request->experience_years ?? 0,
                'consultation_price' => $request->consultation_price,
                'consultation_types' => $request->consultation_types,
                'city'               => $request->city,
                'state'              => $request->state,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Registro exitoso. Tu cédula está en revisión.',
                'user'    => $user->load('doctor.specialty'),
                'token'   => $token,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el doctor.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load($user->isDoctor() ? 'doctor.specialty' : 'patient');

        return response()->json([
            'message' => 'Bienvenido, ' . $user->name,
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load($user->isDoctor() ? 'doctor.specialty' : 'patient');

        return response()->json($user);
    }
}
