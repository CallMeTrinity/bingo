<?php

namespace App\Controller;

use App\Service\BingoChecker;
use App\Entity\BingoItem;
use App\Repository\BingoItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BingoItemController extends AbstractController
{
    #[Route('/bingo/{id}/check', name: 'bingo_check', methods: ['POST'])]
    public function check(BingoItem $item, EntityManagerInterface $em, BingoChecker $bingoService): Response
    {
        $bingo = $item->getBingo();
        $wasDone = $item->getCompletedAt() !== null;

        if ($wasDone) {
            $item->setCompletedAt(null);
        } else {
            $item->setCompletedAt(new \DateTimeImmutable());
        }

        $em->flush();

        $linePositions = $bingoService->getLinePositions($bingo);
        $completedLines = count($bingoService->getCompletedLines($bingo)) + count($bingoService->getCompletedColumns($bingo));
        $completed = 0;
        foreach ($bingo->getBingoItems() as $i) {
            if ($i->getCompletedAt() !== null) {
                $completed++;
            }
        }

        return $this->json([
            'active' => !$wasDone,
            'linePositions' => $linePositions,
            'completedLines' => $completedLines,
            'completed' => $completed,
            'total' => count($bingo->getBingoItems()),
        ]);
    }

}
