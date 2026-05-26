<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MarkdownToHtml extends Command
{
    protected $signature = 'docs:html
                            {file : Path to the Persian markdown file (relative to project root or absolute)}
                            {--output= : Output HTML file path (default: same location as input, .html extension)}
                            {--open : Open the generated file in the default browser after generation}';

    protected $description = 'Convert a Persian (Farsi) Markdown documentation file to a beautiful RTL, print-friendly HTML page';

    public function handle(): int
    {
        $inputPath = $this->resolveInputPath($this->argument('file'));

        if (! File::exists($inputPath)) {
            $this->error("File not found: {$inputPath}");
            return self::FAILURE;
        }

        $markdown = File::get($inputPath);
        $outputPath = $this->resolveOutputPath($inputPath);

        $title = $this->extractTitle($markdown);
        $body  = $this->convertMarkdown($markdown);
        $html  = $this->buildHtmlPage($title, $body, basename($inputPath));

        File::put($outputPath, $html);

        $this->info("HTML generated: {$outputPath}");

        if ($this->option('open')) {
            $this->openInBrowser($outputPath);
        }

        return self::SUCCESS;
    }

    // ─── Path helpers ────────────────────────────────────────────────────────

    private function resolveInputPath(string $path): string
    {
        return $this->isAbsolutePath($path) ? $path : base_path($path);
    }

    private function resolveOutputPath(string $inputPath): string
    {
        if ($out = $this->option('output')) {
            return $this->isAbsolutePath($out) ? $out : base_path($out);
        }

        return preg_replace('/\.md$/i', '.html', $inputPath) ?: $inputPath . '.html';
    }

    private function isAbsolutePath(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return (bool) preg_match('/^[A-Za-z]:[\\\\\/]|^\\\\\\\\/', $path);
        }

        return str_starts_with($path, '/');
    }

    // ─── Markdown parser ─────────────────────────────────────────────────────

    private function extractTitle(string $markdown): string
    {
        if (preg_match('/^#\s+(.+)/m', $markdown, $m)) {
            return htmlspecialchars(trim($m[1]));
        }

        return 'مستندات';
    }

    private function convertMarkdown(string $markdown): string
    {
        $lines  = explode("\n", $markdown);
        $output = '';
        $i      = 0;
        $total  = count($lines);

        while ($i < $total) {
            $line = $lines[$i];

            // Fenced code block
            if (str_starts_with($line, '```')) {
                [$block, $consumed] = $this->parseCodeBlock($lines, $i);
                $output .= $block;
                $i += $consumed;
                continue;
            }

            // Headings
            if (preg_match('/^(#{1,6})\s+(.+)/', $line, $m)) {
                $level = strlen($m[1]);
                $text  = $this->inlineMarkdown(trim($m[2]));
                $output .= $this->renderHeading($level, $text);
                $i++;
                continue;
            }

            // Horizontal rule
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', trim($line))) {
                $output .= '<hr class="my-6 border-slate-200 print:border-slate-300">' . "\n";
                $i++;
                continue;
            }

            // Table
            if ($this->isTableRow($line) && isset($lines[$i + 1]) && $this->isTableSeparator($lines[$i + 1])) {
                [$table, $consumed] = $this->parseTable($lines, $i);
                $output .= $table;
                $i += $consumed;
                continue;
            }

            // Unordered list
            if (preg_match('/^(\s*[-*+])\s+(.+)/', $line)) {
                [$list, $consumed] = $this->parseList($lines, $i);
                $output .= $list;
                $i += $consumed;
                continue;
            }

            // Ordered list
            if (preg_match('/^\d+\.\s+(.+)/', $line)) {
                [$list, $consumed] = $this->parseOrderedList($lines, $i);
                $output .= $list;
                $i += $consumed;
                continue;
            }

            // Blockquote
            if (str_starts_with(ltrim($line), '>')) {
                [$bq, $consumed] = $this->parseBlockquote($lines, $i);
                $output .= $bq;
                $i += $consumed;
                continue;
            }

            // Blank line
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Paragraph
            [$para, $consumed] = $this->parseParagraph($lines, $i);
            $output .= $para;
            $i += $consumed;
        }

        return $output;
    }

    // ─── Block parsers ───────────────────────────────────────────────────────

    private function parseCodeBlock(array $lines, int $start): array
    {
        $lang    = trim(substr($lines[$start], 3));
        $content = '';
        $i       = $start + 1;

        while ($i < count($lines) && ! str_starts_with($lines[$i], '```')) {
            $content .= htmlspecialchars($lines[$i]) . "\n";
            $i++;
        }

        $langLabel = $lang ?: 'code';
        $langClass = $lang ? " language-{$lang}" : '';

        // Code blocks are always LTR
        $html = <<<HTML
<div class="my-5 rounded-xl overflow-hidden border border-slate-200 print:border-slate-300" dir="ltr">
  <div class="flex items-center gap-2 bg-slate-800 px-4 py-2 text-xs text-slate-400 font-mono">
    <span class="w-3 h-3 rounded-full bg-red-500/60"></span>
    <span class="w-3 h-3 rounded-full bg-yellow-500/60"></span>
    <span class="w-3 h-3 rounded-full bg-green-500/60"></span>
    <span class="ml-2">{$langLabel}</span>
  </div>
  <pre class="bg-slate-900 text-slate-100 p-4 text-sm overflow-x-auto leading-relaxed font-mono print:text-xs"><code class="{$langClass}">{$content}</code></pre>
</div>

HTML;

        return [$html, ($i - $start) + 1];
    }

    private function parseTable(array $lines, int $start): array
    {
        $headers = $this->parseTableCells($lines[$start]);
        $aligns  = $this->parseAlignments($lines[$start + 1]);
        $rows    = [];
        $i       = $start + 2;

        while ($i < count($lines) && $this->isTableRow($lines[$i])) {
            $rows[] = $this->parseTableCells($lines[$i]);
            $i++;
        }

        // Detect column semantic types by header text
        $colTypes = [];
        foreach ($headers as $idx => $header) {
            $colTypes[$idx] = $this->detectColumnType(trim($header));
        }

        $html  = '<div class="my-6 overflow-x-auto rounded-xl border border-slate-200 shadow-sm print:shadow-none print:border-slate-300">' . "\n";
        $html .= '<table class="w-full text-sm border-collapse">' . "\n";

        // Header row
        $html .= '<thead>' . "\n";
        $html .= '<tr class="bg-gradient-to-l from-slate-700 to-slate-800 print:bg-slate-700">' . "\n";

        foreach ($headers as $idx => $header) {
            $align = $this->alignClass($aligns[$idx] ?? 'right');
            $html .= "  <th class=\"px-4 py-3 text-xs font-semibold text-slate-200 uppercase tracking-wider {$align} whitespace-nowrap\">"
                . $this->inlineMarkdown(trim($header))
                . "</th>\n";
        }

        $html .= "</tr>\n</thead>\n";

        // Body
        $html .= '<tbody class="divide-y divide-slate-100 print:divide-slate-200">' . "\n";

        foreach ($rows as $rowIdx => $row) {
            $bg   = $rowIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50/60';
            $html .= "<tr class=\"{$bg} hover:bg-blue-50/40 transition-colors print:bg-white\">\n";

            foreach ($headers as $idx => $_) {
                $cell  = trim($row[$idx] ?? '');
                $align = $this->alignClass($aligns[$idx] ?? 'right');
                $type  = $colTypes[$idx] ?? '';
                $html .= "  <td class=\"px-4 py-2.5 {$align} align-top\">"
                    . $this->renderTableCell($cell, $type)
                    . "</td>\n";
            }

            $html .= "</tr>\n";
        }

        $html .= "</tbody>\n</table>\n</div>\n";

        return [$html, $i - $start];
    }

    private function parseList(array $lines, int $start): array
    {
        $html = '<ul class="my-4 mr-5 space-y-1.5 list-none">' . "\n";
        $i    = $start;

        while ($i < count($lines) && preg_match('/^(\s*[-*+])\s+(.+)/', $lines[$i], $m)) {
            $text = $this->inlineMarkdown(trim($m[2]));
            $html .= "  <li class=\"flex gap-2 text-slate-700\"><span class=\"mt-1.5 w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0\"></span><span>{$text}</span></li>\n";
            $i++;
        }

        $html .= "</ul>\n";

        return [$html, $i - $start];
    }

    private function parseOrderedList(array $lines, int $start): array
    {
        $html = '<ol class="my-4 mr-5 space-y-1.5" style="list-style-type: persian; direction: rtl;">' . "\n";
        $i    = $start;

        while ($i < count($lines) && preg_match('/^\d+\.\s+(.+)/', $lines[$i], $m)) {
            $text = $this->inlineMarkdown(trim($m[1]));
            $html .= "  <li class=\"text-slate-700 text-right pr-1\">{$text}</li>\n";
            $i++;
        }

        $html .= "</ol>\n";

        return [$html, $i - $start];
    }

    private function parseBlockquote(array $lines, int $start): array
    {
        $content = '';
        $i       = $start;

        while ($i < count($lines) && str_starts_with(ltrim($lines[$i]), '>')) {
            $content .= ltrim(preg_replace('/^>\s?/', '', $lines[$i])) . "\n";
            $i++;
        }

        $inner = $this->inlineMarkdown(trim($content));
        $html  = <<<HTML
<blockquote class="my-5 pr-4 border-r-4 border-blue-400 bg-blue-50 rounded-l-lg py-3 pl-4 text-slate-700 italic text-sm text-right print:border-blue-600">
  {$inner}
</blockquote>

HTML;

        return [$html, $i - $start];
    }

    private function parseParagraph(array $lines, int $start): array
    {
        $text  = '';
        $i     = $start;
        $skips = ['#', '|', '-', '*', '`', '>'];

        while ($i < count($lines)) {
            $line = $lines[$i];

            if (trim($line) === '') {
                break;
            }

            foreach ($skips as $skip) {
                if (str_starts_with(ltrim($line), $skip)) {
                    break 2;
                }
            }

            if (preg_match('/^\d+\.\s/', $line)) {
                break;
            }

            $text .= ($text ? ' ' : '') . trim($line);
            $i++;
        }

        if (! $text) {
            return ['', 1];
        }

        $html = '<p class="my-3 text-slate-700 leading-loose text-right">' . $this->inlineMarkdown($text) . "</p>\n";

        return [$html, $i - $start];
    }

    // ─── Heading renderer ────────────────────────────────────────────────────

    private function renderHeading(int $level, string $text): string
    {
        $id = 'h-' . preg_replace('/[^a-z0-9\x{0600}-\x{06FF}]+/u', '-', mb_strtolower(strip_tags($text)));

        return match ($level) {
            1 => <<<HTML
<h1 id="{$id}" class="text-3xl font-bold text-slate-900 mt-2 mb-6 pb-3 border-b-2 border-blue-500 text-right print:text-2xl">
  {$text}
</h1>

HTML,
            2 => <<<HTML
<h2 id="{$id}" class="text-xl font-bold text-slate-800 mt-8 mb-3 pb-2 border-b border-slate-200 flex items-center gap-2 print:mt-6">
  <span class="w-1 h-5 rounded-full bg-blue-500 inline-block shrink-0"></span>
  <span>{$text}</span>
</h2>

HTML,
            3 => <<<HTML
<h3 id="{$id}" class="text-base font-semibold text-slate-700 mt-6 mb-2 text-right print:mt-4">
  {$text}
</h3>

HTML,
            default => <<<HTML
<h{$level} id="{$id}" class="text-sm font-semibold text-slate-600 mt-4 mb-1 text-right">{$text}</h{$level}>

HTML,
        };
    }

    // ─── Inline markdown ─────────────────────────────────────────────────────

    private function inlineMarkdown(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Bold + italic
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/u', '<strong><em>$1</em></strong>', $text);
        // Bold
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong class="font-bold text-slate-900">$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/u', '<strong class="font-bold text-slate-900">$1</strong>', $text);
        // Italic (asterisk only — underscore is too common in identifiers)
        $text = preg_replace('/(?<!\*)\*(?!\*)([^*\n]+?)(?<!\*)\*(?!\*)/u', '<em class="italic text-slate-600">$1</em>', $text);
        // Italic underscore — only at non-word boundaries
        $text = preg_replace('/(?<!\w)_([^_\n]+?)_(?!\w)/u', '<em class="italic text-slate-600">$1</em>', $text);
        // Inline code — keep LTR for code fragments
        $text = preg_replace(
            '/`([^`]+)`/',
            '<code dir="ltr" class="inline-block px-1.5 py-0.5 rounded bg-slate-100 text-blue-700 font-mono text-[0.82em] border border-slate-200 print:bg-slate-50">$1</code>',
            $text
        );
        // Links
        $text = preg_replace(
            '/\[([^\]]+)]\(([^)]+)\)/u',
            '<a href="$2" class="text-blue-600 underline underline-offset-2 hover:text-blue-800">$1</a>',
            $text
        );
        // Strikethrough
        $text = preg_replace('/~~(.+?)~~/u', '<del class="text-slate-400">$1</del>', $text);

        return $text;
    }

    // ─── Table helpers ───────────────────────────────────────────────────────

    private function isTableRow(string $line): bool
    {
        return str_contains($line, '|');
    }

    private function isTableSeparator(string $line): bool
    {
        return (bool) preg_match('/^\|[-:\s|]+\|$/', trim($line));
    }

    private function parseTableCells(string $line): array
    {
        $line = preg_replace('/^\||\|$/', '', trim($line));
        return array_map('trim', explode('|', $line));
    }

    private function parseAlignments(string $separator): array
    {
        $cells  = $this->parseTableCells($separator);
        $aligns = [];

        foreach ($cells as $cell) {
            $cell = trim($cell);
            if (str_starts_with($cell, ':') && str_ends_with($cell, ':')) {
                $aligns[] = 'center';
            } elseif (str_starts_with($cell, ':')) {
                $aligns[] = 'right'; // In RTL, left-colon = right alignment
            } else {
                $aligns[] = 'right'; // RTL default
            }
        }

        return $aligns;
    }

    private function alignClass(string $align): string
    {
        return match ($align) {
            'center' => 'text-center',
            'left'   => 'text-left',
            default  => 'text-right',
        };
    }

    private function detectColumnType(string $header): string
    {
        $header = mb_strtolower($header);

        // Persian & English keywords
        $typeKeywords    = ['type', 'نوع', 'data type', 'نوع داده', 'column type'];
        $nullKeywords    = ['null', 'nullable', 'اجباری', 'خالی'];
        // NOTE: avoid broad terms like 'کلید' alone — it matches 'ستون کلید خارجی' (FK column name, not a key-type column)
        $keyKeywords     = ['key type', 'index type', 'نوع کلید', 'نوع ایندکس', 'ایندکس'];
        $defaultKeywords = ['default', 'پیش‌فرض', 'مقدار پیش‌فرض'];

        foreach ($typeKeywords as $kw) {
            if (str_contains($header, $kw)) {
                return 'type';
            }
        }

        foreach ($nullKeywords as $kw) {
            if (str_contains($header, $kw)) {
                return 'null';
            }
        }

        foreach ($keyKeywords as $kw) {
            if (str_contains($header, $kw)) {
                return 'key';
            }
        }

        foreach ($defaultKeywords as $kw) {
            if (str_contains($header, $kw)) {
                return 'default';
            }
        }

        return '';
    }

    private function renderTableCell(string $cell, string $colType): string
    {
        if ($cell === '' || $cell === '—' || $cell === '-') {
            return '<span class="text-slate-300 text-xs">—</span>';
        }

        $rendered = $this->inlineMarkdown($cell);

        if ($colType === 'type') {
            return $this->typeBadge($cell);
        }

        if ($colType === 'null') {
            $lower = mb_strtolower($cell);
            if (in_array($lower, ['yes', 'بله', 'بلی'])) {
                return '<span class="inline-flex items-center gap-1 text-amber-600 font-medium text-xs"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>' . $rendered . '</span>';
            }
            if (in_array($lower, ['no', 'خیر'])) {
                return '<span class="inline-flex items-center gap-1 text-emerald-600 font-medium text-xs"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>' . $rendered . '</span>';
            }
        }

        if ($colType === 'key') {
            return $this->keyBadge($cell);
        }

        return $rendered;
    }

    private function typeBadge(string $type): string
    {
        $lower = strtolower($type);
        $color = 'slate';

        if (str_contains($lower, 'bigint') || str_contains($lower, 'tinyint') || str_contains($lower, 'int')) {
            $color = 'violet';
        } elseif (str_contains($lower, 'varchar') || str_contains($lower, 'char') || str_contains($lower, 'text')) {
            $color = 'emerald';
        } elseif (str_contains($lower, 'timestamp') || str_contains($lower, 'datetime') || str_contains($lower, 'date')) {
            $color = 'blue';
        } elseif (str_contains($lower, 'decimal') || str_contains($lower, 'float') || str_contains($lower, 'double')) {
            $color = 'orange';
        } elseif (str_contains($lower, 'json')) {
            $color = 'pink';
        } elseif (str_contains($lower, 'enum') || str_contains($lower, 'set')) {
            $color = 'amber';
        }

        $classes = match ($color) {
            'violet'  => 'bg-violet-50  text-violet-700  border-violet-200',
            'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'blue'    => 'bg-blue-50    text-blue-700    border-blue-200',
            'orange'  => 'bg-orange-50  text-orange-700  border-orange-200',
            'pink'    => 'bg-pink-50    text-pink-700    border-pink-200',
            'amber'   => 'bg-amber-50   text-amber-700   border-amber-200',
            default   => 'bg-slate-50   text-slate-600   border-slate-200',
        };

        $escaped = htmlspecialchars($type);

        return "<code dir=\"ltr\" class=\"inline-block px-2 py-0.5 rounded-md border text-xs font-mono font-medium {$classes}\">{$escaped}</code>";
    }

    private function keyBadge(string $key): string
    {
        if (trim($key) === '') {
            return '<span class="text-slate-300 text-xs">—</span>';
        }

        $parts  = array_map('trim', explode('/', $key));
        $badges = [];

        foreach ($parts as $part) {
            $classes  = match (strtoupper($part)) {
                'PRI'   => 'bg-yellow-50 text-yellow-700 border-yellow-300',
                'UNI'   => 'bg-blue-50   text-blue-700   border-blue-300',
                'MUL'   => 'bg-purple-50 text-purple-700 border-purple-300',
                'FK'    => 'bg-rose-50   text-rose-700   border-rose-300',
                default => 'bg-slate-50  text-slate-600  border-slate-200',
            };
            $escaped  = htmlspecialchars($part);
            $badges[] = "<span dir=\"ltr\" class=\"inline-block px-1.5 py-0.5 rounded border text-[0.7rem] font-semibold font-mono {$classes}\">{$escaped}</span>";
        }

        return implode(' ', $badges);
    }

    // ─── HTML shell ──────────────────────────────────────────────────────────

    private function buildHtmlPage(string $title, string $body, string $sourceFile): string
    {

        return <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>

  <!-- Vazirmatn — best Persian web font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    /* Persian font as default */
    body, * {
      font-family: 'Vazirmatn', 'Tahoma', 'Arial', sans-serif;
    }

    /* Code/mono keeps Latin font */
    code, pre, kbd, samp, [dir="ltr"] {
      font-family: 'Cascadia Code', 'Fira Code', 'JetBrains Mono', 'Consolas', monospace;
    }

    /* Smooth custom scrollbar */
    ::-webkit-scrollbar        { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track  { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb  { background: #94a3b8; border-radius: 3px; }

    /* Persian numerals in ordered lists */
    ol[style*="persian"] { list-style-type: persian; }

    /* Print */
    @page {
      size: A4;
      margin: 18mm 15mm;
    }

    @media print {
      .no-print             { display: none !important; }
      body                  { background: white !important; }
      .print-full           { max-width: 100% !important; }
      thead                 { display: table-header-group; }
      tr                    { page-break-inside: avoid; }
      h2, h3                { page-break-after: avoid; }
      pre                   { white-space: pre-wrap; word-break: break-all; }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased min-h-screen">

  <!-- Top bar (RTL) -->
  <header class="no-print sticky top-0 z-10 bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-5xl mx-auto px-6 h-12 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="text-slate-300">/</span>
        <span class="text-slate-600 text-xs font-medium" dir="ltr">{$sourceFile}</span>
      </div>
      <button
        onclick="window.print()"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 transition-colors"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        چاپ / ذخیره PDF
      </button>
    </div>
  </header>

  <!-- Page -->
  <main class="max-w-5xl mx-auto px-6 py-10 print-full">
    <article class="bg-white rounded-2xl shadow-sm border border-slate-200 px-10 py-10 print:shadow-none print:border-none print:p-0">
      {$body}
    </article>
  </main>

</body>
</html>
HTML;
    }

    // ─── Browser opener ──────────────────────────────────────────────────────

    private function openInBrowser(string $path): void
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        match (PHP_OS_FAMILY) {
            'Windows' => exec("start \"\" \"{$path}\""),
            'Darwin'  => exec("open \"{$path}\""),
            default   => exec("xdg-open \"{$path}\""),
        };
    }
}
