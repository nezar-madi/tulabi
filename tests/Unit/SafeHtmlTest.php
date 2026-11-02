<?php

namespace Tests\Unit;

use App\Support\SafeHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class descriptions are rendered as HTML (v-html), so the
 * filter is the only thing between instructor input and a visitor's browser.
 */
class SafeHtmlTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function payloads(): array
    {
        return [
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'svg script' => ['<svg><script>alert(1)</script></svg>'],
            'svg onload' => ['<svg onload=alert(1)>'],
            'quote breaking attribute' => ['<p title="x>"onmouseover=alert(1)>hi</p>'],
            'slash attribute' => ['<p/onclick=alert(1)>x</p>'],
            'anchor handler' => ['<a href="https://x" onclick="alert(1)">x</a>'],
            'javascript href' => ['<a href="javascript:alert(1)">x</a>'],
            'entity javascript href' => ['<a href="&#106;avascript:alert(1)">x</a>'],
            'hex tab javascript href' => ['<a href="java&#x09;script:alert(1)">x</a>'],
            'raw tab javascript href' => ["<a href=\"jav\tascript:alert(1)\">x</a>"],
            'spaced javascript href' => ['<a href=" javascript:alert(1)">x</a>'],
            'data href' => ['<a href="data:text/html,<script>alert(1)</script>">x</a>'],
            'vbscript href' => ['<a href="vbscript:msgbox(1)">x</a>'],
            'protocol relative' => ['<a href="//evil.example">x</a>'],
            'backslash protocol relative' => ['<a href="/\\evil.example">x</a>'],
            'encoded script' => ['&lt;script&gt;alert(1)&lt;/script&gt;'],
            'nested script' => ['<scr<script>ipt>alert(1)</script>'],
            'broken tag' => ['<p<script>>alert(1)</p>'],
            'style tag' => ['<style>*{background:url(javascript:alert(1))}</style>'],
            'iframe' => ['<iframe src="https://evil.example"></iframe>'],
            'form' => ['<form action="https://evil.example"><input name=x></form>'],
            'math' => ['<math><mtext><img src=x onerror=alert(1)></mtext></math>'],
            'style attribute' => ['<p style="background:url(javascript:alert(1))">x</p>'],
            'single quoted handler' => ["<a href='#' x='>' onclick=alert(1)>y</a>"],
        ];
    }

    #[DataProvider('payloads')]
    public function test_payload_is_neutralised(string $payload): void
    {
        $clean = SafeHtml::description($payload);

        $this->assertDoesNotMatchRegularExpression('/<\s*(script|img|svg|iframe|style|form|input|math|object|embed)\b/i', $clean);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $clean);
        $this->assertDoesNotMatchRegularExpression('/(href|src)\s*=\s*["\']?\s*(javascript|data|vbscript):/i', $clean);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $clean);
        $this->assertDoesNotMatchRegularExpression('/href="\/[\/\\\\]/i', $clean);

        // Whatever survives contains only the allowed bare tags and safe anchors.
        preg_match_all('/<([a-z0-9]+)([^>]*)>/i', $clean, $tags, PREG_SET_ORDER);
        foreach ($tags as [$whole, $name, $attributes]) {
            if (strtolower($name) === 'a') {
                $this->assertMatchesRegularExpression('/^(\s+href="(https?:\/\/|mailto:|\/(?!\/)|#)[^"]*" target="_blank" rel="noopener noreferrer nofollow")?$/i', $attributes, $whole);
            } else {
                $this->assertSame('', trim($attributes), $whole);
            }
        }
    }

    public function test_ordinary_formatting_is_kept(): void
    {
        $this->assertSame(
            '<p><strong>مرحبا</strong> <a href="https://example.com" target="_blank" rel="noopener noreferrer nofollow">رابط</a></p>',
            SafeHtml::description('<p><strong>مرحبا</strong> <a href="https://example.com">رابط</a></p>'),
        );
    }
}
