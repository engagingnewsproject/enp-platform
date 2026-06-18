<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactories\Original;

use AC;
use AC\TableScreen;
use ACA\MLA\ColumnFactory\Original;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

final class MlaCustomFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return [];
        }

        return [
            'alt_text'       => Original\AltText::class,
            'attached_to'    => Original\AttachedTo::class,
            'author'         => Original\Author::class,
            'caption'        => Original\Caption::class,
            'date'           => Original\Date::class,
            'description'    => Original\Description::class,
            'featured'       => Original\Featured::class,
            'file_url'       => Original\FileUrl::class,
            'galleries'      => Original\Gallery::class,
            'mla_galleries'  => Original\GalleryMla::class,
            'ID_parent'      => Original\IdParent::class,
            'inserted'       => Original\Inserted::class,
            'menu_order'     => Original\MenuOrder::class,
            'modified'       => Original\Modified::class,
            'post_mime_type' => Original\MimeType::class,
            'post_name'      => Original\Name::class,
            'parent'         => Original\IdParent::class,
            'post_title'     => Original\PostTitle::class,
            'title_name'     => Original\TitleName::class,
        ];
    }

}