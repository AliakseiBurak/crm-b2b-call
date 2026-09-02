<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\CampaignAttachment;
use App\Entity\CampaignRecipient;
use App\Entity\Contact;
use App\Entity\Enum\CampaignStatus;
use App\Entity\Organization;
use App\Repository\CampaignRepository;
use App\Repository\OrganizationRepository;
use App\Service\CampaignAttachmentStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Рассылки (change campaign-entity): создание/редактирование доступны
 * администратору и менеджерам (спецификация campaigns), вложения хранятся на
 * кампании, запуск — ручной (кнопка), остановка и сброс ошибки,
 * клонирование, ручные адресаты (все статусы кроме archived) с заменой
 * подтверждением.
 */
class CampaignController extends AbstractController
{
    public function __construct(
        private readonly CampaignRepository $campaigns,
        private readonly OrganizationRepository $organizations,
        private readonly CampaignAttachmentStorage $storage,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/campaigns', name: 'app_campaign_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $sort = $request->query->get('sort', 'name');
        $direction = $request->query->get('dir', 'ASC');

        $allowed = ['name', 'subject', 'status', 'createdAt'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'name';
        }
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $all = $this->campaigns->findBy([], [$sort => $direction]);

        $active = [];
        $archived = [];
        foreach ($all as $c) {
            if ($c->status === CampaignStatus::Archived) {
                $archived[] = $c;
            } else {
                $active[] = $c;
            }
        }

        return $this->render('campaign/index.html.twig', [
            'campaigns' => array_merge($active, $archived),
            'highlight' => (int) $request->query->get('highlight', 0),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/campaigns/new', name: 'app_campaign_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('campaign/form.html.twig', [
            'campaign' => null,
            'errors' => [],
            'attachmentError' => null,
        ]);
    }

    #[Route('/campaigns/new', name: 'app_campaign_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $this->assertCsrfToken($request);

        $campaign = new Campaign();
        $errors = $this->applyRequest($request, $validator, $campaign);
        if ([] !== $errors) {
            return $this->render('campaign/form.html.twig', [
                'campaign' => $campaign,
                'errors' => $errors,
                'attachmentError' => null,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $this->em->persist($campaign);

        $this->handleAttachments($request, $campaign);

        return $this->redirectToRoute('app_campaign_index', ['highlight' => $campaign->id]);
    }

    #[Route('/campaigns/{id}', name: 'app_campaign_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $campaign = $this->campaign($id);

        return $this->render('campaign/show.html.twig', [
            'campaign' => $campaign,
        ]);
    }

    #[Route('/campaigns/{id}/edit', name: 'app_campaign_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        $campaign = $this->campaign($id);

        return $this->render('campaign/form.html.twig', [
            'campaign' => $campaign,
            'errors' => [],
            'attachmentError' => null,
        ]);
    }

    #[Route('/campaigns/{id}/edit', name: 'app_campaign_update', methods: ['POST'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        $campaign = $this->campaign($id);
        $this->assertCsrfToken($request);

        $errors = $this->applyRequest($request, $validator, $campaign);
        if ([] !== $errors) {
            return $this->render('campaign/form.html.twig', [
                'campaign' => $campaign,
                'errors' => $errors,
                'attachmentError' => null,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $this->handleAttachments($request, $campaign);

        return $this->redirectToRoute('app_campaign_index', ['highlight' => $campaign->id]);
    }

    /**
     * Ручной запуск (spec campaigns: Запуск рассылки): проставляется
     * launched_at и статус launched; повторный запуск момент не меняет.
     * Фактическая отправка — будущий сервис MailingService.
     */
    #[Route('/campaigns/{id}/launch', name: 'app_campaign_launch', methods: ['POST'])]
    public function launch(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertCsrfToken($request);

        if (!$campaign->isLaunched()) {
            $campaign->launch();
            $this->em->flush();
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json([
                'status' => $campaign->status->value,
                'label' => $campaign->status->label(),
            ]);
        }

        return $this->redirectToRoute('app_campaign_show', ['id' => $campaign->id]);
    }

    /**
     * Остановка запущенной рассылки (spec campaigns: Остановка запущенной
     * рассылки): статус возвращается в ready, allowing re-launch.
     */
    #[Route('/campaigns/{id}/stop', name: 'app_campaign_stop', methods: ['POST'])]
    public function stop(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertCsrfToken($request);

        if (CampaignStatus::Launched === $campaign->status) {
            $campaign->setStatus(CampaignStatus::Ready);
            $this->em->flush();
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json([
                'status' => $campaign->status->value,
                'label' => $campaign->status->label(),
            ]);
        }

        return $this->redirectToRoute('app_campaign_show', ['id' => $campaign->id]);
    }

    /**
     * Сброс failed-рассылки в ready: пользователь входит в карточку,
     * нажимает «Сбросить», статус меняется на ready, затем можно запустить.
     */
    #[Route('/campaigns/{id}/reset', name: 'app_campaign_reset', methods: ['POST'])]
    public function reset(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertCsrfToken($request);

        if (CampaignStatus::Failed === $campaign->status) {
            $campaign->setStatus(CampaignStatus::Ready);
            $campaign->clearFailureReason();
            $this->em->flush();
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json([
                'status' => $campaign->status->value,
                'label' => $campaign->status->label(),
            ]);
        }

        return $this->redirectToRoute('app_campaign_show', ['id' => $campaign->id]);
    }

    /**
     * Клонирование кампании (spec campaigns: Клонирование рассылки):
     * создается черновик с копией полей и вложений (метаданные).
     * Доступно для всех статусов кроме draft. Получатели — по флагу.
     */
    #[Route('/campaigns/{id}/clone', name: 'app_campaign_clone', methods: ['POST'])]
    public function clone(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertCsrfToken($request);

        if (CampaignStatus::Draft === $campaign->status) {
            throw $this->createNotFoundException('Клонирование недоступно для черновиков');
        }

        $cloneMode = (string) $request->request->get('clone_mode', 'none');
        $withRecipients = 'none' !== $cloneMode;
        $withContacts = 'recipients_with_contacts' === $cloneMode;
        $clone = Campaign::cloneFrom($campaign, $withRecipients, $withContacts);
        $this->em->persist($clone);
        $this->em->flush();

        return $this->redirectToRoute('app_campaign_edit', ['id' => $clone->id]);
    }

    #[Route('/campaigns/{id}/delete', name: 'app_campaign_delete', methods: ['GET'])]
    public function delete(int $id): Response
    {
        $campaign = $this->campaign($id);

        return $this->render('campaign/delete.html.twig', [
            'campaign' => $campaign,
            'error' => null,
        ]);
    }

    #[Route('/campaigns/{id}/delete', name: 'app_campaign_remove', methods: ['POST'])]
    public function remove(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertCsrfToken($request);

        $storageKeys = array_map(
            static fn (CampaignAttachment $a): string => $a->storageKey,
            $campaign->attachments->toArray(),
        );

        $this->em->remove($campaign);
        $this->em->flush();

        foreach ($storageKeys as $storageKey) {
            $this->storage->delete($storageKey);
        }

        return $this->redirectToRoute('app_campaign_index');
    }

    /**
     * Загрузка вложения (tasks 5.2/8.1): файл уходит в storage по
     * сгенерированному ключу, метаданные — в campaign_attachment.
     */
    #[Route('/campaigns/{id}/attachments', name: 'app_campaign_attachment_upload', methods: ['POST'])]
    public function uploadAttachment(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertAttachmentCsrfToken($request);

        $file = $request->files->get('attachment');
        if (!$file instanceof UploadedFile || !$file->isValid() || '' === $file->getClientOriginalName()) {
            return $this->renderEditWithAttachmentError($campaign, 'Выберите файл для загрузки');
        }

        $storageKey = $this->storage->store($file);
        // Метаданные пишутся после сохранения файла; при сбое flush ключ
        // остаётся без записи — файл удаляется вручную.
        $attachment = (new CampaignAttachment($campaign, (string) $file->getClientOriginalName(), $storageKey))
            ->setMimeType($file->getMimeType())
            ->setSize((int) $file->getSize());
        $this->em->persist($attachment);
        $this->em->flush();

        return $this->redirectToRoute('app_campaign_edit', ['id' => $campaign->id]);
    }

    /**
     * Удаление вложения (tasks 5.2/8.2): строка удаляется вместе с файлом
     * в storage.
     */
    #[Route('/campaigns/{id}/attachments/{attachmentId}/delete', name: 'app_campaign_attachment_remove', methods: ['POST'])]
    public function removeAttachment(int $id, int $attachmentId, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertAttachmentCsrfToken($request);

        $attachment = $this->em->find(CampaignAttachment::class, $attachmentId);
        if (null === $attachment || $attachment->campaign->id !== $campaign->id) {
            throw new NotFoundHttpException('Вложение не найдено');
        }

        $storageKey = $attachment->storageKey;
        $this->em->remove($attachment);
        $this->em->flush();
        $this->storage->delete($storageKey);

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json(['ok' => true]);
        }

        return $this->redirectToRoute('app_campaign_edit', ['id' => $campaign->id]);
    }

    /**
     * Страница адресатов рассылки: список, добавление, удаление.
     * Редактирование доступно для всех статусов, кроме archived.
     */
    #[Route('/campaigns/{id}/recipients', name: 'app_campaign_recipients', methods: ['GET'])]
    public function recipients(int $id): Response
    {
        $campaign = $this->campaign($id);
        $available = $this->availableOrganizations($campaign);

        $contactsByOrg = [];
        foreach ($available as $org) {
            $contactsByOrg[$org->id] = array_map(
                static fn (Contact $c): array => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email],
                $org->contacts->toArray(),
            );
        }

        return $this->render('campaign/recipients.html.twig', [
            'campaign' => $campaign,
            'availableOrganizations' => $available,
            'contactsByOrg' => $contactsByOrg,
        ]);
    }

    /**
     * Массовое добавление всех доступных организаций адресатами.
     * Существующие организации пропускаются (тихо).
     */
    #[Route('/campaigns/{id}/recipients/bulk', name: 'app_campaign_recipient_bulk', methods: ['POST'])]
    public function bulkAddRecipients(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertRecipientCsrfToken($request);
        $this->assertRecipientsEditable($campaign);

        $available = $this->availableOrganizations($campaign);
        $added = 0;
        foreach ($available as $organization) {
            $exists = $this->em->getRepository(CampaignRecipient::class)->findOneBy([
                'campaign' => $campaign,
                'organization' => $organization,
            ]);
            if (null !== $exists) {
                continue;
            }
            $this->em->persist(new CampaignRecipient($campaign, $organization));
            ++$added;
        }
        $this->em->flush();

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json([
                'redirect' => $this->generateUrl('app_campaign_recipients', ['id' => $campaign->id]),
                'added' => $added,
            ]);
        }

        return $this->redirectToRoute('app_campaign_recipients', ['id' => $campaign->id]);
    }

    /**
     * Добавление ручного адресата standalone-рассылки (task 5.5): менеджеру
     * разрешены только организации области доступа (ADR-0007) — недоступная
     * организация отклоняется с 403; администратору — любые (ADR-0008).
     * Опционально указывается contact_id для рассылки на email контакта.
     * Адресаты доступны для всех статусов кампании, кроме archived.
     */
    #[Route('/campaigns/{id}/recipients', name: 'app_campaign_recipient_add', methods: ['POST'])]
    public function addRecipient(int $id, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertRecipientCsrfToken($request);
        $this->assertRecipientsEditable($campaign);

        $organization = $this->accessibleOrganizationForRecipient((int) $request->request->get('organization', 0));

        $contactId = $request->request->get('contact');
        $contact = null;
        if (null !== $contactId && '' !== $contactId) {
            $contact = $this->em->find(Contact::class, (int) $contactId);
            if (null === $contact || $contact->organization->id !== $organization->id) {
                throw $this->createNotFoundException('Контакт не найден в данной организации');
            }
        }

        // Точное совпадение (org + contact) — тихое игнорирование.
        $exact = $this->em->getRepository(CampaignRecipient::class)->findOneBy([
            'campaign' => $campaign,
            'organization' => $organization,
            'contact' => $contact,
        ]);
        if (null !== $exact) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json(['redirect' => $this->generateUrl('app_campaign_recipients', ['id' => $campaign->id])]);
            }
            return $this->redirectToRoute('app_campaign_recipients', ['id' => $campaign->id]);
        }

        // Проверка: есть ли другой адресат для той же организации
        // (org-level или другой contact) — замена с подтверждением.
        $existingForOrg = $this->em->getRepository(CampaignRecipient::class)->findOneBy([
            'campaign' => $campaign,
            'organization' => $organization,
        ]);
        if (null !== $existingForOrg) {
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json(['redirect' => $this->generateUrl('app_campaign_recipient_replace_confirm', [
                    'id' => $campaign->id,
                    'recipientId' => $existingForOrg->id,
                    'newContact' => $contact?->id,
                ])]);
            }
            return $this->redirectToRoute('app_campaign_recipient_replace_confirm', [
                'id' => $campaign->id,
                'recipientId' => $existingForOrg->id,
                'newContact' => $contact?->id,
            ]);
        }

        $this->em->persist(new CampaignRecipient($campaign, $organization, $contact));
        $this->em->flush();

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json(['redirect' => $this->generateUrl('app_campaign_recipients', ['id' => $campaign->id])]);
        }

        return $this->redirectToRoute('app_campaign_recipients', ['id' => $campaign->id]);
    }

    /**
     * Страница подтверждения замены адресата: показывает текущий и новый
     * набор (org/contact); при запущенной рассылке — предупреждение о повторной отправке.
     */
    #[Route('/campaigns/{id}/recipients/{recipientId}/replace', name: 'app_campaign_recipient_replace_confirm', methods: ['GET'])]
    public function replaceConfirm(int $id, int $recipientId, Request $request): Response
    {
        $campaign = $this->campaign($id);

        $existing = $this->em->find(CampaignRecipient::class, $recipientId);
        if (null === $existing || $existing->campaign->id !== $campaign->id) {
            throw new NotFoundHttpException('Адресат не найден');
        }

        $newContactId = $request->query->get('newContact');
        $newContact = null;
        if (null !== $newContactId && '' !== $newContactId) {
            $newContact = $this->em->find(Contact::class, (int) $newContactId);
        }

        return $this->render('campaign/replace_recipient.html.twig', [
            'campaign' => $campaign,
            'existing' => $existing,
            'newContact' => $newContact,
            'newOrganization' => $existing->organization,
        ]);
    }

    /**
     * Выполнение замены: удаление старого + добавление нового адресата.
     */
    #[Route('/campaigns/{id}/recipients/{recipientId}/replace', name: 'app_campaign_recipient_replace_execute', methods: ['POST'])]
    public function replaceExecute(int $id, int $recipientId, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertRecipientCsrfToken($request);
        $this->assertRecipientsEditable($campaign);

        $existing = $this->em->find(CampaignRecipient::class, $recipientId);
        if (null === $existing || $existing->campaign->id !== $campaign->id) {
            throw new NotFoundHttpException('Адресат не найден');
        }

        $newContactId = $request->request->get('new_contact_id');
        $newContact = null;
        if (null !== $newContactId && '' !== $newContactId) {
            $newContact = $this->em->find(Contact::class, (int) $newContactId);
        }

        $this->em->remove($existing);
        $this->em->flush();

        // Счётчик увеличивается только если рассылка уже запущена.
        // TODO: в будущем — учитывать статус доставки письма (deliveredAt на CampaignRecipient),
        // чтобы счётчик рос только при реальной доставке, а не просто при запуске рассылки.
        $shouldIncrement = null !== $campaign->launchedAt;
        $this->em->persist(new CampaignRecipient(
            $campaign,
            $existing->organization,
            $newContact,
            $shouldIncrement ? $existing->replacementCount + 1 : $existing->replacementCount,
        ));
        $this->em->flush();

        return $this->redirectToRoute('app_campaign_recipients', ['id' => $campaign->id]);
    }

    #[Route('/campaigns/{id}/recipients/{recipientId}/delete', name: 'app_campaign_recipient_remove', methods: ['POST'])]
    public function removeRecipient(int $id, int $recipientId, Request $request): Response
    {
        $campaign = $this->campaign($id);
        $this->assertRecipientCsrfToken($request);
        $this->assertRecipientsEditable($campaign);

        $recipient = $this->em->find(CampaignRecipient::class, $recipientId);
        if (null === $recipient || $recipient->campaign->id !== $campaign->id) {
            throw new NotFoundHttpException('Адресат не найден');
        }

        $this->em->remove($recipient);
        $this->em->flush();

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->json(['ok' => true]);
        }

        return $this->redirectToRoute('app_campaign_recipients', ['id' => $campaign->id]);
    }

    /**
     * Заполняет кампанию данными формы и возвращает ошибки валидации,
     * сгруппированные по полям (name, subject, preview_text, body, status).
     *
     * @return array<string, string>
     */
    private function applyRequest(Request $request, ValidatorInterface $validator, Campaign $campaign): array
    {
        $errors = [];

        $campaign->setName(trim((string) $request->request->get('name', '')));
        $campaign->setSubject(trim((string) $request->request->get('subject', '')));
        $previewText = trim((string) $request->request->get('preview_text', ''));
        $campaign->setPreviewText('' !== $previewText ? $previewText : null);
        $campaign->setBody((string) $request->request->get('body', ''));

        // Статус выбирается из фиксированного списка draft/ready/launched/archived.
        // failed — технический статус, устанавливаемый MailingService, недоступен в форме.
        $status = (string) $request->request->get('status', CampaignStatus::Draft->value);
        $status = CampaignStatus::tryFrom($status);
        if (null === $status) {
            $errors['status'] = 'Некорректный статус рассылки';
        } elseif (CampaignStatus::Failed === $status) {
            $errors['status'] = 'Статус «Ошибка» устанавливается автоматически сервисом отправки';
        } else {
            $campaign->setStatus($status);
        }

        foreach ($validator->validate($campaign) as $violation) {
            $errors[$violation->getPropertyPath()] ??= $violation->getMessage();
        }

        return $errors;
    }

    private function renderEditWithAttachmentError(Campaign $campaign, string $message): Response
    {
        return $this->render('campaign/form.html.twig', [
            'campaign' => $campaign,
            'errors' => [],
            'attachmentError' => $message,
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * Кампания видна всем вошедшим пользователям (администраторам и
     * менеджерам); рассылка не привязана к организации (спецификация
     * campaigns), поэтому область доступа ADR-0007 здесь не применяется.
     */
    private function campaign(int $id): Campaign
    {
        $campaign = $this->campaigns->find($id);
        if (null === $campaign) {
            throw $this->createNotFoundException('Рассылка не найдена');
        }

        return $campaign;
    }

    /**
     * Защита state-changing форм от CSRF; для AJAX-запросов токен передаётся
     * в заголовке X-CSRF-Token.
     */
    private function assertCsrfToken(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token') ?? (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('campaign', $token)) {
            throw new AccessDeniedHttpException('Недействительный CSRF-токен');
        }
    }

    /**
     * Организации, доступные для добавления адресатом: вся область доступа
     * пользователя (ADR-0007/0008) минус уже выбранные в рассылку.
     */
    private function availableOrganizations(Campaign $campaign): array
    {
        return $this->organizations->findAccessibleOrganizations($this->getUser());
    }

    /**
     * Организация для добавления адресатом с проверкой области доступа
     * (ADR-0007): менеджеру — только организации личной и назначенных групп,
     * администратору — все (ADR-0008, группы не проверяются).
     */
    private function accessibleOrganizationForRecipient(int $id): Organization
    {
        $organization = $this->organizations->find($id);
        if (null === $organization) {
            throw $this->createNotFoundException('Организация не найдена');
        }

        $accessibleIds = $this->organizations->findAccessibleIds($this->getUser());
        if (null !== $accessibleIds && !\in_array($id, $accessibleIds, true)) {
            throw new AccessDeniedHttpException('Организация вне области доступа');
        }

        return $organization;
    }

    /**
     * Адресаты доступны для всех статусов кампании, кроме archived.
     * Для архивных кампаний редактирование запрещено (design campaign-entity,
     * решение 5).
     */
    private function assertRecipientsEditable(Campaign $campaign): void
    {
        if (CampaignStatus::Archived === $campaign->status) {
            throw new AccessDeniedHttpException('Адресаты недоступны для рассылки в статусе «В архиве»');
        }
    }

    private function assertAttachmentCsrfToken(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token') ?? (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('campaign_attachment', $token)) {
            throw new AccessDeniedHttpException('Недействительный CSRF-токен');
        }
    }

    private function assertRecipientCsrfToken(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token') ?? (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('campaign_recipient', $token)) {
            throw new AccessDeniedHttpException('Недействительный CSRF-токен');
        }
    }

    /**
     * Обработка загруженных файлов: поддерживает одиночный файл (input name="attachment")
     * и несколько файлов (input name="attachments[]").
     */
    private function handleAttachments(Request $request, Campaign $campaign): void
    {
        $files = $request->files->get('attachments');

        if (!$files) {
            $single = $request->files->get('attachment');
            $files = $single ? [$single] : [];
        }

        if (!is_array($files)) {
            $files = [];
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid() || '' === $file->getClientOriginalName()) {
                continue;
            }

            try {
                $storageKey = $this->storage->store($file);
                $attachment = (new CampaignAttachment($campaign, (string) $file->getClientOriginalName(), $storageKey))
                    ->setMimeType($file->getMimeType())
                    ->setSize((int) $file->getSize());
                $this->em->persist($attachment);
            } catch (\Throwable) {
            }
        }

        $this->em->flush();
    }
}
