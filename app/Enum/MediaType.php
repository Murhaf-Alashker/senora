<?php

namespace App\Enum;

enum MediaType:string
{
    case MP4 = 'mp4';
    case AVI = 'avi';
    case MOV = 'mov';
    case MPEG = 'mpeg';
    case JPG = 'jpg';
    case JPEG = 'jpeg';
    case PNG = 'png';
    case WEBP = 'webp';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function type(): array
    {
        return array_map(fn($case) => $case, ['images', 'videos', 'pdf']);
    }

    public static function images():array
    {
        return array_map(fn($case) => $case->value, [self::JPG, self::JPEG, self::PNG, self::WEBP]);
    }

    public static function videos():array
    {
        return array_map(fn($case) => $case->value, [self::AVI, self::MOV, self::MPEG, self::MP4]);
    }


}
