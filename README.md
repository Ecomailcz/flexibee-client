# FlexiBee client
[![Build Status](https://travis-ci.org/Ecomailcz/flexibee-client.svg?branch=master?format=flat)](https://travis-ci.org/Ecomailcz/flexibee-client)
[![Latest Stable Version](https://poser.pugx.org/ecomailcz/flexibee-client/v/stable?format=flat)](https://packagist.org/packages/ecomailcz/flexibee-client)
[![Total Downloads](https://poser.pugx.org/ecomailcz/flexibee-client/downloads?format=flat)](https://packagist.org/packages/ecomailcz/flexibee-client)
[![License](https://poser.pugx.org/ecomailcz/flexibee-client/license?format=flat)](https://packagist.org/packages/ecomailcz/flexibee-client)

Jednoduchý cURL client, který se stará o správné složení requestu do systému Flexibee a následné vrácení výsledků.

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
$enableSelfSignedCertificate,
$authSessionId,
);
```
`$enableSelfSignedCertificate - Vyžadání self signed certifikátu`

`$authSessionId - Hodnota authentikačního id pro Flexibee`

## Typy klientů
K dispozici jsou různé druhy klientů, které mají implementovány metody pro snažší práci s FlexiBee API.
```
$bankClient = new BankClient($config);
$companyClient = new CompanyClient($config);
```

## Vygenerování autorizačního tokenu
```
$client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', 'adresar', false, null);
$tokens = $client->getAuthAndRefreshToken();
```

## Vytvoření či editace záznamu
```
$client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', 'adresar', false, null);
$evidenceData['kod'] = 'prvnizaznam'
$evidenceData['nazev'] = 'První kontaktní adresa'
$evidenceItemId = $client->save($evidenceData, null, $dryRun, $uriParameters);
```
Pokud vše proběhne v pořádku, vratí se třída `\EcomailFlexibee\Http\Response\Response:class` s daty ze systému Flexibee. Nastane-li chyba, vyhodí se výjimka
`EcomailFlexibeeRequestError::class`. Pro editaci záznamu stačí vyplnit druhý parametr `$id`.

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
Systém Flexibee umožňuje vyhledávat nas evidencí. (https://www.flexibee.eu/api/dokumentace/ref/filters/)
```
$client->searchInEvidence($query, $uriParameters);
```
## Vytváření vlastních requestů
Client nabízí možnost vytváření vlastních requestů. Stačí zavolat:  
```
$responseData = $client->callRequest(Method $httpMethod, string $section, array $queryParameters);
```
Následně máte k dispozici data vrácená z Flexibee. Chyby jsou ošetřeny vyhozením kontrétních výjimek.

## Oficiální API dokumentace
https://www.flexibee.eu/api/dokumentace/
