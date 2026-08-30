<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Notifications\ContactMessageSubmittedNotification;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Serves the same static content shown on the storefront's About/Partnership/
 * Privacy/Terms/FAQ pages (resources/views/storefront/pages/*.blade.php) —
 * both read from config('static_pages') so mobile and web can never drift
 * out of sync. See that config file's own docblock.
 */
class PageController extends Controller
{
    public function aboutUs(): JsonResponse
    {
        return ApiResponse::success($this->resolvePage('about-us'));
    }

    public function partnership(): JsonResponse
    {
        return ApiResponse::success($this->resolvePage('partnership'));
    }

    public function privacy(): JsonResponse
    {
        return ApiResponse::success($this->resolvePage('privacy'));
    }

    public function terms(): JsonResponse
    {
        return ApiResponse::success($this->resolvePage('terms'));
    }

    public function faq(): JsonResponse
    {
        return ApiResponse::success(config('static_pages.faq'));
    }

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Mirrors Storefront\PageController::submitContact — a mail
        // transport failure must never turn a submitted contact form into
        // a 500 for the visitor.
        try {
            Notification::route('mail', config('mail.from.address'))
                ->notify(new ContactMessageSubmittedNotification(
                    $data['name'],
                    $data['email'],
                    $data['subject'],
                    $data['message'],
                ));
        } catch (Throwable $exception) {
            report($exception);
        }

        return ApiResponse::success(message: "Thanks for reaching out — we'll get back to you soon.");
    }

    /**
     * @return array{title: string, sections: array<int, array{heading: ?string, body: string}>}
     */
    private function resolvePage(string $key): array
    {
        $page = config("static_pages.{$key}");
        $appName = config('app.name');

        return [
            'title' => str_replace(':app_name', $appName, $page['title']),
            'sections' => array_map(fn (array $section) => [
                'heading' => $section['heading'] !== null ? str_replace(':app_name', $appName, $section['heading']) : null,
                'body' => str_replace(':app_name', $appName, $section['body']),
            ], $page['sections']),
        ];
    }
}
