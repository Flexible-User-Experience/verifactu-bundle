TODO
====
 
- [x] Write unit tests for core services
- [x] Add .pfx certificate file exists assert
- [x] Add NIF or CIF asserts
- [x] Handle AeatClient response to return the CSV[^csv]
- [x] Handle all AeatClient InvoiceType requests
- [x] Handle QR code PNG image generation from an AeatClient response
- [ ] Add Symfony command to generate a valid SIF[^sif] statement of responsibility document
- [x] Add Dockerized PHP development environment (Dockerfile, Compose & Makefile)
- [x] Migrate PHPUnit configuration to the modern schema with native deprecation gate
- [x] Add `is_entity_seal_certificate`, `representative`, `requirement_reference` & `voluntary_remission_end_date` AEAT client config options
- [x] Add foreign recipients ("IDOtro" destinatarios) support
- [x] Handle AeatClient CancellationRecord ("RegistroAnulacion") requests
- [x] Add batch sending support with automatic record chaining
- [x] Add XML record storage support (export & import with tamper detection)
- [ ] Document AeatException & PSR ClientExceptionInterface error handling in README (AeatClient throws them since josemmo/verifactu-php 0.3.1)

---

[^sif]: **SIF** — *Sistema Informático de Facturación*.  
Certified invoicing software compliant with Spanish tax regulations.

[^csv]: **CSV** — *Código Seguro de Verificación*.  
Unique verification code returned by the Veri*Factu API to identify a registered invoice.
