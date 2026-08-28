<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Vendor;
use App\Support\Mail\EmailTemplateKeys;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once, right after ApproveVendorApplicationAction provisions the
 * vendor's User/Vendor/Store. Unlike the stock ResetPassword mail (which
 * says nothing about being approved or having a store), this tells the
 * vendor what happened and gives them the same password-set link so they
 * can log in to app.onemarket247.com/vendor for the first time.
 */
class VendorApplicationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Vendor $vendor,
        private readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('vendor.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $template = EmailTemplate::active(EmailTemplateKeys::VendorApplicationApproved);

        if ($template) {
            $rendered = $template->render([
                'vendor_name' => $this->vendor->user->name,
                'store_name' => $this->vendor->store->name,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body'])
                ->action('Set your password & log in', $url)
                ->line('This link expires in 60 minutes for your security — if it expires, use "Forgot password" on the vendor login page to request a new one.');
        }

        return (new MailMessage)
            ->subject('Your OneMarket247 vendor application has been approved!')
            ->greeting('Congratulations, '.$this->vendor->user->name.'!')
            ->line('Your application to sell as "'.$this->vendor->store->name.'" on OneMarket247 has been approved.')
            ->line('Your store is live. Set a password for your vendor account to start adding products and managing orders.')
            ->action('Set your password & log in', $url)
            ->line('This link expires in 60 minutes for your security — if it expires, use "Forgot password" on the vendor login page to request a new one.');
    }
}
