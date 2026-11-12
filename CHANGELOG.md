# Changelog

## 2.0.0 (nevydáno)

Návod pro přechod: [UPGRADE-2.0.md](UPGRADE-2.0.md).

### BC breaky
- Vyžaduje PHP 8.2 nebo novější.
- `Client` se vytváří přes `new Client(new Config(...))`. Parametr `$logFilePath` je nahrazený `$debugMode` (#50).
- `Method` je nativní PHP enum (`Method::GET` místo `Method::get(Method::GET)`) a `SearchQueryOperator` už nedědí z Consistence Enum.
- `UrlBuilder` už nedědí z `Purl\Url` a `ParameterValidator` z `Rakit\Validation\Validator`. `EcomailFlexibeeInvalidRequestParameter` je obyčejná výjimka (#49).
- Ověření TLS certifikátu se při přihlášení přes session už nevypíná (viz Opravy).
- Data k odeslání, která nejdou převést na JSON (např. neplatné UTF-8), vyhodí `EcomailFlexibeeRequestFail`. Verze 1.4 v takovém případě tiše odeslala požadavek bez platného těla. Základní URL bez schématu nebo hostitele je odmítnuta při vytvoření klienta.

### Nové
- Přílohy záznamů: `getAttachments()`, `getAttachmentContent()`, `addAttachment()` a `deleteAttachment()` (#31). `HttpClient::send()` odešle `CurlRequest` včetně binárního těla.
- `Client::findByCodes()` načte více záznamů evidence podle seznamu kódů jedním requestem (#43).
- `Client` a `CompanyClient` přijímají volitelný `HttpClient`.
- `Config::debugMode()` a `Config::withEvidence()`.
- Výchozí timeouty požadavků (5 s spojení, 25 s celkem).

### Opravy
- Odpověď se čte jako PDF jen u `GET`, jehož cesta končí na `.pdf`. Dříve stačilo, aby URL kdekoli obsahovala `.pdf`, např. nahrání přílohy `faktura.pdf` nebo filtr s `.pdf`.
- Kód záznamu s apostrofem je odmítnut výjimkou `EcomailFlexibeeInvalidRequestParameter`. Dříve mohl změnit význam filtru `(kod eq '…')`.
- Ověření TLS certifikátu se při přihlášení přes session nevypíná, pokud je `$verifySSLCertificate` zapnuté.
- Název firmy v URL pro obnovu zálohy je zakódovaný (`&` nebo `#` v názvu už nerozbije URL).

### Závislosti
- Odstraněné: `ecomailcz/purl`, `rakit/validation`, `league/flysystem`, `slevomat/consistence`. Balíček kromě rozšíření PHP nemá žádnou runtime závislost.
