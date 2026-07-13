<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a vendor order status change isn't a legal move from its
 * current status — see App\Actions\Order\UpdateVendorOrderStatusAction's
 * transition map and App\Actions\Order\CancelVendorOrderAction's
 * cancellable-from list.
 */
class InvalidOrderTransitionException extends RuntimeException
{
    //
}
