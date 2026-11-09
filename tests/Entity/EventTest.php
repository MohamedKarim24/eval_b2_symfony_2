<?php

namespace App\Tests\Entity;

use App\Entity\Event;
use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    #[DataProvider('provideRemainingPlacesCases')]
    public function testGetRemainingPlaces(int $totalPlaces, int $participantCount, int $expectedRemaining): void
    {
        $event = new Event();
        $event->setPlaces($totalPlaces);

        for ($i = 0; $i < $participantCount; $i++) {
            $event->addParticipant(new User());
        }

        $this->assertSame($expectedRemaining, $event->getRemainingPlaces());
    }

    public static function provideRemainingPlacesCases(): array
    {
        return [
            'aucun inscrit' => [10, 0, 10],
            'quelques inscrits' => [10, 4, 6],
            'complet' => [5, 5, 0],
        ];
    }

    #[DataProvider('provideCategoryLabels')]
    public function testGetCategoryLabel(string $categoryValue, string $expectedLabel): void
    {
        $event = new Event();
        $event->setCategory($categoryValue);

        $this->assertSame($expectedLabel, $event->getCategoryLabel());
    }

    public static function provideCategoryLabels(): array
    {
        return [
            'Concert' => ['concert', 'Concert'],
            'Conférence' => ['conference', 'Conférence'],
            'Atelier' => ['atelier', 'Atelier'],
            'Sport' => ['sport', 'Sport'],
        ];
    }
}