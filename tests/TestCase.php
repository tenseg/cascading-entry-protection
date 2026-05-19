<?php

namespace Tenseg\CascadingEntryProtection\Tests;

use Tenseg\CascadingEntryProtection\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
