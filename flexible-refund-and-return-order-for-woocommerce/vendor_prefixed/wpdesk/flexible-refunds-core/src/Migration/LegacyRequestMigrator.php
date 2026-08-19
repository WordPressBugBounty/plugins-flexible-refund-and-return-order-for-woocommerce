<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Migration;

use Throwable;
use WC_Order;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Exception\EntityNotFound;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Form\RequestType;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestStatus;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\FormRepository;
use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository\RequestRepository;
final class LegacyRequestMigrator
{
    public const HOOK = 'flexible_refunds_core_migrate_legacy_requests';
    private const VERSION = '1';
    private const VERSION_OPTION = 'fr_refunds_legacy_migration_version';
    private const STATE_OPTION = 'fr_refunds_legacy_migration_state';
    private const ACTION_GROUP = 'flexible-refund-and-return-order-for-woocommerce';
    private const BATCH_SIZE = 25;
    private const REQUEST_DATA_KEY = 'fr_refund_request_data';
    private FormRepository $forms;
    private RequestRepository $requests;
    public function __construct(FormRepository $forms, RequestRepository $requests)
    {
        $this->forms = $forms;
        $this->requests = $requests;
    }
    public function hooks(): void
    {
        add_action(self::HOOK, [$this, 'run_batch']);
    }
    public function maybe_schedule(): bool
    {
        if ($this->is_complete() || $this->is_scheduled()) {
            return \true;
        }
        $form = $this->forms->find_by_type(RequestType::REFUND);
        if (null === $form || null === $form->get_id()) {
            return \false;
        }
        return $this->schedule_next_batch();
    }
    public function is_complete(): bool
    {
        return self::VERSION === (string) get_option(self::VERSION_OPTION, '');
    }
    public function run_batch(): void
    {
        if (self::VERSION === (string) get_option(self::VERSION_OPTION, '')) {
            return;
        }
        $state = get_option(self::STATE_OPTION, ['page' => 1, 'failures' => [], 'retry' => \false]);
        $stored_failures = isset($state['failures']) && is_array($state['failures']) ? $state['failures'] : [];
        $is_retry = !empty($state['retry']) && [] !== $stored_failures;
        $has_pending_retry_orders = \false;
        if ($is_retry) {
            $page = 1;
            $retry_order_ids = array_map('intval', array_keys($stored_failures));
            $order_ids = array_slice($retry_order_ids, 0, self::BATCH_SIZE);
            $failures = array_diff_key($stored_failures, array_fill_keys($order_ids, \true));
            $max_pages = 1;
            $has_pending_retry_orders = count($retry_order_ids) > count($order_ids);
        } else {
            $page = max(1, (int) ($state['page'] ?? 1));
            $query = wc_get_orders(['limit' => self::BATCH_SIZE, 'page' => $page, 'paginate' => \true, 'return' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'meta_query' => [
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                ['key' => self::REQUEST_DATA_KEY, 'compare' => 'EXISTS'],
            ]]);
            $order_ids = isset($query->orders) && is_array($query->orders) ? $query->orders : [];
            $failures = $stored_failures;
            $max_pages = isset($query->max_num_pages) ? (int) $query->max_num_pages : 0;
        }
        foreach ($order_ids as $order_id) {
            try {
                $order = wc_get_order($order_id);
                if ($order instanceof WC_Order) {
                    $this->migrate_order($order);
                }
            } catch (Throwable $e) {
                $failures[(int) $order_id] = $e->getMessage();
                do_action('flexible_refunds_core_legacy_migration_error', $e, (int) $order_id);
            }
        }
        if ($page < $max_pages) {
            update_option(self::STATE_OPTION, ['page' => $page + 1, 'failures' => $failures], \false);
            $this->schedule_next_batch();
            return;
        }
        if ([] !== $failures) {
            update_option(self::STATE_OPTION, ['page' => 1, 'failures' => $failures, 'retry' => \true], \false);
            if ($has_pending_retry_orders) {
                $this->schedule_next_batch();
            }
            // A repeatedly failing batch remains resumable through maybe_schedule() without creating a tight retry loop.
            return;
        }
        update_option(self::VERSION_OPTION, self::VERSION, \false);
        delete_option(self::STATE_OPTION);
    }
    public function migrate_order(WC_Order $order): ?RequestRecord
    {
        $order_id = $order->get_id();
        $existing = $this->requests->find_by_legacy_order($order_id);
        if (null !== $existing) {
            return $existing;
        }
        $form = $this->forms->find_by_type(RequestType::REFUND);
        if (null === $form || null === $form->get_id()) {
            throw new EntityNotFound('The Refund system form must exist before request migration.');
        }
        $submitted_values = $order->get_meta(self::REQUEST_DATA_KEY);
        if (!is_array($submitted_values) || [] === $submitted_values) {
            return null;
        }
        $timestamp = (int) $order->get_meta('fr_refund_request_date');
        $created_at = $timestamp > 0 ? gmdate('Y-m-d H:i:s', $timestamp) : gmdate('Y-m-d H:i:s');
        $status = RequestStatus::normalize_legacy((string) $order->get_meta('fr_refund_request_status'));
        return $this->requests->add(new RequestRecord(null, $order_id, $form->get_id(), $form->get_version(), RequestType::REFUND, $status, (string) $order->get_meta('fr_refund_previous_order_status'), $form->get_snapshot(), $submitted_values, (string) $order->get_meta('fr_refund_request_note'), $order_id, $created_at, $created_at));
    }
    private function is_scheduled(): bool
    {
        if (function_exists('as_has_scheduled_action')) {
            return (bool) as_has_scheduled_action(self::HOOK, [], self::ACTION_GROUP);
        }
        return \false !== wp_next_scheduled(self::HOOK);
    }
    private function schedule_next_batch(): bool
    {
        if (function_exists('as_enqueue_async_action')) {
            return (bool) as_enqueue_async_action(self::HOOK, [], self::ACTION_GROUP);
        }
        if (\false === wp_next_scheduled(self::HOOK)) {
            return (bool) wp_schedule_single_event(time(), self::HOOK);
        }
        return \true;
    }
}
