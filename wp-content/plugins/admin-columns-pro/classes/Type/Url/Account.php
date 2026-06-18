<?php

declare(strict_types=1);

namespace ACP\Type\Url;

use AC\Type\Url\Site;
use ACP\Type\ActivationToken;
use ACP\Type\SiteUrl;

class Account extends Site
{

    public function __construct(SiteUrl $site_url, ?ActivationToken $token = null)
    {
        parent::__construct(Site::PAGE_ACCOUNT_SUBSCRIPTIONS);

        if ($token) {
            $this->add('site_url', (string)$site_url);
            $this->add($token->get_type(), $token->get_token());
        }
    }

}