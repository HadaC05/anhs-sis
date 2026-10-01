<?php

namespace App\Support;

use Smalot\PdfParser\Page;

/** Keep one decoded string per PDF text command so empty strings cannot shift coordinates. */
class Sf2LayoutPdfPage extends Page
{
    public function getTextArray(?Page $page = null): array
    {
        $texts = [];
        $font = null;
        foreach ($this->extractDecodedRawData() as $command) {
            if ($command['o'] === 'Tf') {
                $font = $this->getFont(explode(' ', $command['c'])[0]);
            }
            if (! in_array($command['o'], ['Tj', 'TJ'], true)) {
                continue;
            }
            if (! is_array($command['c'])) {
                $texts[] = $command['c'];

                continue;
            }
            $text = '';
            foreach ($command['c'] as $part) {
                if ($part['t'] === '(') {
                    $text .= $part['c'];
                } elseif ($part['t'] === '<') {
                    $hex = preg_replace('/\s+/', '', $part['c']);
                    if (strlen($hex) % 2) {
                        $hex .= '0';
                    }
                    if ($hex !== '' && ctype_xdigit($hex)) {
                        $decoded = hex2bin($hex);
                        $text .= $font ? $font->decodeContent($decoded) : $decoded;
                    }
                }
            }
            $texts[] = $text;
        }

        return $texts;
    }
}
