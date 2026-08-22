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
    public function index(
        CallRepository $callRepository,
        OrganizationRepository $organizationRepository,
    ): Response {
        $user = $this->getUser();
        if (null === $user) {
            return $this->render('home/index.html.twig');
        }

        $now = new \DateTimeImmutable();
        $organizationIds = $organizationRepository->findAccessibleIds($user);
        // Y — всего организаций области доступа; администратор видит все
        // организации системы (ADR-0008).
        $totalOrgs = null !== $organizationIds
            ? \count($organizationIds)
            : $organizationRepository->count([]);

        return $this->render('home/index.html.twig', [
            'stats' => $callRepository->dashboardStats($organizationIds, $now),
            'statsByOrg' => $callRepository->organizationCounts($organizationIds, $now),
            'totalOrgs' => $totalOrgs,
        ]);
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
        $filter = (string) $request->query->get('filter', '');

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
            'organizationRows' => $organizationRows,
            'contactsByOrganization' => $contactsByOrganization,
            'contactById' => $contactById,
            'callsByOrganization' => $callRepository->findAllCallsByOrganizations($ids),
            'search' => $search,
            'sort' => $sort,
            'dir' => $dir,
            'filter' => $filter,
        ]);
    }
}
