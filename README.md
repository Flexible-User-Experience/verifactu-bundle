VerifactuBundle
===============

VerifactuBundle is Symfony bundle to deal with Veri*Factu Spanish digital
invoicing law. This bundle relies on `josemmo/verifactu-php` library.

## Disclaimer

This Symfony bundle is provided without a responsible declaration, as it is **not** an Invoicing Computer System ("Sistema Informático de Facturación" or "SIF"[^sif] as known reference in Spain's law).
This is a third-party tool to integrate your SIF[^sif] with the Veri*Factu API to comply with the Spanish state government's anti-fraud law. It is **your responsibility** to audit its code and use it in accordance with the applicable regulations.

For more information, see [Artículo 13 del RD 1007/2023](https://www.boe.es/buscar/act.php?id=BOE-A-2023-24840#a1-5).

Installation
------------

VerifactuBundle requires PHP 8.2 or higher and Symfony 6.4 or higher. Run the
following command to install it in your application:

```shell
composer require flexible-ux/verifactu-bundle
```

### Configure the bundle in your `config/packages/flux_verifactu.yaml` file:

```yaml
flux_verifactu:
    aeat_client:
        is_prod_environment: false # only set to true to make real AEAT API calls, be careful here
        pfx_certificate_filepath: '%your_pfx_certificate_filepath%'
        pfx_certificate_password: '%pfx_certificate_password%'
    # SIF (developer) credentials
    computer_system:
        vendor_name: '%your_vendor_name%'
        vendor_nif: '%your_vendor_nif%' # 9 digits (Spanish NIF or CIF)
        name: '%your_name%'
        id: 'ID' # only 2 letters
        version: '%your_version%'
        installation_number: '%your_installation_number%'
        only_supports_verifactu: false
        supports_multiple_taxpayers: false
        has_multiple_taxpayers: false
    # Taxpayer credentials
    fiscal_identifier:
        name: '%your_name%'
        nif: '%your_nif%' # 9 digits (Spanish NIF or CIF)
```

## Usage

### `AeatClientHandler` Service (WIP, for now is only a Proof-Of-Concept)

You can inject the `AeatClientHandler` service in your app. Make `sendRegistrationRecord` method calls to send registration records to AEAT API. Your `Invoice` model (or entity) must implement `Flux\VerifactuBundle\Contract\RegistrationRecordInterface`.

```php
use Flux\VerifactuBundle\Handler\AeatClientHandler;

class AppTestController
{
    public function test(Invoice $invoice, InvoiceManager $invoiceManager, AeatClientHandler $aeatClientHandler)
    {
        $registrationRecord = $invoiceManager->transformInvoiceToRegistrationRecordInterface($invoice);
        // is up to you to create an `InvoiceManager` (or whatever) to transform your Invoice model into a data value object that implements the `RegistrationRecordInterface` contract.
        $aeatClientHandler->sendRegistrationRecord($registrationRecord);
        // for now this method only returns 'OK' or 'KO' as string (please, keep `aeat_client.is_prod_environment` configuration as `false`)
        // TODO `AeatClientHandler` must return the CSV[^csv] response
    }
}
```

Code Style
----------

```shell
php ./vendor/bin/php-cs-fixer fix src/
```

Code Analysis
-------------

```shell
php ./vendor/bin/phpstan analyse
```

Testing
-------

```shell
php ./vendor/bin/phpunit tests/
```

---

[^sif]: **SIF** — *Sistema Informático de Facturación*.  
Certified invoicing software compliant with Spanish tax regulations.

[^nif]: **NIF** — *Número Identificación Fiscal*.  
...

[^cif]: **CIF** — *Código Identificación Fiscal*.  
...

[^csv]: **CSV** — *Código Seguro de Verificación*.  
Unique verification code returned by the Veri*Factu API to identify a registered invoice.
