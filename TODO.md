TODO
====
 
- [ ] Write unit tests for core services
- [ ] Add .pfx certificate file exists assert
- [ ] Handle AeatClient response to return the CSV[^csv]
- [ ] Handle all AeatClient InvoiceType requests
- [ ] Write a TwigExtension to render a QR code from a CSV[^csv]
- [ ] Add Symfony command to generate a valid SIF[^sif] statement of responsibility document

---

[^sif]: **SIF** — *Sistema Informático de Facturación*.  
Certified invoicing software compliant with Spanish tax regulations.

[^csv]: **CSV** — *Código Seguro de Verificación*.  
Unique verification code returned by the Veri*Factu API to identify a registered invoice.
