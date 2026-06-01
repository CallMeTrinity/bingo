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
    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function home(Request $request, BingoRepository $br, BingoChecker $checker, EntityManagerInterface $em): Response
    {
        $bingo = new Bingo();
        $form = $this->createForm(BingoType::class, $bingo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cellCount = $bingo->getSize() ** 2;
            for ($position = 1; $position <= $cellCount; $position++) {
                $item = new BingoItem();
                $item->setLabel('');
                $item->setPosition($position);
                $bingo->addBingoItem($item);
            }
            $em->persist($bingo);
            $em->flush();

            return $this->redirectToRoute('bingo_show', ['slug' => $bingo->getSlug()]);
        }

        $entries = array_map(
            fn(Bingo $bingo) => $this->buildEntry($bingo, $checker),
            $br->findActive(),
        );

        return $this->render('home.html.twig', [
            'entries' => $entries,
            'trashCount' => $br->countTrashed(),
            'form' => $form,
            'openModal' => $form->isSubmitted(),
        ]);
    }

    #[Route('/corbeille', name: 'bingo_trash', methods: ['GET'])]
    public function trash(BingoRepository $br, BingoChecker $checker): Response
    {
        $entries = array_map(
            fn(Bingo $bingo) => $this->buildEntry($bingo, $checker),
            $br->findTrashed(),
        );

        return $this->render('bingo/trash.html.twig', [
            'entries' => $entries,
        ]);
    }

    #[Route('/bingo/{slug}/delete', name: 'bingo_delete', methods: ['POST'])]
    public function delete(string $slug, Request $request, BingoRepository $br, EntityManagerInterface $em): Response
    {
        $bingo = $br->findOneActiveBySlug($slug);
        if (!$bingo) {
            throw $this->createNotFoundException('Bingo not found');
        }

        if (!$this->isCsrfTokenValid('delete_bingo_'.$bingo->getSlug(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $bingo->setDeletedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', sprintf('Bingo « %s » envoyé à la corbeille.', $bingo->getTitle()));

        return $this->redirectToRoute('app_home');
    }

    #[Route('/bingo/{slug}/restore', name: 'bingo_restore', methods: ['POST'])]
    public function restore(string $slug, Request $request, BingoRepository $br, EntityManagerInterface $em): Response
    {
        $bingo = $br->findOneTrashedBySlug($slug);
        if (!$bingo) {
            throw $this->createNotFoundException('Bingo not found');
        }

        if (!$this->isCsrfTokenValid('restore_bingo_'.$bingo->getSlug(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $bingo->setDeletedAt(null);
        $em->flush();

        $this->addFlash('success', sprintf('Bingo « %s » restauré.', $bingo->getTitle()));

        return $this->redirectToRoute('bingo_trash');
    }

    #[Route('/bingo/{slug}/destroy', name: 'bingo_destroy', methods: ['POST'])]
    public function destroy(string $slug, Request $request, BingoRepository $br, EntityManagerInterface $em): Response
    {
        $bingo = $br->findOneTrashedBySlug($slug);
        if (!$bingo) {
            throw $this->createNotFoundException('Bingo not found');
        }

        if (!$this->isCsrfTokenValid('destroy_bingo_'.$bingo->getSlug(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $title = $bingo->getTitle();
        $em->remove($bingo);
        $em->flush();

        $this->addFlash('success', sprintf('Bingo « %s » supprimé définitivement.', $title));

        return $this->redirectToRoute('bingo_trash');
    }

    #[Route('/bingo/{slug}', name: 'bingo_show')]
    public function index(string $slug, BingoRepository $br, BingoChecker $checker): Response
    {
        $bingoData = $this->loadBingoView($slug, $br);

        return $this->render('bingo/index.html.twig', [
            ...$bingoData,
            'completedLinesCount' => count($checker->getCompletedLines($bingoData['bingo'])) + count($checker->getCompletedColumns($bingoData['bingo'])),
            'linePositions' => $checker->getLinePositions($bingoData['bingo']),
        ]);
    }

    #[Route('/b/{slug}', name: 'bingo_share')]
    public function share(string $slug, BingoRepository $br, BingoChecker $checker): Response
    {
        $bingoData = $this->loadBingoView($slug, $br);

        return $this->render('bingo/share.html.twig', [
            ...$bingoData,
            'completedLinesCount' => count($checker->getCompletedLines($bingoData['bingo'])) + count($checker->getCompletedColumns($bingoData['bingo'])),
            'linePositions' => $checker->getLinePositions($bingoData['bingo']),
        ]);
    }

    private function loadBingoView(string $slug, BingoRepository $br): array
    {
        $bingo = $br->findOneActiveBySlug($slug);
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
        return ['bingo' => $bingo, 'completed' => $completed, 'total' => count($items)];
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
