# Transfer Bundle

### Requirements:
* php: >= 8.3
* symfony: >= 7.2
* ext-simplexml: *

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
        <property name="addresses" type="Address[]" description="Shipping addresses" singular="address" isNullable="true"/>
        <property name="roles" type="string[]" description="List of roles" isNullable="false"/>
    </transfer>

    <transfer name="Address">
        <property name="street" type="string"/>
    </transfer>
</transfers>
```
#### Security Bundle Integration
If you want to use this feature make sure you have the Security Bundle installed.

```shell
composer require symfony/security-bundle
```

Then you can define your transfer eg. like this:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<transfers xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:noNamespaceSchemaLocation="../vendor/philipphermes/transfer-bundle/src/Resources/schema/transfer.xsd">

  <transfer name="User" type="user">
    <property name="email" type="string" isIdentifier="true"/>
    <property name="password" type="string"/>
    <property name="plainPassword" type="string" isSensitive="true" isNullable="true"/>
  </transfer>
</transfers>
```

it will implement the UserInterface and have all required methods like:
* getUserIdentifier
* eraseCredentials
* get/set Roles

### Run
```shell
symfony console transfer:generate
```