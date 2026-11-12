# Přechod z verze 1.4 na 2.0

Verze 2.0 obsahuje změny, které nejsou zpětně kompatibilní. Tento návod popisuje všechny změny veřejného API proti verzi 1.4 a jak kód upravit.

## Požadavky

- **PHP 8.2 nebo novější** (dříve `^8.0`). Podporované jsou jen aktuálně udržované verze PHP: 8.2, 8.3, 8.4 a 8.5.
- Balíček už nevyžaduje `ecomailcz/purl`, `rakit/validation`, `league/flysystem` ani `slevomat/consistence`. Pokud je váš projekt používá napřímo, přidejte si je do vlastního `composer.json`.

## Vytvoření klienta

`Client` se konfiguruje objektem `Config` místo samostatných parametrů. Parametr `$logFilePath` byl odstraněn, místo něj je `$debugMode`.

```php
// 1.4
$client = new Client($url, $company, $user, $password, $evidence, $verifySSLCertificate, $authSessionId, $logFilePath);

// 2.0
use EcomailFlexibee\Config;

$client = new Client(new Config($url, $company, $user, $password, $evidence, $verifySSLCertificate, $authSessionId, $debugMode));
```

- `Config::getLogFilePath()` byl odstraněn. Novou volbu vrací `Config::debugMode()`.
- Při `$debugMode = true` obsahuje výjimka `EcomailFlexibeeForbidden` (HTTP 403) odeslané hlavičky požadavku.
- Volitelný druhý parametr `?HttpClient $httpClient` umožňuje předat vlastní HTTP klienta, například v testech. Stejný parametr přijímá i `CompanyClient`.

## HTTP metody: `Method` je nativní PHP enum

`EcomailFlexibee\Http\Method` už nedědí z `Consistence\Enum\Enum`.

```php
// 1.4
$client->callRequest(Method::get(Method::GET), 'properties', [], [], []);
(string) $method;

// 2.0
$client->callRequest(Method::GET, 'properties', [], [], []);
$method->value;
```

## `SearchQueryOperator`

`EcomailFlexibee\Enum\SearchQueryOperator` už nedědí z `Consistence\Enum\Enum`. Statická metoda `convertOperatorsInQuery()` funguje beze změny.

## Výjimky

- `EcomailFlexibeeInvalidRequestParameter` je obyčejná výjimka (`new EcomailFlexibeeInvalidRequestParameter($message)`). Konstruktor už nepřijímá `Rakit\Validation\Validation`.
- Pokud data k odeslání nejdou převést na JSON (např. neplatné UTF-8), vyhodí se `EcomailFlexibeeRequestFail` a příčina (`JsonException`) je dostupná přes `getPrevious()`. Verze 1.4 v takovém případě tiše odeslala požadavek bez platného těla.
- Základní URL bez schématu nebo hostitele (např. `demo.flexibee.eu` místo `https://demo.flexibee.eu`) vyhodí `EcomailFlexibeeRequestFail` už při vytvoření klienta.

## Ověřování TLS certifikátu

Ověření certifikátu se řídí jen volbou `$verifySSLCertificate`. Ve verzi 1.4 se při přihlášení přes `$authSessionId` ověřování vždy vypnulo. Pokud se přes session přihlašujete k serveru s certifikátem, který neprojde ověřením (např. self-signed), nastavte `$verifySSLCertificate` na `false`.

## Timeouty

Požadavky mají výchozí timeouty: 5 s na navázání spojení a 25 s na celý požadavek. Verze 1.4 žádné timeouty nenastavovala.

## Interní třídy

Následující změny se týkají jen kódu, který tyto třídy používá nebo z nich dědí přímo:

- `EcomailFlexibee\Http\UrlBuilder` už nedědí z `Purl\Url`. Zděděné metody (`setPath()`, `setQuery()`, `getUrl()` …) nejsou k dispozici a konstruktor nepřijímá `Purl\ParserInterface`.
- `EcomailFlexibee\Validator\ParameterValidator` už nedědí z `Rakit\Validation\Validator`.
- `EcomailFlexibee\Http\ResponseFactory::createFromOutput()` má navíc parametr `?string $curlInfoDebug`.
- `EcomailFlexibee\Http\HttpCurlBuilder::build()` přijímá `CurlRequest` a `Config` místo samostatných parametrů. `HttpClient::request()` zůstává beze změny.
- `EcomailFlexibee\Config` a `EcomailFlexibee\Result\EvidenceResult` jsou `readonly` třídy.
- Název firmy v `UrlBuilder::createRestoreUrl()` se v URL kóduje (`rawurlencode()`).
