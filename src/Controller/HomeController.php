<?php

namespace App\Controller;

use App\Repository\CallRepository;
use App\Repository\ContactRepository;
use App\Repository\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        Request $request,
        CallRepository $callRepository,
        OrganizationRepository $organizationRepository,
        ContactRepository $contactRepository,
    ): Response {
        $user = $this->getUser();
        $organizationIds = $organizationRepository->findAccessibleIds($user);

        $search = (string) $request->query->get('q', '');
        $sort = (string) $request->query->get('sort', '');
        $dir = (string) $request->query->get('dir', 'asc');

        $organizationRows = $organizationRepository->findForDashboard($user, $search, $sort, $dir);

        $ids = array_map(static fn (\App\Dto\DashboardOrganizationRow $row): int => $row->organization->id, $organizationRows);

        $contacts = $contactRepository->findByOrganizations($ids);
        $contactsByOrganization = [];
        $contactById = [];
        foreach ($contacts as $contact) {
            $contactsByOrganization[$contact->organization->id][] = $contact;
            $contactById[$contact->id] = $contact;
        }

        return $this->render('home/dashboard.html.twig', [
            'stats' => $callRepository->dashboardStats($organizationIds, new \DateTimeImmutable()),
            'organizationRows' => $organizationRows,
            'contactsByOrganization' => $contactsByOrganization,
            'contactById' => $contactById,
            'callsByOrganization' => $callRepository->findAllCallsByOrganizations($ids),
            'search' => $search,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }
}
