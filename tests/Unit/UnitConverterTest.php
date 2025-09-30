<?php

namespace Tests\Unit;

use App\Utils\UnitConverter;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    public function test_weight_conversion_kg_to_g()
    {
        $result = UnitConverter::convert(1, 'kg', 'g');
        $this->assertEquals(1000, $result);
    }

    public function test_weight_conversion_g_to_kg()
    {
        $result = UnitConverter::convert(1000, 'g', 'kg');
        $this->assertEquals(1, $result);
    }

    public function test_weight_conversion_same_unit()
    {
        $result = UnitConverter::convert(100, 'g', 'g');
        $this->assertEquals(100, $result);
    }

    public function test_volume_conversion_l_to_ml()
    {
        $result = UnitConverter::convert(1, 'l', 'ml');
        $this->assertEquals(1000, $result);
    }

    public function test_count_conversion()
    {
        $result = UnitConverter::convert(5, 'pcs', 'piece');
        $this->assertEquals(5, $result);
    }

    public function test_incompatible_unit_conversion_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        UnitConverter::convert(100, 'g', 'ml');
    }

    public function test_convert_to_base_unit()
    {
        $result = UnitConverter::convertToBaseUnit(1, 'kg');

        $this->assertEquals(1000, $result['quantity']);
        $this->assertEquals('g', $result['unit']);
        $this->assertEquals(1, $result['original_quantity']);
        $this->assertEquals('kg', $result['original_unit']);
    }

    public function test_get_unit_type()
    {
        $this->assertEquals('weight', UnitConverter::getUnitType('kg'));
        $this->assertEquals('volume', UnitConverter::getUnitType('ml'));
        $this->assertEquals('count', UnitConverter::getUnitType('pcs'));
        $this->assertNull(UnitConverter::getUnitType('invalid'));
    }

    public function test_are_units_compatible()
    {
        $this->assertTrue(UnitConverter::areUnitsCompatible('kg', 'g'));
        $this->assertTrue(UnitConverter::areUnitsCompatible('l', 'ml'));
        $this->assertTrue(UnitConverter::areUnitsCompatible('pcs', 'piece'));
        $this->assertFalse(UnitConverter::areUnitsCompatible('kg', 'ml'));
    }

    public function test_format_quantity()
    {
        $this->assertEquals('1000', UnitConverter::formatQuantity(1000.123, 'pcs'));
        $this->assertEquals('1.123', UnitConverter::formatQuantity(1.123, 'g'));
        $this->assertEquals('1000.12', UnitConverter::formatQuantity(1000.123, 'kg'));
    }
}