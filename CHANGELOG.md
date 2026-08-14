CHANGELOG
=========

0.1.7
-----
 
 * Add Dockerized PHP development environment (Dockerfile, Compose & Makefile)
 * Fix BreakdownDetailTransformer dropping surcharge (recargo de equivalencia) fields, breaking C18 regime invoices
 * Require josemmo/verifactu-php ^0.3.4 to avoid silent FechaOperacion & RechazoPrevio loss on older releases
 * Fix copy-pasted PHPUnit testsuite name

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
