# 🧾 Lemonade PDF

![MIT License](https://img.shields.io/github/license/johnnyxlemonade/component_pdf)
![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)
![Master Protected](https://img.shields.io/badge/branch-master%20protected-blue)
![PHP Version](https://img.shields.io/badge/PHP-8.1+-informational)

---
Jednoduchá komponenta pro generování faktur ve formátu PDF v PHP.

---

## 📦 Instalace

Tento balíček není publikován na Packagist. Pro použití ho musíš přidat ručně do `composer.json` jako repozitář typu `vcs`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/johnnyxlemonade/component_pdf"
    }
  ],
  "require": {
    "lemonade/pdf": "dev-master"
  }
}
```

Pak spusť:

```bash
  composer update
```

Alternativně můžeš použít repozitář jako `path`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../cesta/k/repozitari"
    }
  ],
  "require": {
    "lemonade/pdf": "*"
  }
}
```

---

## 🚀 Ukázka použití

Kompletní ukázku najdeš v souboru [EXAMPLES.md](EXAMPLES.md).

---

## 📁 Struktura projektu

- 📄 `PdfInvoiceTemplate` – šablona faktury
- 🧱 `Order`, `Company`, `Customer` – datové modely
- 🎨 `Schema` – barvy, loga, razítka
- 🧾 `PdfOutput` – zobrazení, uložení, export PDF

---

## 🤝 Přispívání

Pull requesty jsou vítány!  
Podívej se na [CONTRIBUTING.md](CONTRIBUTING.md) pro více informací.

---

## 🔒 Bezpečnost

Větev `master` je chráněná. Všechny změny probíhají přes fork a Pull Request.  
Více v [SECURITY.md](SECURITY.md)

---

## ⚖️ Licence

Tento projekt je licencován pod [MIT licencí](LICENSE.md).
