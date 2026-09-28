<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Enums\AppointmentStatus;
use App\Services\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function __construct(private CommissionService $commissionService)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * POST /api/v1/patient/payments/intent
     * Crea un PaymentIntent de Stripe para pagar una cita.
     */
    public function createIntent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        $appointment = Appointment::findOrFail($data['appointment_id']);
        $patient     = $request->user()->patient;

        // Verificar que la cita pertenece al paciente
        if ($appointment->patient_id !== $patient->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // Verificar que no haya un pago exitoso previo
        if ($appointment->payment && $appointment->payment->status->value === 'paid') {
            return response()->json(['message' => 'Esta cita ya fue pagada.'], 422);
        }

        // Crear o recuperar el payment record
        $payment = Payment::firstOrCreate(
            ['appointment_id' => $appointment->id, 'patient_id' => $patient->id],
            [
                'amount'   => $appointment->price,
                'currency' => strtolower($appointment->currency ?? 'mxn'),
                'status'   => 'pending',
                'gateway'  => 'stripe',
            ]
        );

        // Crear el PaymentIntent en Stripe
        $intent = PaymentIntent::create([
            'amount'   => (int) ($appointment->price * 100), // Stripe usa centavos
            'currency' => strtolower($appointment->currency ?? 'mxn'),
            'metadata' => [
                'appointment_id' => $appointment->id,
                'patient_id'     => $patient->id,
                'doctor_id'      => $appointment->doctor_id,
            ],
        ]);

        // Guardar el ID del intent
        $payment->update(['stripe_payment_intent_id' => $intent->id]);

        return response()->json([
            'client_secret'  => $intent->client_secret,
            'payment_id'     => $payment->id,
            'amount'         => $appointment->price,
            'currency'       => $appointment->currency ?? 'MXN',
        ]);
    }

    /**
     * GET /api/v1/patient/payments
     * Historial de pagos del paciente.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = $request->user()->patient
            ->payments()
            ->with(['appointment.doctor.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($payments);
    }

    /**
     * POST /api/v1/webhooks/stripe
     * Webhook de Stripe para actualizar el estado del pago.
     * Esta ruta es pública pero verificada con la firma de Stripe.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function handlePaymentSucceeded($intent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();
        if (!$payment) return;

        $payment->update([
            'status'           => 'paid',
            'paid_at'          => now(),
            'gateway_response' => (array) $intent,
        ]);

        // Confirmar la cita automáticamente
        if ($payment->appointment) {
            $payment->appointment->update(['status' => AppointmentStatus::Confirmed]);
        }

        // Calcular y registrar comisión
        $this->commissionService->calculateAndStore($payment->fresh(['appointment.doctor']));
    }

    private function handlePaymentFailed($intent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();
        if (!$payment) return;

        $payment->update([
            'status'           => 'failed',
            'gateway_response' => (array) $intent,
        ]);
    }
}
