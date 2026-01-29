<?php

namespace ACP\Admin\Page;

use AC;
use AC\Admin\RenderableHead;
use AC\Asset;
use AC\Asset\Assets;
use AC\Asset\Location;
use AC\Renderable;
use AC\Type\Url\Documentation;
use ACP\AdminColumnsPro;

class Tools implements Asset\Enqueueables, Renderable, RenderableHead
{

    public const NAME = 'import-export';

    /**
     * @var Renderable[]
     */
    private array $sections = [];

    private Location\Absolute $location;

    private Renderable $head;

    private bool $is_network;

    public function __construct(AdminColumnsPro $plugin, Renderable $head, bool $is_network)
    {
        $this->location = $plugin->get_location();
        $this->head = $head;
        $this->is_network = $is_network;
    }

    public function render_head(): Renderable
    {
        return $this->head;
    }

    public function get_assets(): Assets
    {
        $script = new Asset\Script(
            'acp-script-tools',
            $this->location->with_suffix('assets/core/js/admin-page-tools.js'),
            ['ac-global-translations']
        );
        $script->localize(
            'acp_tools_i18n',
            Asset\Script\Localize\Translation::create([
                'list_screen_id'                    => __('List Screen ID', 'codepress-admin-columns'),
                'list_screen_source'                => __('List Screen Source', 'codepress-admin-columns'),
                'database'                          => __('Database', 'codepress-admin-columns'),
                'active'                            => __('Active', 'codepress-admin-columns'),
                'and'                               => __('and', 'codepress-admin-columns'),
                'preview'                           => __('Preview', 'codepress-admin-columns'),
                'delete'                            => __('Delete', 'codepress-admin-columns'),
                'excluded'                          => __('excluded', 'codepress-admin-columns'),
                'included'                          => __('included', 'codepress-admin-columns'),
                'out_of'                            => _x('out of', 'x out of x', 'codepress-admin-columns'),
                'select_all'                        => __('Select / Deselect All', 'codepress-admin-columns'),
                'export'                            => __('Export', 'codepress-admin-columns'),
                'export_description'                => __(
                    'Export column settings to a JSON file.',
                    'codepress-admin-columns'
                ),
                'export_tooltip'                    => __(
                    'Select the column settings you would like to export. Export to a .json file which you can then import to another Admin Columns installation.',
                    'codepress-admin-columns'
                ),
                'export_selected'                   => __('Export Selected', 'codepress-admin-columns'),
                'items_selected'                    => __('items selected for export', 'codepress-admin-columns'),
                'item_selected'                     => __('item selected for export', 'codepress-admin-columns'),
                'table_headers'                     => [
                    'list_table'    => __('List Table', 'codepress-admin-columns'),
                    'name'          => __('Name', 'codepress-admin-columns'),
                    'saved_filters' => __('Saved Filters', 'codepress-admin-columns'),
                    'source'        => __('Source', 'codepress-admin-columns'),
                    'description'   => __('Description', 'codepress-admin-columns'),
                ],
                'search'                            => __('Search', 'codepress-admin-columns'),
                'no_results'                        => __('No results found', 'codepress-admin-columns'),
                'import'                            => __('Import', 'codepress-admin-columns'),
                'import_file'                       => __('Import File', 'codepress-admin-columns'),
                'column'                            => __('column', 'codepress-admin-columns'),
                'columns'                           => __('columns', 'codepress-admin-columns'),
                'saved_filter'                      => __('saved filter', 'codepress-admin-columns'),
                'saved_filters'                     => __('saved filters', 'codepress-admin-columns'),
                'import_description'                => __(
                    'Import a JSON file with column settings.',
                    'codepress-admin-columns'
                ),
                'templates'                         => __('Templates', 'codepress-admin-columns'),
                'templates_tooltip'                 => __(
                    'A Template is a predefined column configuration that you can load to quickly set up your list table. Use it as a starting point and customize it as needed.',
                    'codepress-admin-columns'
                ),
                'template_read_only'                => __('File is read only.', 'codepress-admin-columns'),
                'file_location'                     => __('File Location', 'codepress-admin-columns'),
                'delete_message'                    => __(
                    "Warning! The template will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop",
                    'codepress-admin-columns'
                ),
                'templates_description'             => __(
                    'Pre-defined table views containing column settings that can be used as a template.',
                    'codepress-admin-columns'
                ),
                'advanced_file_storage'             => __('Advanced File Storage', 'codepress-admin-columns'),
                'advanced_file_storage_description' => sprintf(
                    __('Set multiple folders on your file system using %s.', 'codepress-admin-columns'),
                    sprintf(
                        '<a target="_blank" href="%s">%s</a>',
                        Documentation::create_local_storage('advanced-setup'),
                        __('the advanced setup', 'codepress-admin-columns')
                    )
                ),
                'file_storage_is_set_to'            => __(
                    'The file storage directory is set to:',
                    'codepress-admin-columns'
                ),
                'file_storage'                      => __('File Storage', 'codepress-admin-columns'),
                'file_storage_section_description'  => __(
                    'Set a folder on your file system using the simple setup.',
                    'codepress-admin-columns'
                ),
                'file_storage_description'          => __(
                    'Store column settings on the file system in PHP files instead of the database.',
                    'codepress-admin-columns'
                ),
                'file_storage_directories'          => __('File Storage Directories', 'codepress-admin-columns'),
                'file_storage_run_migration'        => sprintf(
                    __('You can %s from the database to file storage.', 'codepress-admin-columns'),
                    sprintf(
                        '<a href="#migrate">%s</a>',
                        __('migrate your column settings', 'codepress-admin-columns')
                    )
                ),
                'file'                              => _x('file', '1 file', 'codepress-admin-columns'),
                'files'                             => _x('files', 'x files', 'codepress-admin-columns'),
                'file_storage_tooltip'              => __(
                    'Stores column settings, views, and templates as PHP files in your project. Ideal for version control, deployment, and maintaining consistency across environments.',
                    'codepress-admin-columns'
                ),
                'file_storage_specific_locations'   => __(
                    'Specific column settings are stored on different locations on your file system.',
                    'codepress-admin-columns'
                ),
                'source_id'                         => __('Source ID', 'codepress-admin-columns'),
                'export_and_import'                 => __('Export and Import', 'codepress-admin-columns'),
                'enable_file_storage'               => __('Enable File Storage', 'codepress-admin-columns'),
                'enable_file_storage_description'   => sprintf(
                    __(
                        'Enable file storage and select a folder on your file system using %s.',
                        'codepress-admin-columns'
                    ),
                    sprintf(
                        '<a target="_blank" href="%s">%s</a>',
                        Documentation::create_local_storage('simple-setup'),
                        __('the simple setup', 'codepress-admin-columns')
                    )
                ),
                'file_directories'                  => __('File Directories', 'codepress-admin-columns'),
                'file_directory'                    => __('File Directory', 'codepress-admin-columns'),
                'file_directory_description'        => __(
                    'Set the storage directory to a folder on your file system.',
                    'codepress-admin-columns'
                ),
                'directory_contains'                => __(
                    'The directory contains %s with column settings.',
                    'codepress-admin-columns'
                ),
                'directory_not_writable'            => sprintf(
                    __('The directory is %s.', 'codepress-admin-columns'),
                    sprintf(
                        '<span class="acu-text-notification-red">%s</span>',
                        __('read only', 'codepress-admin-columns')
                    )
                ),
                'migrate_confirmation'              => __('Are you sure?', 'codepress-admin-columns'),
                'migrate_settings'                  => __('Migrate to File Storage', 'codepress-admin-columns'),
                'migrate_settings_description'      => __(
                    'Migrate all column settings from the database to your file system.',
                    'codepress-admin-columns'
                ),
                'migrate_run'                       => __('Run Migration', 'codepress-admin-columns'),
                'save'                              => __('Save', 'codepress-admin-columns'),
                'show_all'                          => sprintf(
                    '%s %s',
                    __('Show all', 'codepress-admin-columns'),
                    __('%d items', 'codepress-admin-columns')
                ),
            ])
        );

        $preferences = new AC\Admin\Preference\ScreenOptions();

        $script->add_inline_variable('acp_tools', [
            'nonce'          => (new AC\Nonce\Ajax())->create(),
            'is_network'     => $this->is_network,
            'screen_options' => [
                'show_list_screen_id'           => $preferences->is_active('show_list_screen_id'),
                'show_tools_list_screen_source' => $preferences->is_active('show_tools_list_screen_source'),
            ],
        ]);

        $view = new AC\View();
        $script->add_template(
            'storage-file-directory',
            $view->set_template('admin/script/storage-file-directory')->render()
        );

        $assets = new Asset\Assets([
            new Asset\Style('acp-style-tools', $this->location->with_suffix('assets/core/css/admin-tools.css')),
            $script,
        ]);

        foreach ($this->sections as $section) {
            if ($section instanceof Asset\Enqueueables) {
                $assets->add_collection($section->get_assets());
            }
        }

        return $assets;
    }

    public function render(): string
    {
        return '';
    }

}