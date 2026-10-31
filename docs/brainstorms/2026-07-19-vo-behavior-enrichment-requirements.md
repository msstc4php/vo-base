---
date: 2026-07-19
topic: vo-behavior-enrichment
---

# VO Behavior Enrichment (v1.1)

> Итерация над уже собранной библиотекой `msstc4php/value-objects`. Ядро,
> контракты, мосты и каталог из [[2026-07-03-php-value-objects-library-requirements]]
> считаются сделанными и стабильными. Здесь — только **обогащение
> существующих VO поведением**, без расширения каталога и без смены
> архитектуры. Все изменения **аддитивные** (semver-minor, обратно
> совместимо).

## Problem Frame

Библиотека сильна в **валидации и конструировании**, но большинство VO —
«валидатор со строкой внутри». Доменные операции, которые и есть главная
причина брать типизированный VO, отсутствуют ровно там, где они нужнее
всего: `Cidr` не умеет `contains()`, `IpAddress` — `isPrivate()`, `Decimal`
заявляет arbitrary precision, но не считает, `Duration` не складывается,
`Comparable` объявлен, но реализован лишь в одном классе.

Цель итерации — превратить анемичные валидаторы в **примитивы с
поведением**, сохранив принципы ядра: `final readonly`, fail-fast,
immutable (`with*` возвращают новый инстанс), zero-dependency ядро
(поведение не должно тянуть runtime-зависимости, кроме явно оговорённой
арифметики через `brick/math`).

Аудитория — прежняя: сам разработчик и его Hex/DDD-проекты.

## Key Decisions

- **KD1. Приоритет — поведение, не каталог.** Новые VO (Timezone, Ulid,
  Bytes и т.п.) в эту итерацию НЕ входят — отдельный трек. Здесь только
  методы на существующих типах.
- **KD2. Арифметика делегируется `brick/math`** (optional dep, как
  `brick/money`). Свою арифметику с округлением не пишем — это тот самый
  риск полу-пути из D6 исходного дока. `Decimal`/`Percentage` арифметика
  доступна только при установленном `brick/math`; без него VO продолжает
  валидироваться, сравниваться (`compareTo`) и сериализоваться.
- **KD3. Сетевые предикаты — zero-dep.** `Cidr::contains()`,
  `IpAddress::isInRange()` и семейство `isPrivate/isLoopback/...`
  реализуются через `inet_pton` + битовую маску по бинарной строке
  (ext-standard). brick/math для них НЕ требуется. IPv6 покрывается тем же
  бинарным подходом.
- **KD4. Иммутабельность сохраняется.** Любой мутатор — `with*(): static`,
  строит новый инстанс через тот же валидирующий конструктор. Ни один
  новый метод не создаёт невалидного состояния и не мутирует `$this`.
- **KD5. Поведение переиспользует существующую валидацию.** Аксессоры,
  возвращающие другой VO (`EmailAddress::domain(): DomainName`,
  `Cidr::address(): IpAddress`), конструируют его через штатный путь —
  единый источник правды не дублируется.

## Requirements

### Cross-cutting

- **B1. Повсеместный `Comparable`.** Реализовать `Comparable` (уже
  объявленный контракт) во всех VO с естественным порядком: `Port`,
  `Percentage`, `NonNegativeInt`, `PositiveInt`, `TimeOfDay`, `Duration`,
  `SemVer` (semver-precedence), `Latitude`, `Longitude`. Плюс дефолтные
  хелперы `isBefore`/`isAfter`/`min`/`max`, выведенные из `compareTo`
  (можно тонким трейтом).
- **B2. `with*`-мутаторы — контракт.** Каждый мутатор возвращает `static`,
  не мутирует `$this`, перепроходит валидацию. Никаких сеттеров.
- **B3. Аддитивность и совместимость.** Все методы — новые; сигнатуры
  существующих не меняются. PHPStan level 9 зелёный, покрытие и infection
  MSI не падают ниже текущего.

### Сеть (zero-dep, KD3)

- **B4. `Cidr`** — `contains(IpAddress): bool`, `firstAddress()`,
  `lastAddress()`, `netmask()`, `broadcast()` (v4), `hostCount()` (см.
  вопрос по IPv6-переполнению), `overlaps(self)`, `isSubnetOf(self)`.
- **B5. `IpAddress`/`Ipv4Address`/`Ipv6Address`** — `isPrivate()`,
  `isLoopback()`, `isLinkLocal()`, `isMulticast()`, `isPublic()`,
  `isInRange(Cidr): bool`. Для `IpAddress` — делегирование конкретной
  версии.
- **B6. `Url`/`Uri`** — `withScheme()`, `withHost()`, `withPort()`,
  `withPath()`, `withQuery()`, `withFragment()`; `query(): array`-разбор
  query-строки (или доступ к сырой query). Все `with*` перевалидируют
  результат целиком (B2).
- **B7. `EmailAddress`** — `localPart(): string`, `domain(): DomainName`.
- **B8. `Hostname`/`DomainName`** — `labels(): list<string>`, `tld()`,
  `isSubdomainOf(DomainName): bool`.

### Временны́е

- **B9. `Duration`** — `toSeconds(): int`, `plus(self)`, `minus(self)`,
  `multipliedBy(int)`, `isZero()`, `Comparable`. Отрицательные/переполнение
  — согласно валидации конструктора.
- **B10. `DateRange`** — `duration(): Duration`, `intersect(self): ?self`,
  `gap(self): ?self`, `contains(self): bool` (диапазон в диапазоне),
  `isAdjacentTo(self): bool`. Все времена — UTC.
- **B11. `TimeOfDay`** — `plus(Duration)`, `minus(Duration)` (с
  переносом/wrap по решению планирования), `isBefore`/`isAfter`,
  `Comparable`.

### Числа и формат (арифметика через `brick/math`, KD2)

- **B12. `Decimal`** — `add`, `subtract`, `multiply`, `divide` (с режимом
  округления/scale), `round`, `abs`, `negated`, `sign()`. Реализация —
  через `brick/math`; при его отсутствии методы бросают понятное
  `MissingDependencyException` с подсказкой установки, а
  `compareTo`/`isZero`/валидация работают без него.
- **B13. `Percentage`** — `of(int|Decimal)`, `applyTo(int|Decimal)`,
  `fromRatio(...)`, арифметика через тот же путь brick/math.
- **B14. `IntRange`** — `contains(int): bool`, `length(): int`,
  `overlaps(self)`, `intersect(self): ?self`.
- **B15. `SemVer`** — `compareTo` по правилам semver-precedence (учёт
  pre-release), `isStable()`, `nextMajor/Minor/Patch()` (`with*`, B2).

## Success Criteria

- Каждый новый метод покрыт тестами (границы, immutable-инвариант,
  round-trip где уместно); PHPStan 9 зелёный; infection MSI не ниже
  текущего.
- **Сетевое поведение доказуемо zero-dep**: тест `Cidr::contains` для
  IPv4 и IPv6 проходит без `brick/math` в autoload (в т.ч. «core-only»
  CI-нога).
- **Арифметика изолирована**: библиотека ставится и валидирует `Decimal`
  без `brick/math`; при вызове арифметики без него — внятная ошибка, не
  фатал в неожиданном месте. С `brick/math` — округление корректно
  (доказано тестом на «неудобных» делениях).
- Ни один новый метод не создаёт невалидного состояния и не мутирует
  `$this` (доказано тестом иммутабельности).
- `Comparable` реализован во всех перечисленных в B1 VO; сортировка
  массива VO через `compareTo` даёт ожидаемый порядок.

## Scope Boundaries

- **Не** расширяем каталог VO (Timezone/Ulid/PostalCode/Bytes и пр. —
  отдельная будущая итерация).
- **Не** делаем сетевой I/O (DNS/ping/HTTP) — только вычисления над
  значением (маски, диапазоны — чистая арифметика на бинарной строке).
- **Не** строим свой Money и свою арифметику с округлением — только
  делегирование `brick/math` (KD2/D6).
- **Не** трогаем существующие сигнатуры и мосты, кроме добавления новых
  Doctrine-типов, если появятся новые сериализуемые формы (не ожидается).
- **Не** вводим violation-collecting путь (по-прежнему fail-fast).

## Dependencies / Assumptions

- `brick/math` — новый **optional** dep (suggest + require-dev), нужен
  только для арифметики `Decimal`/`Percentage` (B12/B13).
- `ext-standard` (`inet_pton`/`inet_ntop`) — для сетевых предикатов;
  доступен всегда.
- Прочее — как в исходном доке (PHP 8.4+, Doctrine/Symfony optional).

## Outstanding Questions

### Resolve Before Planning

_Пусто — блокеров нет. Все продуктовые развилки (направление, судьба
арифметики) закрыты (KD1/KD2)._

### Deferred to Planning

- **[Affects B4][Technical]** `hostCount()` для больших IPv6-подсетей
  переполняет `int`. Решить: возвращать `string`/`BigInteger` (brick/math,
  но тогда метод перестаёт быть zero-dep) либо ограничить/опустить для
  очень широких префиксов.
- **[Affects B12][Technical]** Форма API арифметики `Decimal` при
  отсутствии `brick/math`: методы бросают `MissingDependencyException`
  vs отдельный класс-обёртка `BrickDecimal`/трейт. Выбрать в планировании.
- **[Affects B6][Technical]** Модель query у `Url`: `array<string,string>`
  разбор vs отдельный typed `QueryString` VO (кандидат каталога — но не в
  этой итерации). Пока — минимальный разбор в массив.
- **[Affects B11][Technical]** `TimeOfDay::plus` при переходе через
  полночь: wrap (mod 24h) vs исключение. Решить в планировании (вероятно
  wrap с ясным контрактом).
- **[Affects B12/B13][Needs research]** Дефолтный режим округления и scale
  для `divide` — свериться с политикой `brick/math`/`brick/money`, чтобы
  поведение совпадало с денежным bridge.

## Next Steps

→ `/ce:plan` для структурного плана реализации. Блокеров нет; технические
уточнения (IPv6 hostCount, форма Decimal-API без brick/math, модель query,
wrap у TimeOfDay, режим округления) решаются в ходе планирования.
