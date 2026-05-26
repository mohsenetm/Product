<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ScenarioToHtml extends Command
{
    protected $signature = 'scenario:to-html
                            {file? : مسیر فایل markdown (پیش‌فرض: scenarios/scenario-02-rejected-booking.fa.md)}
                            {--output= : مسیر فایل خروجی HTML (پیش‌فرض: کنار فایل ورودی)}';

    protected $description = 'تبدیل فایل Markdown سناریو به صفحه HTML با استایل Tailwind CSS';

    public function handle(): int
    {
        $inputPath = $this->argument('file')
            ?? base_path('scenarios/scenario-02-rejected-booking.fa.md');

        $inputPath = realpath($inputPath) ?: $inputPath;

        if (!File::exists($inputPath)) {
            $this->error("فایل یافت نشد: {$inputPath}");
            return self::FAILURE;
        }

        $outputPath = $this->option('output')
            ?? preg_replace('/\.md$/i', '.html', $inputPath);

        // Extract scenario number from filename, e.g. "scenario-02-..." → 2
        $imageName = null;
        if (preg_match('/scenario-0*(\d+)/i', basename($inputPath), $m)) {
            $imageName = $m[1] . '.png';   // "2.png", "3.png", ...
        }

        $markdown = File::get($inputPath);
        $html     = $this->renderHtmlPage($markdown, basename($inputPath, '.md'), $imageName);

        File::put($outputPath, $html);

        $this->info("✅  HTML ذخیره شد: {$outputPath}");
        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────
    //  HTML page wrapper
    // ─────────────────────────────────────────────────────────────
    private function renderHtmlPage(string $markdown, string $title, ?string $imageName = null): string
    {
        $body = $this->parseMarkdown($markdown);

        $heroImage = $imageName
            ? "<img src=\"./{$imageName}\" alt=\"{$title}\" class=\"w-full rounded-2xl shadow-md mb-8 object-cover\" />"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{$title}</title>
  <link rel="stylesheet" href="./tailwind.css">
  <script src="./tailwind.js"></script>

  <style>
    @font-face {
        font-family: 'IRANYekan';
        src: url('./iranyekanwebregular.woff2') format('woff2');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }

    body {
        font-family: 'IRANYekan', Tahoma, sans-serif;
    }

    pre code {
        font-family: 'Courier New', Consolas, monospace;
        direction: ltr;
        text-align: left;
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen py-10 px-4">
  <div class="max-w-4xl mx-auto">
    {$heroImage}
    {$body}
  </div>
</body>
</html>
HTML;
    }

    // ─────────────────────────────────────────────────────────────
    //  Markdown → HTML parser
    // ─────────────────────────────────────────────────────────────
    private function parseMarkdown(string $md): string
    {
        $lines  = explode("\n", $md);
        $output = '';
        $i      = 0;
        $total  = count($lines);

        while ($i < $total) {
            $line = $lines[$i];

            // ── Fenced code block ─────────────────────────────────
            if (preg_match('/^```(\w*)/', $line, $m)) {
                $lang      = strtolower($m[1] ?? '');
                $codeLines = [];
                $i++;
                while ($i < $total && !str_starts_with($lines[$i], '```')) {
                    $codeLines[] = htmlspecialchars($lines[$i], ENT_QUOTES);
                    $i++;
                }
                $code   = implode("\n", $codeLines);
                $label  = $lang ? "<span class=\"text-xs font-mono text-slate-400 uppercase tracking-widest\">{$lang}</span>" : '';
                $output .= <<<HTML
<div class="my-4 rounded-xl overflow-hidden border border-slate-200 shadow-sm">
  <div class="flex items-center justify-between bg-slate-800 px-4 py-2">{$label}<span class="w-2 h-2 rounded-full bg-red-400 ml-1 inline-block"></span></div>
  <pre dir="ltr" class="bg-slate-900 text-green-300 text-sm p-4 overflow-x-auto leading-relaxed"><code>{$code}</code></pre>
</div>
HTML;
                $i++;
                continue;
            }

            // ── Horizontal rule ───────────────────────────────────
            if (preg_match('/^---+\s*$/', $line)) {
                $output .= '<hr class="my-6 border-slate-200" />' . "\n";
                $i++;
                continue;
            }

            // ── Headings ──────────────────────────────────────────
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                $level   = strlen($m[1]);
                $text    = $this->parseInline($m[2]);
                $classes = match ($level) {
                    1 => 'text-3xl font-bold text-slate-900 mb-4 mt-2 pb-3 border-b-2 border-blue-500',
                    2 => 'text-2xl font-semibold text-slate-800 mb-3 mt-8',
                    3 => 'text-xl font-semibold text-blue-700 mb-2 mt-6',
                    4 => 'text-lg font-medium text-slate-700 mb-2 mt-4',
                    default => 'text-base font-medium text-slate-700 mb-1 mt-3',
                };
                $output .= "<h{$level} class=\"{$classes}\">{$text}</h{$level}>\n";
                $i++;
                continue;
            }

            // ── Blockquote ────────────────────────────────────────
            if (str_starts_with($line, '> ')) {
                $quoteLines = [];
                while ($i < $total && str_starts_with($lines[$i], '> ')) {
                    $quoteLines[] = $this->parseInline(substr($lines[$i], 2));
                    $i++;
                }
                $inner  = implode('<br />', $quoteLines);
                $output .= "<blockquote class=\"my-4 border-r-4 border-amber-400 bg-amber-50 pr-4 py-3 pl-3 rounded-lg text-slate-700 text-sm leading-7\">{$inner}</blockquote>\n";
                continue;
            }

            // ── Table ─────────────────────────────────────────────
            if (str_contains($line, '|') && isset($lines[$i + 1]) && preg_match('/^\|[-| :]+\|$/', trim($lines[$i + 1]))) {
                $tableRows = [];
                while ($i < $total && str_contains($lines[$i], '|')) {
                    $tableRows[] = $lines[$i];
                    $i++;
                }
                $output .= $this->renderTable($tableRows);
                continue;
            }

            // ── Unordered list ────────────────────────────────────
            if (preg_match('/^[-*+]\s+(.+)$/', $line, $m)) {
                $output .= '<ul class="list-disc list-inside my-3 space-y-1 text-slate-700">' . "\n";
                while ($i < $total && preg_match('/^[-*+]\s+(.+)$/', $lines[$i], $mm)) {
                    $output .= '<li class="leading-7">' . $this->parseInline($mm[1]) . '</li>' . "\n";
                    $i++; // ← always advances; loop exits when line no longer matches
                }
                $output .= "</ul>\n";
                continue;
            }

            // ── Ordered list ──────────────────────────────────────
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
                $output .= '<ol class="list-decimal list-inside my-3 space-y-1 text-slate-700">' . "\n";
                while ($i < $total && preg_match('/^\d+\.\s+(.+)$/', $lines[$i], $mm)) {
                    $output .= '<li class="leading-7">' . $this->parseInline($mm[1]) . '</li>' . "\n";
                    $i++; // ← always advances
                }
                $output .= "</ol>\n";
                continue;
            }

            // ── Blank line ────────────────────────────────────────
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // ── Paragraph ─────────────────────────────────────────
            // Exclude headings, blockquotes, TRUE list items ([-*+] followed by space),
            // ordered list items, fenced code fences, and horizontal rules.
            // NOTE: lines starting with * but NOT followed by a space (e.g. italic paragraphs)
            // must NOT be excluded — otherwise $i never advances and we loop forever.
            $paraLines = [];
            while (
                $i < $total
                && trim($lines[$i]) !== ''
                && !preg_match('/^#{1,6}\s/', $lines[$i])
                && !str_starts_with($lines[$i], '>')
                && !preg_match('/^[-*+]\s/', $lines[$i])   // real list item (marker + space)
                && !preg_match('/^\d+\.\s/', $lines[$i])
                && !str_starts_with($lines[$i], '```')
                && !preg_match('/^---+\s*$/', $lines[$i])
                && !str_contains($lines[$i], '|')          // table row
            ) {
                $paraLines[] = $this->parseInline($lines[$i]);
                $i++;
            }
            if ($paraLines) {
                $output .= '<p class="my-3 leading-8 text-slate-700">' . implode(' ', $paraLines) . '</p>' . "\n";
                continue;
            }

            // ── Safety fallback: nothing matched → skip line to avoid infinite loop ──
            $i++;
        }

        return $output;
    }

    // ─────────────────────────────────────────────────────────────
    //  Inline styles: bold, italic, inline-code, link
    // ─────────────────────────────────────────────────────────────
    private function parseInline(string $text): string
    {
        // inline code
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) {
            $code = htmlspecialchars($m[1], ENT_QUOTES);
            return "<code class=\"bg-slate-100 text-rose-600 font-mono text-sm px-1.5 py-0.5 rounded\">{$code}</code>";
        }, $text);

        // bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong class="font-semibold text-slate-900">$1</strong>', $text);

        // italic
        $text = preg_replace('/\*(.+?)\*/', '<em class="italic text-slate-600">$1</em>', $text);

        // links
        $text = preg_replace(
            '/\[(.+?)\]\((.+?)\)/',
            '<a href="$2" class="text-blue-600 underline hover:text-blue-800">$1</a>',
            $text
        );

        return $text;
    }

    // ─────────────────────────────────────────────────────────────
    //  Table renderer
    // ─────────────────────────────────────────────────────────────
    private function renderTable(array $rawRows): string
    {
        $rows     = [];
        $isHeader = true;

        foreach ($rawRows as $raw) {
            $raw = trim($raw);
            if (preg_match('/^[\|:\- ]+$/', $raw)) {
                // separator row — skip
                $isHeader = false;
                continue;
            }
            $cells = array_map('trim', explode('|', trim($raw, '|')));
            $rows[] = ['cells' => $cells, 'isHeader' => $isHeader];
        }

        $html  = '<div class="my-6 overflow-x-auto rounded-xl border border-slate-200 shadow-sm">';
        $html .= '<table class="w-full text-sm text-right">';
        $html .= '<thead class="bg-blue-600 text-white">';

        $headerDone = false;
        foreach ($rows as $row) {
            if ($row['isHeader'] && !$headerDone) {
                $html .= '<tr>';
                foreach ($row['cells'] as $cell) {
                    $html .= '<th class="px-4 py-3 font-semibold">' . $this->parseInline($cell) . '</th>';
                }
                $html .= '</tr>';
                $headerDone = true;
            }
        }

        $html .= '</thead><tbody>';
        $alt = false;
        foreach ($rows as $row) {
            if ($row['isHeader']) continue;
            $bg   = $alt ? 'bg-slate-50' : 'bg-white';
            $html .= "<tr class=\"{$bg} hover:bg-blue-50 transition-colors\">";
            foreach ($row['cells'] as $cell) {
                $html .= '<td class="px-4 py-3 border-t border-slate-100">' . $this->parseInline($cell) . '</td>';
            }
            $html .= '</tr>';
            $alt = !$alt;
        }

        $html .= '</tbody></table></div>';
        return $html;
    }
}
