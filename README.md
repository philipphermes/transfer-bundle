# Transfer Bundle

### Requirements:
* php: >= 8.3

## Usage:

```shell
composer require philipphermes/transfer-bundle
```

### Configuration:

```php
// config/bundles.php
return [
    // ...
    PhilippHermes\TransferBundle\PhilippHermesTransferBundle::class => ['all' => true],
];
```

#### Optional Configs:
* `transfer.namespace`: `App\\Generated\\Transfers`
* `transfer.schema_dir`: `%kernel.project_dir%/transfers`
* `transfer.output_dir`: `%kernel.project_dir%/src/Generated/Transfers`

### Define Transfers:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<transfers xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:noNamespaceSchemaLocation="../vendor/philipphermes/transfer-bundle/src/Resources/schema/transfer.xsd">

    <transfer name="User">
        <property name="email" type="string" description="The email of the user"/>
        <property name="password" type="string" description="The password of the user"/>
        <property name="addresses" type="Address[]" description="Shipping addresses" singular="address" isNullable="true"/>
        <property name="roles" type="string[]" description="List of roles" isNullable="false"/>
    </transfer>

    <transfer name="Address">
        <property name="street" type="string"/>
    </transfer>
</transfers>
```