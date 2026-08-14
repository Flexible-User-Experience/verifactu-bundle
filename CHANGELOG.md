CHANGELOG
=========

0.1.7
-----
 
 * Add Dockerized PHP development environment (Dockerfile, Compose & Makefile)
 * Fix BreakdownDetailTransformer dropping surcharge (recargo de equivalencia) fields, breaking C18 regime invoices
 * Require josemmo/verifactu-php ^0.3.4 to avoid silent FechaOperacion & RechazoPrevio loss on older releases
 * Fix copy-pasted PHPUnit testsuite name
 * Migrate phpunit.xml.dist to the modern PHPUnit schema with native deprecation gate & Symfony bridge extension
 * Require phpunit/phpunit ^11.1 and symfony/phpunit-bridge ^7.3
 * Fix Symfony 6.4 incompatibility in bundle configuration (stringNode() only exists since Config 7.2)
 * Add `is_entity_seal_certificate` AEAT client config option to support entity seal certificates ("certificado de sello de entidad")
 * Fix unprocessable bundle configuration definition (boolean nodes cannot be required and have a default value at once) and cover it with a config definition test
 * Add foreign recipients support ("IDOtro" destinatarios) with a new `ForeignFiscalIdentifierInterface` contract, DTO, transformer & factory
 * Apply sorted-config-arrays convention (sort composer.json packages & Makefile targets, enable Composer sort-packages)

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
