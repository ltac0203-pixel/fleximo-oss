<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AllergenAdvisory;
use PHPUnit\Framework\TestCase;

class AllergenAdvisoryTest extends TestCase
{
    // 各caseのlabel()が正しい日本語を返す
    public function test_label_returns_japanese_name(): void
    {
        $this->assertEquals('アーモンド', AllergenAdvisory::Almond->label());
        $this->assertEquals('あわび', AllergenAdvisory::Abalone->label());
        $this->assertEquals('いか', AllergenAdvisory::Squid->label());
        $this->assertEquals('いくら', AllergenAdvisory::Salmon_roe->label());
        $this->assertEquals('オレンジ', AllergenAdvisory::Orange->label());
        $this->assertEquals('カシューナッツ', AllergenAdvisory::Cashew->label());
        $this->assertEquals('キウイフルーツ', AllergenAdvisory::Kiwi->label());
        $this->assertEquals('牛肉', AllergenAdvisory::Beef->label());
        $this->assertEquals('ごま', AllergenAdvisory::Sesame->label());
        $this->assertEquals('さけ', AllergenAdvisory::Salmon->label());
        $this->assertEquals('さば', AllergenAdvisory::Mackerel->label());
        $this->assertEquals('大豆', AllergenAdvisory::Soybean->label());
        $this->assertEquals('鶏肉', AllergenAdvisory::Chicken->label());
        $this->assertEquals('バナナ', AllergenAdvisory::Banana->label());
        $this->assertEquals('豚肉', AllergenAdvisory::Pork->label());
        $this->assertEquals('まつたけ', AllergenAdvisory::Matsutake->label());
        $this->assertEquals('もも', AllergenAdvisory::Peach->label());
        $this->assertEquals('やまいも', AllergenAdvisory::Yam->label());
        $this->assertEquals('りんご', AllergenAdvisory::Apple->label());
        $this->assertEquals('ゼラチン', AllergenAdvisory::Gelatin->label());
    }

    // fromBitmaskが正しいcaseを返す
    public function test_from_bitmask_returns_matching_cases(): void
    {
        $cases = AllergenAdvisory::fromBitmask(2048 | 16384); // 大豆 + 豚肉

        $this->assertCount(2, $cases);
        $this->assertSame(AllergenAdvisory::Soybean, $cases[0]);
        $this->assertSame(AllergenAdvisory::Pork, $cases[1]);
    }

    // labelsが正しい日本語ラベルを返す
    public function test_labels_returns_japanese_labels(): void
    {
        $labels = AllergenAdvisory::labels(2048 | 16384); // 大豆 + 豚肉

        $this->assertEquals(['大豆', '豚肉'], $labels);
    }

    // 全caseが2の冪乗で一意である
    public function test_all_cases_have_unique_power_of_two_values(): void
    {
        $values = [];
        foreach (AllergenAdvisory::cases() as $case) {
            // 2の冪乗かチェック（ビット1つだけがセット）
            $this->assertSame(0, $case->value & ($case->value - 1), "{$case->name} の値 {$case->value} は2の冪乗ではありません");
            $this->assertNotContains($case->value, $values, "{$case->name} の値 {$case->value} は重複しています");
            $values[] = $case->value;
        }
    }
}
