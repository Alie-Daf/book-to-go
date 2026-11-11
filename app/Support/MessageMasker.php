<?php

namespace App\Support;

// Hides phone numbers, emails and links in chats until a booking is Confirmed (anti-leakage)
class MessageMasker
{
    private const HIDDEN = '[hidden until booking is confirmed]';

    public static function mask(string $text): string
    {
        $patterns = [
            // the words "GCash number" followed by digits, e.g. "gcash no. 0917 123 4567"
            '/gcash\s*(?:number|no\.?|#)?\s*[:\-]?\s*(?:\d[\s\-]?){6,}/i',
            // email addresses
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
            // links: http://..., https://... or www....
            '/(?:https?:\/\/|www\.)\S+/i',
            // Philippine mobile numbers: 09XXXXXXXXX or +639XXXXXXXXX (spaces and dashes allowed)
            '/(?:\+?63|0)[\s\-]?9(?:[\s\-]?\d){9}/',
        ];

        return preg_replace($patterns, self::HIDDEN, $text);
    }
}