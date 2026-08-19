<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Repository;

use FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Request\RequestRecord;
interface RequestRepository
{
    public function find(int $id): ?RequestRecord;
    public function find_active_by_order(int $order_id): ?RequestRecord;
    public function find_latest_by_order(int $order_id): ?RequestRecord;
    public function find_by_legacy_order(int $order_id): ?RequestRecord;
    /** @return RequestRecord[] */
    public function find_by_order(int $order_id): array;
    public function add(RequestRecord $request): RequestRecord;
    public function save(RequestRecord $request): RequestRecord;
}
