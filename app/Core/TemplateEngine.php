<?php
namespace App\Core;

/**
 * Safe, logic-less template engine (a Mustache subset).
 *
 * It NEVER executes PHP or arbitrary code — templates are plain text with
 * a small token vocabulary, so an uploaded/custom design can be rendered
 * for a tenant without any security risk of code execution.
 *
 * Supported syntax:
 *   {{ name }}          variable, HTML-escaped
 *   {{ a.b }}           dotted path lookup (walks the context stack)
 *   {{{ name }}}        raw/unescaped variable  (also {{& name}})
 *   {{# section }} ...{{/ section }}
 *                       - if value is a list  -> repeat block per item
 *                       - if value is a map   -> render block once with it
 *                       - if value is truthy  -> render block once
 *                       - otherwise           -> skip
 *   {{^ section }} ...{{/ section }}
 *                       inverted: render only when value is empty/falsy
 *   {{! comment }}      ignored
 *   {{.}}               the current scalar item inside a list section
 */
class TemplateEngine
{
    public static function render(string $template, array $data): string
    {
        $tokens = self::tokenize($template);
        [$tree] = self::parse($tokens, 0, null);
        return self::renderNodes($tree, [$data]);
    }

    // ---- tokenizer ----

    private static function tokenize(string $tmpl): array
    {
        $pattern = '/\{\{\{\s*[\w.]+\s*\}\}\}|\{\{\s*[#\/^&!]?\s*[\w.]+\s*\}\}|\{\{\s*\.\s*\}\}/';
        $tokens = [];
        $offset = 0;
        if (preg_match_all($pattern, $tmpl, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as [$tag, $pos]) {
                if ($pos > $offset) {
                    $tokens[] = ['t' => 'text', 'v' => substr($tmpl, $offset, $pos - $offset)];
                }
                $tokens[] = self::classify($tag);
                $offset = $pos + strlen($tag);
            }
        }
        if ($offset < strlen($tmpl)) {
            $tokens[] = ['t' => 'text', 'v' => substr($tmpl, $offset)];
        }
        return $tokens;
    }

    private static function classify(string $tag): array
    {
        if (str_starts_with($tag, '{{{')) {
            return ['t' => 'var', 'raw' => true, 'key' => trim($tag, "{} \t")];
        }
        $inner = trim(substr($tag, 2, -2)); // strip {{ }}
        $sigil = $inner[0] ?? '';
        return match ($sigil) {
            '#'     => ['t' => 'open',     'key' => trim(substr($inner, 1)), 'inverted' => false],
            '^'     => ['t' => 'open',     'key' => trim(substr($inner, 1)), 'inverted' => true],
            '/'     => ['t' => 'close',    'key' => trim(substr($inner, 1))],
            '&'     => ['t' => 'var',      'raw' => true, 'key' => trim(substr($inner, 1))],
            '!'     => ['t' => 'comment'],
            default => ['t' => 'var',      'raw' => false, 'key' => $inner],
        };
    }

    // ---- parser (token list -> nested tree) ----

    private static function parse(array $tokens, int $i, ?string $stopKey): array
    {
        $nodes = [];
        $n = count($tokens);
        while ($i < $n) {
            $tok = $tokens[$i];
            if ($tok['t'] === 'close') {
                return [$nodes, $i + 1]; // consume the matching close
            }
            if ($tok['t'] === 'open') {
                [$children, $next] = self::parse($tokens, $i + 1, $tok['key']);
                $nodes[] = [
                    't' => 'section',
                    'key' => $tok['key'],
                    'inverted' => $tok['inverted'],
                    'children' => $children,
                ];
                $i = $next;
                continue;
            }
            if ($tok['t'] !== 'comment') {
                $nodes[] = $tok;
            }
            $i++;
        }
        return [$nodes, $i];
    }

    // ---- renderer ----

    private static function renderNodes(array $nodes, array $stack): string
    {
        $out = '';
        foreach ($nodes as $node) {
            switch ($node['t']) {
                case 'text':
                    $out .= $node['v'];
                    break;
                case 'var':
                    $val = self::lookup($stack, $node['key']);
                    if (is_scalar($val)) {
                        $s = (string) $val;
                        $out .= $node['raw'] ? $s : htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    }
                    break;
                case 'section':
                    $out .= self::renderSection($node, $stack);
                    break;
            }
        }
        return $out;
    }

    private static function renderSection(array $node, array $stack): string
    {
        $val = self::lookup($stack, $node['key']);
        $truthy = self::isTruthy($val);

        if ($node['inverted']) {
            return $truthy ? '' : self::renderNodes($node['children'], $stack);
        }
        if (!$truthy) {
            return '';
        }
        // list of items -> repeat
        if (is_array($val) && array_is_list($val)) {
            $out = '';
            foreach ($val as $item) {
                $frame = is_array($item) ? $item : ['.' => $item];
                $out .= self::renderNodes($node['children'], [...$stack, $frame]);
            }
            return $out;
        }
        // associative map -> render once with it pushed
        if (is_array($val)) {
            return self::renderNodes($node['children'], [...$stack, $val]);
        }
        // truthy scalar/bool -> render once in current context
        return self::renderNodes($node['children'], $stack);
    }

    private static function lookup(array $stack, string $key): mixed
    {
        $key = trim($key);
        if ($key === '.') {
            $top = end($stack);
            return is_array($top) ? ($top['.'] ?? null) : $top;
        }
        $parts = explode('.', $key);
        $first = $parts[0];
        // find the nearest frame (top-down) that has the first segment
        $val = null;
        for ($i = count($stack) - 1; $i >= 0; $i--) {
            if (is_array($stack[$i]) && array_key_exists($first, $stack[$i])) {
                $val = $stack[$i][$first];
                break;
            }
            if ($i === 0) {
                return null;
            }
        }
        // descend remaining segments
        foreach (array_slice($parts, 1) as $seg) {
            if (is_array($val) && array_key_exists($seg, $val)) {
                $val = $val[$seg];
            } else {
                return null;
            }
        }
        return $val;
    }

    private static function isTruthy(mixed $val): bool
    {
        if ($val === null || $val === false || $val === '' || $val === 0 || $val === '0') {
            return false;
        }
        if (is_array($val)) {
            return count($val) > 0;
        }
        return true;
    }
}
