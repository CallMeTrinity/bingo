<?php

namespace App\DataFixtures;

use App\Entity\Bingo;
use App\Entity\BingoItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BingoFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $bingo = new Bingo();
        $bingo->setTitle('Test Bingo');
        $bingo->setSlug('test-bingo');
        $bingo->setYear(2026);
        $manager->persist($bingo);
        $items = [
            'Rencontrer un nouveau chat', 'Faire une séance de yoga', 'Tenir l\'équilibre (5s)', 'Faire un feu de camps',
            'Faire un don du sang', 'Lire un livre', 'Mettre un costume cravate', 'Nager 1KM d\'affilé',
            'Regarder le lever et le coucher du soleil le même jour', 'Apprendre 1 petit morceau au piano', 'Faire du bricolage', 'Écrire quelque chose (histoire, poème...)',
            'Faire le bingo 2027', 'Changer une roue', 'Faire des pancakes', 'Faire l\'équivalent en D+ de l\'Éverest'
        ];

        foreach ($items as $index => $itemLabel) {
            $bingoItem = new BingoItem();
            $bingoItem->setBingo($bingo);
            $bingoItem->setLabel($itemLabel);
            $bingoItem->setPosition($index);
            $manager->persist($bingoItem);
        }

        $manager->flush();
    }
}
