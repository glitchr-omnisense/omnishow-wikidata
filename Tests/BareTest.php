<?php

namespace Omnishow\Wikidata\Tests;

use Omnishow\Testing\BareTestCase;
use Omnishow\Wikidata\WikidataProvider;

/** omnishow/wikidata's classes load with omnisense/omnishow and Symfony's HTTP client contracts alone. */
final class BareTest extends BareTestCase
{
    protected static function entryClass(): string
    {
        return WikidataProvider::class;
    }

    protected static function requiredPackages(): array
    {
        return ['symfony/http-client-contracts'];
    }
}
