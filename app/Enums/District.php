<?php

namespace App\Enums;

enum District: string
{
    case Odunpazari = 'odunpazari';
    case Tepebasi = 'tepebasi';
    case Alpu = 'alpu';
    case Beylikova = 'beylikova';
    case Cifteler = 'cifteler';
    case Gunyuzu = 'gunyuzu';
    case Han = 'han';
    case Inonu = 'inonu';
    case Mahmudiye = 'mahmudiye';
    case Mihalgazi = 'mihalgazi';
    case Mihaliccik = 'mihaliccik';
    case Saricakaya = 'saricakaya';
    case Seyitgazi = 'seyitgazi';
    case Sivrihisar = 'sivrihisar';

    public function label(): string
    {
        return match ($this) {
            self::Odunpazari => 'Odunpazarı',
            self::Tepebasi => 'Tepebaşı',
            self::Alpu => 'Alpu',
            self::Beylikova => 'Beylikova',
            self::Cifteler => 'Çifteler',
            self::Gunyuzu => 'Günyüzü',
            self::Han => 'Han',
            self::Inonu => 'İnönü',
            self::Mahmudiye => 'Mahmudiye',
            self::Mihalgazi => 'Mihalgazi',
            self::Mihaliccik => 'Mihalıççık',
            self::Saricakaya => 'Sarıcakaya',
            self::Seyitgazi => 'Seyitgazi',
            self::Sivrihisar => 'Sivrihisar',
        };
    }
}
