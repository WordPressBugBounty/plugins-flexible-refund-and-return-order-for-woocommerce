<?php

namespace FRFreeVendor\WPDesk\Library\FlexibleRefundsCore\Domain\Exception;

use RuntimeException;
final class ActiveRequestExists extends RuntimeException
{
    public static function for_order(int $order_id): self
    {
        return new self(sprintf('Order %d already has an active request.', $order_id));
    }
}
