<?php

namespace App\Controller;

use App\Repository\CallRepository;
use App\Repository\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(
        CallRepository $callRepository,
        OrganizationRepository $organizationRepository,
    ): Response {
        $user = $this->getUser();
        $organizationIds = $organizationRepository->findAccessibleIds($user);

        return $this->render('home/dashboard.html.twig', [
            'stats' => $callRepository->dashboardStats($organizationIds, new \DateTimeImmutable()),
        ]);
    }
}