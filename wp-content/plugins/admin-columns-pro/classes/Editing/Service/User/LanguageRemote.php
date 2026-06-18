<?php

namespace ACP\Editing\Service\User;

use AC\Helper;
use AC\Helper\Select\Option;
use AC\Helper\Select\OptionGroup;
use AC\Helper\Select\Options;
use ACP\Editing;
use ACP\Editing\Service\BasicStorage;
use ACP\Editing\View;

class LanguageRemote extends BasicStorage implements Editing\RemoteOptions
{

    public function __construct()
    {
        parent::__construct(new Editing\Storage\User\Meta('locale'));
    }

    public function get_view(string $context): ?View
    {
        return new View\RemoteSelect();
    }

    public function get_remote_options(?int $id = null): Options
    {
        $translations = Helper\Translations::create()->get_available_translations();
        $installed_languages = array_flip(get_available_languages());

        $installed = [new Option('', _x('Site Default', 'default site language'))];
        $available = [];

        foreach ($translations as $language => $translation) {
            $option = new Option($language, $translation['native_name']);

            if (isset($installed_languages[$language])) {
                $installed[] = $option;
            } else {
                $available[] = $option;
            }
        }

        $groups = [new OptionGroup(__('Installed', 'codepress-admin-columns'), $installed)];

        if ($available) {
            $groups[] = new OptionGroup(__('Available', 'codepress-admin-columns'), $available);
        }

        return new Options($groups);
    }

}