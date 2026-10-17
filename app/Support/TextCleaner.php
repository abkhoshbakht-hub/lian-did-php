<?php

namespace App\Support;

class TextCleaner
{
    /**
     * Removes the English agency tags and stray punctuation Iranian feeds
     * append to titles, while keeping Persian titles untouched.
     */
    public static function title(string $title): string
    {
        $cleaned = preg_replace(
            '/[A-Za-z0-9\x{0660}-\x{0669}\x{06F0}-\x{06F9}]*[A-Za-z][A-Za-z0-9\x{0660}-\x{0669}\x{06F0}-\x{06F9}]*/u',
            '',
            $title
        ) ?? $title;

        $cleaned = preg_replace('/\(\s*\)|\[\s*\]|"\s*"|«\s*»/u', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s{2,}/u', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/^[\s\-\x{2013}\x{2014}:»«,\|\/\\\\]+/u', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/[\s\-\x{2013}\x{2014}:»«,\|\/\\\\]+$/u', '', $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);

        return preg_match('/[\p{L}\p{N}]/u', $cleaned) ? $cleaned : trim($title);
    }

    /** Strips tags and caps the length so the database stays small. */
    public static function description(string $html, int $max = 400): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
            // cut on a word boundary
            $sp = mb_strrpos($text, ' ');
            if ($sp !== false && $sp > $max * 0.6) $text = mb_substr($text, 0, $sp);
            $text .= '…';
        }

        return $text;
    }
}
