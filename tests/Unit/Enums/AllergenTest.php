<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Allergen;
use PHPUnit\Framework\TestCase;

class AllergenTest extends TestCase
{
    // 各caseのlabel()が正しい日本語を返す
    public function test_label_returns_japanese_name(): void
    {
        $this->assertEquals('えび', Allergen::Shrimp->label());
        $this->assertEquals('かに', Allergen::Crab->label());
        $this->assertEquals('くるみ', Allergen::Walnut->label());
        $this->assertEquals('小麦', Allergen::Wheat->label());
        $this->assertEquals('そば', Allergen::Buckwheat->label());
        $this->assertEquals('卵', Allergen::Egg->label());
        $this->assertEquals('乳', Allergen::Milk->label());
        $this->assertEquals('落花生', Allergen::Peanut->label());
    }

    // fromBitmask(41)で[Shrimp, Wheat, Egg]を返す
    public function test_from_bitmask_returns_matching_cases(): void
    {
        $cases = Allergen::fromBitmask(41); // 1 + 8 + 32

        $this->assertCount(3, $cases);
        $this->assertSame(Allergen::Shrimp, $cases[0]);
        $this->assertSame(Allergen::Wheat, $cases[1]);
        $this->assertSame(Allergen::Egg, $cases[2]);
    }

    // fromBitmask(0)で空配列を返す
    public function test_from_bitmask_returns_empty_for_zero(): void
    {
        $cases = Allergen::fromBitmask(0);

        $this->assertCount(0, $cases);
    }

    // labels(41)で['えび','小麦','卵']を返す
    public function test_labels_returns_japanese_labels(): void
    {
        $labels = Allergen::labels(41); // 1 + 8 + 32

        $this->assertEquals(['えび', '小麦', '卵'], $labels);
    }

    // 全caseが2の冪乗で一意である
    public function test_all_cases_have_unique_power_of_two_values(): void
    {
        $values = [];
        foreach (Allergen::cases() as $case) {
            // 2の冪乗かチェック（ビット1つだけがセット）
            $this->assertSame(0, $case->value & ($case->value - 1), "{$case->name} の値 {$case->value} は2の冪乗ではありません");
            $this->assertNotContains($case->value, $values, "{$case->name} の値 {$case->value} は重複しています");
            $values[] = $case->value;
        }
    }
}
