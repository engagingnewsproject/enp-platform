<?php

declare(strict_types=1);

namespace ACP\Editing\Service;

use AC\Helper\Select\Option;
use AC\Type\ToggleOptions;
use ACP\Editing;
use ACP\Editing\Service;
use ACP\Editing\Storage;
use ACP\Editing\View;

class ServiceFactory
{

    public static function create(Editing\View $view, Storage $storage): Service
    {
        return new Editing\Service\Basic($view, $storage);
    }

    public static function create_serialized(Storage $storage, array $keys): Service
    {
        return new Service\SerializedMeta($storage, $keys);
    }

    public static function create_toggle(Storage $storage): Service
    {
        $view = new View\Toggle(
            new ToggleOptions(
                new Option('0', __('False', 'codepress-admin-columns')),
                new Option('1', __('True', 'codepress-admin-columns'))
            )
        );

        return self::create($view, $storage);
    }

    public static function create_color(Storage $storage): Service
    {
        $view = (new View\Color())->set_clear_button(true);

        return self::create($view, $storage);
    }

    public static function create_date_time(Storage $storage, ?string $date_format = null): Service
    {
        $view = (new View\DateTime())->set_clear_button(true);

        return new Service\DateTime($view, $storage, $date_format);
    }

    public static function create_date(Storage $storage, ?string $date_format = null): Service
    {
        $view = (new View\Date())->set_clear_button(true);

        return new Service\Date($view, $storage, $date_format);
    }

    public static function create_media(Storage $storage): Service
    {
        $view = (new View\Media())->set_multiple(true)->set_clear_button(true);

        return self::create($view, $storage);
    }

    public static function create_image(Storage $storage): Service
    {
        $view = (new View\Image())->set_clear_button(true);

        return self::create($view, $storage);
    }

    public static function create_select(Storage $storage, array $options): Service
    {
        $view = (new View\Select($options))->set_clear_button(true);

        return self::create($view, $storage);
    }

    public static function create_multiple_select(Storage $storage, array $options): Service
    {
        $view = (new View\AdvancedSelect($options))->set_multiple(true)->set_clear_button(true);

        return self::create($view, $storage);
    }

    public static function create_internal_link(Storage $storage): Service
    {
        $view = (new View\InternalLink())->set_clear_button(true)
                                         ->set_placeholder(__('Paste URL or type to search'));

        return self::create($view, $storage);
    }

    public static function create_number(Storage $storage): Service\ComputedNumber
    {
        return new Service\ComputedNumber($storage);
    }

    public static function create_post(Storage $storage, array $post_types = []): Service
    {
        return new Service\Post(
            (new View\AjaxSelect())->set_clear_button(true),
            $storage,
            new Editing\PaginatedOptions\Posts($post_types)
        );
    }

    public static function create_posts(Storage $storage, array $post_types = []): Service
    {
        return new Service\Posts(
            (new View\AjaxSelect())->set_clear_button(true),
            $storage,
            new Editing\PaginatedOptions\Posts($post_types)
        );
    }

    public static function create_users(Storage $storage): Service
    {
        return new Service\User(
            (new View\AjaxSelect())->set_clear_button(true),
            $storage,
            new Editing\PaginatedOptions\Users()
        );
    }

    public static function create_wysiwyg(Storage $storage): Service
    {
        $view = (new Editing\View\Wysiwyg())->set_clear_button(true);

        return self::create($view, $storage);
    }

    public static function create_textarea(Storage $storage): Service
    {
        $view = (new Editing\View\TextArea())->set_clear_button(true);

        return self::create($view, $storage);
    }

    public static function create_text(Storage $storage): Service
    {
        $view = (new Editing\View\Text())->set_clear_button(true);

        return self::create($view, $storage);
    }

}