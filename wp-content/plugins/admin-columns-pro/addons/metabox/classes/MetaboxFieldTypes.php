<?php

declare(strict_types=1);

namespace ACA\MetaBox;

final class MetaboxFieldTypes
{

    // BASIC
    public const BUTTON = 'button';
    public const CHECKBOX_LIST = 'checkbox_list';
    public const CHECKBOX = 'checkbox';
    public const EMAIL = 'email';
    public const HIDDEN = 'hidden';
    public const NUMBER = 'number';
    public const PASSWORD = 'password';
    public const RADIO = 'radio';
    public const RANGE = 'range';
    public const SELECT = 'select';
    public const SELECT_ADVANCED = 'select_advanced';
    public const TEXT = 'text';
    public const TEXTAREA = 'textarea';
    public const URL = 'url';

    // ADVANCED
    public const AUTOCOMPLETE = 'autocomplete';
    public const COLORPICKER = 'color';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const FIELDSET_TEXT = 'fieldset_text';
    public const GOOGLE_MAPS = 'map';
    public const IMAGE_SELECT = 'image_select';
    public const OEMBED = 'oembed';
    public const SLIDER = 'slider';
    public const TEXT_LIST = 'text_list';
    public const TIME = 'time';
    public const WYSIWYG = 'wysiwyg';

    // WORDPRESS
    public const POST = 'post';
    public const TAXONOMY = 'taxonomy';
    public const TAXONOMY_ADVANCED = 'taxonomy_advanced';
    public const USER = 'user';

    // UPLOAD
    public const FILE = 'file';
    public const FILE_ADVANCED = 'file_advanced';
    public const FILE_INPUT = 'file_input';
    public const FILE_UPLOAD = 'file_upload';
    public const IMAGE = 'image';
    public const IMAGE_ADVANCED = 'image_advanced';
    public const SINGLE_IMAGE = 'single_image';
    public const VIDEO = 'video';

    // SPECIAL
    public const GROUP = 'group';
}