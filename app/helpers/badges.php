<?php
function orderBadge($status) {
    return match($status) {
        'pending'    => 'bg-warning text-dark',
        'confirmed'  => 'bg-primary',
        'processing' => 'bg-info text-dark',
        'shipped'    => 'bg-dark',
        'delivered'  => 'bg-success',
        'cancelled'  => 'bg-danger',
        'refunded'   => 'bg-secondary',
        default      => 'bg-secondary'
    };
}

// Alias — same as orderBadge, used in detail views
function orderStatusBadge($status) {
    return orderBadge($status);
}

function paymentBadge($status) {
    return match($status) {
        'paid'      => 'bg-success',
        'pending'   => 'bg-warning text-dark',
        'failed'    => 'bg-danger',
        'refunded'  => 'bg-secondary',
        'cancelled' => 'bg-danger',
        default     => 'bg-secondary'
    };
}

function shippingBadge($status) {
    return match($status) {
        'preparing'        => 'bg-warning text-dark',
        'shipped'          => 'bg-primary',
        'out_for_delivery' => 'bg-info text-dark',
        'delivered'        => 'bg-success',
        'returned'         => 'bg-danger',
        'cancelled'        => 'bg-danger',
        default            => 'bg-secondary'
    };
}
?>