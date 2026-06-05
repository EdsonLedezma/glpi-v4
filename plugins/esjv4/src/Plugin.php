<?php

declare(strict_types=1);

namespace GlpiPlugin\Esjv4;

final class Plugin
{
    public const KEY = 'esjv4';
    public const NAME = 'ESJ V4';
    public const VERSION = '0.1.0';

    public static function rootPath(): string
    {
        return dirname(__DIR__);
    }
}
