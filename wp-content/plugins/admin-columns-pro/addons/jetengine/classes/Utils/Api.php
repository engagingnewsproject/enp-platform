<?php

declare(strict_types=1);

namespace ACA\JetEngine\Utils;

use Jet_Engine\Glossaries;
use Jet_Engine\Relations;
use Jet_Engine_Meta_Boxes;
use LogicException;

final class Api
{

    private static function validate(): void
    {
        if ( ! function_exists('jet_engine')) {
            throw new LogicException('Jet Engine is not active');
        }
    }

    public static function relations(): Relations\Manager
    {
        self::validate();

        return jet_engine()->relations;
    }

    public static function metaboxes(): Jet_Engine_Meta_Boxes
    {
        self::validate();

        return jet_engine()->meta_boxes;
    }

    public static function glossaries_meta(): Glossaries\Meta_Fields
    {
        self::validate();

        return jet_engine()->glossaries->meta_fields;
    }

}