<?php

namespace App\Enums;

/**
 * Define cómo se resuelve el bronce en la propagación del bracket (S2C+).
 */
enum ThirdPlaceMode: string
{
    case NoBronze = 'no_bronze';
    case ChampionCarriesBronze = 'champion_carries_bronze';
    case BronzeMatch = 'bronze_match';
    case DualBronze = 'dual_bronze';

    public function label(): string
    {
        return match ($this) {
            self::NoBronze => 'Sin bronce',
            self::ChampionCarriesBronze => 'Campeón arrastra bronce',
            self::BronzeMatch => 'Pelea por bronce (3° y 4°)',
            self::DualBronze => 'Bronce dual (sin pelea)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NoBronze => 'Solo 1° y 2° lugar. No hay tercer lugar.',
            self::ChampionCarriesBronze => 'Quien pierde contra el campeón obtiene el bronce automáticamente, sin combate extra.',
            self::BronzeMatch => 'Los dos semifinalistas perdedores pelean por 3° y 4° lugar.',
            self::DualBronze => 'Ambos semifinalistas perdedores reciben bronce, sin pelea adicional.',
        };
    }
}
