<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat;

use AC\Capabilities;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\ListScreenId;
use ACP\ConditionalFormat\Type\Key;
use BadMethodCallException;

abstract class RequestHandler implements RequestAjaxHandler
{

    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    abstract protected function handle_validated(): void;

    abstract protected function get_required_fields(): array;

    protected function validate(): void
    {
        if ( ! (new Nonce\Ajax())->verify($this->request)) {
            wp_send_json('', 400);
        }

        foreach ($this->get_required_fields() as $key) {
            if ( ! $this->request->has($key)) {
                wp_send_json(sprintf('Missing `%s` argument.', $key), 400);
            }
        }
    }

    protected function validate_user_rights(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            if ( ! $this->has_user_id() || $this->get_user_id() !== get_current_user_id()) {
                wp_send_json('', 401);
            }
        }
    }

    protected function has_list_id(): bool
    {
        return $this->request->has('list_id');
    }

    protected function get_list_id(): ListScreenId
    {
        if ( ! $this->has_list_id()) {
            throw new BadMethodCallException('Missing list_id argument.');
        }

        return new ListScreenId($this->request->get('list_id'));
    }

    protected function has_user_id(): bool
    {
        return $this->request->has('user_id') &&
               preg_match('/^[1-9]\d*$/', $this->request->get('user_id'));
    }

    protected function get_user_id(): int
    {
        if ( ! $this->has_user_id()) {
            throw new BadMethodCallException('Missing user_id argument.');
        }

        return (int)$this->request->get('user_id');
    }

    protected function has_key(): bool
    {
        return $this->request->has('key');
    }

    protected function get_key(): Key
    {
        return new Key($this->request->get('key'));
    }

    public function handle(): void
    {
        $this->validate();
        $this->handle_validated();
    }

}