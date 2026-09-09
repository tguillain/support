<?php

namespace Functional\Tickets\Tests\Unit;

use Functional\Tickets\Attachments\AttachmentConstraints;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What the product accepts as an attachment is a pure decision — no disk, no
 * container — so it belongs in the Unit tier.
 */
class AttachmentConstraintsTest extends TestCase
{
    public function test_the_byte_limit_matches_the_kilobyte_limit(): void
    {
        $this->assertSame(
            AttachmentConstraints::maxKilobytes() * 1024,
            AttachmentConstraints::maxBytes(),
        );
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function mimeTypes(): array
    {
        return [
            'pdf' => ['application/pdf', true],
            'png' => ['image/png', true],
            'csv' => ['text/csv', true],
            'executable' => ['application/x-msdownload', false],
            'php script' => ['application/x-httpd-php', false],
            'zip archive' => ['application/zip', false],
        ];
    }

    #[DataProvider('mimeTypes')]
    public function test_it_accepts_only_the_declared_types(string $mimeType, bool $accepted): void
    {
        $this->assertSame($accepted, AttachmentConstraints::accepts($mimeType));
    }

    public function test_the_rules_carry_the_same_limit_and_types(): void
    {
        $rules = AttachmentConstraints::rules();

        $this->assertContains('file', $rules);
        $this->assertContains('max:'.AttachmentConstraints::maxKilobytes(), $rules);
        $this->assertContains(
            'mimetypes:'.implode(',', AttachmentConstraints::allowedMimeTypes()),
            $rules,
        );
    }

    public function test_no_executable_type_can_slip_into_the_allow_list(): void
    {
        foreach (AttachmentConstraints::allowedMimeTypes() as $mimeType) {
            $this->assertDoesNotMatchRegularExpression(
                '#(msdownload|x-httpd|x-sh|x-executable|octet-stream)#',
                $mimeType,
            );
        }
    }
}
