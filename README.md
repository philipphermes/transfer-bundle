# Transfer Bundle

[![CI](https://github.com/philipphermes/transfer-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/philipphermes/transfer-bundle/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/symfony-%3E%3D%207.4-8892BF.svg)](https://symfony.com)

A Symfony bundle for generating type-safe transfer objects (DTOs) from XML schema definitions. Supports OpenAPI attribute generation for API documentation.

---

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Defining Transfers](#defining-transfers)
  - [Property Attributes](#property-attributes)
  - [Generating Transfers](#generating-transfers)
- [OpenAPI Integration](#openapi-integration)
- [Development](#development)

---

## Installation

```shell
composer require philipphermes/transfer-bundle
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    PhilippHermes\TransferBundle\PhilippHermesTransferBundle::class => ['all' => true],
];
```

---

## Configuration

Create `config/packages/transfer.yaml` to customize the bundle:

```yaml
transfer:
    schema_dirs:
        - '%kernel.project_dir%/transfers'
    exclude_dirs: []
    output_dir: '%kernel.project_dir%/src/Generated/Transfers'
    namespace: 'App\Generated\Transfers'
```

### Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `schema_dirs` | `['%kernel.project_dir%/transfers']` | Directories to scan for XML schema files (supports glob patterns) |
| `exclude_dirs` | `[]` | Directories to exclude from scanning |
| `output_dir` | `%kernel.project_dir%/src/Generated/Transfers` | Output directory for generated transfer classes |
| `namespace` | `App\Generated\Transfers` | PHP namespace for generated classes |

### Vendor-Level Discovery

To include transfers from vendor packages:

```yaml
transfer:
    schema_dirs:
        - '%kernel.project_dir%/transfers'
        - '%kernel.project_dir%/vendor/*/*/transfers'
    exclude_dirs:
        - '%kernel.project_dir%/vendor/*/tests'
        - '%kernel.project_dir%/vendor/*/*/tests'
```

---

## Usage

### Defining Transfers

Create XML schema files in your configured schema directories (default: `transfers/`).

```xml
<?xml version="1.0" encoding="UTF-8"?>
<transfers xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:noNamespaceSchemaLocation="vendor/philipphermes/transfer-bundle/src/Resources/schema/transfer.xsd">

    <transfer name="User">
        <property name="email" type="string" description="The email of the user"/>
        <property name="password" type="string" description="The password of the user"/>
        <property name="addresses" type="Address[]" singular="address" isNullable="true"/>
        <property name="roles" type="string[]"/>
    </transfer>

    <transfer name="Address">
        <property name="street" type="string"/>
    </transfer>

</transfers>
```

**Key features:**
- Multiple XML files are supported and will be merged (files are read in sorted path order)
- Transfers with the same name across files are combined; `api="true"` in any definition makes it an API transfer
- First definition of a property takes precedence; a later definition with a different type produces a warning

### Property Attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| `name` | Yes | Property name |
| `type` | Yes | Property type, see [Types](#types) |
| `description` | No | Property description (used in OpenAPI docs) |
| `singular` | No | Singular name used for the `addX()` method of `[]` properties (default: the property name) |
| `isNullable` | No | Whether the property can be null (`true`/`false`/`1`/`0`) |
| `default` | No | Initial value for `string`, `int`, `float` and `bool` properties, converted to the property type |
| `example` | No | Example value for the OpenAPI docs |
| `deprecated` | No | Marks the property and its accessors `@deprecated` (and `deprecated` in the OpenAPI docs) |

Names of transfers, properties and `singular` must be valid PHP identifiers, and the generated
accessors must not collide (method names are case-insensitive, so `foo` and `Foo` can't coexist).

### Types

| Type | Result |
|------|--------|
| `string`, `int`, `float`, `bool`, `array`, `object`, `mixed` | used as-is |
| Name of a defined transfer, e.g. `Address` | `AddressTransfer` from the generated namespace |
| Existing class, interface or enum, e.g. `DateTime`, `App\Enum\Status` | used as fully qualified name |
| `X[]` of a PHP type, e.g. `string[]` | `array` |
| `X[]` of a transfer or class, e.g. `Address[]` | `ArrayObject` |

Any other type is reported as an error.

### Schema Validation

Every file is validated against `transfer.xsd`. Violations such as a misspelled attribute (`isNulable="true"`) are
reported as warnings with file and line, parsing continues as before.

### Generated Methods

For every property the generator creates `getX()`, `setX()` and `hasX()` (`true` if the property is set and not
null). `[]` properties additionally get `addX()`. Getters of `[]` properties always return a collection (an empty
one if unset or set to `null`).

Every transfer also gets:

| Method | Description |
|--------|-------------|
| `toArray(): array` | Converts the transfer recursively: nested transfers become arrays, collections plain arrays, dates `DATE_ATOM` strings and enums their value (backed) or name. Every property is present, unset ones are `null`. |
| `static fromArray(array $data): self` | The reverse of `toArray()`. Missing keys stay unset, unknown keys are ignored, already converted values (e.g. a transfer object) are accepted as well. |
| `__clone()` | Makes `clone` deep for nested transfers, `ArrayObject` collections and `DateTime` values, so a clone never shares state with the original. Only generated when needed. |

```php
$user = UserTransfer::fromArray(['email' => 'jane@example.com', 'addresses' => [['street' => 'Main St']]]);
$user->getAddresses()[0]; // AddressTransfer
$user->toArray();         // ['email' => 'jane@example.com', 'password' => null, 'addresses' => [['street' => 'Main St']], 'roles' => []]
```

### Generating Transfers

Run the generator command:

```shell
php bin/console transfer:generate
```

Options:
- `--clean-disable` - Keep stale transfers in the output directory
- `--check` - Don't write anything, only check whether the generated transfers are up to date. Lists new, changed and
  stale files and exits with a non-zero code if there are any, e.g. for CI:

```shell
php bin/console transfer:generate --check
```

Files whose content didn't change are not rewritten, so their modification time stays the same.

After a successful generation, generated transfers that no longer exist in the schemas are removed from the output
directory. Only top-level files carrying the `This file is auto-generated.` header are removed. If parsing fails, the
command prints the errors, exits with a non-zero code and doesn't touch the output directory. Warnings (e.g. a schema
directory that matches nothing) are printed but don't fail the command.

---

## OpenAPI Integration

Add `api="true"` to transfers to automatically generate OpenAPI attributes.

### Transfer Attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| `name` | Yes | Transfer name (generates `{name}Transfer` class) |
| `api` | No | Set to `true` to generate OpenAPI attributes |
| `apiAlias` | No | Custom name for OpenAPI documentation (default: transfer name without "Transfer" suffix) |

### Example

```xml
<transfer name="User" api="true" apiAlias="UserResource">
    <property name="email" type="string" description="The email of the user"/>
    <property name="password" type="string" description="The password of the user"/>
</transfer>

<transfer name="Error" api="true">
    <property name="status" type="int"/>
    <property name="message" type="string"/>
</transfer>
```

Use in your controllers with NelmioApiDocBundle:

```php
use App\Generated\Transfers\UserTransfer;
use App\Generated\Transfers\ErrorTransfer;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class UserApiController extends AbstractController
{
    #[OA\Tag(name: 'user')]
    #[OA\Response(
        response: 200,
        description: 'Returns a user by id',
        content: new Model(type: UserTransfer::class)
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new Model(type: ErrorTransfer::class)
    )]
    #[Route('/api/user/{id}', methods: ['GET'])]
    public function getUserById(int $id): Response
    {
        // ...
    }
}
```

Property attributes in the generated `OA\Property`:
- `description`, `example` and `default` from the XML
- `nullable: true` for nullable non-collection properties, `deprecated: true` for deprecated ones
- backed enums get their backing type and `enum` with the case values, pure enums `type: 'string'` with the case names

> [!NOTE]
> Child transfers do not inherit `api="true"` - you must set it explicitly on each transfer.

---

## Development

### Static Analysis

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

### Testing

```bash
vendor/bin/phpunit
```

With coverage report:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage-report
```
