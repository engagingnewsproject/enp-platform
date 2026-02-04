<?php

declare(strict_types=1);

namespace ACP\Admin\ScriptFactory;

use AC\Asset;
use AC\Asset\Script;
use AC\Asset\Script\Localize\Translation;
use AC\Nonce\Ajax;
use AC\TableScreen;
use ACP\Access\PermissionsStorage;
use ACP\AdminColumnsPro;

class ColumnSettingsFactory
{

    public const HANDLE = 'acp-page-columns';

    private Asset\Location $location;

    private PermissionsStorage $permissions_storage;

    public function __construct(AdminColumnsPro $plugin, PermissionsStorage $permissions_storage)
    {
        $this->location = $plugin->get_location();
        $this->permissions_storage = $permissions_storage;
    }

    public function create(TableScreen $table_screen): Script
    {
        $script = new Asset\Script(
            self::HANDLE,
            $this->location->with_suffix('assets/core/js/admin-page-columns.js'),
            [
                'ac-admin-page-columns',
                Script\GlobalTranslationFactory::HANDLE,
            ]
        );

        $translation = new Translation([
            'table_views' => [
                'add'                         => __('Add', 'codepress-admin-columns'),
                'add_view'                    => __('+ Add View', 'codepress-admin-columns'),
                'cancel'                      => __('Cancel', 'codepress-admin-columns'),
                'copy_settings'               => __(
                    'Select settings from an existing view or template',
                    'codepress-admin-columns'
                ),
                'copy_view'                   => __('Copy Table View', 'codepress-admin-columns'),
                'copy_view_description'       => __(
                    'Create a copy of the table view.',
                    'codepress-admin-columns'
                ),
                'copy_view_destination'       => __(
                    'Select the destination for this table view',
                    'codepress-admin-columns'
                ),
                'create'                      => __('Create', 'codepress-admin-columns'),
                'create_label'                => sprintf(
                    __('Create a new view for the %s list table.', 'codepress-admin-columns'),
                    $table_screen->get_labels()->get_singular()
                ),
                'create_view'                 => __('Create table view', 'codepress-admin-columns'),
                'create_template'             => __('Create template', 'codepress-admin-columns'),
                'create_template_description' => __(
                    'Create a re-usable template for this table view.',
                    'codepress-admin-columns'
                ),
                'current'                     => __('current', 'codepress-admin-columns'),
                'default_columns'             => __('Default columns', 'codepress-admin-columns'),
                'delete'                      => __('Delete', 'codepress-admin-columns'),
                'delete_view'                 => __('Delete view', 'codepress-admin-columns'),
                'delete_message'              => __(
                    "Warning! The %s columns data will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop",
                    'codepress-admin-columns'
                ),
                'enter_name'                  => __('Enter Name', 'codepress-admin-columns'),
                'group_presets'               => __('Templates', 'codepress-admin-columns'),
                'group_table_views'           => __('Table Views', 'codepress-admin-columns'),
                'instructions'                => __('Instructions', 'codepress-admin-columns'),
                'list_table'                  => __('List Table', 'codepress-admin-columns'),
                'make_a_copy'                 => __('Make a copy', 'codepress-admin-columns'),
                'manage_views'                => __('Manage Views', 'codepress-admin-columns'),
                'name'                        => __('Name', 'codepress-admin-columns'),
                'preview'                     => __('Preview', 'codepress-admin-columns'),
                'read_only'                   => __('Read Only', 'codepress-admin-columns'),
                'show_all'                    => _x('show all', 'table views', 'codepress-admin-columns'),
                'settings'                    => __('Settings', 'codepress-admin-columns'),
                'settings_description'        => __(
                    'Modify the appearance of the list table',
                    'codepress-admin-columns'
                ),
                'table_view'                  => __('Table View', 'codepress-admin-columns'),
                'table_views'                 => __('Table Views', 'codepress-admin-columns'),
                'table_views_description'     => __(
                    'Create multiple views for this list table',
                    'codepress-admin-columns'
                ),
                'table_views_tooltip'         => __(
                    'With Table Views, you can set up multiple column configurations for the same list table and easily switch between them.',
                    'codepress-admin-columns'
                ),
                'template'                    => __('Settings Template', 'codepress-admin-columns'),
                'template_created'            => __('New template created', 'codepress-admin-columns'),
                'templates'                   => __('Templates', 'codepress-admin-columns'),
                'templates_tooltip'           => __(
                    'A Template is a predefined column configuration that you can load to quickly set up your list table. Use it as a starting point and customize it as needed.',
                    'codepress-admin-columns'
                ),
                'template_notice'             => __(
                    'You are viewing a Template. This can be used to %s.',
                    'codepress-admin-columns'
                ),
                'template_notice_create'      => __('create a table view', 'codepress-admin-columns'),
                'view_created'                => __('New View created', 'codepress-admin-columns'),
                'roles'                       => __('Roles', 'codepress-admin-columns'),
                'user'                        => __('User', 'codepress-admin-columns'),
                'users'                       => __('Users', 'codepress-admin-columns'),
            ],
        ]);

        $script->localize('acp_column_settings_i18n', $translation);

        $script->add_inline_variable(
            'acp_column_settings',
            [
                'ajax_nonce'        => (new Ajax())->create(),
                'list_screen_label' => $table_screen->get_labels()->get_singular(),
                'confirm_delete'    => (bool)apply_filters('ac/delete_confirmation', true),
                'roles'             => $this->get_roles(),
                'permissions'       => $this->permissions_storage->retrieve()->to_array(),
            ]
        );

        return $script;
    }

    private function get_roles(): array
    {
        $options = [];

        $default_roles = ['super_admin', 'administrator', 'editor', 'author', 'contributor', 'subscriber'];

        foreach (get_editable_roles() as $name => $role) {
            $name = (string)$name;

            $group = in_array($name, $default_roles, true)
                ? __('Default', 'codepress-admin-columns')
                : __('Other', 'codepress-admin-columns');

            $group = (string)apply_filters('ac/editing/role_group', $group, $name);

            $options[] = [
                'value' => $name,
                'label' => $role['name'],
                'group' => $group,
            ];
        }

        return $options;
    }

}