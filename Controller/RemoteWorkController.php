<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Export\Spreadsheet\AnnotatedObjectExporter;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Form\MultiUpdate\MultiUpdateTable;
use App\Form\MultiUpdate\MultiUpdateTableDTO;
use App\Form\YearByUserForm;
use App\Reporting\YearByUser\YearByUser;
use App\Repository\Query\BaseQuery;
use App\Utils\DataTable;
use App\Utils\FileHelper;
use App\Utils\PageSetup;
use App\Utils\Pagination;
use KimaiPlugin\RemoteWorkBundle\Constants;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use KimaiPlugin\RemoteWorkBundle\Form\RemoteWorkForm;
use KimaiPlugin\RemoteWorkBundle\RemoteWorkConfiguration;
use KimaiPlugin\RemoteWorkBundle\RemoteWorkService;
use KimaiPlugin\RemoteWorkBundle\RemoteWorkTypeFactory;
use KimaiPlugin\RemoteWorkBundle\Validator\OverlapValidator;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/remote-work')]
#[IsGranted('view_own_remote_work')]
final class RemoteWorkController extends AbstractController
{
    public function __construct(
        private readonly RemoteWorkTypeFactory $typeFactory,
        private readonly RemoteWorkService $service,
        private readonly RemoteWorkConfiguration $configuration,
        private readonly OverlapValidator $overlapValidator,
        private readonly RequestStack $requestStack,
        private readonly \KimaiPlugin\RemoteWorkBundle\CalDav\IcalHelper $icalHelper,
        private readonly \KimaiPlugin\RemoteWorkBundle\CalDav\CalDavConfiguration $calDavConfiguration,
    ) {
    }

    private function getPageSetup(): PageSetup
    {
        $page = new PageSetup('remote_work');
        $page->setActionName('remote_work_page');

        return $page;
    }

    #[Route(path: '/', name: 'remote_work', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->isGranted('view_other_remote_work');
        $defaultDate = $dateTimeFactory->createStartOfYear();
        $now = $dateTimeFactory->createDateTime();

        $values = new YearByUser();
        $values->setUser($currentUser);
        $values->setDate($defaultDate);

        $form = $this->createFormForGetRequest(YearByUserForm::class, $values, [
            'include_user' => $canChangeUser,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        /** @var User $profile */
        $profile = $values->getUser() ?? $currentUser;

        if ($profile !== $currentUser && !$this->isGranted('view_other_remote_work')) {
            throw $this->createAccessDeniedException('Cannot access other user\'s remote work');
        }

        /** @var \DateTimeInterface $yearDate */
        $yearDate = $values->getDate() ?? $defaultDate;

        $year = (int) $yearDate->format('Y');
        $entries = $this->service->findByUserAndYear($profile, $year);

        $page = $this->getPageSetup();
        $page->setActionPayload(['year' => $yearDate, 'profile' => $profile]);
        $page->setPaginationForm($form);

        // Group entries by month (descending)
        /** @var array<string, array{'items': array<RemoteWork>, 'table': null|DataTable, 'hasNew': bool}> $months */
        $months = [];

        foreach ($entries as $entry) {
            $date = $entry->getDate();
            if ($date === null) {
                continue;
            }
            $monthKey = $date->format('Y-m');
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = ['items' => [], 'table' => null, 'hasNew' => false];
            }
            $months[$monthKey]['items'][] = $entry;
            if ($entry->isNew()) {
                $months[$monthKey]['hasNew'] = true;
            }
        }

        // Sort months descending (newest first)
        krsort($months);

        foreach ($months as $monthKey => $settings) {
            $table = new DataTable('remote_work_' . $monthKey, new BaseQuery());
            $pagination = new Pagination(new ArrayAdapter($settings['items']));
            $pagination->setMaxPerPage(999);
            $table->setPagination($pagination);
            $table->setReloadEvents('kimai.remoteWork');

            // Add batch form if there are new entries that can be approved
            if ($settings['hasNew'] && $this->configuration->isApprovalRequired()) {
                $multiUpdateForm = $this->getMultiUpdateForm($yearDate->format('Y-m-d'), $profile, 'new');
                if ($multiUpdateForm !== null) {
                    $table->setBatchForm($multiUpdateForm);
                    $table->addColumn('id', ['class' => 'alwaysVisible multiCheckbox', 'orderBy' => false, 'title' => false, 'batchUpdate' => true]);
                }
            }

            $table->addColumn('date', ['class' => 'alwaysVisible w-min', 'orderBy' => false]);
            $table->addColumn('type', ['class' => 'alwaysVisible w-min', 'orderBy' => false]);
            $table->addColumn('time', ['class' => 'alwaysVisible w-min', 'orderBy' => false]);
            if ($this->configuration->isApprovalRequired()) {
                $table->addColumn('status', ['class' => 'alwaysVisible w-min', 'orderBy' => false]);
            }
            if ($canChangeUser && $currentUser !== $profile) {
                $table->addColumn('user', ['class' => 'd-none d-sm-table-cell w-min', 'orderBy' => false]);
            }
            $table->addColumn('comment', ['class' => 'd-none d-md-table-cell', 'orderBy' => false]);
            $table->addColumn('actions', ['class' => 'actions']);

            $months[$monthKey]['table'] = $table;
        }

        // Calculate statistics
        $stats = $this->service->calculateStatistic($profile, $year);

        // Prepare types with stats
        $types = $this->typeFactory->all();

        $createDate = $yearDate->format('Y-m-d');
        if ($yearDate->format('Y') === $now->format('Y')) {
            $createDate = $now->format('Y-m-d');
        }

        return $this->render('@RemoteWork/remote-work.html.twig', [
            'stats' => $stats,
            'types' => $types,
            'page_setup' => $page,
            'now' => $now,
            'year' => $yearDate,
            'user' => $profile,
            'dataTables' => $months,
            'create_date' => $createDate,
            'approval_required' => $this->configuration->isApprovalRequired(),
            'caldav_enabled' => $this->calDavConfiguration->isEnabled(),
            'caldav_host' => $this->calDavConfiguration->getDomain(),
        ]);
    }

    #[Route(path: '/create/{type}/{profile}/{date}', name: 'remote_work_create', defaults: ['profile' => null], methods: ['GET', 'POST'])]
    public function create(string $type, ?User $profile, string $date, Request $request): Response
    {
        $dateFactory = $this->getDateTimeFactory();
        $nowDate = $dateFactory->createDateTime();

        try {
            $createDate = $dateFactory->createDateTime($date);
        } catch (\Exception $ex) {
            $createDate = $dateFactory->createDateTime();
        }

        try {
            $remoteWorkType = $this->typeFactory->create($type);
        } catch (\Exception $ex) {
            throw $this->createNotFoundException('Invalid type given: ' . $type);
        }

        $currentUser = $this->getUser();
        $profile = $profile ?? $currentUser;

        if ($profile !== $currentUser && !$this->isGranted('edit_other_remote_work')) {
            throw $this->createAccessDeniedException('Cannot create remote work for other user');
        }

        $page = $this->getPageSetup();
        $entity = new RemoteWork($currentUser, $nowDate);
        $entity->setType($remoteWorkType->getType());
        $entity->setUser($profile);
        $entity->setDate($createDate);

        $form = $this->createForm(RemoteWorkForm::class, $entity, [
            'include_user' => $this->isGranted('edit_other_remote_work'),
            'year' => $createDate->format('Y'),
            'action' => $this->generateUrl('remote_work_create', [
                'type' => $remoteWorkType->getType(),
                'profile' => $profile->getId(),
                'date' => $date
            ]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userId = $profile->getId();

            try {
                $endDate = $entity->getEnd();

                // Check for overlaps (warnings only)
                $ignoreWarning = $request->request->getBoolean('ignore_warning', false);
                if (!$ignoreWarning) {
                    $overlaps = $this->overlapValidator->checkOverlaps($entity, $endDate);
                    if (\count($overlaps) > 0) {
                        return $this->render($remoteWorkType->getTemplate(), [
                            'type' => $remoteWorkType,
                            'entity' => $entity,
                            'form' => $form->createView(),
                            'page_setup' => $page,
                            'overlaps' => $overlaps,
                        ]);
                    }
                }

                $this->service->createNewEntries($currentUser, $entity, $endDate);
                $this->flashSuccess('remote_work.created');
            } catch (\Exception $ex) {
                $this->logException($ex);
                $this->flashUpdateException($ex);
            }

            return $this->redirectToRouteAfterCreate('remote_work', [
                'user' => $userId,
                'date' => $dateFactory->createStartOfYear($createDate)->format('Y-m-d')
            ]);
        }

        return $this->render($remoteWorkType->getTemplate(), [
            'type' => $remoteWorkType,
            'entity' => $entity,
            'form' => $form->createView(),
            'page_setup' => $page,
            'overlaps' => [],
        ]);
    }

    #[Route(path: '/edit/{id}', name: 'remote_work_edit', methods: ['GET', 'POST'])]
    public function edit(RemoteWork $entity, Request $request): Response
    {
        $currentUser = $this->getUser();
        $isOwn = $entity->getUser() === $currentUser;

        if ($isOwn && !$this->isGranted('edit_own_remote_work')) {
            throw $this->createAccessDeniedException('Cannot edit own remote work');
        }

        if (!$isOwn && !$this->isGranted('edit_other_remote_work')) {
            throw $this->createAccessDeniedException('Cannot edit other user\'s remote work');
        }

        $page = $this->getPageSetup();
        $remoteWorkType = $this->typeFactory->fromRemoteWork($entity);

        $form = $this->createForm(RemoteWorkForm::class, $entity, [
            'include_user' => false,
            'is_edit' => true,
            'action' => $this->generateUrl('remote_work_edit', ['id' => $entity->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->service->save($entity);
                $this->flashSuccess('remote_work.updated');
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }

            return $this->redirectToRoute('remote_work');
        }

        return $this->render($remoteWorkType->getTemplate(), [
            'type' => $remoteWorkType,
            'entity' => $entity,
            'form' => $form->createView(),
            'page_setup' => $page,
            'overlaps' => [],
        ]);
    }

    /**
     * @return FormInterface<MultiUpdateTableDTO>|null
     */
    private function getMultiUpdateForm(string $year, User $user, string $action): ?FormInterface
    {
        if ($action === 'approved') {
            return null;
        }

        $dto = new MultiUpdateTableDTO();

        if ($action === 'new' && $this->configuration->isApprovalRequired()) {
            if ($this->isGranted('approve_remote_work')) {
                $dto->addAction('approve', $this->generateUrl('remote_work_batch_approve', [
                    'user' => $user->getId(),
                    'year' => $year,
                ]));
                $dto->addAction('reject', $this->generateUrl('remote_work_batch_reject', [
                    'user' => $user->getId(),
                    'year' => $year,
                ]));
            }
        }

        $canDelete = ($user === $this->getUser() && $this->isGranted('delete_own_remote_work'))
            || $this->isGranted('delete_other_remote_work');

        if ($canDelete) {
            $dto->addDelete($this->generateUrl('remote_work_batch_delete', [
                'user' => $user->getId(),
                'year' => $year,
            ]));
        }

        if (!$dto->hasAction()) {
            return null;
        }

        return $this->createForm(MultiUpdateTable::class, $dto, [
            'action' => $this->generateUrl('remote_work'),
            'repository' => $this->service->getRepository(),
            'method' => 'POST',
        ]);
    }

    #[Route(path: '/{year}/{user}/batch-delete', name: 'remote_work_batch_delete', methods: ['POST'])]
    public function batchDelete(string $year, User $user, Request $request): Response
    {
        return $this->batchAction($year, $user, $request, 'delete');
    }

    #[Route(path: '/{year}/{user}/batch-approve', name: 'remote_work_batch_approve', methods: ['POST'])]
    public function batchApprove(string $year, User $user, Request $request): Response
    {
        return $this->batchAction($year, $user, $request, 'approve');
    }

    #[Route(path: '/{year}/{user}/batch-reject', name: 'remote_work_batch_reject', methods: ['POST'])]
    public function batchReject(string $year, User $user, Request $request): Response
    {
        return $this->batchAction($year, $user, $request, 'reject');
    }

    private function batchAction(string $year, User $user, Request $request, string $action): Response
    {
        $form = $this->getMultiUpdateForm($year, $user, $action);

        if ($form !== null) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    /** @var MultiUpdateTableDTO $dto */
                    $dto = $form->getData();
                    /** @var iterable<RemoteWork> $entities */
                    $entities = $dto->getEntities();

                    // Validate that all entries belong to the user
                    foreach ($entities as $entity) {
                        if ($entity->getUser() !== $user) {
                            throw $this->createAccessDeniedException('Entry does not belong to user');
                        }
                    }

                    if ($action === 'delete') {
                        $toDelete = [];
                        foreach ($entities as $entity) {
                            $isOwn = $entity->getUser() === $this->getUser();
                            if ($isOwn && !$this->isGranted('delete_own_remote_work')) {
                                throw $this->createAccessDeniedException('Not allowed to delete own remote work');
                            }
                            if (!$isOwn && !$this->isGranted('delete_other_remote_work')) {
                                throw $this->createAccessDeniedException('Not allowed to delete other remote work');
                            }
                            $toDelete[] = $entity;
                        }

                        if (\count($toDelete) > 0) {
                            $this->service->batchDelete($toDelete);
                            $this->flashSuccess('remote_work.deleted');
                        }
                    } elseif ($action === 'approve') {
                        if (!$this->isGranted('approve_remote_work')) {
                            throw $this->createAccessDeniedException('Not allowed to approve remote work');
                        }
                        $this->service->approve($entities, $this->getUser());
                        $this->flashSuccess('remote_work.approved');
                    } elseif ($action === 'reject') {
                        if (!$this->isGranted('approve_remote_work')) {
                            throw $this->createAccessDeniedException('Not allowed to reject remote work');
                        }
                        $this->service->reject($entities);
                        $this->flashSuccess('remote_work.rejected');
                    }
                } catch (\Exception $ex) {
                    $this->logException($ex);
                    $this->flashException($ex, 'Could not change remote work: ' . $ex->getMessage());
                }
            }
        }

        return $this->redirectToRoute('remote_work', ['user' => $user->getId(), 'date' => $year]);
    }

    #[Route(path: '/delete/{id}', name: 'remote_work_delete', methods: ['GET', 'POST'])]
    public function delete(RemoteWork $entity, Request $request): Response
    {
        $currentUser = $this->getUser();
        $isOwn = $entity->getUser() === $currentUser;

        if ($isOwn && !$this->isGranted('delete_own_remote_work')) {
            throw $this->createAccessDeniedException('Cannot delete own remote work');
        }

        if (!$isOwn && !$this->isGranted('delete_other_remote_work')) {
            throw $this->createAccessDeniedException('Cannot delete other user\'s remote work');
        }

        try {
            $this->service->delete($entity);
            $this->flashSuccess('remote_work.deleted');
        } catch (\Exception $ex) {
            $this->logException($ex);
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('remote_work');
    }

    #[Route(path: '/sync/{year}/{month}/{profile}', name: 'remote_work_sync', methods: ['POST'])]
    #[IsGranted('view_own_remote_work')]
    public function syncToCalendar(int $year, int $month, User $profile, Request $request): Response
    {
        $isOwn = $profile === $this->getUser();
        if (!$isOwn && !$this->isGranted('view_other_remote_work')) {
            throw $this->createAccessDeniedException('Cannot sync other user\'s remote work');
        }

        if (!$this->isCsrfTokenValid('remote_work.sync', $request->request->get('_token'))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('remote_work', ['user' => $profile->getId(), 'date' => $year . '-01-01']);
        }

        $entries = $this->service->findByUserAndMonth($profile, $year, $month);
        $synced = $this->service->syncToCalendarBatch($entries);

        if ($synced > 0) {
            $this->flashSuccess('remote_work.synced');
        } else {
            $this->flashWarning('remote_work.sync_nothing');
        }

        return $this->redirectToRoute('remote_work', ['user' => $profile->getId(), 'date' => $year . '-01-01']);
    }

    #[Route(path: '/export/{year}/{profile}', name: 'remote_work_export', methods: ['GET'])]
    #[IsGranted('view_own_remote_work')]
    public function export(\DateTimeInterface $year, User $profile, AnnotatedObjectExporter $exporter): Response
    {
        $isOwn = $profile === $this->getUser();
        if (!$isOwn && !$this->isGranted('view_other_remote_work')) {
            throw $this->createAccessDeniedException('Cannot export other user\'s remote work');
        }

        $yearInt = (int) $year->format('Y');
        $entries = $this->service->findByUserAndYear($profile, $yearInt);

        $spreadsheet = $exporter->export(RemoteWork::class, $entries);

        $filename = \sprintf('%s-%s-remote-work', $profile->getDisplayName(), $year->format('Y'));
        $filename = FileHelper::convertToAsciiFilename($filename);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), $filename);

        return $writer->getFileResponse($spreadsheet);
    }

    #[Route(path: '/ical/{year}/{profile}', name: 'remote_work_ical', methods: ['GET'])]
    #[IsGranted('view_own_remote_work')]
    public function icalExport(\DateTimeInterface $year, User $profile): Response
    {
        $isOwn = $profile === $this->getUser();
        if (!$isOwn && !$this->isGranted('view_other_remote_work')) {
            throw $this->createAccessDeniedException('Cannot export other user\'s remote work');
        }

        $yearInt = (int) $year->format('Y');
        $entries = $this->service->findByUserAndYear($profile, $yearInt);

        $domain = $this->requestStack->getCurrentRequest()?->getHost() ?? 'kimai.local';
        $ical = $this->icalHelper->generateCalendar($entries, $profile, $domain);

        $filename = \sprintf('%s-%s-remote-work', $profile->getDisplayName(), $year->format('Y'));
        $filename = FileHelper::convertToAsciiFilename($filename) . '.ics';

        $response = new Response($ical);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

}
