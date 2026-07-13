<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Notifications\ContactMessageSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PageController extends Controller
{
    public function contact(): View
    {
        return view('storefront.pages.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Notification::route('mail', config('mail.from.address'))
            ->notify(new ContactMessageSubmittedNotification(
                $data['name'],
                $data['email'],
                $data['subject'],
                $data['message'],
            ));

        return back()->with('status', "Thanks for reaching out — we'll get back to you soon.");
    }

    public function faq(): View
    {
        return view('storefront.pages.faq');
    }

    public function terms(): View
    {
        return view('storefront.pages.terms');
    }

    public function privacy(): View
    {
        return view('storefront.pages.privacy');
    }
}
