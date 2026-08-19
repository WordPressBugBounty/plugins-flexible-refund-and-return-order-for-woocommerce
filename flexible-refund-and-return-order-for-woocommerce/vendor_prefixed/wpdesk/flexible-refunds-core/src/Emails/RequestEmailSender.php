<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails;

use Throwable;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestStatus;
class RequestEmailSender
{
    public function send(WC_Order $order, RequestRecord $request, string $status): void
    {
        RequestStatus::assert_valid($status);
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();
        $email = $emails['fr_email_refund_' . $status] ?? null;
        if ($email instanceof AbstractRefundEmail) {
            $this->trigger($email, $order, $request);
        }
        if (RequestStatus::REQUESTED !== $status) {
            return;
        }
        $admin_email = $emails[EmailRefundRequestedAdmin::ID] ?? null;
        if ($admin_email instanceof AbstractRefundEmail) {
            $this->trigger($admin_email, $order, $request);
        }
    }
    private function trigger(AbstractRefundEmail $email, WC_Order $order, RequestRecord $request): void
    {
        try {
            $email->trigger($order, $request);
        } catch (Throwable $e) {
            // Email delivery must not roll back an already persisted request transition.
        }
    }
}
