<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Controller;

use Petyus\SyliusAuditLogger\AuditEntryRepositoryInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Component\Grid\View\GridView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuditEntryPageController extends AbstractController
{
    public const GRID_CODE = 'petyus_audit_logger_admin_audit_entry';

    public function __construct(
        private readonly GridProviderInterface $gridProvider,
        private readonly DataProviderInterface $dataProvider,
        private readonly AuditEntryRepositoryInterface $repository,
    ) {
    }

    #[Route('/admin/audit-logs/', name: 'petyus_audit_logger_admin_audit_entry_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $grid = $this->gridProvider->get(self::GRID_CODE);
        $parameters = new Parameters($request->query->all());
        $data = $this->dataProvider->getData($grid, $parameters);

        return $this->render('@AuditLoggerPlugin/admin/audit_entry/index.html.twig', [
            'grid' => new GridView($data, $grid, $parameters),
        ]);
    }

    #[Route('/admin/audit-logs/{id}', name: 'petyus_audit_logger_admin_audit_entry_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $entry = $this->repository->find($id);

        if (null === $entry) {
            return new Response('Audit entry not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->render('@AuditLoggerPlugin/admin/audit_entry/show.html.twig', [
            'entry' => $entry,
        ]);
    }
}
