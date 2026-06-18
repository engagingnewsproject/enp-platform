<?php

declare(strict_types=1);

namespace ACA\WC\Editing\User;

use ACA\WC\Type\AddressType;
use ACP\Editing\Service;
use ACP\Editing\View;

class Address implements Service
{

    private AddressType $address_type;

    public function __construct(AddressType $address_type)
    {
        $this->address_type = $address_type;
    }

    private function get_address_fields(): array
    {
        return [
            'first_name' => __('First Name', 'woocommerce'),
            'last_name'  => __('Last Name', 'woocommerce'),
            'company'    => __('Company', 'woocommerce'),
            'address_1'  => sprintf(__('Address line %s', 'codepress-admin-columns'), 1),
            'address_2'  => sprintf(__('Address line %s', 'codepress-admin-columns'), 2),
            'city'       => __('City', 'woocommerce'),
            'state'      => __('State', 'woocommerce'),
            'postcode'   => __('Postcode', 'woocommerce'),
            'country'    => __('Country', 'woocommerce'),
        ];
    }

    public function text_field($field): View\CompositeField\CompositeField
    {
        $fields = $this->get_address_fields();

        return new View\CompositeField\TextField($field, $fields[$field] ?? $field);
    }

    public function get_view(string $context): ?View
    {
        $view = new View\Composite('vertical');

        $view->add_field($this->text_field('first_name'));
        $view->add_field($this->text_field('last_name'));
        $view->add_field($this->text_field('company'));
        $view->add_field($this->text_field('address_1'));
        $view->add_field($this->text_field('address_2'));
        $view->add_field($this->text_field('city'));
        $view->add_field(
            new View\CompositeField\SelectField(
                'country',
                __('Country', 'woocommerce'),
                WC()->countries->get_countries()
            )
        );
        $view->add_field($this->text_field('state'));
        $view->add_field($this->text_field('postcode'));

        return $view;
    }

    public function get_value(int $id): array
    {
        $prefix = $this->address_type->get() . '_';
        $data = [];
        foreach ($this->get_address_fields() as $field => $label) {
            $data[$field] = get_user_meta($id, $prefix . $field, true);
        }

        return $data;
    }

    public function update(int $id, $data): void
    {
        $prefix = $this->address_type->get() . '_';

        foreach ($this->get_address_fields() as $field => $label) {
            update_user_meta($id, $prefix . $field, $data[$field]);
        }
    }
}