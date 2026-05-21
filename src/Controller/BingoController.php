<?php

namespace App\Controller;

use App\Repository\BingoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BingoController extends AbstractController
{
    #[Route('/bingo/{year}', name: 'bingo_show')]
    public function index(int $year, BingoRepository $br): Response
    {
        $bingo = $br->findOneBy(['year' => $year]);
        if (!$bingo) {
            throw $this->createNotFoundException('Bingo not found');
        }
        return $this->render('bingo/index.html.twig', [
            'controller_name' => 'BingoController',
            'bingo' => $bingo,
        ]);
    }
}
