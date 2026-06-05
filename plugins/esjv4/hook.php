<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Schema;
use GlpiPlugin\Esjv4\TemplateService;

function plugin_esjv4_install(): bool
{
    if (!Schema::install()) {
        return false;
    }

    TemplateService::seedDefaults();

    return true;
}

function plugin_esjv4_uninstall(): bool
{
    return true;
}
