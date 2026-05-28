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
        $bingos = [
            ['title' => 'Bingo 2024', 'slug' => 'y2024', 'year' => 2024, 'items' => [
                'Rencontrer un nouveau chat', 'Faire une séance de yoga', 'Tenir l\'équilibre (5s)', 'Faire un feu de camp',
                'Faire un don du sang', 'Lire un livre', 'Mettre un costume cravate', 'Nager 1KM d\'affilé',
                'Regarder le lever et le coucher du soleil le même jour', 'Apprendre 1 petit morceau au piano',
                'Faire du bricolage', 'Écrire quelque chose (histoire, poème...)',
                'Changer une roue', 'Faire des pancakes', 'Faire l\'équivalent en D+ de l\'Éverest', 'Partir en voyage',
            ]],
            ['title' => 'Bingo 2025', 'slug' => 'y2025', 'year' => 2025, 'items' => [
                'Faire une randonnée de 20km', 'Apprendre une nouvelle langue', 'Cuisiner un plat exotique', 'Planter un arbre',
                'Faire du bénévolat', 'Visiter un musée', 'Apprendre à méditer', 'Faire du kayak',
                'Regarder une aurore boréale', 'Faire un puzzle de 1000 pièces', 'Apprendre à tricoter', 'Tenir un journal pendant 30 jours',
                'Faire une sortie escalade', 'Préparer un repas de chef', 'Nager en eau froide', 'Aller à un concert',
            ]],
            ['title' => 'Bingo 2026', 'slug' => 'y2026', 'year' => 2026, 'items' => [
                'Faire du surf', 'Apprendre la guitare', 'Faire un séjour en montagne', 'Participer à un marathon',
                'Faire une retraite numérique (1 semaine)', 'Visiter 3 pays différents', 'Apprendre à faire du pain', 'Faire de la plongée',
                'Lire 12 livres en un an', 'Faire un road trip', 'Apprendre à dessiner', 'Faire du trail',
                'Monter une tente et dormir dehors', 'Faire un cours de cuisine', 'Apprendre à nager le crawl', 'Participer à un quiz',
            ]],
            ['title' => 'Bingo 2027', 'slug' => 'y2027', 'year' => 2027, 'items' => [
                'Faire le bingo 2027', 'Faire un voyage en train longue distance', 'Apprendre à faire du vélo de route', 'Visiter la Tour Eiffel',
                'Faire un tour de France à vélo (étape)', 'Courir un semi-marathon', 'Apprendre à coder', 'Faire une randonnée en raquettes',
                'Faire un voyage en bateau', 'Apprendre la photographie', 'Faire du parapente', 'Écrire un court métrage',
                'Participer à un hackathon', 'Faire une retraite yoga', 'Apprendre à faire du skate', 'Construire un meuble soi-même',
            ]],
        ];

        foreach ($bingos as $bingoData) {
            $bingo = $this->createBingo($bingoData['title'], $bingoData['slug'], $bingoData['year']);
            $manager->persist($bingo);

            foreach ($bingoData['items'] as $position => $label) {
                $bingoItem = $this->createBingoItem($label, $position + 1);
                $bingo->addBingoItem($bingoItem);
                $manager->persist($bingoItem);
            }
        }

        $manager->flush();
    }

    private function createBingo(string $title, string $slug, int $year): Bingo
    {
        $bingo = new Bingo();
        $bingo->setTitle($title);
        $bingo->setSlug($slug);
        $bingo->setYear($year);
        $this->addReference('bingo-' . $year, $bingo);
        return $bingo;
    }

    private function createBingoItem(string $label, int $position): BingoItem
    {
        $bingoItem = new BingoItem();
        $bingoItem->setLabel($label);
        $bingoItem->setPosition($position);
        return $bingoItem;
    }
}
