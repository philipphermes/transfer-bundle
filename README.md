# Transfer Generator Bundle

### Requirements:
* php: >= 8.3

## Usage:

### Configuration:

```php
// config/bundles.php
return [
    // ...
    PhilippHermes\TransferBundle\PhilippHermesTransferBundle::class => ['all' => true],
];
```

#### Optional Configs:
* `transfer.namespace`: the namespace of the transfers (default: `App\\Generated\\Transfers`)
* `transfer.schema_dir`: the namespace of the transfers (default: `%kernel.project_dir%/transfers`)
* `transfer.output_dir`: the namespace of the transfers (default: `%kernel.project_dir%/src/Generated/Transfers`)

### Define Transfers:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<transfers xmlns=""
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="src/Resources/config/schema/transfer.xsd">

    <transfer name="email" type="string" description="The email of the user"/>
    <transfer name="password" type="string" description="The password of the user"/>
    <transfer name="addresses" type="Address[]" description="Shipping addresses" singular="address" isNullable="true"/>
    <transfer name="roles" type="string[]" description="List of roles" isNullable="false"/>
</transfers>
```