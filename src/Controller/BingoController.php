<?php

namespace App\Controller;

use App\Entity\Bingo;
use App\Repository\BingoRepository;
use App\Service\BingoChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BingoController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(BingoRepository $br, BingoChecker $checker): Response
    {
        $bingos = $br->findBy([], ['year' => 'DESC']);

        $entries = array_map(
            fn(Bingo $bingo) => $this->buildEntry($bingo, $checker),
            $bingos,
        );

        return $this->render('home.html.twig', [
            'entries' => $entries,
        ]);
    }

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

    /**
     * @return array{bingo: Bingo, completed: int, total: int, completedPositions: int[], completedLinesCount: int, hasBingo: bool}
     */
    private function buildEntry(Bingo $bingo, BingoChecker $checker): array
    {
        $completedPositions = $checker->getCompletedPositions($bingo);
        $completedLinesCount = count($checker->getCompletedLines($bingo))
            + count($checker->getCompletedColumns($bingo))
            + count($checker->getCompletedDiagonals($bingo));

        return [
            'bingo' => $bingo,
            'completed' => count($completedPositions),
            'total' => count($bingo->getBingoItems()),
            'completedPositions' => $completedPositions,
            'completedLinesCount' => $completedLinesCount,
            'hasBingo' => $checker->hasBingo($bingo),
        ];
    }
}
