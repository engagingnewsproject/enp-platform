<?php

namespace ACP\Editing\Service\User;

use AC\Helper\Select\Option;
use AC\Type\ToggleOptions;
use ACP\Editing;
use ACP\Editing\Service\BasicStorage;
use ACP\Editing\View;

class RichEditing extends BasicStorage {

	public function __construct() {
		parent::__construct( new Editing\Storage\User\Meta( 'rich_editing' ) );
	}

	public function get_view( string $context ): ?View {
		return new View\Toggle(
			new ToggleOptions(
				new Option( 'true', __( 'True', 'codepress-admin-columns' ) ),
				new Option( 'false', __( 'False', 'codepress-admin-columns' ) )
			)
		);
	}

}