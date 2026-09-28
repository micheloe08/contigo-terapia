<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Calcula y registra la comisión de la plataforma cuando se paga una cita.
     */
    public function calculateAndStore(Payment $payment): void
    {
        $appointment = $payment->appointment;
        if (!$appointment) return;

        $doctor  = $appointment->doctor;
        $amount  = (float) $payment->amount;
        $gateway = $payment->gateway;

        // 1. Comisión del gateway (Stripe o MercadoPago)
        $gatewayCommission = $this->calculateGatewayFee($amount, $gateway);

        // 2. Comisión de la plataforma
        $platformPercentage = $this->getPlatformPercentage($doctor);
        $platformCommission = round($amount * ($platformPercentage / 100), 2);

        // 3. Si el doctor tiene membresía activa, aplicar descuento en comisión
        if ($doctor->membership_status === 'active') {
            $discount = (float) Setting::get('membership_commission_discount', 5.00);
            $platformCommission = round($amount * (max(0, $platformPercentage - $discount) / 100), 2);
        }

        // 4. Monto neto para el terapeuta
        $doctorNet = round($amount - $platformCommission - $gatewayCommission, 2);

        // 5. Registrar la comisión
        DB::table('platform_commissions')->insert([
            'appointment_id'       => $appointment->id,
            'doctor_id'            => $doctor->id,
            'payment_id'           => $payment->id,
            'appointment_amount'   => $amount,
            'platform_percentage'  => $platformPercentage,
            'platform_commission'  => $platformCommission,
            'gateway'              => $gateway,
            'gateway_fee_percentage' => $this->getGatewayFeePercentage($gateway),
            'gateway_fee_fixed'    => $this->getGatewayFeeFixed($gateway),
            'gateway_commission'   => $gatewayCommission,
            'doctor_net_amount'    => $doctorNet,
            'currency'             => $payment->currency,
            'status'               => 'pending',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    private function getPlatformPercentage($doctor): float
    {
        if ($doctor->commission_override) {
            return (float) $doctor->commission_percentage;
        }
        return (float) Setting::get('default_commission_percentage', 15.00);
    }

    private function calculateGatewayFee(float $amount, string $gateway): float
    {
        $percentage = $this->getGatewayFeePercentage($gateway);
        $fixed      = $this->getGatewayFeeFixed($gateway);
        return round(($amount * $percentage / 100) + $fixed, 2);
    }

    private function getGatewayFeePercentage(string $gateway): float
    {
        return match($gateway) {
            'stripe'      => (float) Setting::get('stripe_fee_percentage', 3.60),
            'mercadopago' => (float) Setting::get('mercadopago_fee_percentage', 3.49),
            default       => 0,
        };
    }

    private function getGatewayFeeFixed(string $gateway): float
    {
        return match($gateway) {
            'stripe'      => (float) Setting::get('stripe_fee_fixed', 3.00),
            'mercadopago' => (float) Setting::get('mercadopago_fee_fixed', 0.00),
            default       => 0,
        };
    }
}
