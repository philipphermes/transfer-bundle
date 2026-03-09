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
- Multiple XML files are supported and will be merged
- Transfers with the same name across files are combined
- First definition of a property takes precedence

### Property Attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| `name` | Yes | Property name |
| `type` | Yes | PHP type (`string`, `int`, `bool`, `float`, `array`, `Transfer`, `Transfer[]`) |
| `description` | No | Property description (used in OpenAPI docs) |
| `singular` | No | Singular name for array properties (enables `addX()` method) |
| `isNullable` | No | Whether the property can be null (`true`/`false`) |

### Generating Transfers

Run the generator command:

```shell
php bin/console transfer:generate
```

Options:
- `--clean-disable` - Skip cleaning the output directory before generation

---

## OpenAPI Integration

Add `api="true"` to transfers to automatically generate OpenAPI attributes:

```xml
<transfer name="User" api="true">
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
use Nelmio\ApiDocBundle\Annotation\Model;
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

> **Note:** Child transfers do not inherit `api="true"` - you must set it explicitly on each transfer.

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
