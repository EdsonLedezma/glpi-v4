<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Plugin;

esjv4_assert_true(class_exists(Plugin::class), 'Plugin class must exist');
esjv4_assert_same('esjv4', Plugin::KEY, 'Plugin key must match folder name');
esjv4_assert_same('ESJ V4', Plugin::NAME, 'Plugin name must be user-facing');
esjv4_assert_same('0.1.0', Plugin::VERSION, 'Initial plugin version must be 0.1.0');
