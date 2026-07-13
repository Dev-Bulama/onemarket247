<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by CompleteCheckoutAction when a fresh re-validation at the
 * moment of checkout finds the cart no longer matches what the customer
 * last saw (price drift, insufficient stock) — never silently substituted,
 * per docs/architecture/09-lifecycles.md "Checkout → Order Creation".
 */
class CheckoutValidationException extends RuntimeException
{
    //
}
