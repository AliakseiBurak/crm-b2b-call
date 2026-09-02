## MODIFIED Requirements

### Requirement: Результат звонка
The call result SHALL be a combination of independent actions: at most one mailing campaign per save (see `campaigns`) which creates or replaces a recipient, optional removal of the organization from a campaign it already belongs to, a deal mark, a no-answer mark, and a next call created from a submitted date; a call MAY have none of them, recording only the fact of the call. Mailing and next call MAY be combined. Refusal of campaign A and mailing of campaign B MAY be combined. Refusal and mailing of the same campaign SHALL replace the recipient (mailing action). Deal and no-answer SHALL NOT prevent mailing.

#### Scenario: Результат — рассылка
- **WHEN** менеджер завершает звонок по организации «ООО Ромашка»
- **AND** выбирает из списка рассылку «Осенняя рассылка»
- **THEN** организация «ООО Ромашка» становится адресатом рассылки «Осенняя рассылка» по итогам звонка

#### Scenario: Результат — сделка
- **WHEN** менеджер завершает звонок по организации «ООО Ромашка»
- **AND** отмечает «сделка совершена»
- **THEN** в карточке звонка отображается отметка о совершённой сделке

#### Scenario: Результат — нет ответа
- **WHEN** менеджер завершает звонок по организации «ООО Ромашка»
- **AND** отмечает «нет ответа»
- **THEN** в карточке звонка отображается отметка об отсутствии ответа

#### Scenario: Результат — будущий звонок
- **WHEN** менеджер завершает звонок по организации «ООО Ромашка»
- **AND** указывает дату следующего звонка
- **THEN** создаётся новый звонок с этой датой, связанный со звонком
- **AND** организация «ООО Ромашка» попадает в планирование/напоминание

#### Scenario: Комбинация результатов
- **WHEN** менеджер завершает звонок по организации «ООО Ромашка»
- **AND** одновременно выбирает рассылку «Осенняя рассылка», отмечает сделку, «нет ответа» и назначает следующий звонок
- **THEN** все выбранные действия выполняются вместе со звонком

#### Scenario: Отказ и другое письмо
- **WHEN** организация «ООО Ромашка» уже адресат рассылки «Акция»
- **AND** менеджер убирает её из «Акция» и выбирает рассылку «Осенняя рассылка»
- **THEN** организация больше не адресат «Акция»
- **AND** организация становится адресатом «Осенняя рассылка»

#### Scenario: Отказ и письмо одной кампании
- **WHEN** организация «ООО Ромашка» уже адресат рассылки «Осенняя рассылка»
- **AND** менеджер убирает её из «Осенняя рассылка» и снова выбирает рассылку «Осенняя рассылка»
- **THEN** адресат заменяется по правилам действия «рассылка»
- **AND** организация остаётся адресатом «Осенняя рассылка»

#### Scenario: Звонок без результата
- **WHEN** менеджер завершает звонок по организации «ООО Ромашка»
- **AND** не выбирает рассылку, отказ, сделку, «нет ответа» и следующий звонок
- **THEN** фиксируется только факт звонка (кто и когда)

### Requirement: Сущность звонка
The system SHALL store a call with an organization, an optional contact, a scheduled date, the fact of the call (who made it and when), optional notes, optional deal and no-answer marks, an optional reference to the last mailing campaign chosen from this call, and an optional reference to the last next call created from this call.

#### Scenario: Запланированный звонок
- **WHEN** в системе существует организация «ООО Ромашка» и контакт «Иван Петров»
- **AND** менеджер планирует звонок контакту «Иван Петров» на завтра
- **THEN** звонок сохраняется с датой «завтра»

#### Scenario: Фиксация факта звонка
- **WHEN** менеджер «Иван Петров» провёл звонок контакту организации «ООО Ромашка»
- **AND** отмечает дату и время звонка
- **THEN** в истории звонков организации отображается, что звонок осуществил менеджер «Иван Петров» в указанные дату и время
