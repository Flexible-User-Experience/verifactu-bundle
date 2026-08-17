VerifactuBundle
===============

VerifactuBundle is a Symfony bundle to deal with Veri\*Factu Spanish digital invoicing law. This bundle relies on `josemmo/verifactu-php` library to send your invoices to the AEAT[^aeat] Veri\*Factu API.

This bundle also can generate legal QR validation codes as PNG image to include into your printed invoices.

## Disclaimer

This Symfony bundle is provided without a responsible declaration, as it is **not** an Invoicing Computer System ("Sistema Informático de Facturación" or "SIF"[^sif] as known reference in Spain's law).

This is a third-party tool to integrate your SIF[^sif] with the Veri\*Factu API to comply with the Spanish state government's anti-fraud law. It is **your responsibility** to audit its code and use it in accordance with the applicable regulations.

For more information, see [Artículo 13 del RD 1007/2023](https://www.boe.es/buscar/act.php?id=BOE-A-2023-24840#a1-5).

Installation
------------

VerifactuBundle requires PHP 8.2 or higher and Symfony 6.4 or higher. Run the following command to install it in your application:

```shell
composer require flexible-ux/verifactu-bundle
```

### Configure the bundle in your `config/packages/flexible_ux_verifactu.yaml` file:

```yaml
flexible_ux_verifactu:
    aeat_client:
        is_entity_seal_certificate: false # only set to true if your PFX certificate is an entity seal ("certificado de sello de entidad")
        is_prod_environment: false # only set to true to make real AEAT API calls, be careful here
        pfx_certificate_filepath: '%your_pfx_certificate_filepath%'
        pfx_certificate_password: '%pfx_certificate_password%'
        representative: # optional ("Representante"), remove if not applicable
            name: '%your_representative_name%'
            nif: '%your_representative_nif%'
        requirement_is_last_submission: false # only used together with a requirement_reference
        requirement_reference: null # only for remissions upon AEAT request ("remisión por requerimiento")
        voluntary_remission_end_date: null # 'YYYY-MM-DD' format, only set it when ending Veri*Factu voluntary remission
        voluntary_remission_is_affected_by_incident: false # only used together with a voluntary_remission_end_date
    # SIF (developer) credentials
    computer_system:
        vendor_name: '%your_vendor_name%'
        vendor_nif: '%your_vendor_nif%' # 9 digits (Spanish NIF or CIF)
        name: '%your_name%'
        id: 'ID' # only 2 letters
        version: '%your_version%'
        installation_number: '%your_installation_number%'
        only_supports_verifactu: false # depending on your Invoicing Computer System or ERP
        supports_multiple_taxpayers: false # for now this bundle only supports a single taxpayer, keep it to false
        has_multiple_taxpayers: false # for now this bundle only has a single taxpayer, keep it to false
    # Taxpayer (enterprise who emit the legal invoices) credentials
    fiscal_identifier:
        name: '%your_name%'
        nif: '%your_nif%' # 9 digits (Spanish NIF or CIF)
```

## Usage

### `AeatClientHandler` and `QrCodeHandler` Services

You can inject the `AeatClientHandler` service in your app. Make `sendRegistrationRecord` method calls to send registration records to AEAT API. Your `Invoice` model (or entity) must implement `FlexibleUx\VerifactuBundle\Contract\RegistrationRecordInterface`.

For now, you must generate the QR code image at same time, so inject `QrCodeHandler` service too.

```php
use FlexibleUx\VerifactuBundle\Handler\AeatClientHandler;
use FlexibleUx\VerifactuBundle\Handler\QrCodeHandler;

class AppTestController
{
    public function test(Invoice $invoice, InvoiceManager $invoiceManager, AeatClientHandler $aeatClientHandler, QrCodeHandler $qrCodeHandler)
    {
        $registrationRecord = $invoiceManager->transformInvoiceToRegistrationRecordInterface($invoice, $invoice->getPreviousInvoice());
        // is up to you to create an `InvoiceManager` (or whatever) to transform your Invoice model into a data value object that implements the `RegistrationRecordInterface` contract.
        // to keep traceability you must include a reference to the previous registered invoice, only can be null for the very first invoice.
        $result = $aeatClientHandler->sendRegistrationRecord($registrationRecord);
        // $result is an `AeatResponseInterface` contract. Ask it whether AEAT registered the record instead of reading the envelope status yourself, see "Reading the response" below.
        if (!$result->isAccepted()) {
            // a refused record never entered the chain: do NOT persist its hash, or the next invoice would chain to a record AEAT does not hold.
            throw new \RuntimeException($result->getErrorDescription());
        }
        $aeatJsonArrayResponse = $aeatClientHandler->getJsonArrayFromAeatResponseDto($result);
        // we recommend you to always store the result array or a JSON serialized version into your Invoice entity
        $invoice->setAeatJsonResponse($aeatJsonArrayResponse);
        // persist the `hash` and `hashedAt` values into your Invoice because it is mandatory to attach with the previous invoice traceability information and to keep the current Invoice integrity.
        // this values has been updated into the `$registrationRecord` object during the `sendRegistrationRecord` method call.
        $invoice
            ->setAeatHash($registrationRecord->getHash())
            ->setAeatHashedAt($registrationRecord->getHashedAt())
        ;
        $this->invoiceRepository->update(true);
        // store the changes into your Invoice entity.
        $qrCodePngImage = $qrCodeHandler->buildQrCodeAsPngImageFromRegistrationRecordAndAeatResponseInterfaces($registrationRecord, $result);
        // finally you can get a legal QR code as a PNG image, but keep in mind that for now must be generated at the same moment with successfully responses.
        $qrCodePngImage->saveToFile(sprintf('%s/var/qr_invoice_id_%s.png', $this->assetsManager->getProjectRootDir(), $invoice->getId()));
        // save QR PNG image to disk.
        // read `endroid/qr-code` documentation to handle the image file.
    }
}
```

### Reading the response

The envelope status returned by `getStatus()` describes the whole submission, not your record: `ResponseStatus::PartiallyCorrect` means *some* record failed, without saying which one. Deciding on it alone is how a refused record ends up persisted and the chain gets a hash AEAT never stored.

`AeatResponseInterface` answers that question for you:

| Method | Answers |
|---|---|
| `isAccepted()` | did AEAT register **every** record of the submission? A response carrying no record is never an acceptance |
| `getRegisteredItems()` | the records that entered the chain, so their hash must be persisted |
| `getRejectedItems()` | the records AEAT refused, whose hash must **not** be persisted |
| `getErrorDescription()` | description of the first refused record, or `null` when nothing was refused |

Note that a record answered with `ItemStatus::AcceptedWithErrors` **is** registered by AEAT and belongs to the chain, errors notwithstanding, so it counts as registered.

For a batch, iterate the two lists to tell which invoices to update:

```php
$result = $aeatClientHandler->sendRegistrationRecords($registrationRecords);
foreach ($result->getRejectedItems() as $rejectedItem) {
    // $rejectedItem->invoiceId identifies the invoice, $rejectedItem->errorDescription says why
}
```

### Error handling

Besides checking the returned response status, wrap the `AeatClientHandler` send calls to handle these exceptions (since `josemmo/verifactu-php` 0.3.1 HTTP errors do not throw at transport level, so AEAT server faults surface as `AeatException`):

```php
use josemmo\Verifactu\Exceptions\AeatException;
use josemmo\Verifactu\Exceptions\InvalidModelException;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

try {
    $result = $aeatClientHandler->sendRegistrationRecord($registrationRecord);
} catch (ValidationFailedException|InvalidModelException $exception) {
    // thrown BEFORE anything is sent: your data does not fulfill the bundle DTO asserts
    // (ValidationFailedException) or the josemmo/verifactu-php model validations
    // (InvalidModelException), fix the invoice data and send again.
} catch (AeatException $exception) {
    // the AEAT server returned a SOAP fault or an unparseable response: the remission outcome
    // is UNKNOWN, treat the record as not registered and retry later.
} catch (ClientExceptionInterface $exception) {
    // PSR-18 network/transport failure (timeout, DNS, TLS): same treatment as AeatException.
}
```

An `\InvalidArgumentException` is also thrown for a missing or unreadable PFX certificate file and for an invalid batch size (1 to 1000 records). Keep in mind that the record `hash` & `hashedAt` values are only written back to your entity **after** a response is received, so none of these exceptions can leave a half-updated invoice behind.

### Cancellation records

To cancel a previously registered invoice, make your cancellation model implement `FlexibleUx\VerifactuBundle\Contract\CancellationRecordInterface` (or build the provided `CancellationRecordDto` directly) and call:

```php
$result = $aeatClientHandler->sendCancellationRecord($cancellationRecord);
```

The previous invoice identifier and its hash are **mandatory** for every cancellation record to keep the chain ("encadenamiento") integrity. Like with registration records, the record's `hash` and `hashedAt` values are updated during the call and you must persist them, and you must check the returned response status.

### Batch sending

You can send up to 1000 records (the AEAT remission limit) in a single API call:

```php
$result = $aeatClientHandler->sendRegistrationRecords($registrationRecords);
$result = $aeatClientHandler->sendCancellationRecords($cancellationRecords);
```

Every record after the first one of the batch is chained to the preceding record automatically (its previous invoice identifier & hash are computed for you, so only the first record of the batch must reference the last previously registered record). The `hash` and `hashedAt` values of every record are updated during the call, and you can correlate per-record acceptance through `$result->getItems()`, which contains one response item per submitted record.

### XML record storage

Inject the `XmlRecordHandler` service to keep legal XML copies of your sent records and to read them back:

```php
use FlexibleUx\VerifactuBundle\Handler\XmlRecordHandler;

$xml = $xmlRecordHandler->exportRegistrationRecordToXmlString($registrationRecord); // standalone <sum1:RegistroAlta /> XML string
$xml = $xmlRecordHandler->exportCancellationRecordToXmlString($cancellationRecord); // standalone <sum1:RegistroAnulacion /> XML string
$record = $xmlRecordHandler->importRecordFromXmlString($xml); // back to a josemmo/verifactu-php record model
```

Export keeps the **stored** `hash` and `hashedAt` values of the already sent record (make sure your entity returns them exactly as persisted, timezone included) and re-validates the record, so any tampering with the persisted data is detected — the same integrity check runs on import, unless you pass `$validate: false` to inspect a corrupted record.

### SIF statement of responsibility

Generate a draft of the legal "declaración responsable" document (Artículo 13 del RD 1007/2023) from your configured `computer_system` credentials:

```shell
php bin/console flexible-ux:verifactu:generate-sif-statement "Barcelona" --output var/declaracion-responsable.txt
```

The generated document is a **draft**: review it with your legal counsel before signing it and keeping it available to your clients and to the AEAT[^aeat].

Development with Docker
-----------------------

This repository includes a Dockerized PHP environment to work on the bundle without a local PHP installation. It only requires [Docker](https://docs.docker.com/get-docker/) with the Compose plugin:

```shell
make install # build the PHP image, start the container and install Composer dependencies
make it      # run the full local gate: PHP-CS-Fixer, PHPStan and PHPUnit
make shell   # open an interactive shell into the PHP container
make         # list all available targets
```

By default the image is built with PHP 8.4. To rebuild the environment with another PHP version of the CI matrix (8.2 to 8.5) run, for example:

```shell
PHP_VERSION=8.2 make destroy install
```

Xdebug is installed but disabled by default. Enable it with the `XDEBUG_MODE` environment variable (e.g. `XDEBUG_MODE=debug make startd` or `XDEBUG_MODE=coverage make test`).

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

[^aeat]: **AEAT** — *Agencia Estatal de Administración Tributaria*.  
Agency of the Government of Spain.

[^sif]: **SIF** — *Sistema Informático de Facturación*.  
Certified invoicing software compliant with Spanish tax regulations.

[^nif]: **NIF** — *Número Identificación Fiscal*.  
Is the tax identification number used by the Spanish Tax Agency to identify individuals and legal entities for tax purposes.

[^cif]: **CIF** — *Código Identificación Fiscal*.  
Is the tax identification number used by the Spanish Tax Agency to identify companies or enterprises entities for tax purposes.

[^csv]: **CSV** — *Código Seguro de Verificación*.  
Unique verification code returned by the Veri\*Factu API to identify a registered invoice.
