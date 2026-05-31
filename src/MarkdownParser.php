<?php

namespace App;

class MarkdownParser
{
    private array $codeBlocks = [];

    public function parse(string $content): array
    {
        $metadata = [];
        $body = $content;

        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $metadata = $this->parseFrontmatter($matches[1]);
            $body = trim($matches[2]);
        }

        $html = $this->parseMarkdown($body);
        $html = $this->applyMetadataTransforms($html, $metadata);

        return [
            'metadata' => $metadata,
            'body'     => $body,
            'html'     => $html,
        ];
    }

    private function parseFrontmatter(string $yaml): array
    {
        $metadata = [];
        $lines = explode("\n", $yaml);
        $currentKey = null;

        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s*(.*)$/', $line, $m)) {
                $currentKey = $m[1];
                $value = trim($m[2]);

                if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                    $items = array_map('trim', explode(',', trim($value, '[]')));
                    $items = array_map(fn($i) => trim($i, '"\'"'), $items);
                    $metadata[$currentKey] = $items;
                } elseif ($value === 'true' || $value === 'yes') {
                    $metadata[$currentKey] = true;
                } elseif ($value === 'false' || $value === 'no') {
                    $metadata[$currentKey] = false;
                } elseif (is_numeric($value)) {
                    $metadata[$currentKey] = str_contains($value, '.') ? (float)$value : (int)$value;
                } else {
                    $metadata[$currentKey] = trim($value, '"\'');
                }
            } elseif (preg_match('/^\s+-\s+(.*)$/', $line, $m) && $currentKey !== null) {
                $metadata[$currentKey] ??= [];
                if (is_string($metadata[$currentKey])) {
                    $metadata[$currentKey] = [$metadata[$currentKey]];
                }
                $metadata[$currentKey][] = trim($m[1], '"\'');
            }
        }

        return $metadata;
    }

    private function parseMarkdown(string $text): string
    {
        $this->codeBlocks = [];
        $text = $this->extractCodeBlocks($text);
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = $this->parseBlockquotes($text);
        $text = $this->parseHeaders($text);
        $text = $this->parseHorizontalRules($text);
        $text = $this->parseLists($text);
        $text = $this->parseInlineFormatting($text);
        $text = $this->parseImages($text);
        $text = $this->parseLinks($text);
        $text = $this->parseParagraphs($text);
        $text = $this->restoreCodeBlocks($text);
        return $text;
    }

    private function extractCodeBlocks(string $text): string
    {
        return preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($m) {
            $i = count($this->codeBlocks);
            $lang = !empty($m[1]) ? ' class="language-' . $m[1] . '"' : '';
            $code = htmlspecialchars($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->codeBlocks[] = '<pre><code' . $lang . '>' . $code . '</code></pre>';
            return "%%%CODEBLOCK{$i}%%%";
        }, $text);
    }

    private function restoreCodeBlocks(string $text): string
    {
        foreach ($this->codeBlocks as $i => $html) {
            $text = str_replace("%%%CODEBLOCK{$i}%%%", $html, $text);
        }
        return $text;
    }

    private function parseImages(string $text): string
    {
        return preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);
    }

    private function parseLinks(string $text): string
    {
        return preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
    }

    private function parseHeaders(string $text): string
    {
        $text = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $text);
        $text = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $text);
        $text = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $text);
        $text = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $text);
        return $text;
    }

    private function parseBlockquotes(string $text): string
    {
        $text = preg_replace('/^&gt;\s*(.*)$/m', '<blockquote>$1</blockquote>', $text);
        $text = preg_replace('/^>\s*(.*)$/m', '<blockquote>$1</blockquote>', $text);
        return $text;
    }

    private function parseHorizontalRules(string $text): string
    {
        return preg_replace('/^(---|\*\*\*|___)\s*$/m', '<hr>', $text);
    }

    private function parseLists(string $text): string
    {
        $lines = explode("\n", $text);
        $inList = null;
        $result = [];
        $buffer = [];

        foreach ($lines as $line) {
            $ulMatch = [];
            $olMatch = [];
            $isUl = preg_match('/^(\s*)[*+-]\s+(.+)$/', $line, $ulMatch);
            $isOl = preg_match('/^(\s*)\d+\.\s+(.+)$/', $line, $olMatch);

            if ($isUl) {
                $type = 'ul';
                $content = $ulMatch[2];
            } elseif ($isOl) {
                $type = 'ol';
                $content = $olMatch[2];
            } else {
                $type = null;
            }

            if ($type !== null) {
                if ($inList !== $type && !empty($buffer)) {
                    $result[] = '<' . $inList . '>' . implode('', $buffer) . '</' . $inList . '>';
                    $buffer = [];
                }
                $inList = $type;
                $buffer[] = '<li>' . $content . '</li>';
            } else {
                if ($inList !== null && !empty($buffer)) {
                    $result[] = '<' . $inList . '>' . implode('', $buffer) . '</' . $inList . '>';
                    $buffer = [];
                }
                $inList = null;
                $result[] = $line;
            }
        }

        if ($inList !== null && !empty($buffer)) {
            $result[] = '<' . $inList . '>' . implode('', $buffer) . '</' . $inList . '>';
        }

        return implode("\n", $result);
    }

    private function parseParagraphs(string $text): string
    {
        $blocks = 'h[1-6]|ul|ol|li|blockquote|pre|hr|table|p|div';
        $text = preg_replace('/^(?!\s*<(?:' . $blocks . '))(.+)$/m', '<p>$1</p>', $text);
        $text = preg_replace('/<\/p>\s*<p>/', "</p>\n<p>", $text);
        return $text;
    }

    private function parseInlineFormatting(string $text): string
    {
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        return $text;
    }

    private function applyMetadataTransforms(string $html, array $metadata): string
    {
        if (!empty($metadata['image'])) {
            $position = $metadata['image_position'] ?? 'center';
            $classes = 'content-image position-' . htmlspecialchars($position);
            $imgTag = '<div class="' . $classes . '"><img src="' . htmlspecialchars($metadata['image']) . '" alt="' . htmlspecialchars($metadata['title'] ?? '') . '"></div>';

            if (!empty($metadata['image_wrapper'])) {
                $wrapper = htmlspecialchars($metadata['image_wrapper']);
                $imgTag = '<div class="' . $wrapper . '">' . $imgTag . '</div>';
            }

            $html = $imgTag . "\n" . $html;
        }

        if (!empty($metadata['div_class'])) {
            $html = '<div class="' . htmlspecialchars($metadata['div_class']) . '">' . $html . '</div>';
        }

        return $html;
    }

    public function getContentHash(array $files): string
    {
        $hash = '';
        foreach ($files as $file) {
            $hash .= $file['path'] . ':' . $file['mtime'] . '|';
        }
        return md5($hash);
    }
}
