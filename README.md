# FlexiBee - PHP HTTP client
Jednoduchý cURL client, který se stará o správné složení requestu do systému Flexibee a následné vrácení výsledků.

[![License](https://poser.pugx.org/ecomailcz/flexibee-client/license?format=flat)](https://packagist.org/packages/ecomailcz/flexibee-client)
[![Latest version](https://img.shields.io/packagist/v/ecomailcz/flexibee-client.svg?colorB=007EC6)](https://packagist.org/packages/ecomailcz/flexibee-client)
[![Downloads](https://img.shields.io/packagist/dt/ecomailcz/flexibee-client.svg?colorB=007EC6)](https://packagist.org/packages/ecomailcz/flexibee-client)
![PHPStan](https://img.shields.io/badge/style-level%207-brightgreen.svg?&label=phpstan)
## Sponsored by
[![Downloads](https://ecomail.cz/images/logo@2.png)](https://ecomail.cz)

## Instalace přes composer
```composer require ecomailcz/flexibee-client```

## Implementace
```
$client = new Client(
$accountUrl, 
$companyCode, 
$restApiUserName, 
$restApiPassword, 
$evidenceName, 
$disableSelfSignedCertificate,
$authSessionId,
);
```
`$disableSelfSignedCertificate - Vypnutí self signed certifikátu`

`$authSessionId - Hodnota authentikačního id pro Flexibee`

## Vygenerování autorizačního tokenu
```
$client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', 'adresar', false, null);
$tokens = $client->getAuthAndRefreshToken();
```

## Informace o firmách či firmě
```
$client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', 'adresar', false, null);
$companies = $client->getCompanies();
$company = $client->getCompany();
```

## Vytvoření či editace záznamu
```
$client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', 'adresar', false, null);
$evidenceData['kod'] = 'prvnizaznam'
$evidenceData['nazev'] = 'První kontaktní adresa'
$evidenceItemId = $client->save($evidenceData, null, $dryRun, $uriParameters);
```
Pokud vše proběhne v pořádku, vratí se třída `\EcomailFlexibee\Http\Response\Response:class` s daty ze systému Flexibee. Nastane-li chyba, vyhodí se výjimka
`EcomailFlexibeeRequestFail::class`. Pro editaci záznamu stačí vyplnit druhý parametr `$id`.

## Vrácení záznamu dle parametrů
Nalezení záznamu dle id s vyhozením výjimky, pokud záznam neexistuje  
```
$evidenceItem = $client->getById($evidenceItemId, $uriParameters);
$evidenceItem = $client->getByCode($evidenceItemCode, $uriParameters);
```

Vrácení prázného záznamu, pokud neexistuje ve Flexibee (bez vyhození výjimky)  
```
$evidenceItem = $client->findById($evidenceItemId, $uriParameters);
$evidenceItem = $client->findByCode($evidenceItemCode, $uriParameters);
$evidenceItem = $client->findLastInEvidence($evidenceItemCode, $uriParameters);
```

## Sumace
```
$client->sumInEvidence();
```

## Smazání záznamu
```
$client->deleteById($id, $dryRun);
$client->deleteByCode($code, $dryRun);
```

## Generování PDF
Systém Flexibee umožňuje vrátit vygenerované faktury.
```
$client->getPdfById($id, $uriParameters);
```

## Vyhledávání v evidenci
Systém Flexibee umožňuje vyhledávat nad evidencí. (https://www.flexibee.eu/api/dokumentace/ref/filters/)
```
$client->searchInEvidence($query, $uriParameters);
```

## Vyhledávání v evidenci se stránkováním
Systém Flexibee umožňuje vyhledávat nad evidencí. (https://www.flexibee.eu/api/dokumentace/ref/filters/)
K uri parametrům je automaticky přiřazený parameter ```'add-row-count' => 'true'```.
Vrací array s daty a celkovým počtem záznámů.
```
$client->searchInEvidencePaginated($query, $uriParameters);
```
## Seznam položek v evidenci
Seznam všech dostupných prvků pro konkrétní evidenci
```
$client->getPropertiesForEvidence();
```
## Vytváření vlastních requestů
Client nabízí možnost vytváření vlastních requestů. Stačí zavolat:  
```
$responseData = $client->callRequest(Method $httpMethod, string $section, array $queryParameters);
```
Následně máte k dispozici data vrácená z Flexibee. Chyby jsou ošetřeny vyhozením kontrétních výjimek.

## Oficiální API dokumentace
https://www.flexibee.eu/api/dokumentace/
