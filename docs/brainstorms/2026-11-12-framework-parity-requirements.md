# Framework parity: the Symfony experience in Laravel, Yii2, CodeIgniter 4 and Spiral

Date: 2026-11-12 (UTC). Status: approved in chat; builder check done, its questions settled below.

## Why

The library exists because its author, like most PHP developers, wanted ready value objects for everyday things and a
way to validate input *before* it becomes a value object, so the client sees a field error instead of an exception
from a constructor. The Symfony bundle delivers the whole chain. The other bridges (0.1.0, released 2026-11-11) only
store and validate. Success: a Laravel, Yii2, CodeIgniter 4 or Spiral developer gets the same four conveniences the
Symfony developer has, in the shape their framework already uses.

## The four conveniences (what "parity" means)

| # | Symfony today | Behaviour every bridge must match |
|---|---|---|
| 1 | `#[ValidValueObject]` constraint | Field validation: `null` and `''` pass (emptiness is the "required" rule's job), non-scalar is a type error, invalid content is a field error. Already in every bridge. |
| 2 | Serializer + `#[MapRequestPayload]` | Request body/query → value objects, invalid input → the framework's normal per-field validation error (422 where the framework's idiom gives one), never a 500. |
| 3 | `ValueObjectValueResolver` | Route parameter → typed controller argument (`/users/{email}` → `EmailAddress $email`); invalid → 404. |
| 4 | Doctrine types | Storage. Already in every bridge (Spiral uses `vo-base-cycle`). |

Rules shared by 2 and 3, inherited from the core's trust boundary (`ValueObjectMapper`):

- Input goes through `fromUntrustedPayload()` only. Classes the mapper marks trusted-source only (`BoundedString`) are
  refused on every input path: a field error on 2, never offered on 3.
- Client-facing messages never contain a PHP class name. Use the value object's label or the JSON type, as the
  bundle does (`label()`, `payloadType()`).
- Route binding (3) covers scalar-backed value objects only (a URL segment is a string). Composites are allowed on 2
  where the input is an array.
- Backed enums: left to the framework where it already binds them natively (Laravel routes, Spiral filters); handled by
  the bridge where it does not (Yii2 and CodeIgniter 4 action arguments). *(builder's call made by the designer)*
- Nullable arguments/properties accept a missing or `null` value.

## Decided: the shape per framework

Each framework gets the conveniences through its own native mechanism, not a port of Symfony's DTO-and-attributes
model. Extension points below were verified against the installed framework sources on 2026-11-12.

### Laravel (`vo-base-laravel` 0.2.0; Laravel 13 only, unchanged)

- **2a. `$request->valueObject($key, Foo::class)`**: a `Request::macro` registered by a new auto-discovered service
  provider (`extra.laravel.providers`). Missing/empty → `null`. Invalid → `ValidationException::withMessages([$key =>
  …])`, which Laravel renders as 422 with the field key. Deliberately unlike `$request->enum()`, which silently falls
  back to a default: a silent default would hide a client error.
- **2b. Form requests**: a trait for `FormRequest` with `valueObject($key, Foo::class)` reading `validated()` (the
  `ValidValueObject` rule has already vetted the value). `ValidatedInput` (`safe()`) is not macroable, so no
  `safe()->valueObject()`.
- **3. Route binding**: a `Router::matched()` (`RouteMatched` event) listener registered by the provider. It runs in
  `Router::runRoute` before the middleware stack, inspects the route's signature parameters, replaces a matching
  string parameter with the value object (`$route->setParameter`), and throws `NotFoundHttpException` on invalid
  input. Not `Router::substituteImplicitBindingsUsing()`: it is a single global slot an application may already use.
- Routing/HTTP classes are needed only for 2a/3: `illuminate/http` and `illuminate/routing` go to `require-dev` +
  `suggest`; the provider registers the macro/listener only when those classes exist, so Eloquent-only users
  (Capsule) keep the current dependency set.

### Yii2 (`vo-base-yii2` 0.2.0)

- **3. Action arguments**: a controller trait overriding `web\Controller::bindActionParams()`. For each parameter whose
  declared type the mapper supports (scalar-backed value objects and backed enums) and whose name is in `$params`,
  convert it, then delegate to `parent::bindActionParams()` (Yii passes an object through for a class-typed
  parameter). Invalid → `NotFoundHttpException` (404). Standalone `Action` classes go through the same method.
  Without this, Yii hands the raw string to the typed argument and PHP throws a `TypeError` (500).
- **2. Models**: a trait for `yii\base\Model` with `valueObject(string $attribute)` and `valueObjects()` that read the
  `valueObjectClass` of the attribute's existing `ValueObjectValidator` rule and convert the validated attribute.
  Calling it while the model has errors, or for an attribute without such a rule, is a `LogicException`. Returning the
  invalid model from a REST action already gives 422 (`rest\Serializer`).

### CodeIgniter 4 (`vo-base-codeigniter4` 0.2.0)

- **3. Controller arguments**: a controller trait implementing `_remap(string $method, string ...$params)`. It
  reflects the target method, converts parameters whose type the mapper supports (scalar-backed value objects and
  backed enums), and calls it; invalid → `PageNotFoundException` (404). Because `_remap` disables CodeIgniter's own
  method-exists 404, the trait must 404 itself for a missing, non-public, static or `_`-prefixed method. A controller
  that already has its own `_remap` cannot use the trait (document it). **Owner's decision:** explicit routes only.
  CodeIgniter's Auto Routing (Improved) refuses any controller with `_remap()` (404, `AutoRouterImproved::checkRemap()`);
  the README states this incompatibility. Auto Routing is off by default (`Config\Routing::$autoRoute = false`).
- **2. Validated data**: trait methods `validatedValueObject($field, Foo::class)` / `validatedValueObjects([...])` over
  `$this->validator->getValidated()`.
- `ResponseTrait::failValidationErrors()` answers 400 by default (`$codes['invalid_data']`); the README shows the
  one-line override for 422. The bridge does not change it.

### Spiral (new package `vo-base-spiral` 0.1.0)

- **2. Filters**: a `Spiral\Filters\Model\Mapper\CasterInterface` implementation registered in the package bootloader
  (`CasterRegistryInterface::register()`), so a filter property typed `EmailAddress` is filled automatically.
  Invalid → `SetterException`, which Spiral records as that field's error → `ValidationException` → 422. Trusted-only
  classes are claimed and always refused (so no other caster builds one). Enums stay with Spiral's `EnumCaster`.
- **3. Action arguments**: an interceptor (`Spiral\Interceptors\InterceptorInterface`) converting route arguments typed
  with a scalar-backed value object; invalid → `TargetCallException::NOT_FOUND` (404). Registered by the user
  (globally through the domain-core interceptors or per route); the README shows both.
- **1. Validation**: a `spiral/validator` checker (e.g. alias `vo`, rule `['vo:valid', Foo::class]`), registered only
  when `spiral/validator` is installed.
- **4. Storage**: none in this package; README points to `vo-base-cycle`.
- Users add the bootloader in `Kernel::defineBootloaders()` (Spiral core has no auto-discovery).
- Scaffolding exactly like the other bridges (`vo-base-doctrine` as the model: manifests, byte-identical tool configs,
  CI, docs, `.claude/docs`). Package name `msstc4php/vo-base-spiral`, namespace `MSSTC4PHP\ValueObject\Spiral\`.
  Verified to resolve: spiral/framework 3.18.2 on PHP 8.4 with cycle/orm 2.18; tests need a concrete PSR-17 factory
  (`nyholm/psr7`).

## Settled after the builder check *(designer's calls)*

- **Where the mapper lives** (needed for custom payload codecs on the new entry points):
  - Laravel: the provider binds `ValueObjectMapper` as a container singleton; the macro, the form-request trait and the
    route listener resolve it from the container. An application rebinds it to add codecs.
  - Spiral: the bootloader binds it as a singleton; the caster, interceptor and checker get it injected.
  - Yii2: the traits resolve it from `Yii::$container`, so it is configured through container definitions.
  - CodeIgniter 4: built-in codecs only, exactly as 0.1.0's rules and casts (no framework-wide registry to hook into
    without taking over a service).
- **Laravel form requests**: `FormRequest` exists only in the `laravel/framework` monolith, so it goes to `require-dev`
  (tests run against the real class); runtime requirements stay the `illuminate/*` splits, and `suggest` names
  `laravel/framework`.
- **Unbindable argument types in Yii2/CI4** (a trusted-source-only class like `BoundedString`, or a composite like
  `DateRange`, as a typed action argument): a `LogicException` naming the argument and the reason. It is a programmer
  error, found on the first request in development; it must never fall through to the raw string and a `TypeError`.

## Goals

- All four conveniences in Laravel, Yii2, CodeIgniter 4 and Spiral, tested against real framework components (an HTTP
  round trip through the router where the framework can run one in-process).
- Existing 0.1.0 APIs keep working: these are additive minor releases.
- Same gates as every package: PHPStan max + `phpstan-ci.neon`, cs-fixer, rector, tests, Infection ≥ 75% covered MSI,
  lowest-deps job, templates `cmp`, `/acc:code-review`, docs in `README`, `CHANGELOG`, `.claude/docs`.

## Non-goals

- Laravel 12 (blocked by the core's `brick/math` floor).
- A DTO mapper for frameworks that have none (no `spatie/laravel-data` integration in this round).
- Changes to the core or the Symfony bundle, unless a bridge needs a missing mapper capability (then a core minor
  release goes first).
- Packagist publication (the owner does it in the web UI).

## Anti-goals (failures even if it "works")

- A PHP class name or internal message reaching an HTTP client.
- `BoundedString` (or any trusted-source-only class) built from request input.
- A bridge taking over a global, single-slot framework hook an application may already own.
- Invalid client input producing a 500 anywhere in the four paths.

## Left to the builder

Class and trait names, file layout inside each package, test structure, exact message wording (within the rules
above), and whether a framework's helper also offers a plural form.

## Release order

`vo-base-laravel`, `vo-base-yii2`, `vo-base-codeigniter4` at 0.2.0 and `vo-base-spiral` at 0.1.0; all depend only on
`vo-base ^1.1`, so they are independent. The owner creates the empty `msstc4php/vo-base-spiral` repository on GitHub
before the push. Push and tag only after explicit confirmation, tag only after green CI.
