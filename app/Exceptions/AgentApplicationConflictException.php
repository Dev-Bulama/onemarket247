<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by ApproveAgentApplicationAction when the applicant's email
 * collides with an existing agent — turns a raw unique-constraint crash
 * into an actionable admin-facing message, same rationale as
 * VendorApplicationConflictException.
 */
class AgentApplicationConflictException extends RuntimeException
{
    //
}
