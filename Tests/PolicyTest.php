<?php

namespace Omnishow\Wikidata\Tests;

use Omnisense\Contracts\Policy\InvalidPolicyException;
use Omnisense\Contracts\Policy\Permission;
use Omnishow\Exception\OmnishowException;
use Omnishow\Exception\UsageLimitReachedException;
use Omnishow\Model\Query;
use Omnishow\Registry;
use Omnishow\Testing\RecordedHttpClient;
use Omnishow\Wikidata\WikidataFactory;
use Omnishow\Wikidata\WikidataProvider;
use PHPUnit\Framework\TestCase;

/**
 * WikidataProvider's usage policy (policy.json) through omnishow's registry:
 * as declared, a site's limit counted with the call over it never made,
 * a 429 and its Retry-After holding every next call, a looser setting
 * refused. No network: recorded answers only.
 */
final class PolicyTest extends TestCase
{
    private const OPTIONS = ['user_agent' => 'omnishow-tests/1.0 (+https://github.com/glitchr-omnisense/omnishow)'];

    public function testThePolicyIsTheOneDeclared(): void
    {
        $policy = (new Registry([new WikidataFactory(RecordedHttpClient::routes([]))], ['films' => ['factory' => 'wikidata', 'options' => self::OPTIONS]]))->policy('films');

        self::assertSame('wikidata', $policy->provider);
        self::assertSame('2026-11-10', $policy->provenance->verifiedAt);
        self::assertFileExists(WikidataProvider::POLICY);
        self::assertSame(['200/minute/application/*'], array_map(static fn ($l) => $l->id(), $policy->rateLimits ?? []));
        self::assertSame('CC0-1.0', $policy->rights->licence);
        self::assertTrue($policy->requirements->userAgent);
        self::assertTrue($policy->requirements->acceptsUserAgent(self::OPTIONS['user_agent']));
        self::assertFalse($policy->requirements->acceptsUserAgent('Mozilla/5.0'), 'a name, a version and a contact');
    }

    public function testASitesLimitIsCountedAndTheCallOverItIsNeverMade(): void
    {
        $http = RecordedHttpClient::routes([]);
        $registry = new Registry([new WikidataFactory($http)], ['films' => ['factory' => 'wikidata', 'options' => self::OPTIONS, 'policy' => ['rateLimits' => [['max' => 1, 'window' => 'day', 'operations' => ['search']]]]]]);

        try {
            $registry->search('films')->search(new Query('stalker'));
        } catch (UsageLimitReachedException $e) {
            throw $e;
        } catch (OmnishowException) {
            // Nothing recorded: the provider's own answer to that, counted all the same.
        }
        $made = \count($http->requests);
        self::assertGreaterThan(0, $made);
        try {
            $registry->search('films')->search(new Query('stalker'));
            self::fail('The call over the site\'s limit was made.');
        } catch (UsageLimitReachedException $e) {
            self::assertInstanceOf(OmnishowException::class, $e, 'the family\'s catch holds it');
            self::assertSame('1/day/application/search', $e->limit?->id());
        }
        self::assertCount($made, $http->requests, 'the refused call reached nothing');
    }

    public function testA429AndItsRetryAfterHoldEveryCallUntilTheWaitIsOver(): void
    {
        $http = RecordedHttpClient::routes(['~.~' => [429, '{"error":"too many requests"}', ['retry-after' => ['120']]]]);
        $registry = new Registry([new WikidataFactory($http)], ['films' => ['factory' => 'wikidata', 'options' => self::OPTIONS]]);

        try {
            $registry->search('films')->search(new Query('stalker'));
            self::fail('A 429 was read as an answer.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame(120, $e->retryAfter);
            self::assertSame('wikidata answered 429 Too Many Requests: try again in 120 s.', $e->getMessage());
        }
        try {
            $registry->search('films')->search(new Query('stalker'));
            self::fail('A call went out while the provider asked to wait.');
        } catch (UsageLimitReachedException $e) {
            self::assertGreaterThanOrEqual(119, $e->retryAfter);
        }
        self::assertCount(1, $http->requests, 'the wait held the next request back');
    }

    public function testASiteCannotLoosenThePolicy(): void
    {
        $registry = new Registry([new WikidataFactory(RecordedHttpClient::routes([]))], ['films' => ['factory' => 'wikidata', 'options' => self::OPTIONS, 'policy' => ['rateLimits' => [['max' => 1000, 'window' => 'minute']]]]]);

        $this->expectException(InvalidPolicyException::class);
        $this->expectExceptionMessage('200 a minute');
        $registry->search('films');
    }
}
