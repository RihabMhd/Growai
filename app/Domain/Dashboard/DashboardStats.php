<?php 
namespace App\Domain\Dashboard;

final class DashboardStats
{
    public function __construct(
        public readonly int    $total,
        public readonly int    $confirmed,
        public readonly int    $pending,
        public readonly int    $cancelled,
        public readonly int    $delivered,
        public readonly float  $revenue,
        public readonly float  $revenueGrowth,
        public readonly int    $confirmationRate,
        public readonly int    $deliveryRate,
        public readonly ?float $avgConfirmationTime,
    ) {}

    public function toArray(): array
    {
        return [
            'total'                 => $this->total,
            'confirmed'             => $this->confirmed,
            'pending'               => $this->pending,
            'cancelled'             => $this->cancelled,
            'delivered'             => $this->delivered,
            'revenue'               => $this->revenue,
            'revenue_growth'        => $this->revenueGrowth,
            'confirmation_rate'     => $this->confirmationRate,
            'delivery_rate'         => $this->deliveryRate,
            'avg_confirmation_time' => $this->avgConfirmationTime,
        ];
    }
}