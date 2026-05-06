---
name: api-platform-endpoints
description: >
  Design and implement read endpoints with API Platform 3.4 in this Chamilo HR
  project. Auto-invoke when: user creates or modifies a state provider, an
  #[ApiResource] declaration, or a DTO under `src/CoreBundle/ApiResource/`;
  asks how to expose a custom API endpoint, projection, aggregation, or filter;
  mentions ApiResource, ApiFilter, ApiPlatform, FilterExtension,
  PaginationExtension, OrderExtension, ProviderInterface, output DTO,
  state provider, $context['filters'], or RequestStack inside a state provider.
  Do NOT invoke for: pure entity / migration work without an exposed endpoint,
  legacy PHP under `public/main/`, or unrelated Symfony controllers that do
  not interact with API Platform.
allowed-tools:
  - Read
  - Edit
  - Write
  - Grep
  - Glob
  - Bash
---

This skill is the canonical reference for designing **read endpoints** on API Platform 3.4
in this project. Use it whenever you create or modify an `#[ApiResource]`, a state provider,
an output DTO, or a controller that returns API data.

---

## Decision tree: which path do I take?

| What does the endpoint return?                                                        | Path                                                                                        |
|---------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------|
| One or more instances of a **mapped entity** that already has `#[ApiResource]`        | **Path A** — state provider added to the entity's existing operations                       |
| A **custom projection / aggregation / DTO**, query maps to ONE main Doctrine entity   | **Path B canonical** — new operation on that entity with `output: YourDto::class`           |
| A **custom projection / aggregation / DTO**, query joins through 2+ entities, no main | **Path B fallback** — DTO-as-resource (`#[ApiResource]` on the DTO) + `$context['filters']` |
| A **side-effect** action (send message, mark as read, file I/O), no entity response   | **Path C** — plain Symfony controller (last resort)                                         |

Never use Path C just because the response shape is complex: a DTO state provider handles
any shape. Plain controllers are for actions, not data reads.

---

## Path A — State provider for an existing entity

1. Create `src/CoreBundle/State/{FeatureName}StateProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\YourEntity;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\YourEntityRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<YourEntity>
 */
final class YourFeatureStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly YourEntityRepository $repository,
        private readonly UserHelper $userHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            throw new AccessDeniedHttpException();
        }

        return $this->repository->findByUser($user);
    }
}
```

2. Add a new `GetCollection` (or `Get`) operation to the entity's `#[ApiResource]` block:

```php
new GetCollection(
    uriTemplate: '/your_entities/my_subset',
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    provider: YourFeatureStateProvider::class,
    paginationEnabled: false,
),
```

Key rules for Path A:
- `uriTemplate` lives under `/api/` automatically — no `-data` suffix needed.
- `paginationEnabled: false` when the result set is always small and the client expects a plain array.
- `normalizationContext: ['groups' => ['entity:read']]` on the operation when a subset of
  fields differs from the resource's default group.
- Use `UserHelper::getCurrent()` (constructor-injected) to get the authenticated user — never
  call `$this->getUser()` (no controller context inside a provider).
- Expose related-entity fields needed by the frontend by adding the entity's read group to the
  property in the related entity (e.g. add `'benefit_assignment:read'` to `Compensation::$description`).

Reference: `src/CoreBundle/State/PublicCatalogueCourseStateProvider.php`,
`src/CoreBundle/State/CourseRelUserCollectionStateProvider.php` — apply
`FilterExtension` / `OrderExtension` / `PaginationExtension` against the entity class,
because the resource IS the entity and filters declared on it work natively.

---

## Path B canonical — DTO output on the entity's `#[ApiResource]` (preferred)

When the response is a custom DTO but the underlying query maps cleanly to ONE Doctrine entity
(one main `FROM` clause), add a new operation to that entity's existing `#[ApiResource]` block
with `output: YourDto::class`. The DTO becomes the response shape; the entity remains the
resource so filters work natively and OpenAPI parameters are generated.

**Step B-canonical-1 — Add the operation to the existing entity.**

```php
// src/CoreBundle/Entity/Session.php  (modification of upstream entity is acceptable here —
// see "Project rule" below)
use Chamilo\CoreBundle\ApiResource\HrRoiCourseItem;
use Chamilo\CoreBundle\State\HrRoiCourseStateProvider;

#[ApiResource(
    operations: [
        // ... existing operations ...

        // Chamilo HR extension: ROI by course (Training ROI management).
        new GetCollection(
            uriTemplate: '/hr_roi/courses',
            paginationEnabled: false,
            normalizationContext: ['groups' => ['hr_roi_course:read']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR')",
            filters: ['hr.session.date_filter'],
            output: HrRoiCourseItem::class,
            name: 'hr_roi_courses',
            provider: HrRoiCourseStateProvider::class,
        ),
    ],
)]
class Session { /* unchanged */ }
```

**Step B-canonical-2 — Register the filter service** (Doctrine ORM filter — DateFilter,
SearchFilter, OrderFilter, etc.) in `src/CoreBundle/Resources/config/services.yml`:

```yaml
hr.session.date_filter:
    parent: 'api_platform.doctrine.orm.date_filter'
    arguments:
        $properties: { accessStartDate: ~ }
    tags: [ 'api_platform.filter' ]
```

The service ID (`hr.session.date_filter`) is what you reference in the operation's `filters: [...]`.

**Step B-canonical-3 — Create the output DTO** in `src/CoreBundle/ApiResource/{DtoName}.php`.
It is a **plain class with `Groups` attributes** — NO `#[ApiResource]`, NO `#[ApiFilter]`, NO
`#[ApiProperty(identifier:)]`. Example: `src/CoreBundle/ApiResource/SessionPlanItem.php`.

```php
<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

// Chamilo HR extension

use Symfony\Component\Serializer\Attribute\Groups;

final class HrRoiCourseItem
{
    #[Groups(['hr_roi_course:read'])]
    public int $sessionId;

    #[Groups(['hr_roi_course:read'])]
    public string $title;
    // ... other public typed fields with the same group ...
}
```

**Step B-canonical-4 — Create the state provider** that builds a QueryBuilder on the entity,
applies the API Platform extensions (which read the operation's `filters` automatically), then
transforms each row into a DTO.

```php
<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

// Chamilo HR extension

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\HrRoiCourseItem;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProviderInterface<HrRoiCourseItem>
 */
final class HrRoiCourseStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FilterExtension $filterExtension,
    ) {}

    /**
     * @return HrRoiCourseItem[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(Session::class, 's')
            ->orderBy('s.accessStartDate', 'DESC')
        ;

        $this->filterExtension->applyToCollection(
            $qb,
            new QueryNameGenerator(),
            Session::class,           // pass the entity class, NOT the DTO class
            $operation,
            $context,
        );

        return array_map(fn (Session $s) => $this->toDto($s), $qb->getQuery()->getResult());
    }
}
```

URLs and OpenAPI parameters are generated automatically:
- `?accessStartDate[after]=YYYY-MM-DD&accessStartDate[before]=YYYY-MM-DD` (DateFilter — emits 4 OpenAPI params: `after`, `before`, `strictly_after`, `strictly_before`).
- `?order[accessStartDate]=desc` (OrderFilter).
- `?user=/api/users/42` or `?user=42` (SearchFilter on a relation, `exact` strategy).

Inject `OrderExtension` and/or `PaginationExtension` the same way when needed (see
`PublicCatalogueCourseStateProvider`).

**Project rule on modifying upstream Chamilo entities** (see project `CLAUDE.md`): adding an
HR-specific operation to an entity's `#[ApiResource]` block IS acceptable when:
- the operation is the natural place for filters that target real columns of that entity;
- the change is annotated with a `// Chamilo HR extension:` comment (so it's easy to spot
  and rebase);
- no entity properties or table mappings are altered.

---

## Path B fallback — DTO-as-resource + manual `$context['filters']`

Use this fallback when the underlying query does NOT fit a single entity — multi-step joins
like `User → UserToFunctionInUnit → FunctionInUnit → BusinessUnit`, or filter parameters that
are not properties of any entity reachable from the resource. In that case, keep the DTO as
the resource and read parsed query parameters from `$context['filters']`.

> **Critical limitation: `#[ApiFilter]` does NOT work on DTOs.**
> Doctrine ORM filters need the resource class to be a Doctrine entity backed by a database
> table. They use the resource's ORM metadata to build SQL/DQL fragments. A plain DTO is not
> an entity. Declaring `#[ApiFilter]` on a DTO has no effect on filtering, generates no
> OpenAPI parameter documentation, and cannot be "rescued" by passing the underlying entity
> class to `FilterExtension::applyToCollection` — that hack works at runtime but produces
> empty OpenAPI parameters and is non-idiomatic.

**Step B-fallback-1 — Create the DTO with `#[ApiResource]` on the DTO itself.**

```php
<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

// Chamilo HR extension

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\State\YourDtoStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'YourDtoName',
    operations: [
        new GetCollection(
            uriTemplate: '/your_dto_endpoint',
            security: "is_granted('ROLE_ADMIN')",
            paginationEnabled: false,
            normalizationContext: ['groups' => ['your_dto:read']],
            provider: YourDtoStateProvider::class,
        ),
    ],
)]
final class YourDtoName
{
    #[ApiProperty(identifier: true)]
    #[Groups(['your_dto:read'])]
    public int $someId;

    #[Groups(['your_dto:read'])]
    public string $someField;
}
```

For a singleton (single-item Get with no DB row), use the `getId()` fixed-string trick:

```php
#[ApiProperty(identifier: true, readable: false, writable: false)]
public function getId(): string
{
    return 'your-dto-name';
}
```

**Step B-fallback-2 — State provider reads `$context['filters']`.**

API Platform 3.4 populates `$context['filters']` from the query string via `parse_str` on
every request, regardless of any `#[ApiFilter]` declarations. Read it directly:

```php
public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
{
    // Required scalar filter that doesn't map to a single entity property.
    $unitId = (int) ($context['filters']['unit'] ?? 0);
    if ($unitId <= 0) {
        return [];
    }

    // Standard API Platform DateFilter URL convention parses to a nested array:
    // ?accessStartDate[after]=YYYY-MM-DD → $context['filters']['accessStartDate']['after']
    $dateFilter = $context['filters']['accessStartDate'] ?? [];
    $after  = \is_array($dateFilter) ? ($dateFilter['after']  ?? null) : null;
    $before = \is_array($dateFilter) ? ($dateFilter['before'] ?? null) : null;

    // Apply $unitId, $after, $before manually in your QueryBuilder using setParameter() with
    // explicit Types::INTEGER / Types::DATETIME_MUTABLE. Build and return YourDtoName[].
}
```

Stick to API Platform's standard URL conventions even in the fallback path:
`?accessStartDate[after]=...`, `?user=42`, `?unit=7`. URLs stay consistent across canonical
and fallback endpoints. Document the parameters via the operation's `openapiContext` (or
`parameters: []`) when you use this fallback, since OpenAPI cannot infer them from
`#[ApiFilter]`.

**Reference examples for Path B**:
- Canonical: `src/CoreBundle/State/HrRoiCourseStateProvider.php` + the `hr_roi_courses`
  operation in `src/CoreBundle/Entity/Session.php` — HR-specific operation on the upstream
  entity, `output: HrRoiCourseItem::class`, `filters: ['hr.session.date_filter']`,
  `FilterExtension` applied to a `Session` QueryBuilder.
- Fallback: `src/CoreBundle/State/HrRoiPersonStateProvider.php` and
  `src/CoreBundle/State/HrRoiUnitStateProvider.php` — multi-entity joins, DTO-as-resource,
  reading `$context['filters']` for `user` / `unit` / `accessStartDate` and applying manually.

---

## Path C — Plain controller (side-effect actions only)

Create controllers in `src/CoreBundle/Controller/` (or `.../Admin/` for admin-only features)
**only** for endpoints that:
- Trigger side-effects without returning entity data (send a message, mark as read, etc.).
- Wrap legacy operations (`MessageManager`, file I/O) that have no API Platform equivalent.

Functional rules:
- URL must end in `-data` to avoid clashing with Vue SPA routes (e.g. SPA at `/my-feature` →
  data at `/my-feature-data`).
- Use `EntityManagerInterface` and Doctrine QueryBuilder / DQL — no raw SQL or `Connection`
  unless there's no ORM mapping.
- DQL uses **entity property names**, not column names. Read the entity file before writing
  the query.
- Do not order by SELECT aliases in DQL; use the full expression.
- `USER_SOFT_DELETED = -2` (not -1). User entity constants: `SOFT_DELETED=-2`,
  `INACTIVE_AUTOMATIC=-1`, `INACTIVE=0`, `ACTIVE=1`.
- Return `$this->json(...)` — no HTML, no legacy `Display::` output.
- `#[IsGranted('IS_AUTHENTICATED_FULLY')]` for any logged-in user, `#[IsGranted('ROLE_ADMIN')]`
  for admin-only. Reproduce mixed legacy access control (admins + course coaches, etc.) via
  Symfony voters or manual permission checks.

---

## Anti-patterns (never do this)

- ❌ **Inject `RequestStack` and call `$request->query->get('foo')`** in a state provider.
  Bypasses the API Platform filter system, produces inconsistent URL conventions, and is
  invisible in OpenAPI.
- ❌ **Declare `#[ApiFilter(DateFilter::class, …)]` on a DTO class.** Silently does nothing.
  Tricking it via `FilterExtension::applyToCollection($qb, …, EntityClass::class, …)` works
  at runtime but produces empty OpenAPI parameters.
- ❌ **Invent custom URL parameter names** (`?dateStart=...`, `?userId=...`) instead of using
  API Platform's standard parsing (`?accessStartDate[after]=...`, `?user=42`).
- ❌ **Use Path B canonical when the query needs joins through 2+ entities** with no single
  natural "main" entity — use the fallback pattern instead.
- ❌ **Use Path C just because the response shape is complex.** A DTO state provider handles
  any shape. Path C is for actions, not data reads.
- ❌ **Add a `-data` suffix to the URL of an API Platform operation.** That suffix is for
  plain Symfony controllers (Path C) that share a base path with a Vue SPA route. API
  Platform endpoints already live under `/api/` and don't clash.

---

## PHP code style rules (apply to every provider, controller, DTO)

ECS (Symfony + PSR-12 + PhpCsFixer):
- `declare(strict_types=1);` at the top of every file.
- Yoda conditions: `'all' === $listType`, not `$listType === 'all'`.
- No string interpolation with `{}` — use concatenation with **no spaces** around `.`:
  `'%'.$keyword.'%'`, not `"%{$keyword}%"`.
- Short array syntax `[]`, trailing commas in multiline constructs.
- No useless `else` — use early returns. No useless `return` at end of void methods.
- No empty phpdoc. No `@param`/`@return` tags that duplicate type hints.
- `void` return type on methods that return nothing.
- Import classes via `use` — never inline `\Fully\Qualified\ClassName` in code bodies.
- Ordered class elements: constants → properties → constructor → public → protected → private.
- `(int)` not `intval()`, `(string)` not `strval()`.

Psalm static analysis:
- `setParameter()` must include an explicit type (3rd arg) for non-scalar values:
  `Types::INTEGER` for entity IDs, `Types::DATETIME_MUTABLE` for `DateTime`,
  `ArrayParameterType::INTEGER` for int arrays.
- All methods must have return types. All parameters must have type hints.
- Check for possibly-null access before using object properties or calling methods.

DTO and provider conventions:
- DTOs go in `src/CoreBundle/ApiResource/` — never in `Entity/`.
- Providers go in `src/CoreBundle/State/`.
- Add `// Chamilo HR extension` after the `declare` line on HR-specific files.
- `@implements ProviderInterface<YourDtoName>` (not `@template-implements`) above the class.
- For `GetCollection`, return type is `array` and the body returns `YourDtoName[]`.
- `paginationEnabled: false` on the operation when the client expects a plain Hydra member array.
- All serialized properties carry `#[Groups([...])]`.
- Do **not** add ORM attributes (`#[ORM\Column]`, `#[ORM\Entity]`) to DTO classes.

---

## Post-write verification

Run all three checks from the project root and fix every issue before considering the endpoint done:

```bash
# 1. ECS / PHP code style
docker compose -f /home/aquiroz/Dev/docker/skillms/docker-compose.yml exec webserver \
    composer phpcs-fix -- src/CoreBundle/State/YourStateProvider.php \
                          src/CoreBundle/ApiResource/YourDtoName.php

# 2. Psalm static analysis
docker compose -f /home/aquiroz/Dev/docker/skillms/docker-compose.yml exec webserver \
    composer psalm -- --no-cache src/CoreBundle/State/YourStateProvider.php \
                                 src/CoreBundle/ApiResource/YourDtoName.php

# 3. Container wiring (provider arguments and any new filter service)
symfony console cache:clear --no-warmup
symfony console debug:container 'Chamilo\CoreBundle\State\YourStateProvider' --show-arguments
```

## OpenAPI verification

Always confirm the endpoint is actually exposed with the parameters you expect:

```bash
symfony console api:openapi:export 2>&1 | python3 -c "
import sys, json
d = json.load(sys.stdin)
op = d['paths'].get('/api/your_endpoint', {}).get('get', {})
params = op.get('parameters', [])
print(f'parameters: {len(params)}')
for p in params:
    print(f'  - {p.get(\"name\")} (in={p.get(\"in\")})')
"
```

If you used Path B canonical, every filter declared via `filters: [...]` should appear here.
If `parameters: 0` and you expected query-string filters, the canonical pattern is broken
(likely the filter service is not tagged correctly, or you accidentally took the fallback
path).

For Path B fallback, OpenAPI will not show the parameters automatically — that's expected.
Add `openapiContext: ['parameters' => [...]]` (or the operation-level `parameters: [...]`)
to document them.
