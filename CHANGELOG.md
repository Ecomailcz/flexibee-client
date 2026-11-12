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
- `Client` a `CompanyClient` přijímají volitelný `HttpClient`.
- `Config::debugMode()` a `Config::withEvidence()`.
- Výchozí timeouty požadavků (5 s spojení, 25 s celkem).

### Opravy
- Ověření TLS certifikátu se při přihlášení přes session nevypíná, pokud je `$verifySSLCertificate` zapnuté.
- Název firmy v URL pro obnovu zálohy je zakódovaný (`&` nebo `#` v názvu už nerozbije URL).

### Závislosti
- Odstraněné: `ecomailcz/purl`, `rakit/validation`, `league/flysystem`, `slevomat/consistence`. Balíček kromě rozšíření PHP nemá žádnou runtime závislost.
