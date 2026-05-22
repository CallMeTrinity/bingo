<?php

namespace App\Controller;

use App\Entity\BingoItem;
use App\Repository\BingoItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BingoItemController extends AbstractController
{
    #[Route('/bingo/{id}/check', name: 'bingo_check', methods: ['POST'])]
    public function check(BingoItem $item, EntityManagerInterface $em): Response
    {
        if ($item->getCompletedAt() == null) {
            $item->setCompletedAt(new \DateTimeImmutable());
        } else {
            $item->setCompletedAt(null);
        }

        $em->flush();

        return $this->json(['active' => $item->getCompletedAt() !== null]);

    }

}
