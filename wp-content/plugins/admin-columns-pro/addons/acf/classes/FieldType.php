<?php

declare(strict_types=1);

namespace ACA\ACF;

interface FieldType
{

    public const TYPE_BUTTON_GROUP = 'button_group';
    public const TYPE_BOOLEAN = 'true_false';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_CLONE = 'clone';
    public const TYPE_COLOR_PICKER = 'color_picker';
    public const TYPE_DATE_PICKER = 'date_picker';
    public const TYPE_DATE_TIME_PICKER = 'date_time_picker';
    public const TYPE_EMAIL = 'email';
    public const TYPE_FILE = 'file';
    public const TYPE_FLEXIBLE_CONTENT = 'flexible_content';
    public const TYPE_GOOGLE_MAP = 'google_map';
    public const TYPE_GROUP = 'group';
    public const TYPE_GALLERY = 'gallery';
    public const TYPE_IMAGE = 'image';
    public const TYPE_LINK = 'link';
    public const TYPE_NUMBER = 'number';
    public const TYPE_MESSAGE = 'message';
    public const TYPE_OEMBED = 'oembed';
    public const TYPE_PAGE_LINK = 'page_link';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_POST = 'post_object';
    public const TYPE_RADIO = 'radio';
    public const TYPE_RANGE = 'range';
    public const TYPE_REPEATER = 'repeater';
    public const TYPE_RELATIONSHIP = 'relationship';
    public const TYPE_SELECT = 'select';
    public const TYPE_TAXONOMY = 'taxonomy';
    public const TYPE_TAB = 'tab';
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_TIME_PICKER = 'time_picker';
    public const TYPE_URL = 'url';
    public const TYPE_USER = 'user';
    public const TYPE_WYSIWYG = 'wysiwyg';

    // 3rd party
    public const TYPE_IMAGE_CROP = 'image_crop';

}