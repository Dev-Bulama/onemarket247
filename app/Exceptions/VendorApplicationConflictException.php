<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by ApproveVendorApplicationAction when the applicant's email or
 * phone number now collides with an existing user account — the
 * users.email/users.phone unique constraints would otherwise turn this
 * into a raw, uncaught SQLSTATE[23000] 500 error when an admin clicks
 * "Approve" (see the users_phone_unique constraint).
 */
class VendorApplicationConflictException extends RuntimeException
{
    //
}
