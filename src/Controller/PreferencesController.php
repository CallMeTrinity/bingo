<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreferencesController extends AbstractController
{
    #[Route('/preferences', name: 'app_preferences', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new Response(null, 401);
        }
        if (!$this->isCsrfTokenValid('preferences', $request->request->get('_token'))) {
            return new Response(null, 403);
        }

        $palette = $request->request->get('palette');
        if ($palette !== null && in_array($palette, User::PALETTES, true)) {
            $user->setPalette($palette);
        }

        $density = $request->request->get('density');
        if ($density !== null && in_array($density, User::DENSITIES, true)) {
            $user->setDensity($density);
        }

        $entityManager->flush();

        return new Response(null, 204);
    }
}
