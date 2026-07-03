---
date: 2026-07-03
topic: php-value-objects-library
---

# Библиотека Value Objects для PHP 8.4+

> Статус: готов к планированию. Все блокеры закрыты (D1/D2/D3 подтверждены
> пользователем; имя пакета и Money приняты как ревизуемые defaults, пока
> пользователь был away). Технические уточнения вынесены в «Deferred to
> Planning».
>
> Пакет: **`msstc4php/value-objects`**, namespace **`MSSTC4PHP\ValueObject\`**.

## Problem Frame

Нужна переиспользуемая библиотека базовых Value Object'ов для PHP 8.4+.
Сейчас в каждом проекте VO (email, IP, UUID, валюты, гео-координаты...)
пишутся заново — с расходящейся валидацией, без единого контракта,
без готовой интеграции с Doctrine и Symfony Validator.

Цель — один источник правды для базовых доменных примитивов, который:

- работает **standalone** (голый PHP, без фреймворка, ноль обязательных
  runtime-зависимостей);
- **совместим с Doctrine ORM и ODM** — каждый скалярный VO имеет готовый
  mapping (custom Type), составные — embeddable;
- **совместим с Symfony Validator** — каждый VO имеет Constraint +
  ConstraintValidator;
- каждый VO **fail-fast**: невалидируемое значение → исключение в
  конструкторе, невалидного состояния не существует.

Аудитория — сам разработчик и его проекты (Hex/DDD-стиль). VO — это
Domain-примитивы, поэтому библиотека должна укладываться в
[[hex-architecture]] без протечки инфраструктуры в ядро.

## Requirements

### Ядро и контракт VO

- **R1.** Каждый VO — `final readonly class`, реализующий общий интерфейс
  `ValueObject` (`equals(self): bool`, `jsonSerialize()`, где уместно
  `__toString()`). Невалидного состояния после конструктора не бывает.
- **R2.** Конструктор валидирует вход и бросает типизированное исключение
  (`InvalidArgumentException`-потомок, напр. `InvalidIpv4Exception`) с
  внятным сообщением. Ни один VO не хранит «частично валидное» состояние.
- **R3.** Единый источник правды валидации: каждый VO предоставляет
  статический `isValid(mixed): bool` и `tryFrom(mixed): ?static`.
  Конструктор, `tryFrom` и Symfony-валидатор **делегируют одному и тому же
  правилу** — регулярки/логика не дублируются.
- **R4.** Именованные конструкторы: `from()` / `tryFrom()` как базовый
  контракт + типо-специфичные (`Uuid::v4()`, `IpAddress::fromString()`,
  `Money::fromMinorUnits()`).
- **R5.** Тонкие абстрактные базы для повторяющихся форм:
  `AbstractStringValueObject`, `AbstractIntValueObject` — чтобы конкретный
  VO сводился к объявлению правила, а не к плите бойлерплейта.

### Интеграция с Symfony Validator

- **R6.** Для каждого VO — пара Constraint + ConstraintValidator. Валидатор
  делегирует `Vo::isValid()` (R3), не дублируя правило. Эргономичные
  атрибуты вида `#[AssertIpv4]` с адаптированными сообщениями.
- **R7.** Плюс один generic-fallback constraint `#[ValidValueObject(Ipv4::class)]`
  для VO, которым не нужен именной атрибут.
- **R8.** Интеграции подключаются только при наличии `symfony/validator`;
  ядро от него не зависит.

### Интеграция с Doctrine

- **R9.** Для каждого скалярного VO — кастомный Doctrine DBAL `Type`
  (ORM, хранение в `VARCHAR`/`INT`) и аналогичный ODM `Type` (Mongo).
- **R10.** Составные VO (`GeoCoordinates` = lat + lng) — вариант
  `#[ORM\Embeddable]` / ODM `EmbeddedDocument` **и** строково-сериализуемый
  Type на выбор потребителя.
- **R11.** Хелпер-регистратор Type'ов, не зависящий от Symfony-бандла
  (работает и в голой Doctrine), + пример конфигурации для бандла.
- **R12.** Интеграции подключаются только при наличии
  `doctrine/orm` / `doctrine/dbal` / `doctrine/mongodb-odm`.

### Каталог VO (v1)

- **R13. Сеть:** `Ipv4Address`, `Ipv6Address`, `IpAddress` (v4|v6 с
  дискриминацией), `MacAddress`, `Hostname` (RFC 1123), `DomainName`,
  `Port` (`int<0,65535>`), `Uri` (RFC 3986), `Url` (абсолютный http/https),
  `UrlScheme`, `UrlPath`.
- **R14. Идентификаторы:** `Uuid` (валидация + определение версии
  v1/v3/v4/v5/v6/v7), `EmailAddress` (прагматичный RFC 5321/5322; DNS-проверка
  **не** входит в VO).
- **R15. i18n / гео:** `CountryCode` (ISO 3166-1 alpha-2/alpha-3/numeric),
  `LanguageCode` (ISO 639-1), `CurrencyCode` (ISO 4217), `Locale` (BCP 47),
  `Latitude` (−90..90), `Longitude` (−180..180), `GeoCoordinates` (lat+lng).
- **R16. Базовые примитивы** (из slepic + awesome-list):
  `NonEmptyString`, `BoundedString` (min/max), `PositiveInt`,
  `NonNegativeInt`, `IntRange`, `Percentage`, `HexColor`, `Slug`,
  `Base64String`, `JsonString`, `PhoneNumber` (E.164).

### Тесты и качество

- **R17.** Полное покрытие юнит-тестами каждого VO (валидные/невалидные
  входы, границы, `equals`, сериализация) + тесты Doctrine Type round-trip
  и Symfony Constraint. PHPStan level max, строгая типизация (см. глобальные
  правила проекта).

## Success Criteria

- Один `composer require`, VO работают без фреймворка сразу же.
- Установка `symfony/validator` / `doctrine/*` включает интеграции без
  дополнительного кода в ядре (autoload-safe bridges).
- Валидация VO и Symfony-констрейнта **всегда совпадает** (общее правило R3)
  — доказано тестом, гоняющим один вход через оба пути.
- Doctrine round-trip (PHP VO → БД → PHP VO) сохраняет равенство по `equals`.
- Добавление нового VO = 1 класс VO + 1 Type + 1 Constraint по шаблону,
  без правок ядра.
- PHPStan max зелёный, покрытие VO ~100%.

## Scope Boundaries

- **Не** реализуем сетевые операции (DNS-резолв, ping, HTTP) — только
  синтаксическая/структурная валидация значений.
- **Не** тянем тяжёлую доменную арифметику денег в ядро (см. решение о Money).
- **Не** делаем страна-специфичные `PostalCode` в v1 (сотни несводимых
  форматов) — кандидат на потом.
- **Не** пишем свой Symfony-бандл обязательным: интеграция работает и на
  голой Doctrine/Validator; бандл-конфиг — опциональный пример.
- **Не** поддерживаем PHP < 8.4 (нужны `final readonly`, актуальные фичи).

## Key Decisions

- **D1. Упаковка — один Composer-пакет + optional deps.** ✅ подтверждено.
  Ядро VO без обязательных зависимостей; `doctrine/*` и `symfony/validator`
  в `suggest` + `require-dev`. Bridge-классы autoload-safe (грузятся только
  при наличии фреймворка). *Обоснование:* низкий порог установки, идиоматично
  (ramsey/uuid, brick/math). Монорепо признано избыточным для базовой
  VO-библиотеки (YAGNI).
- **D2. Единый источник валидации (R3).** ✅ подтверждено. Правило живёт в
  VO (`isValid()`/`tryFrom()`); Symfony-валидатор его переиспользует.
  Пара Constraint+Validator сохраняется, но правило не дублируется —
  исключает расхождение VO ↔ Validator.
- **D3. ISO-множества — backed enum'ы.** ✅ подтверждено. `CountryCode`,
  `CurrencyCode`, `LanguageCode` — конечные стандартные наборы → enum даёт
  исчерпываемость и типобезопасность. Данные генерируются из ISO-источника
  на этапе сборки.
- **D4. `final readonly` + fail-fast конструктор** — по глобальным правилам
  проекта (immutable VO, валидация один раз при создании).
- **D5. Имя пакета `msstc4php/value-objects`, namespace
  `MSSTC4PHP\ValueObject\`.** ✅ подтверждено пользователем. Bridge-
  подпространства: `MSSTC4PHP\ValueObject\Doctrine\`,
  `MSSTC4PHP\ValueObject\Symfony\`.
- **D6. Money — interop с `brick/money`, без своей арифметики.** Default
  (принят). В ядре только `CurrencyCode` (ISO 4217 enum). Полноценные
  деньги (округления, конвертации, операции) — зона ответственности
  `brick/money`; опциональный bridge даёт Doctrine Type для `Brick\Money`.
  *Обоснование:* деньги — отдельный сложный домен, «минимальный свой Money»
  провоцирует полупуть (люди дописывают add/sub/rounding) и баги округления.

## Dependencies / Assumptions

- PHP 8.4+ (целевая, единственная).
- Doctrine ORM 3.x / DBAL 4.x, doctrine/mongodb-odm — как optional.
- Symfony Validator 7.x — как optional.
- Namespace вендора `MSSTC4PHP\ValueObject\...` (D5).
- `brick/money` — optional dep для денежного bridge (D6).
- Инфраструктура качества по глобальным правилам: PHPStan max, PHP-CS-Fixer,
  Rector, PHPUnit.

## Outstanding Questions

### Resolve Before Planning

_Пусто — все блокеры закрыты (D1/D2/D3 подтверждены; D5 имя пакета и D6 Money
приняты как ревизуемые defaults). Если вернёшься и захочешь сменить имя
пакета или включить свой Money — это локальные правки, планирование не
блокируют._

### Deferred to Planning

- **[Affects R14][Technical]** `Uuid`: самодостаточная реализация валидации
  vs тонкая обёртка над `symfony/uid` / `ramsey/uuid`. Влияет на
  зависимости ядра — решить при планировании.
- **[Affects R15][Needs research]** Источник ISO-данных для enum'ов:
  генерация из `symfony/intl` vs встроенный статический датасет. Проверить
  лицензии/актуальность при планировании.
- **[Affects R9-R11][Technical]** Форма API регистрации Doctrine Type'ов для
  голой Doctrine (без Symfony) — уточнить при планировании.
- **[Affects R16][Technical]** Финальный отбор «базовых примитивов» v1 vs
  v1.1 (`CreditCardNumber`/Luhn, `Ean`/`Isbn`, `Timezone`, `CidrBlock` —
  кандидаты, не блокеры).

## Next Steps

→ `/ce:plan` для структурного плана реализации. Блокеров нет; технические
уточнения (`Uuid`-зависимость, источник ISO-данных, API регистрации Doctrine
Type, финальный отбор базовых примитивов) решаются в ходе планирования.
