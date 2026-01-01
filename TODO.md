TODO
====
 
- [ ] Write unit tests for core services
- [ ] Add .pfx certificate file exists assert
- [ ] Add NIF or CIF asserts
- [x] Handle AeatClient response to return the CSV[^csv]
- [ ] Handle all AeatClient InvoiceType requests
- [x] Handle QR code PNG image generation from an AeatClient response
- [ ] Add Symfony command to generate a valid SIF[^sif] statement of responsibility document

---

[^sif]: **SIF** — *Sistema Informático de Facturación*.  
Certified invoicing software compliant with Spanish tax regulations.

[^csv]: **CSV** — *Código Seguro de Verificación*.  
Unique verification code returned by the Veri*Factu API to identify a registered invoice.
