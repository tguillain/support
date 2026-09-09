<?php

namespace Functional\Tickets\Attachments;

/**
 * What the product accepts as an attachment, declared once.
 *
 * Both boundaries that receive a file — the Livewire upload and the REST
 * resource — read their validation rules from here, so the limit cannot drift
 * between them.
 */
final class AttachmentConstraints
{
    private const MAX_KILOBYTES = 10240;

    /**
     * @var list<string>
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'text/csv',
        'text/plain',
    ];

    public static function maxKilobytes(): int
    {
        return self::MAX_KILOBYTES;
    }

    public static function maxBytes(): int
    {
        return self::MAX_KILOBYTES * 1024;
    }

    /**
     * @return list<string>
     */
    public static function allowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * The validation rules a file must satisfy, shared by every entry point.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return [
            'file',
            'max:'.self::MAX_KILOBYTES,
            'mimetypes:'.implode(',', self::ALLOWED_MIME_TYPES),
        ];
    }

    public static function accepts(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_MIME_TYPES, true);
    }
}
