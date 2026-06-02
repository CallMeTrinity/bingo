<?php

namespace App\Controller;

use App\Entity\Bingo;
use App\Entity\BingoItem;
use App\Form\BingoType;
use App\Repository\BingoRepository;
use App\Security\Voter\BingoVoter;
use App\Service\BingoChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
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
        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $cellCount = $bingo->getSize() ** 2;
            $labels = $this->parseItems((string) $form->get('items')->getData());

            if (count($labels) > $cellCount) {
                $form->get('items')->addError(new FormError(
                    sprintf('Tu as saisi %d cases pour une grille de %d.', count($labels), $cellCount)
                ));
            }
            foreach ($labels as $label) {
                if (mb_strlen($label) > 255) {
                    $form->get('items')->addError(new FormError(
                        sprintf('Une case dépasse 255 caractères : « %s… »', mb_substr($label, 0, 30))
                    ));
                    break;
                }
            }

            if ($form->isValid()) {
                shuffle($labels);
                $bingo->setOwner($user);
                for ($position = 1; $position <= $cellCount; $position++) {
                    $item = new BingoItem();
                    $item->setLabel($labels[$position - 1] ?? '');
                    $item->setPosition($position);
                    $bingo->addBingoItem($item);
                }
                $em->persist($bingo);
                $em->flush();

                return $this->redirectToRoute('bingo_show', ['slug' => $bingo->getSlug()]);
            }
        }

        $entries = array_map(
            fn(Bingo $bingo) => $this->buildEntry($bingo, $checker),
            $br->findActiveForOwner($user),
        );

        return $this->render('home.html.twig', [
            'entries' => $entries,
            'trashCount' => $br->countTrashed($user),
            'form' => $form,
            'openModal' => $form->isSubmitted(),
        ]);
    }

    #[Route('/corbeille', name: 'bingo_trash', methods: ['GET'])]
    public function trash(BingoRepository $br, BingoChecker $checker): Response
    {
        $user = $this->getUser();
        $entries = array_map(
            fn(Bingo $bingo) => $this->buildEntry($bingo, $checker),
            $br->findTrashed($user),
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
        $this->denyAccessUnlessGranted(BingoVoter::EDIT, $bingo);

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
        $this->denyAccessUnlessGranted(BingoVoter::EDIT, $bingo);

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
        $this->denyAccessUnlessGranted(BingoVoter::EDIT, $bingo);

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
        $bingo = $br->findOneActiveBySlug($slug);
        if (!$bingo) {
            throw $this->createNotFoundException('Bingo not found');
        }
        $this->denyAccessUnlessGranted(BingoVoter::EDIT, $bingo);

        return $this->render('bingo/index.html.twig', [
            ...$this->buildBingoView($bingo),
            'readOnly' => false,
            'completedLinesCount' => count($checker->getCompletedLines($bingo)) + count($checker->getCompletedColumns($bingo)),
            'linePositions' => $checker->getLinePositions($bingo),
        ]);
    }

    #[Route('/b/{slug}', name: 'bingo_share')]
    public function share(string $slug, BingoRepository $br, BingoChecker $checker): Response
    {
        $bingo = $br->findOneActiveBySlug($slug);
        if (!$bingo || !$bingo->isPublic()) {
            throw $this->createNotFoundException('Bingo not found');
        }

        return $this->render('bingo/share.html.twig', [
            ...$this->buildBingoView($bingo),
            'readOnly' => true,
            'completedLinesCount' => count($checker->getCompletedLines($bingo)) + count($checker->getCompletedColumns($bingo)),
            'linePositions' => $checker->getLinePositions($bingo),
        ]);
    }

    /**
     * @return array{bingo: Bingo, completed: int, total: int}
     */
    private function buildBingoView(Bingo $bingo): array
    {
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

    /**
     * @return list<string>
     */
    private function parseItems(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $trimmed = array_map('trim', $lines);

        return array_values(array_filter($trimmed, fn(string $line) => $line !== ''));
    }

    #[Route('/bingo/{slug}/visibility', name: 'bingo_visibility', methods: ['POST'])]
    public function visibility(string $slug, Request $request, BingoRepository $br, EntityManagerInterface $em): Response
    {
        $bingo = $br->findOneActiveBySlug($slug);
        if (!$bingo) {
            throw $this->createNotFoundException('Bingo not found');
        }
        $this->denyAccessUnlessGranted(BingoVoter::EDIT, $bingo);

        if (!$this->isCsrfTokenValid('visibility_bingo_'.$bingo->getSlug(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $isPublic = filter_var($request->request->get('state'), FILTER_VALIDATE_BOOLEAN);
        $bingo->setIsPublic($isPublic);
        $em->flush();

        return $this->json([
            'isPublic' => $bingo->isPublic(),
        ]);
    }
}
