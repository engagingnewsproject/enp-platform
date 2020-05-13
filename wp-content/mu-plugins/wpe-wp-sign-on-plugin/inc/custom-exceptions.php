<?php

namespace wpengine\sign_on_plugin;

require_once __DIR__ . '/security-checks.php';
\wpengine\sign_on_plugin\check_security();

class UserCreationException extends \Exception {
}

class UserMetaAdditionException extends \Exception {
}

class InvalidInstallNameException extends \Exception {
}

class NonceMetaDataValidationException extends \Exception {
}

class ImpersonatedUserException extends \Exception {
}

class MultisiteEnabledException extends \Exception {
}

class NoRefererException extends \Exception {
}
