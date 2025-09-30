<?php

namespace App\Utils;

class UnitConverter
{
    // Define base units and conversion factors
    const WEIGHT_BASE_UNIT = 'g'; // grams as base unit
    const VOLUME_BASE_UNIT = 'ml'; // milliliters as base unit
    const COUNT_BASE_UNIT = 'pcs'; // pieces as base unit

    // Weight conversion factors (to grams)
    const WEIGHT_CONVERSIONS = [
        'g' => 1,
        'gram' => 1,
        'grams' => 1,
        'kg' => 1000,
        'kilogram' => 1000,
        'kilograms' => 1000,
        'lb' => 453.592,
        'pound' => 453.592,
        'pounds' => 453.592,
        'oz' => 28.3495,
        'ounce' => 28.3495,
        'ounces' => 28.3495,
    ];

    // Volume conversion factors (to milliliters)
    const VOLUME_CONVERSIONS = [
        'ml' => 1,
        'milliliter' => 1,
        'milliliters' => 1,
        'l' => 1000,
        'liter' => 1000,
        'liters' => 1000,
        'cup' => 236.588,
        'cups' => 236.588,
        'tbsp' => 14.7868,
        'tablespoon' => 14.7868,
        'tablespoons' => 14.7868,
        'tsp' => 4.92892,
        'teaspoon' => 4.92892,
        'teaspoons' => 4.92892,
    ];

    // Count units (no conversion needed)
    const COUNT_CONVERSIONS = [
        'pcs' => 1,
        'piece' => 1,
        'pieces' => 1,
        'item' => 1,
        'items' => 1,
        'unit' => 1,
        'units' => 1,
    ];

    /**
     * Get all available units grouped by type
     */
    public static function getAllUnits(): array
    {
        return [
            'weight' => array_keys(self::WEIGHT_CONVERSIONS),
            'volume' => array_keys(self::VOLUME_CONVERSIONS),
            'count' => array_keys(self::COUNT_CONVERSIONS),
        ];
    }

    /**
     * Get unit type (weight, volume, count)
     */
    public static function getUnitType(string $unit): ?string
    {
        $unit = strtolower($unit);

        if (array_key_exists($unit, self::WEIGHT_CONVERSIONS)) {
            return 'weight';
        }

        if (array_key_exists($unit, self::VOLUME_CONVERSIONS)) {
            return 'volume';
        }

        if (array_key_exists($unit, self::COUNT_CONVERSIONS)) {
            return 'count';
        }

        return null;
    }

    /**
     * Convert quantity from one unit to another
     */
    public static function convert(float $quantity, string $fromUnit, string $toUnit): float
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);

        // If units are the same, no conversion needed
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        $fromType = self::getUnitType($fromUnit);
        $toType = self::getUnitType($toUnit);

        // Can only convert within the same type
        if ($fromType !== $toType || $fromType === null) {
            throw new \InvalidArgumentException("Cannot convert from {$fromUnit} to {$toUnit}: incompatible unit types");
        }

        // Get conversion factors
        $conversions = match ($fromType) {
            'weight' => self::WEIGHT_CONVERSIONS,
            'volume' => self::VOLUME_CONVERSIONS,
            'count' => self::COUNT_CONVERSIONS,
        };

        // Convert from source unit to base unit, then to target unit
        $baseQuantity = $quantity * $conversions[$fromUnit];
        return $baseQuantity / $conversions[$toUnit];
    }

    /**
     * Convert quantity to the base unit (for storage in database)
     */
    public static function convertToBaseUnit(float $quantity, string $fromUnit): array
    {
        $fromUnit = strtolower($fromUnit);
        $unitType = self::getUnitType($fromUnit);

        if ($unitType === null) {
            throw new \InvalidArgumentException("Unknown unit: {$fromUnit}");
        }

        $baseUnit = match ($unitType) {
            'weight' => self::WEIGHT_BASE_UNIT,
            'volume' => self::VOLUME_BASE_UNIT,
            'count' => self::COUNT_BASE_UNIT,
        };

        $baseQuantity = self::convert($quantity, $fromUnit, $baseUnit);

        return [
            'quantity' => $baseQuantity,
            'unit' => $baseUnit,
            'original_quantity' => $quantity,
            'original_unit' => $fromUnit
        ];
    }

    /**
     * Convert from base unit to display unit
     */
    public static function convertFromBaseUnit(float $baseQuantity, string $baseUnit, string $toUnit): float
    {
        return self::convert($baseQuantity, $baseUnit, $toUnit);
    }

    /**
     * Check if two units are compatible for conversion
     */
    public static function areUnitsCompatible(string $unit1, string $unit2): bool
    {
        $type1 = self::getUnitType($unit1);
        $type2 = self::getUnitType($unit2);

        return $type1 !== null && $type1 === $type2;
    }

    /**
     * Get suggested units for a given unit type
     */
    public static function getSuggestedUnits(string $unitType): array
    {
        return match (strtolower($unitType)) {
            'weight' => ['g', 'kg', 'lb', 'oz'],
            'volume' => ['ml', 'l', 'cup', 'tbsp', 'tsp'],
            'count' => ['pcs', 'piece', 'item'],
            default => [],
        };
    }

    /**
     * Format quantity with appropriate precision
     */
    public static function formatQuantity(float $quantity, string $unit): string
    {
        $unitType = self::getUnitType($unit);

        // Use appropriate decimal places based on unit type
        $decimals = match ($unitType) {
            'count' => 0, // No decimals for counting units
            'weight' => $quantity >= 1000 ? 2 : 3, // More precision for smaller weights
            'volume' => $quantity >= 1000 ? 2 : 3, // More precision for smaller volumes
            default => 2,
        };

        return number_format($quantity, $decimals, '.', '');
    }
}