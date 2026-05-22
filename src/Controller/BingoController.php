<?php

namespace App\Controller;

use App\Repository\BingoRepository;
use App\Service\BingoChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BingoController extends AbstractController
{
    #[Route('/bingo/{year}', name: 'bingo_show')]
    public function index(int $year, BingoRepository $br, BingoChecker $checker): Response
    {
        $bingo = $br->findOneBy(['year' => $year]);
        if (!$bingo) {
            throw $this->createNotFoundException('Bingo not found');
        }

        $items = $bingo->getBingoItems();
        $completed = 0;
        foreach ($items as $item) {
            if ($item->getCompletedAt() !== null) {
                $completed++;
            }
        }

        return $this->render('bingo/index.html.twig', [
            'bingo' => $bingo,
            'completed' => $completed,
            'total' => count($items),
            'completedLinesCount' => count($checker->getCompletedLines($bingo)) + count($checker->getCompletedColumns($bingo)),
            'linePositions' => $checker->getLinePositions($bingo),
        ]);
    }
}
