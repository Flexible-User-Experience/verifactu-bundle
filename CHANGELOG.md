CHANGELOG
=========

0.3.0
-----
 
 * **BC break:** Add `getRegisteredItems()`, `getRejectedItems()`, `isAccepted()` & `getErrorDescription()` to the `AeatResponseInterface` contract, so any custom implementation of it must provide them too
 * Move the response reading rules into the bundle instead of leaving every consumer to rediscover them: which `ItemStatus` values count as registered (`AceptadoConErrores` does), that the envelope `ResponseStatus` does not tell which record of a batch failed, and that a response carrying no record is never an acceptance
 * Fix the README recommending to persist the record hash whenever the envelope status is `Correcto` or `ParcialmenteCorrecto`, which on a partially correct submission persists a refused record and chains the next invoice to a hash AEAT never stored
 * Document the batch flow to tell registered and refused records apart through `getRegisteredItems()` & `getRejectedItems()`

0.2.0
-----
 
 * Add Dockerized PHP development environment (`Dockerfile`, `compose.yaml` & `Makefile`)
 * Add `is_entity_seal_certificate` AEAT client config option to support entity seal certificates ("certificado de sello de entidad")
 * Add foreign recipients support ("IDOtro" destinatarios) with a new `ForeignFiscalIdentifierInterface` contract, DTO, transformer & factory
 * Add CancellationRecord support ("RegistroAnulacion") with a new `CancellationRecordInterface` contract, DTO, transformer, factory & `AeatClientHandler::sendCancellationRecord()` method
 * Add `representative`, `requirement_reference` & `voluntary_remission_end_date` AEAT client config options to support "Representante", "RemisionRequerimiento" & "RemisionVoluntaria" headers
 * Add batch sending support with automatic record chaining (`sendRegistrationRecords()` & `sendCancellationRecords()`, up to 1000 records per AEAT API call)
 * Add XML record storage support with a new `XmlRecordHandler` service to export sent records as standalone XML strings (keeping stored hashes) and import them back with tamper detection
 * Add AeatClientHandler, QrCodeHandler & AeatClientFactory unit tests to complete core services coverage
 * Add existence & readability assert for the configured PFX certificate file when the AEAT client is built (clear early failure instead of a cryptic send-time error)
 * Add a reusable `#[NifOrCif]` validation constraint asserting the Spanish NIF/CIF format on the fiscal identifier, invoice issuer & vendor NIF fields
 * Add per-InvoiceType test coverage proving F1, F2, F3 & R1-R5 registration records are fully supported
 * Add `flux:verifactu:generate-sif-statement` Symfony command to generate a draft SIF statement of responsibility document ("declaración responsable", Art. 13 RD 1007/2023) from the configured computer system, adding symfony/console as a new requirement
 * Require josemmo/verifactu-php **^0.3.4** to avoid silent FechaOperacion & RechazoPrevio loss on older releases
 * Migrate `phpunit.xml.dist` to the modern PHPUnit schema with native deprecation gate & Symfony bridge extension
 * Require phpunit/phpunit **^11.1** and symfony/phpunit-bridge **^7.3**
 * Apply sorted-config-arrays convention (sort `composer.json` packages & `Makefile` targets, enable Composer `sort-packages`)
 * Extract AeatClientFactory and expose the configured `AeatClient` as an injectable service
 * Require khanamiryan/qrcode-detector-decoder **^2.0.3** to avoid PHP 8.4 deprecation notices
 * Align require-dev caret constraints to the latest tested minors (friendsofphp/php-cs-fixer **^3.95**, phpstan/phpstan **^2.2** & phpunit/phpunit **^11.5**)
 * Bump Symfony requirements to the currently supported versions (**^6.4|^7.4|^8.1**) across `composer.json` & the CI matrix
 * Document the AeatClientHandler exception surface (AeatException, PSR ClientExceptionInterface, validation exceptions) in the README & the send methods @throws annotations
 * Align the root namespace to `FlexibleUx\VerifactuBundle\`: the bundle class is now `FlexibleUx\VerifactuBundle\FlexibleUxVerifactuBundle`, the config key & service ids use the `flexible_ux_verifactu` prefix and the command is renamed to `flexible-ux:verifactu:generate-sif-statement`
 * Improve README documentation
 * Assorted housekeeping (`composer.json` keywords reformat, `Makefile` bash alias, TODO updates)
 * Fix BreakdownDetailTransformer dropping surcharge (recargo de equivalencia) fields, breaking C18 regime invoices
 * Fix copy-pasted PHPUnit testsuite name
 * Fix Symfony 6.4 incompatibility in bundle configuration (`stringNode()` only exists since Config 7.2)
 * Fix unprocessable bundle configuration definition (boolean nodes cannot be required and have a default value at once) and cover it with a config definition test
 * Fix QR code validation always failing at the AEAT recommended 850px dimensions (the decode-back check now runs on a downscaled copy of the generated image)
 * Fix corrected & replaced invoice identifiers not being transformed to library models, which made rectificative (R1-R5) & substitutive (F3) invoices crash on export

0.1.6
-----
 
 * Add QrCode validation
 * Apply AEAT QR recommendations for dimensions & error correction level
 * Manage `hash` & `hashedAt` attributes update during `AeatClientHandler` sendRegistrationRecord method call
 * Improve README documentation

0.1.5
-----
 
 * Add QrCode handler
 * Add Json serialization for Aeat responses
 * Add AeatResponseDto test
 * Enable Symfony 8.0 compatibility

0.1.4
-----
 
 * Add more DTO unit tests
 * Add BreakdownDetail transformer
 * Add AeatResponse factory

0.1.3
-----
 
 * Improve AeatClientHandler sendRegistrationRecord method validations
 * Improve README
 * Add InvoiceIdentifier transformer
 * Add TODO

0.1.2
-----
 
 * Simplify AeatClientHandler
 * Add transformers to better responsibility split

0.1.1
-----
 
 * Add Validatable interface
 * Add RegistrationRecord factory
 * Add AeatClient handler

0.1.0
-----
 
 * Add first Proof-Of-Concept

---

[^sif]: **SIF** — *Sistema Informático de Facturación*.  
Certified invoicing software compliant with Spanish tax regulations.

[^csv]: **CSV** — *Código Seguro de Verificación*.  
Unique verification code returned by the Veri*Factu API to identify a registered invoice.
