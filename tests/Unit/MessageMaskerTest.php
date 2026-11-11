<?php

namespace Tests\Unit;

use App\Support\MessageMasker;
use PHPUnit\Framework\TestCase;

class MessageMaskerTest extends TestCase
{
    public function test_phone_email_and_link_are_hidden(): void
    {
        $text = MessageMasker::mask('Call 0917 123 4567, mail me@mail.com or see https://example.com');

        $this->assertStringNotContainsString('0917', $text);
        $this->assertStringNotContainsString('me@mail.com', $text);
        $this->assertStringNotContainsString('https://', $text);
        $this->assertStringContainsString('[hidden until booking is confirmed]', $text);
    }

    public function test_normal_text_is_not_changed(): void
    {
        $this->assertSame('See you on Friday at 2 PM!', MessageMasker::mask('See you on Friday at 2 PM!'));
    }
}