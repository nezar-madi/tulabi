<?php

namespace Tests\Unit;

use App\Support\InstructorSlug;
use App\Support\ProfileLinks;
use PHPUnit\Framework\TestCase;

class InstructorSlugTest extends TestCase
{
    public function test_the_route_pattern_matches_good_slugs_and_no_reserved_word(): void
    {
        $pattern = '#^'.InstructorSlug::routePattern().'$#';

        foreach (['ahmed', 'ahmed-math', 'm2', 'admin2', 'teach-me', 'loginx'] as $slug) {
            $this->assertSame(1, preg_match($pattern, $slug), $slug);
            $this->assertSame(1, preg_match(InstructorSlug::PATTERN, $slug), $slug);
        }

        foreach ([...InstructorSlug::RESERVED, 'Ahmed', '-a', 'a--b', 'a_b', '1abc'] as $slug) {
            $this->assertSame(0, preg_match($pattern, $slug), $slug);
        }
    }

    public function test_links_keep_only_web_addresses_and_know_their_kind(): void
    {
        $this->assertSame(
            ['https://facebook.com/a', 'http://x.ly'],
            ProfileLinks::clean(['https://facebook.com/a', 'javascript:alert(1)', ' ', 'http://x.ly', 'https://facebook.com/a']),
        );

        $this->assertSame('facebook', ProfileLinks::kind('https://m.facebook.com/a'));
        $this->assertSame('youtube', ProfileLinks::kind('https://youtu.be/x'));
        $this->assertSame('telegram', ProfileLinks::kind('https://t.me/x'));
        $this->assertSame('website', ProfileLinks::kind('https://notfacebook.com'));
    }
}
