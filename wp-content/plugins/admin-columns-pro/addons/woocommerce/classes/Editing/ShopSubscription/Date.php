<?php

declare(strict_types=1);

namespace ACA\WC\Editing\ShopSubscription;

use ACP;
use ACP\Editing\View;
use Exception;
use RuntimeException;

class Date implements ACP\Editing\Service
{

    private $date_key;

    private $meta_key;

    public function __construct(string $date_key, string $meta_key)
    {
        $this->date_key = $date_key;
        $this->meta_key = $meta_key;
    }

    public function get_view(string $context): ?View
    {
        return new ACP\Editing\View\DateTime();
    }

    public function get_value(int $id)
    {
        return get_post_meta($id, $this->meta_key, true);
    }

    public function update(int $id, $data): void
    {
        $subscription = wcs_get_subscription($id);

        try {
            $subscription->update_dates([
                $this->date_key => $data,
            ], get_option('timezone_string'));

            $subscription->save();
        } catch (Exception $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }
}
