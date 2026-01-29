<?php

declare(strict_types=1);

namespace ACA\Pods;

use AC\MetaType;
use AC\Type\PostTypeSlug;
use AC\Type\TaxonomySlug;
use LogicException;
use Pods\Whatsit;
use Pods\Whatsit\Pod;

class Field
{

    private $field;

    private $pod;

    public function __construct(Pod $pod, Whatsit\Field $field)
    {
        $this->field = $field;
        $this->pod = $pod;
    }

    public function get_meta_type(): MetaType
    {
        switch ($this->pod->get_type()) {
            case 'post_type':
            case 'media':
                return new MetaType(MetaType::POST);
            case 'taxonomy':
                return new MetaType(MetaType::TERM);
            case 'user':
                return new MetaType(MetaType::USER);
            case 'comment':
                return new MetaType(MetaType::COMMENT);
            default:
                throw new LogicException('Unknown meta type');
        }
    }

    public function get_taxonomy(): ?TaxonomySlug
    {
        return MetaType::TERM === (string)$this->get_meta_type()
            ? new TaxonomySlug($this->pod->get_name())
            : null;
    }

    public function get_post_type(): ?PostTypeSlug
    {
        return MetaType::POST === (string)$this->get_meta_type()
            ? new PostTypeSlug($this->pod->get_name())
            : null;
    }

    public function get_field(): Whatsit\Field
    {
        return $this->field;
    }

    public function get_pod(): Pod
    {
        return $this->pod;
    }

    public function get_label(): string
    {
        return $this->get_field()->get_label();
    }

    public function get_type(): string
    {
        $type = $this->field->get_type();

        switch ($type) {
            case 'pick':
                return $this->field->get_arg('pick_object', '');
            default:
                return $type;
        }
    }

    public function get_name(): string
    {
        return $this->field->get_name();
    }

    public function get_arg(string $name, $default = null)
    {
        return $this->field->get_arg($name, $default);
    }

}