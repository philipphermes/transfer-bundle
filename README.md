# Transfer Bundle

[![CI](https://github.com/philipphermes/transfer-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/philipphermes/transfer-bundle/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)]((https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg))
[![Symfony](https://img.shields.io/badge/symfony-%3E%3D%207.4-8892BF.svg)]((https://img.shields.io/badge/symfony-%3E%3D%207.4-8892BF.svg))

## Table of Contents

1. [Installation](#installation)
    1. [configuration](#configuration)
    2. [openApi](#openapi)
2. [Code Quality](#code-quality)
    2. [phpstan](#phpstan)
3. [Test](#test)
    1. [phpunit](#phpunit)

## Installation

```shell
composer require philipphermes/transfer-bundle
```

### Configuration

```php
// config/bundles.php
return [
    // ...
    PhilippHermes\TransferBundle\PhilippHermesTransferBundle::class => ['all' => true],
];
```

#### Optional Configs

* `transfer.namespace`: `App\\Generated\\Transfers`
* `transfer.schema_dir`: `%kernel.project_dir%/transfers`
* `transfer.output_dir`: `%kernel.project_dir%/src/Generated/Transfers`

### Define Transfers

* you can create multiple files
* if multiple files have the same transfer they will be merged
    * if you define the same property twice the first on it gets is taken

```xml
<?xml version="1.0" encoding="UTF-8"?>
<transfers xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:noNamespaceSchemaLocation="../vendor/philipphermes/transfer-bundle/src/Resources/schema/transfer.xsd">

    <transfer name="User">
        <property name="email" type="string" description="The email of the user"/>
        <property name="password" type="string" description="The password of the user"/>
        <property name="addresses" type="Address[]" description="Shipping addresses" singular="address"
                  isNullable="true"/>
        <property name="roles" type="string[]" description="List of roles" isNullable="false"/>
    </transfer>

    <transfer name="Address">
        <property name="street" type="string"/>
    </transfer>
</transfers>
```

### OpenAPI

You can add `api="true"` to transfers to add OpenApi attributes automatically.
Child transfers won't get it automatically.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<transfers xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:noNamespaceSchemaLocation="../vendor/philipphermes/transfer-bundle/src/Resources/schema/transfer.xsd">

    <transfer name="User" api="true">
        <property name="email" type="string" description="The email of the user"/>
        <property name="password" type="string" description="The password of the user"/>
        <property name="addresses" type="Address[]" description="Shipping addresses" singular="address"
                  isNullable="true"/>
        <property name="roles" type="string[]" description="List of roles" isNullable="false"/>
    </transfer>

    <transfer name="Address" api="true">
        <property name="street" type="string"/>
    </transfer>

    <transfer name="Error" api="true">
        <property name="status" type="int"/>
        <property name="messages" singular="message" type="ErrorMessage"/>
    </transfer>

    <transfer name="ErrorMessage" api="true">
        <property name="message" type="string"/>
    </transfer>
</transfers>
```

then you can use it in api routes for example like this:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Generated\Transfers\UserTransfer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
        description: 'Returns a error',
        content: new Model(type: ErrorTransfer::class)
    )]
    #[OA\Response(
        response: 500,
        description: 'Returns a error',
        content: new Model(type: ErrorTransfer::class)
    )]
    #[Route('/api/user/{id}', name: 'get_user_by_id', methods: ['GET'])]
    public function getUserByIdAction(int $id): Response
    {
        $user = $this->userFacade->getUserById($id);
    
        return $this->json($user);
    }
}
```

## Generate transfers

```shell
symfony console transfer:generate
```

## Working with Transfers

### Array Conversion

All generated transfers extend `AbstractTransfer` which provides `toArray()` and `fromArray()` methods for easy serialization.

#### toArray()

Convert a transfer object to an array:

```php
$user = new UserTransfer();
$user->setEmail('user@example.com');
$user->setPassword('secret');

// Basic conversion with camelCase keys (default)
$array = $user->toArray();
// ['email' => 'user@example.com', 'password' => 'secret']

// Convert to snake_case keys
$array = $user->toArray('snake_case');
// ['email' => 'user@example.com', 'password' => 'secret']

// Custom DateTime format
$user->setCreatedAt(new DateTime('2024-01-15 10:30:00'));
$array = $user->toArray('camelCase', true, 'Y-m-d H:i:s');
// ['createdAt' => '2024-01-15 10:30:00']

// Recursive conversion (includes nested transfers)
$address = new AddressTransfer();
$address->setStreet('Main Street');
$user->addAddress($address);
$array = $user->toArray('camelCase', true);
// ['email' => '...', 'addresses' => [['street' => 'Main Street']]]
```

**Parameters:**
- `$keyFormat` (string): `'camelCase'` (default) or `'snake_case'`
- `$recursive` (bool): Convert nested objects recursively (default: `true`)
- `$dateTimeFormat` (string): PHP date format for DateTime objects (default: `DateTimeInterface::ATOM`)

#### fromArray()

Populate a transfer from an array:

```php
$data = [
    'email' => 'user@example.com',
    'password' => 'secret',
    'addresses' => [
        ['street' => 'Main Street', 'zip' => 12345]
    ]
];

$user = new UserTransfer();
$user->fromArray($data, 'camelCase', true);

echo $user->getEmail(); // 'user@example.com'
```

**Parameters:**
- `$data` (array): Array with data to populate
- `$keyFormat` (string): `'camelCase'` (default) or `'snake_case'`
- `$recursive` (bool): Recursively create nested transfers (default: `true`)
- `$dateTimeFormat` (string): PHP date format for parsing DateTime strings (default: `DateTimeInterface::ATOM`)

### Property Constants

Each transfer generates constants for property names to avoid magic strings:

```php
// Instead of using string literals
$user->fromArray([
    'email' => 'test@example.com',
    'password' => 'secret'
]);

// Use IDE-friendly constants
$user->fromArray([
    UserTransfer::EMAIL => 'test@example.com',
    UserTransfer::PASSWORD => 'secret'
]);
```

Constants are automatically generated in `UPPER_CASE` format from property names:
- `email` → `UserTransfer::EMAIL`
- `createdAt` → `UserTransfer::CREATED_AT`
- `addresses` → `UserTransfer::ADDRESSES`

### Entity Array Conversion (Doctrine)

For Doctrine entities, use the `EntityArrayConvertibleTrait` which handles Doctrine-specific features:

```php
use PhilippHermes\TransferBundle\Entity\EntityArrayConvertibleTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    use EntityArrayConvertibleTrait;

    #[ORM\Column]
    private string $email;

    #[ORM\OneToMany(mappedBy: 'user')]
    private Collection $orders;

    // ... getters/setters
}

// Convert entity to array
$user = $entityManager->find(User::class, 1);
$array = $user->toArray('camelCase', true, 'Y-m-d H:i:s', 5);

// Populate entity from array (basic properties only)
$user = new User();
$user->fromArray($data, 'camelCase');
```

**Entity-specific features:**
- **Proxy Detection**: Skips uninitialized Doctrine proxies to prevent lazy loading
- **Circular Reference Prevention**: Tracks visited entities to avoid infinite loops
- **Collection Support**: Properly handles `Collection` instances (OneToMany, ManyToMany)
- **Max Depth**: Fourth parameter limits recursion depth (default: 10)

**Parameters:**
- `$keyFormat` (string): `'camelCase'` (default) or `'snake_case'`
- `$recursive` (bool): Convert nested entities recursively (default: `true`)
- `$dateTimeFormat` (string): PHP date format for DateTime objects (default: `DateTimeInterface::ATOM`)
- `$maxDepth` (int): Maximum recursion depth (default: `10`)

**Important Notes:**
- `fromArray()` only sets basic scalar properties and DateTimes, not relations
- Use EntityManager to properly manage relations and persist entities
- Uninitialized proxy objects are skipped to avoid triggering lazy loading
- Circular references are detected and marked as `['_circular_reference' => true]`

## Code Quality

### Phpstan

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

## Test

### Phpunit

```bash
vendor/bin/phpunit

# With coverage
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage-report
```
