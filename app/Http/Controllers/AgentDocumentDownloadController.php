<?php

namespace App\Http\Controllers;

use App\Models\AgentDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Agent documents live on the private "local" disk, never a publicly
 * reachable URL — same rationale as VendorDocumentDownloadController.
 * Access is admin-only (agents have no account of their own to authenticate
 * as, unlike a vendor downloading their own document).
 */
class AgentDocumentDownloadController extends Controller
{
    public function __invoke(AgentDocument $agentDocument): StreamedResponse
    {
        if (! auth()->user()?->can('agents.manage')) {
            throw new AccessDeniedHttpException;
        }

        return Storage::disk('local')->download($agentDocument->file_path);
    }
}
