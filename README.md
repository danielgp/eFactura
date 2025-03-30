# eFactura


##  Description

eFactura scripts to produce and read XML files containing official selling details to help out business data exchange and authorities oversight. Since the way different companies chose to store their business selling relevant details, this solution does not cover extraction nor writing information to such systems/documents.


## Target audience

PHP developers that serves various business to accomodate custom applications involving selling activity in Romania.


## Usage


* add to your `composer.json` file, branch `require` following line: `"danielgp/efactura": "<target_version_specification_here>"` and save
* execute a composer update to fetch the library components
* add `use \danielgp\eFactura\TraitBackEndRomania;` to your custom class
* customize $this->arraySolutionCustomSettings as per your needs (see definition from [TraitBackEndRomania.php](/source/TraitBackEndRomania.php) file)
* consult [ClassElectronicInvoiceUserInterface.php](/source/ClassElectronicInvoiceUserInterface.php) file and method named `setActionToDo` refers to main features: checkAllMessages, checkSingleMessage and uploadElectronicInvoicesFromFolderToRomanianAuthority which you may take to your custom class


## Terms dictionary

* ABIE - Aggregate Business Information Entity
* AdES - Advanced Electronic Signature
* ASBIE - Association Business Information Entity
* ASIC-S - Associated Signature Container (simple form). A standard container that associates a single data object with one or more detached signature(s) that apply to it. See [ASiC](http://docs.oasis-open.org/ubl/os-UBL-2.1/UBL-2.1.html#b_asic)
* BBIE - Basic Business Information Entity
* BIE - Business Information Entity
* C14N - Canonicalization
* CC - Core Component
* CPFR - Collaborative Planning, Forecasting, and Replenishment [CPFR](http://docs.oasis-open.org/ubl/os-UBL-2.1/UBL-2.1.html#cpfr)
* CV2 - Credit Card Verification Numbering System
* DSig - Digital Signature
* EDI - Electronic Data Interchange
* IEC - International Electrotechnical Commission
* ISO - International Organization for Standardization
* NDR - Naming and Design Rules
* QC - Qualified Certificate
* QS - Qualified Signature
* UBL - Universal Business Language
* UML - Unified Modeling Language
* UN/CEFACT - United Nations Centre for Trade Facilitation and Electronic Business
* UNDG - United Nations Dangerous Goods
* URI - Uniform Resource Identifier
* UUID - Universally Unique Identifier
* XAdES - Digital Signature based on [XAdES](http://docs.oasis-open.org/ubl/os-UBL-2.1/UBL-2.1.html#b_XAdES)
* XML - Extensible Markup Language
* XMLDSig - XML Digital Signature [xmldsig](http://docs.oasis-open.org/ubl/os-UBL-2.1/UBL-2.1.html#b_xmldsig)
* XPath - The XML Path Language
* XSD - W3C XML Schema Language [XSD1](http://docs.oasis-open.org/ubl/os-UBL-2.1/UBL-2.1.html#xsd1) [XSD2](http://docs.oasis-open.org/ubl/os-UBL-2.1/UBL-2.1.html#xsd2)
* XSLT - Extensible Stylesheet Language Transformations (a transformation language) [XSLT 2.0](http://docs.oasis-open.org/ubl/os-UBL-2.1/UBL-2.1.html#b_xslt20)


## Useful links

* [Aplicații web RO-eFactura - completare manuală](https://mfinante.gov.ro/ro/web/efactura/aplicatii-web-ro-efactura)
* [E-invoicing in Europe](https://dddinvoices.com/learn/e-invoicing-europe/)
* [GitHub - eInvoicing-EN16931](https://github.com/ConnectingEurope/eInvoicing-EN16931)
* [Prezentare servicii web pentru Sistemul național privind factura electronică RO e-Factura](https://mfinante.gov.ro/static/10/eFactura/prezentare%20api%20efactura.pdf)
* [UBL specifications - multiple versions](https://ubl.xml.org/wiki/ubl-specifications)
* [UNL 2.1 specification](https://docs.oasis-open.org/ubl/UBL-2.1.html)
* [UBL 2.1 Invoice Example: 6 Steps to Create Your Own (XML Format)](https://www.storecove.com/blog/en/creating-your-own-ubl-invoice/?unbounce_brid=1705651446_009411_26a4ce94605ccc39070d57d1622f2a4d) by Nikkie Bakker on 2019 April 17


## Repository badges

[![Latest Stable Version](https://poser.pugx.org/danielgp/efactura/v/stable)](https://packagist.org/packages/danielgp/efactura)
[![License](https://poser.pugx.org/danielgp/efactura/license)](https://packagist.org/packages/danielgp/efactura)
[![Total Downloads](https://poser.pugx.org/danielgp/efactura/downloads)](https://packagist.org/packages/danielgp/efactura)
[![Monthly Downloads](https://poser.pugx.org/danielgp/efactura/d/monthly)](https://packagist.org/packages/danielgp/efactura)
[![Daily Downloads](https://poser.pugx.org/danielgp/efactura/d/daily)](https://packagist.org/packages/danielgp/efactura)


## Code quality analysis

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/danielgp/efactura/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/danielgp/efactura/?branch=main)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/danielgp/eFactura/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/danielgp/eFactura/?branch=main)
