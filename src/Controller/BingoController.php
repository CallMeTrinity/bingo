<?php

namespace App\Controller;

use App\Entity\Bingo;
use App\Entity\BingoItem;
use App\Form\BingoType;
use App\Repository\BingoRepository;
use App\Service\BingoChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BingoController extends AbstractController
{
    private const GRID_SIZE = 16;

    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function home(Request $request, BingoRepository $br, BingoChecker $checker, EntityManagerInterface $em): Response
    {
        $bingo = new Bingo();
        $form = $this->createForm(BingoType::class, $bingo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            for ($position = 1; $position <= self::GRID_SIZE; $position++) {
                $item = new BingoItem();
                $item->setLabel('');
                $item->setPosition($position);
                $bingo->addBingoItem($item);
            }
            $em->persist($bingo);
            $em->flush();

            return $this->redirectToRoute('bingo_show', ['year' => $bingo->getYear()]);
        }

        $bingos = $br->findBy([], ['year' => 'DESC']);

        $entries = array_map(
            fn(Bingo $bingo) => $this->buildEntry($bingo, $checker),
            $bingos,
        );

        return $this->render('home.html.twig', [
            'entries' => $entries,
            'form' => $form,
            'openModal' => $form->isSubmitted(),
        ]);
    }

    #[Route('/bingo/{year}cd w  ', name: 'bingo_show')]
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

        return [
            'bingo' => $bingo,
            'completed' => count($completedPositions),
            'total' => count($bingo->getBingoItems()),
            'completedPositions' => $completedPositions,
            'completedLinesCount' => count($checker->getCompletedLines($bingo)) + count($checker->getCompletedColumns($bingo)),
            'hasBingo' => $checker->hasBingo($bingo),
        ];
    }
}
