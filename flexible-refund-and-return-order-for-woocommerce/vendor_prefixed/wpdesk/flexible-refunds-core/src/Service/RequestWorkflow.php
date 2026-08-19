<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Service;

use Exception;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestStatus;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Emails\RequestEmailSender;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Helpers\Statuses;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\OrderNote;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Integration\RegisterOrderStatus;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
final class RequestWorkflow
{
    private RequestRepository $requests;
    private RequestService $service;
    private RequestEmailSender $emails;
    private OrderNote $order_note;
    private MonetaryRefundProcessor $monetary_refunds;
    private MonetaryRefundTracker $refund_tracker;
    public function __construct(RequestRepository $requests, RequestService $service, RequestEmailSender $emails, OrderNote $order_note, ?MonetaryRefundProcessor $monetary_refunds = null, ?MonetaryRefundTracker $refund_tracker = null)
    {
        $this->requests = $requests;
        $this->service = $service;
        $this->emails = $emails;
        $this->order_note = $order_note;
        $this->monetary_refunds = $monetary_refunds ?? new MonetaryRefundProcessor();
        $this->refund_tracker = $refund_tracker ?? new MonetaryRefundTracker();
    }
    /**
     * @throws Exception When a monetary refund cannot be processed.
     */
    public function change_status(WC_Order $order, RequestRecord $request, string $status, string $note = '', array $items = []): RequestRecord
    {
        RequestStatus::assert_valid($status);
        if ($order->get_id() !== $request->get_order_id() || null === $request->get_id()) {
            throw new Exception('The request does not belong to this order.');
        }
        if (!$request->is_active()) {
            throw new Exception('A completed request cannot change status.');
        }
        if (RequestType::REFUND === $request->get_request_type()) {
            $is_processed = $this->refund_tracker->has_processed($order, $request->get_id());
            if (RequestStatus::APPROVED === $request->get_status() && !$is_processed) {
                $this->refund_tracker->mark_processed($order, $request->get_id());
                $is_processed = \true;
            }
            if (RequestStatus::APPROVED === $status && !$is_processed) {
                $this->monetary_refunds->process($order, $request, $items, function () use ($order, $request): void {
                    $this->refund_tracker->mark_processed($order, $request->get_id());
                });
            }
        }
        $updated = $this->service->change_status($request->get_id(), $status, trim($note));
        $this->update_order($order, $updated);
        $this->add_order_notes($order, $updated);
        $this->emails->send($order, $updated, $status);
        return $updated;
    }
    private function update_order(WC_Order $order, RequestRecord $request): void
    {
        $active = $this->requests->find_active_by_order($order->get_id());
        if (null !== $active) {
            $order->set_status(RegisterOrderStatus::REQUEST_REFUND_STATUS);
        } elseif (RequestStatus::APPROVED === $request->get_status() && RequestType::REFUND === $request->get_request_type() && (float) $order->get_total_refunded() >= (float) $order->get_total()) {
            $order->set_status('wc-refunded');
        } elseif ('' !== $request->get_previous_order_status()) {
            $order->set_status($request->get_previous_order_status());
        }
        $order->save();
    }
    private function add_order_notes(WC_Order $order, RequestRecord $request): void
    {
        if ('' !== $request->get_note()) {
            $this->order_note->add_refund_note($order, $request->get_note());
        }
        $this->order_note->add_refund_note($order, sprintf(
            /* translators: 1: request ID, 2: request type, 3: request status. */
            __('Request #%1$d (%2$s) status: %3$s', 'flexible-refund-and-return-order-for-woocommerce'),
            $request->get_id(),
            RequestType::get_label($request->get_request_type()),
            Statuses::get_status_label($request->get_status())
        ));
    }
}
