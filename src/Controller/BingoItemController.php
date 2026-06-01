<?php

namespace App\Controller;

use App\Entity\BingoItem;
use App\Form\BingoItemType;
use App\Service\BingoChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BingoItemController extends AbstractController
{
    #[Route('/bingo/{id}/check', name: 'bingo_check', methods: ['POST'])]
    public function check(BingoItem $item, EntityManagerInterface $em, BingoChecker $bingoService): Response
    {
        $bingo = $item->getBingo();
        if ($bingo === null || $bingo->isTrashed()) {
            throw $this->createNotFoundException('Bingo not found');
        }
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

    #[Route('/bingo/item/{id}/edit', name: 'bingo_item_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        BingoItem $item,
        EntityManagerInterface $em,
        BingoChecker $checker,
    ): Response {
        $bingo = $item->getBingo();
        if ($bingo === null || $bingo->isTrashed()) {
            throw $this->createNotFoundException('Bingo not found');
        }
        $form = $this->createForm(BingoItemType::class, $item, [
            'completed_default' => $item->getCompletedAt() !== null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isCompleted = (bool) $form->get('completed')->getData();
            if ($isCompleted && $item->getCompletedAt() === null) {
                $item->setCompletedAt(new \DateTimeImmutable());
            } elseif (!$isCompleted && $item->getCompletedAt() !== null) {
                $item->setCompletedAt(null);
            }

            $em->flush();

            $linePositions = $checker->getLinePositions($bingo);
            $completed = 0;
            foreach ($bingo->getBingoItems() as $i) {
                if ($i->getCompletedAt() !== null) {
                    $completed++;
                }
            }

            return $this->json([
                'cellHtml' => $this->renderView('bingo/_cell.html.twig', [
                    'item' => $item,
                    'linePositions' => $linePositions,
                ]),
                'stats' => [
                    'position' => $item->getPosition(),
                    'completed' => $completed,
                    'total' => count($bingo->getBingoItems()),
                    'completedLines' => count($checker->getCompletedLines($bingo)) + count($checker->getCompletedColumns($bingo)),
                    'linePositions' => $linePositions,
                ],
            ]);
        }

        $status = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return $this->render('bingo_item/edit.html.twig', [
            'form' => $form,
            'item' => $item,
        ], new Response(null, $status));
    }
}
