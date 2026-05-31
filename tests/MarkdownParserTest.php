<?php

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

$parser = new App\MarkdownParser();
$passed = 0;
$failed = 0;

function test(string $name, string $input, string $expectedHtml, array $expectedMeta = []): void
{
    global $parser, $passed, $failed;
    $result = $parser->parse($input);

    $ok = true;
    $errors = [];

    if (trim($result['html']) !== trim($expectedHtml)) {
        $ok = false;
        $errors[] = "HTML mismatch";
        $errors[] = "  Expected: " . json_encode($expectedHtml);
        $errors[] = "  Got:      " . json_encode($result['html']);
    }

    foreach ($expectedMeta as $key => $val) {
        $got = $result['metadata'][$key] ?? '__MISSING__';
        if ($got !== $val) {
            $ok = false;
            $errors[] = "Meta '{$key}': expected " . json_encode($val) . ", got " . json_encode($got);
        }
    }

    if ($ok) {
        $passed++;
    } else {
        $failed++;
        echo "FAIL: {$name}\n";
        foreach ($errors as $e) echo "  {$e}\n";
    }
}

// ── Headers ──
test("h1", "# Title", "<h1>Title</h1>");
test("h2", "## Section", "<h2>Section</h2>");
test("h3", "### Subsection", "<h3>Subsection</h3>");
test("h4", "#### Sub-subsection", "<h4>Sub-subsection</h4>");
test("h5", "##### Heading 5", "<h5>Heading 5</h5>");
test("h6", "###### Heading 6", "<h6>Heading 6</h6>");
test("headers consecutive", "# A\n\n## B\n\n### C", "<h1>A</h1>\n\n<h2>B</h2>\n\n<h3>C</h3>");

// ── Paragraphs ──
test("single paragraph", "Hello world", "<p>Hello world</p>");
test("two paragraphs", "One\n\nTwo", "<p>One</p>\n<p>Two</p>");
test("paragraph with bold", "Hello **world**", '<p>Hello <strong>world</strong></p>');

// ── Bold ──
test("bold", "**bold**", "<p><strong>bold</strong></p>");
test("bold in sentence", "a **bold** word", '<p>a <strong>bold</strong> word</p>');

// ── Italic ──
test("italic", "*italic*", "<p><em>italic</em></p>");
test("italic in sentence", "a *italic* word", '<p>a <em>italic</em> word</p>');

// ── Bold + Italic ──
test("bold italic", "***bold italic***", "<p><strong><em>bold italic</em></strong></p>");

// ── Strikethrough ──
test("strikethrough", "~~deleted~~", "<p><del>deleted</del></p>");

// ── Inline code ──
test("inline code", "use `code` here", "<p>use <code>code</code> here</p>");

// ── Code blocks ──
test("code block no lang", "```\necho hi;\n```", "<pre><code>echo hi;\n</code></pre>");
test("code block with lang", "```php\necho hi;\n```", "<pre><code class=\"language-php\">echo hi;\n</code></pre>");
test("code block preserves html", "```\n<div>tag</div>\n```", "<pre><code>&lt;div&gt;tag&lt;/div&gt;\n</code></pre>");

// ── Blockquotes ──
test("blockquote >", "> cita", "<blockquote>cita</blockquote>");
test("blockquote > multiple", "> a\n> b", "<blockquote>a</blockquote>\n<blockquote>b</blockquote>");

// ── Horizontal rules ──
test("hr ---", "---", "<hr>");
test("hr ***", "***", "<hr>");
test("hr ___", "___", "<hr>");

// ── Unordered lists ──
test("ul single", "- one", "<ul><li>one</li></ul>");
test("ul multiple", "- a\n- b", "<ul><li>a</li><li>b</li></ul>");
test("ul with *", "* one\n* two", "<ul><li>one</li><li>two</li></ul>");

// ── Ordered lists ──
test("ol single", "1. one", "<ol><li>one</li></ol>");
test("ol multiple", "1. a\n2. b", "<ol><li>a</li><li>b</li></ol>");

// ── Links ──
test("link", "[text](url)", '<p><a href="url">text</a></p>');
test("link in sentence", "see [docs](guide.md)", '<p>see <a href="guide.md">docs</a></p>');

// ── Images ──
test("image", "![alt](img.png)", '<p><img src="img.png" alt="alt"></p>');

// ── Frontmatter ──
test("frontmatter title",
    "---\ntitle: Hola\n---\n\nBody",
    "<p>Body</p>",
    ['title' => 'Hola']
);

test("frontmatter tags",
    "---\ntags: [a, b, c]\n---\n\nBody",
    "<p>Body</p>",
    ['tags' => ['a', 'b', 'c']]
);

test("frontmatter all fields",
    "---\ntitle: T\n date: 2024-01-01\nauthor: Me\ntags: [x, y]\n---\n\n# Header",
    "<h1>Header</h1>",
    ['title' => 'T', 'author' => 'Me', 'tags' => ['x', 'y']]
);

// ── Boolean frontmatter ──
test("frontmatter boolean true",
    "---\nflag: true\n---\n\nBody",
    "<p>Body</p>",
    ['flag' => true]
);

test("frontmatter boolean false",
    "---\nflag: false\n---\n\nBody",
    "<p>Body</p>",
    ['flag' => false]
);

// ── Numeric frontmatter ──
test("frontmatter numeric",
    "---\ncount: 42\n---\n\nBody",
    "<p>Body</p>",
    ['count' => 42]
);

// ── Image metadata transforms ──
test("frontmatter image center",
    "---\ntitle: Doc\nimage: img.png\nimage_position: center\n---\n\n# Title",
    '<div class="content-image position-center"><img src="img.png" alt="Doc"></div>' . "\n<h1>Title</h1>",
    ['title' => 'Doc', 'image' => 'img.png', 'image_position' => 'center']
);

// ── Empty content ──
test("empty content", "", "");
test("only frontmatter", "---\ntitle: X\n---\n", "", ['title' => 'X']);

// ── HTML escaping ──
test("html special chars escaped",
    "a < b & c > d",
    "<p>a &lt; b &amp; c &gt; d</p>"
);

// ── Mixed content ──
test("mixed content",
    "# Title\n\nParagraph with **bold** and *italic*.\n\n- item 1\n- item 2",
    "<h1>Title</h1>\n\n<p>Paragraph with <strong>bold</strong> and <em>italic</em>.</p>\n\n<ul><li>item 1</li><li>item 2</li></ul>"
);

// ── Consecutive lists (ul then ol) ──
test("ul then ol",
    "- a\n- b\n\n1. c\n2. d",
    "<ul><li>a</li><li>b</li></ul>\n\n<ol><li>c</li><li>d</li></ol>"
);

// ── Link with %20 (already encoded) ──
test("link with encoded space",
    "[doc](file%20name.md)",
    '<p><a href="file%20name.md">doc</a></p>'
);

// ── UTF-8 content ──
test("utf-8 content", "# Café", "<h1>Café</h1>");

// ── Summary ──
echo "\n";
echo str_repeat('=', 50) . "\n";
echo "MarkdownParser Tests\n";
echo str_repeat('=', 50) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo str_repeat('=', 50) . "\n";
exit($failed > 0 ? 1 : 0);
