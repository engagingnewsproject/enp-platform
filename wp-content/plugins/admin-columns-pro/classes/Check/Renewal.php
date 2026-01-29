<?php

declare(strict_types=1);

namespace ACP\Check;

use AC\Admin\Page\Columns;
use AC\Admin\Page\Settings;
use AC\Ajax;
use AC\Capabilities;
use AC\Message;
use AC\Registerable;
use AC\Screen;
use AC\Storage;
use AC\Type\Url\UtmTags;
use ACP\Access\ActivationStorage;
use ACP\ActivationTokenFactory;
use ACP\AdminColumnsPro;
use ACP\Type;
use ACP\Type\Activation\ExpiryDate;
use ACP\Type\Url\AccountFactory;
use DateTime;

class Renewal
    implements Registerable
{

    private AdminColumnsPro $plugin;

    private ActivationTokenFactory $activation_token_factory;

    private ActivationStorage $activation_storage;

    private array $intervals = [1, 7, 21];

    private AccountFactory $account_url_factory;

    public function __construct(
        AdminColumnsPro $plugin,
        ActivationTokenFactory $activation_token_factory,
        ActivationStorage $activation_storage,
        AccountFactory $url_factory
    ) {
        $this->plugin = $plugin;
        $this->activation_token_factory = $activation_token_factory;
        $this->activation_storage = $activation_storage;
        $this->account_url_factory = $url_factory;
    }

    public function register(): void
    {
        add_action('ac/screen', [$this, 'display']);

        $this->get_ajax_handler()->register();
    }

    public function ajax_dismiss_notice(): void
    {
        $this->get_ajax_handler()->verify_request();

        $interval = (int)filter_input(INPUT_POST, 'interval', FILTER_SANITIZE_NUMBER_INT);

        if ( ! array_key_exists($interval, $this->intervals)) {
            exit;
        }

        // 90 days
        $this->get_dismiss_option($interval)->save(time() + (MONTH_IN_SECONDS * 3));

        exit;
    }

    protected function get_ajax_handler(): Ajax\Handler
    {
        $handler = new Ajax\Handler();
        $handler->set_action('ac_notice_dismiss_renewal')
                ->set_callback([$this, 'ajax_dismiss_notice']);

        return $handler;
    }

    protected function get_dismiss_option(int $interval): Storage\Timestamp
    {
        return new Storage\Timestamp(
            new Storage\UserMeta('ac_notice_dismiss_renewal_' . $interval)
        );
    }

    private function get_activation(): ?Type\Activation
    {
        $token = $this->activation_token_factory->create();

        return $token
            ? $this->activation_storage->find($token)
            : null;
    }

    private function is_activation_up_for_renewal(Type\Activation $activation): bool
    {
        return ! $activation->is_auto_renewal() &&
               ! $activation->is_expired() &&
               ! $activation->is_lifetime() &&
               ! $activation->is_cancelled();
    }

    public function display(Screen $screen): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        if (true === apply_filters('acp/hide_renewal_notice', false)) {
            return;
        }

        switch (true) {
            // Inline message on plugin page
            case $screen->is_plugin_screen():
                $activation = $this->get_activation();

                if ($activation
                    && $this->is_activation_up_for_renewal($activation)
                    && $activation->has_expiry_date()
                    && $activation->get_expiry_date()->is_expiring_within_seconds(DAY_IN_SECONDS * 21)) {
                    $notice = new Message\Plugin(
                        $this->get_message($activation->get_expiry_date()),
                        $this->plugin->get_basename()
                    );
                    $notice->register();
                }

                return;

            // Permanent displayed on settings page
            case $screen->is_admin_screen(Settings::NAME):
                $activation = $this->get_activation();

                if ($activation
                    && $this->is_activation_up_for_renewal($activation)
                    && $activation->has_expiry_date()
                    && $activation->get_expiry_date()->is_expiring_within_seconds(DAY_IN_SECONDS * 21)) {
                    $notice = new Message\Notice($this->get_message($activation->get_expiry_date()));
                    $notice
                        ->set_type($notice::WARNING)
                        ->register();
                }

                return;

            // Dismissible
            case ($screen->is_table_screen() || $screen->is_admin_screen(Columns::NAME)):
                $activation = $this->get_activation();

                if ( ! $activation
                     || ! $this->is_activation_up_for_renewal($activation)
                     || ! $activation->has_expiry_date()) {
                    return;
                }

                $days_remaining = $activation->get_expiry_date()->get_remaining_days();

                $interval = $days_remaining > 0
                    ? $this->get_current_interval((int)floor($days_remaining))
                    : null;

                if ($interval && $this->get_dismiss_option($interval)->is_expired()) {
                    $notice = new Message\Notice\Dismissible(
                        $this->get_message($activation->get_expiry_date()),
                        $this->get_ajax_handler_interval($interval)
                    );
                    $notice
                        ->set_type($notice::WARNING)
                        ->register();
                }

                return;
        }
    }

    private function get_ajax_handler_interval(int $interval): Ajax\Handler
    {
        $ajax_handler = $this->get_ajax_handler();
        $ajax_handler->set_param('interval', $interval);

        return $ajax_handler;
    }

    /**
     * Get the current interval compared to the license state. Returns false when no interval matches
     */
    protected function get_current_interval(int $remaining_days): ?int
    {
        foreach ($this->intervals as $k => $interval) {
            if ($interval >= $remaining_days) {
                return $k;
            }
        }

        return null;
    }

    private function localize_date(DateTime $date): string
    {
        return (string)ac_format_date(get_option('date_format'), $date->getTimestamp());
    }

    protected function get_message(ExpiryDate $expiry_date): string
    {
        $url = new UtmTags($this->account_url_factory->create(), 'renewal');

        $renewal_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url->get_url()),
            __('Renew your license', 'codepress-admin-columns')
        );
        $remaining_time = sprintf('<strong>%s</strong>', $expiry_date->get_human_time_diff());
        $localize_date = sprintf('<strong>%s</strong>', $this->localize_date($expiry_date->get_value()));

        return sprintf(
            __(
                "Your Admin Columns Pro license will expire in %s. In order get access to new features and receive security updates, please %s before %s.",
                'codepress-admin-columns'
            ),
            $remaining_time,
            strtolower($renewal_link),
            $localize_date
        );
    }

}