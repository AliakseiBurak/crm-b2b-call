# ER-схема БД (ядро: организации + контакты + модель доступа + обзвон)

Сгенерировано из спецификаций `organizations`, `contacts`, `calls`,
`campaigns`, `organization-groups`, `access-control` (spec-driven,
`openspec/design`).

## Диаграмма

```mermaid
erDiagram
    USER ||--o| ORGANIZATION_GROUP : "владеет user-<id>-group (0..1)"
    USER ||--o{ GROUP_ASSIGNMENT : "назначен на группы"
    ORGANIZATION_GROUP ||--o{ GROUP_ASSIGNMENT : "назначается менеджерам"
    ORGANIZATION_GROUP ||--o{ ORG_GROUP_MEMBERSHIP : "содержит организации"
    ORGANIZATION ||--o{ ORG_GROUP_MEMBERSHIP : "состоит в группах"
    ORGANIZATION ||--o{ CONTACT : "имеет контакты"
    USER ||--o{ CALL : "сделал звонок (made_by)"
    ORGANIZATION ||--o{ CALL : "история звонков"
    CONTACT ||--o{ CALL : "звонки по контакту"
    CALL o|--o| CALL : "next_call_id (self-ref, 0..1)"
    CAMPAIGN ||--o{ CAMPAIGN_ATTACHMENT : "вложения на кампании"
    CAMPAIGN ||--o{ CAMPAIGN_RECIPIENT : "ручные адресаты standalone"
    ORGANIZATION ||--o{ CAMPAIGN_RECIPIENT : "адресат standalone-рассылки"
    CONTACT ||--o{ CAMPAIGN_RECIPIENT : "адресат-контакт (nullable)"
    CAMPAIGN ||--o{ EMAIL_OUTBOX : "письма к отправке"
    EMAIL_OUTBOX ||--o{ EMAIL_STATUS_LOG : "история статусов"
    COMMUNICATION_TEMPLATE o|--o{ COURSE : "embedded courses (0..N)"

    USER {
        bigint id PK
        string email UK
        string password_hash
        enum role "admin|manager"
        datetime created_at
    }

    ORGANIZATION_GROUP {
        bigint id PK
        string name "user-<id>-group | custom name"
        string slug UK "напр. minsk-region-group"
        enum type "user|custom"
        bigint owner_user_id FK "менеджер для type=user; NULL для admin/custom"
        datetime created_at
    }

    GROUP_ASSIGNMENT {
        bigint user_id FK "manager"
        bigint group_id FK
        datetime assigned_at
        PK(user_id, group_id)
    }

    ORG_GROUP_MEMBERSHIP {
        bigint organization_id FK
        bigint group_id FK
        datetime added_at
        PK(organization_id, group_id)
    }

    ORGANIZATION {
        bigint id PK
        string name
        string industry
        datetime created_at
        datetime updated_at
    }

    CONTACT {
        bigint id PK
        bigint organization_id FK
        string name
        string phone
        string email
        string position
        enum contact_type "person|legal_entity"
        string contact_person
        text notes
        datetime created_at
        datetime updated_at
    }

    CALL {
        bigint id PK
        bigint organization_id FK
        bigint contact_id FK
        datetime scheduled_at "будущее -> планирование/напоминание"
        datetime made_at "факт звонка: когда"
        bigint made_by FK "факт звонка: кто"
        text notes
        boolean is_deal "результат: сделка"
        bigint next_call_id FK "self-ref: вновь созданный Call (0..1)"
        bigint campaign_id FK "результат: одна рассылка (0..1)"
        datetime created_at
    }

    CAMPAIGN {
        bigint id PK
        string name
        string subject "тема письма"
        string preview_text "nullable, прехедер"
        text body "текст письма (токены {{greeting}}, {{contact_name}}, {{organization_name}})"
        enum status "draft|ready|launched|failed|archived, default draft"
        datetime launched_at "nullable; ручной запуск — кнопка «Запустить»"
        datetime failed_at "nullable; фиксируется при ошибке отправки"
        datetime created_at
    }

    CAMPAIGN_ATTACHMENT {
        bigint id PK
        bigint campaign_id FK "ON DELETE CASCADE (строки); файлы удаляет storage-слой"
        string filename "оригинальное имя файла"
        string storage_key UK "ключ файла в var/storage/campaign-attachments"
        string mime_type "nullable"
        int size "nullable"
        datetime created_at
    }

    CAMPAIGN_RECIPIENT {
        bigint id PK
        bigint campaign_id FK "ON DELETE CASCADE"
        bigint organization_id FK "ON DELETE CASCADE; менеджеру — только область доступа (ADR-0007), отказ 403"
        bigint contact_id FK "nullable, ON DELETE CASCADE; рассылка на email контакта"
        datetime created_at
        "unique(campaign_id, organization_id)"
    }

    EMAIL_OUTBOX {
        bigint id PK
        bigint campaign_id FK
        bigint organization_id FK
        bigint contact_id FK
        enum status "pending|sent|delivered|bounced|opened"
        string recipient_email
        string tracking_token UK "для opened через pixel"
        text rendered_subject
        text rendered_body
        datetime sent_at
        datetime updated_at
    }

    EMAIL_STATUS_LOG {
        bigint id PK
        bigint outbox_id FK
        enum status
        datetime changed_at
    }

    COMMUNICATION_TEMPLATE {
        bigint id PK
        string subject "с токенами {{contact_name}} {{organization_name}}"
        text body "с токенами и встроенными курсами"
        datetime created_at
    }

    COURSE {
        bigint id PK
        string name
        string category
        decimal base_price
        text description
        string pdf "ссылка на PDF-материал"
        datetime created_at
    }
```

> `COMMUNICATION_TEMPLATE` больше не связана с `Campaign`: с change
> `campaign-entity` тема и шаблон письма хранятся на самой кампании
> (`campaign.subject`, `campaign.template`), вложения — в
> `campaign_attachment`.

## Правила (ADR-0001–0009)

1. **`USER.role`** — фиксированный enum `admin|manager` (ADR-0009); роли не
   создаются/не изменяются через CRUD.
2. **Организация** — главная модель (ADR-0001); контакт принадлежит ровно
   одной организации (ADR-0002).
3. **Пользователи создаются администратором**; при создании менеджера
   автосоздаётся `user-<id>-group` (ADR-0003, ADR-0005). Админ собственной
   группы не имеет; группы для него не проверяются (ADR-0008).
4. **Custom-группы** — slug-имена, создаёт администратор, назначаются
   менеджерам через `GROUP_ASSIGNMENT`; членство организации в группах —
   many-to-many (ADR-0006).
5. **Область доступа менеджера** — бинарная: организации собственной группы +
   назначенных custom-групп (ADR-0007); per-org ACL не вводится.
6. **Сущность `Call`** (ADR-0004): результат — комбинация независимых отметок
   (одна `campaign_id`, `is_deal`, `next_call_id`); факт звонка всегда
   фиксируется (`made_at`, `made_by`).
7. **Рассылки** не привязаны к одной организации; формируются из результатов
   звонков и/или вручную (standalone). Отправка — outbox через SMTP, статусы
   per-письмо + `opened` через tracking-pixel (ADR-0010).
8. **`Campaign`** (change `campaign-entity`) хранит тему, текст письма и вложения на
   себе; адресаты — в `campaign_recipient` (contact_id nullable).
   запуск — ручной (кнопка «Запустить») —
   проставляет `launched_at` и `status = launched`.

## Скоуп первой реализации

Таблицы **ядра**: `user`, `organization_group`, `group_assignment`,
`org_group_membership`, `organization`, `contact`. Обзвон (`call`) реализован;
рассылки — `campaign` и `campaign_attachment` (change `campaign-entity`);
`email_outbox`/`call_result` — следующие шаги (e-mail → результаты звонков).