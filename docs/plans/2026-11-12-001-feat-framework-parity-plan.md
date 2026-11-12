# Framework Parity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or
> superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give Laravel, Yii2, CodeIgniter 4 and Spiral developers the Symfony bundle's request → value object (field
error) and route → value object (404) conveniences, each through its framework's native mechanism.

**Architecture:** Four independent packages, each depending only on `msstc4php/vo-base ^1.1` and going through
`Payload\ValueObjectMapper` (`supports`, `supportsUntrusted`, `isScalarBacked`, `isComposite`, `fromUntrustedPayload`).
Three existing bridges get additive 0.2.0 releases; `vo-base-spiral` is a new 0.1.0 package scaffolded like
`vo-base-doctrine`. Parts A–D below are independent and may run in parallel.

**Tech Stack:** PHP 8.4, Laravel 13, Yii 2.0.55+, CodeIgniter 4.7.4+, Spiral 3.18, PHPUnit ≥ 11, PHPStan max, Infection.

**Spec:** `vo-base/docs/brainstorms/2026-11-12-framework-parity-requirements.md` — read it first; it holds the
verified framework extension points (file and method names) this plan relies on.

## Global Constraints

- Input only through `ValueObjectMapper::fromUntrustedPayload()`; a class with `supportsUntrusted() === false`
  (`BoundedString`) is refused on every input path.
- No PHP class name in any client-facing message. Use the type label ("email address" for `EmailAddress`): short class
  name split at lower→upper boundaries, lowercased — the algorithm of `vo-base-laravel/src/Rules/ValidValueObject.php`
  `typeLabel()`. Each package keeps one `@internal` copy (`Internal\TypeLabel::of(class-string): string`); in Laravel
  the rule switches to it.
- Route/action binding: scalar-backed value objects only. A parameter typed with a supported but unbindable class
  (trusted-source only, or composite) is a `\LogicException` naming the parameter and the reason — never the raw string
  reaching the typed parameter.
- Enums: Laravel routes and Spiral filters leave backed enums to the framework; Yii2 and CodeIgniter 4 action
  arguments convert them.
- Nullable parameter/property: missing or `null` → `null`. Empty string `''` on a non-nullable parameter is invalid.
- An argument value that is already an instance of the declared class passes through untouched.
- Union and intersection parameter types are left alone (no conversion, no exception).
- Existing 0.1.0 public APIs unchanged. CHANGELOG entry `## 0.2.0 - <release date UTC>` (Spiral `## 0.1.0`).
- Gates per package, with the local install (`COMPOSER=composer-local.json`): `make check` (PHPStan max +
  `phpstan-ci.neon`, cs-fixer, rector, deptrac), `make test`, `XDEBUG_MODE=coverage vendor/bin/infection
  --threads=$(nproc) --no-interaction --min-msi=75 --min-covered-msi=75`, `composer validate`, the templates `cmp`
  from `checks.yml`, and one `--prefer-lowest --prefer-stable` run in a `$TMPDIR` copy (clear any kernel/cache
  directory shared through `sys_get_temp_dir()` afterwards). `#[CoversClass]` only on the package's own classes.
- Tool configs byte-identical to `bundle-standard` templates; never edit them.
- Comments minimal, English. Commits: `git -c user.name="Maxim Shamaev" -c user.email="maxim.shamaev@gmail.com"`,
  ending with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. **Never push or tag.**
- Docs per package: README section per new feature with a usage example, `CHANGELOG.md`, `.claude/docs/`
  (`architecture.md`, `integrations.md`, `known-issues.md` for every framework quirk met).

## Review Focus

1. Route parameter name in a different case from the argument (`{user_email}` → `$userEmail`): Laravel's implicit
   binding matches both, so ours must too (A4 test).
2. Array where a scalar is expected (`?email[]=x`, a JSON array body for `EmailAddress`): field error / 404, not 500
   (A2, A4, B1, C1, D2 tests).
3. A value that is already a value object (bound earlier by `Route::bind`, passed programmatically to a Yii action):
   passes through (A4, B1 tests).
4. Nullable argument with the route/query parameter absent → `null`; non-nullable absent → the framework's own
   missing-parameter behaviour, unchanged (B1, C1 tests).
5. Union type (`EmailAddress|string $id`): untouched by the binder (A4, B1, C1, D3 tests).

---

## Part A — `vo-base-laravel` 0.2.0

### Task A1: Service provider, shared mapper, type label

**Files:**
- Create: `src/ValueObjectServiceProvider.php`, `src/Internal/TypeLabel.php`
- Modify: `src/Rules/ValidValueObject.php` (use `TypeLabel`), `composer.json`, `composer-ci.json`, `composer-local.json`
- Test: `tests/Unit/ValueObjectServiceProviderTest.php`, `tests/Unit/Internal/TypeLabelTest.php`

**Interfaces:**
- Produces: `final class ValueObjectServiceProvider extends ServiceProvider` — `register()` binds
  `ValueObjectMapper::class` as a singleton (`new ValueObjectMapper()`); `boot()` calls `RequestMacros::register()`
  (A2) and `$router->matched(RouteValueObjectBinder)` (A4), each only when `Illuminate\Http\Request` /
  `Illuminate\Routing\Router` exist. `TypeLabel::of(class-string): string`.
- composer: `extra.laravel.providers: ["MSSTC4PHP\\ValueObject\\Laravel\\ValueObjectServiceProvider"]`; `require-dev`
  `laravel/framework ^13.0` (and `orchestra/testbench` if the builder wants an app harness); `suggest` names
  `laravel/framework` for request and route support. Runtime `require` unchanged.

- [ ] Step 1: tests — `testMapperIsASingleton` (two `make(ValueObjectMapper::class)` are identical),
      `testApplicationMayRebindTheMapperWithCodecs`, `TypeLabel::of(EmailAddress::class) === 'email address'`,
      `TypeLabel::of(Ipv4Address::class) === 'ipv4 address'`.
- [ ] Step 2: run, expect FAIL (classes missing).
- [ ] Step 3: implement; `ValidValueObject` keeps its behaviour and message.
- [ ] Step 4: `make test` passes, existing rule tests unchanged.
- [ ] Step 5: commit `feat: auto-discovered service provider with a shared mapper`.

### Task A2: `$request->valueObject()`

**Files:** Create `src/Http/RequestMacros.php`; Test `tests/Unit/Http/RequestValueObjectTest.php`

**Interfaces:**
- Produces: `RequestMacros::register(): void` adding `Request::macro('valueObject', …)` with the signature
  `valueObject(string $key, class-string<T> $class): ?T`. Reads `$this->input($key)`; not filled (missing, `null`,
  `''`) → `null`; composite: array, or JSON text decoded with `JSON_THROW_ON_ERROR`; invalid (incl. JSON error,
  trusted-only class) → `ValidationException::withMessages([$key => __(ValidValueObject::DEFAULT_MESSAGE,
  ['attribute' => <key with _ as spaces>, 'type' => TypeLabel::of($class)])])`. Unsupported class → `LogicException`.
  Mapper resolved from the container.

- [ ] Step 1: tests — valid `'a@b.c'` → `EmailAddress`; missing / `''` → `null`; `'nope'` → `ValidationException`
      whose `errors()` is `['email' => ['The email field is not a valid email address.']]` and `status === 422`;
      `['x']` for `EmailAddress` → same exception; `GeoCoordinates` from array and from JSON text; `BoundedString` →
      `ValidationException`; `stdClass` → `LogicException`; no message contains `MSSTC4PHP`.
- [ ] Step 2: run, FAIL. Step 3: implement. Step 4: run, PASS.
- [ ] Step 5: commit `feat: Request::valueObject() macro`.

### Task A3: Form request trait

**Files:** Create `src/Http/ValidatedValueObjects.php` (trait); Test `tests/Unit/Http/ValidatedValueObjectsTest.php`
with a fixture `FormRequest` in `tests/Fixtures/`.

**Interfaces:**
- Produces: `trait ValidatedValueObjects` — `public function valueObject(string $key, string $class): ?object`
  (generic `class-string<T>` → `?T`), reading `$this->validated($key)`; same null/composite/invalid rules as A2.

- [ ] Step 1: tests through a real `FormRequest` resolved with the `ValidValueObject` rule: validated value → value
      object; optional field absent → `null`; a key not in `rules()` → `null`.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: ValidatedValueObjects trait for form requests`.

### Task A4: Route parameters

**Files:** Create `src/Routing/RouteValueObjectBinder.php`; Test `tests/Integration/RouteBindingTest.php` (real
router dispatch, the provider booted).

**Interfaces:**
- Produces: `final readonly class RouteValueObjectBinder` with `__invoke(RouteMatched $event): void`. For each
  `$route->signatureParameters()` with a single named type: skip builtin, enum, and anything the mapper does not
  support; find the route parameter by name or `Str::snake(name)`; skip `null` and instances of the type; scalar-backed
  → `$route->setParameter()` with the value object; invalid → `NotFoundHttpException`; supported but not
  scalar-backed or not untrusted → `LogicException` (Global Constraints).

- [ ] Step 1: tests — `GET /users/a@b.c` with `fn (EmailAddress $email)` → 200 with the address; `/users/nope` →
      404 and the body has no `MSSTC4PHP`; `{user_email}` + `$userEmail` binds; `?EmailAddress $email` on an optional
      `{email?}` absent → `null`; `Route::bind('email', …)` returning an `EmailAddress` passes through; enum parameter
      still bound by Laravel; `EmailAddress|string` untouched; `BoundedString $x` → `LogicException`; controller-class
      action works like a closure.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: route parameters become value objects`.

### Task A5: Docs and release gates

- [ ] README sections "Request input", "Form requests", "Route parameters" (incl. rebinding the mapper for custom
      codecs, and that HTTP features need `laravel/framework`); CHANGELOG 0.2.0; `.claude/docs` updated.
- [ ] All Global Constraints gates pass; commit `docs: 0.2.0`.

## Part B — `vo-base-yii2` 0.2.0

### Task B1: Action parameters

**Files:** Create `src/Web/ValueObjectActionParams.php` (trait), `src/Internal/TypeLabel.php`; Test
`tests/Unit/Web/ValueObjectActionParamsTest.php` with fixture controllers (inline and standalone `Action`).

**Interfaces:**
- Produces: `trait ValueObjectActionParams` for `yii\web\Controller` overriding
  `bindActionParams($action, $params): array`. Reflects `InlineAction::$actionMethod` on `$this`, or `run` on a
  standalone action; for each parameter with a single named, non-builtin type present in `$params`: instance → as is;
  `null`/`''` → `null` if nullable, else invalid; scalar-backed or backed enum → `fromUntrustedPayload`; invalid
  (incl. arrays) → `yii\web\NotFoundHttpException`; supported but unbindable → `\LogicException`; then
  `parent::bindActionParams($action, $converted)`. Mapper: `Yii::$container->get(ValueObjectMapper::class)`.

- [ ] Step 1: tests via `$controller->runAction('view', ['email' => …])` — valid → value object reaches the action;
      `'nope'` → 404; `['x']` → 404; enum `'high'` → case, `'bogus'` → 404; nullable absent → `null`; non-nullable
      absent → Yii's `BadRequestHttpException` (missing param) unchanged; object passed in → untouched;
      `EmailAddress|string` untouched; `BoundedString` → `LogicException`; standalone `Action::run(EmailAddress $e)`
      works; DI-injected service parameters still injected.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: value objects as action parameters`.

### Task B2: Model values

**Files:** Create `src/Model/ValueObjectModel.php` (trait); Test `tests/Unit/Model/ValueObjectModelTest.php`.

**Interfaces:**
- Consumes: existing `Validator\ValueObjectValidator` (`$valueObjectClass`, `$mapper`), `Internal\CompositeJson`.
- Produces: `trait ValueObjectModel` for `yii\base\Model` — `valueObject(string $attribute): ?object` and
  `valueObjects(): array<string, object|null>` (every attribute with a `ValueObjectValidator`). Class and mapper come
  from the attribute's active `ValueObjectValidator` (validator's `$mapper` if set, else the container's). `null`/`''`
  → `null`. `\LogicException` when the model has errors, the attribute has no such validator, or the value does not
  convert (`validate()` was not called).

- [ ] Step 1: tests — after successful `load()`+`validate()` → `EmailAddress`; composite from JSON text; empty →
      `null`; with errors → `LogicException`; attribute without the validator → `LogicException`; `valueObjects()` keys.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: validated model attributes as value objects`.

### Task B3: Docs and release gates

- [ ] README "Action parameters" and "Model values" (REST 422 via returning the model); CHANGELOG 0.2.0;
      `.claude/docs`. All gates; commit `docs: 0.2.0`.

## Part C — `vo-base-codeigniter4` 0.2.0

### Task C1: Controller arguments

**Files:** Create `src/Controller/ValueObjectArguments.php` (trait), `src/Internal/TypeLabel.php` only if a message
needs it; Test `tests/Integration/ControllerArgumentsTest.php` (explicit routes through the framework, existing
`tests/Support/CodeIgniterApp.php`).

**Interfaces:**
- Produces: `trait ValueObjectArguments` with `public function _remap(string $method, string ...$params): mixed`.
  `PageNotFoundException::forMethodNotFound($method)` when the method is missing, non-public, static, `_`-prefixed or
  declared by `CodeIgniter\Controller`. Positional conversion (`$params[i]` ↔ parameter i; variadics untouched):
  single named non-builtin type → scalar-backed or backed enum via `fromUntrustedPayload`; `''` → `null` if nullable,
  else invalid; invalid → `PageNotFoundException`; supported but unbindable → `\LogicException`. Mapper:
  `new ValueObjectMapper()` (built-in codecs, as 0.1.0).

- [ ] Step 1: tests — `$routes->get('users/(:segment)', 'Users::show/$1')` with `show(EmailAddress $email)` → 200;
      `users/nope` → 404, body without `MSSTC4PHP`; enum segment → case / 404; nullable trailing parameter absent →
      `null`; `_secret`, `initController`, missing method → 404; `EmailAddress|string` untouched; `BoundedString` →
      `LogicException`.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: value objects as controller arguments`.

### Task C2: Validated data

**Files:** Create `src/Controller/ValidatedValueObjects.php` (trait); Test
`tests/Integration/ValidatedValueObjectsTest.php`.

**Interfaces:**
- Produces: `trait ValidatedValueObjects` for controllers — `validatedValueObject(string $field, string $class):
  ?object` reading `$this->validator->getValidated()` (dot keys via `dot_array_search`); missing/`null`/`''` → `null`;
  composite from array or JSON text; `\LogicException` when no validation ran or the value does not convert.

- [ ] Step 1: tests — after `$this->validate(['email' => 'valid_value_object[' . EmailAddress::class . ']'])` →
      `EmailAddress`; nested `profile.email`; before validation → `LogicException`.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: validated data as value objects`.

### Task C3: Docs and release gates

- [ ] README "Controller arguments" (explicit routes only; Auto Routing (Improved) refuses `_remap()`; a controller
      with its own `_remap()` cannot use the trait) and "Validated data" (incl. `$codes['invalid_data'] = 422` for
      `ResponseTrait`); CHANGELOG 0.2.0; `.claude/docs`. All gates; commit `docs: 0.2.0`.

## Part D — `vo-base-spiral` 0.1.0 (new package)

### Task D1: Scaffold and bootloader

**Files:** the full scaffold of `vo-base-doctrine` (manifests, byte-identical configs, CI, `.gitattributes`,
`LICENSE`, `SECURITY.md`, `CLAUDE.md`, `.claude/docs/`), `src/Bootloader/ValueObjectBootloader.php`,
`src/Internal/TypeLabel.php`; Test `tests/Integration/BootloaderTest.php`.

**Interfaces:**
- Produces: `final class ValueObjectBootloader extends Bootloader` — `DEPENDENCIES = [FiltersBootloader::class]`,
  `ValueObjectMapper` singleton, `boot()` registers `ValueObjectCaster` (D2) in `CasterRegistryInterface` and, when
  `spiral/validator` is installed, the checker (D4) under alias `vo`.
- composer: name `msstc4php/vo-base-spiral`, namespace `MSSTC4PHP\ValueObject\Spiral\`, require `spiral/framework
  ^3.18`, `msstc4php/vo-base ^1.1`; require-dev `spiral/validator ^1.8`, `nyholm/psr7 ^1.8`; suggest
  `spiral/validator`, `msstc4php/vo-base-cycle`. `git init -b main`, remote
  `git@github.com:msstc4php/vo-base-spiral.git`.

- [ ] Step 1: test — a booted test kernel has the mapper singleton and the caster registered.
- [ ] Step 2–4: FAIL → implement → PASS (plus `composer validate` and templates `cmp`).
- [ ] Step 5: commit `feat: msstc4php/vo-base-spiral 0.1.0 scaffold`.

### Task D2: Filter caster

**Files:** Create `src/Filter/ValueObjectCaster.php`; Test `tests/Integration/FilterTest.php` (real `FilterProvider`
and input).

**Interfaces:**
- Produces: `final readonly class ValueObjectCaster implements CasterInterface` — `supports(ReflectionNamedType)`:
  non-builtin, mapper-supported, not an enum (trusted-only classes included, to refuse them). `setValue()`: composite
  JSON text decoded; trusted-only or invalid → `SetterException` with a message naming the label, never the class.

- [ ] Step 1: tests — filter with `#[Post] public EmailAddress $email`: valid → value object; `'nope'` → 422 with
      `email` error and no `MSSTC4PHP`; array → 422; `GeoCoordinates` from JSON; `BoundedString` property → 422;
      enum property still handled by Spiral's `EnumCaster`; nullable property with `null` input stays `null`.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: filter caster for value objects`.

### Task D3: Action argument interceptor

**Files:** Create `src/Interceptor/ValueObjectArgumentsInterceptor.php`; Test `tests/Integration/InterceptorTest.php`
(real router/handler where feasible, else real `CallContext`).

**Interfaces:**
- Produces: `final readonly class ValueObjectArgumentsInterceptor implements InterceptorInterface` —
  `intercept(CallContextInterface $context, HandlerInterface $handler): mixed`; reflects
  `$context->getTarget()->getReflection()`, converts named arguments typed with a scalar-backed value object
  (`withArguments()`); invalid → `TargetCallException(…, TargetCallException::NOT_FOUND)`; unbindable →
  `\LogicException`; enums, builtins, unions untouched.

- [ ] Step 1: tests — `{email}` → `EmailAddress`; invalid → 404; union untouched; `BoundedString` → `LogicException`.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: route arguments become value objects`.

### Task D4: Validator checker

**Files:** Create `src/Validation/ValueObjectChecker.php`; Test `tests/Integration/CheckerTest.php`.

**Interfaces:**
- Produces: `final class ValueObjectChecker extends AbstractChecker` with rule `valid(mixed $value, string $class):
  bool` used as `['vo:valid', EmailAddress::class]`; `null`/`''` pass; trusted-only or unsupported class →
  `\LogicException`; message without a class name.

- [ ] Step 1: tests through a `FilterDefinition` with the rule: valid passes, invalid gives the field error.
- [ ] Step 2–4: FAIL → implement → PASS.
- [ ] Step 5: commit `feat: vo:valid checker`.

### Task D5: Docs and release gates

- [ ] README (install, `defineBootloaders()`, filters, interceptor registered globally and per route, checker,
      storage via `vo-base-cycle`), CHANGELOG 0.1.0, `.claude/docs`, CI `checks.yml` modelled on the siblings with a
      working lowest-deps job. All gates; commit `docs: 0.1.0`.

## Close-out (after A–D)

- [ ] `/acc:code-review high` per package; fix every finding; re-review at medium.
- [ ] Core: README framework table and `.claude/docs/integrations.md` list `vo-base-spiral`; commit in `vo-base`.
- [ ] Report; push and tag only after the owner confirms (tags after green CI).
