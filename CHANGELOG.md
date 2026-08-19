CHANGELOG
=========

0.5.0
-----
 
 * Add `statement_of_responsibility` config section (`composition`, `functionalities`, `installation_characteristics`, `typology` & `vendor_address`) to fill the Art. 13 RD 1007/2023 content that no AEAT record carries: the generated statement of responsibility only identified the producer by name & NIF and the system by name, code & version, so it missed the location data of the producer, the typology, composition & functionalities of the system and the characteristics of the installation (not even the configured `computer_system.installation_number` was printed), which made the document unusable as a "declaración responsable"
 * Render every missing statement of responsibility content as a "[PENDIENTE DE COMPLETAR]" marker naming the config option to fill and list the missing ones through the command error output, so the document stays clean on `--output` files & shell redirections
 * **BC break:** Generate the SIF statement of responsibility as a Markdown document (`templates/statement_of_responsibility.md.twig` replaces the removed `templates/statement_of_responsibility.txt.twig`), so any application overriding the template must port its copy
 * **BC break:** `GenerateSifStatementCommand` takes the statement of responsibility config array as its second constructor argument, so any manual instantiation of the service must pass it
 * Fix the mandatory "QR tributario:" label being rendered below the code in "No Veri*Factu" mode, where the law places it above the code and leaves it to whoever prints the invoice: the generated PNG now only carries the "VERI*FACTU" legend under the code in Veri*Factu mode and comes bare otherwise, instead of stamping a misplaced label that a caller drawing it right would duplicate

0.4.0
-----
 
 * **BC break:** `AeatClientHandler` takes the AEAT client config array as its first constructor argument, so any manual instantiation of the service must pass it
 * **BC break:** `QrCodeHandler` takes an `InvoiceIdentifierFactory` as its second constructor argument, so any manual instantiation of the service must pass it
 * **BC break:** Rename the unused `QrCodeHandler::QR_CODE_TOP_LEGAL_LABEL` constant to `QR_CODE_TRIBUTARY_LEGAL_LABEL`, which is now actually rendered in "No Veri*Factu" mode
 * Add `AeatClientHandler::sendRecords()` & `sendRecordsUponRequirement()` to send a batch mixing registration & cancellation records in a single AEAT API call, chaining across record types, since `AeatClient::send()` always accepted mixed remissions while the bundle forced two calls (and two 1000 record windows) for them
 * **BC break:** Every record after the first one of a batch must implement the new `ChainableRecordInterface` contract, otherwise the batch is rejected before anything is sent
 * Fix batch sending dropping the chaining it computes: the previous invoice identifier & hash of records 2..N were never written back to the entities (the record contracts only exposed `setHash()` & `setHashedAt()`) although the record hash is calculated over them, so those records could not be rebuilt from the persisted data and their `XmlRecordHandler` export or requirement answer failed the hash validation with an "Invalid hash" error
 * Add `AeatClientHandler::sendRegistrationRecordsUponRequirement()` & `sendCancellationRecordsUponRequirement()` to answer an AEAT requirement ("remisión por requerimiento") page by page with a per-call reference & "FinRequerimiento" flag, which so far could only be set through the static `aeat_client.requirement_reference` config option (changing the app configuration and clearing the cache between pages, and with no way to mark the closing page within the same process)
 * Send the records of a requirement answer verbatim, keeping their stored hash & hashedAt values instead of re-chaining & re-hashing them like a new remission does, since a requirement remits the records exactly as they were recorded
 * Add `AeatClientHandler::sendVoluntaryRemissionEndNotification()` to notify the AEAT of the end of the Veri*Factu voluntary remission ("baja de la remisión voluntaria") with an empty remission (`AeatClient::send([])`), which the batch size assert made unreachable until now, accepting a per-call end date & incident flag and restoring the configured header afterwards
 * Add `is_verifactu_mode` AEAT client config option to support SIFs operating in "No Veri*Factu" mode: the generated QR codes point to the AEAT `ValidarQRNoVerifactu` endpoint (`QrGenerator::setOnlineMode()`, never exposed until now, so far every QR code was built for the Veri*Factu endpoint) and the rendered PNG drops the `VERI*FACTU` legend, only lawful for invoices remitted under the voluntary remission
 * Add QR code generation without an AEAT response (`buildQrCodeAsPngImageFromRegistrationRecordInterface()`, `buildQrCodeAsPngImageFromRegistrationRecordDto()` & `buildQrCodeAsPngImageFromInvoiceIdentifierInterface()`): the QR code content only depends on the invoice identifier & total amount, so requiring an accepted response made it impossible to print the QR before submitting the record, to reprint an invoice without keeping its response or to generate it at all in "No Veri*Factu" mode
 * Add QR code URL builders (`buildQrCodeUrlFromRegistrationRecordInterface()`, `buildQrCodeUrlFromRegistrationRecordDto()`, `buildQrCodeUrlFromInvoiceIdentifierInterface()` & `buildQrCodeUrl()`) exposing `QrGenerator::fromInvoiceId()` & `QrGenerator::from()`, to render the code with any other writer (PDF, SVG) than the bundled PNG one
 * Document the QR code generation options & the "No Veri*Factu" mode in the README

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
