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

you can add `api="true"` to transfers to add attributes and a ref automatically.
child transfers won't get it automatically.

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
        content: new OA\JsonContent(
            ref: '#/components/schemas/User',
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Returns a error',
        content: new OA\JsonContent(
            ref: '#/components/schemas/Error',
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Returns a error',
        content: new OA\JsonContent(
            ref: '#/components/schemas/Error',
        )
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
