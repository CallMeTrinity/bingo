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

        $beforeLines = $bingoService->getCompletedLines($bingo);
        $beforeColumns = $bingoService->getCompletedColumns($bingo);
        $wasBingo = count($beforeLines) > 0 || count($beforeColumns) > 0;

        if ($item->getCompletedAt() === null) {
            $item->setCompletedAt(new \DateTimeImmutable());
        } else {
            $item->setCompletedAt(null);
        }

        $em->flush();

        $afterLines = $bingoService->getCompletedLines($bingo);
        $afterColumns = $bingoService->getCompletedColumns($bingo);
        $isBingo = count($afterLines) > 0 || count($afterColumns) > 0;

        $newLines = array_values(array_filter($afterLines, fn($l) => !in_array($l, $beforeLines, true)));
        $newColumns = array_values(array_filter($afterColumns, fn($c) => !in_array($c, $beforeColumns, true)));

        $newlyCompleted = [];
        foreach (array_merge($newLines, $newColumns) as $group) {
            $newlyCompleted = array_merge($newlyCompleted, $group);
        }
        $newlyCompleted = array_values(array_unique($newlyCompleted));

        return $this->json([
            'active' => $item->getCompletedAt() !== null,
            'newlyCompleted' => $newlyCompleted,
            'newlyBingo' => !$wasBingo && $isBingo,
        ]);
    }

}
