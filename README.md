[![Build Status](https://travis-ci.org/Ecomailcz/flexibee-client.svg?branch=master)](https://travis-ci.org/Ecomailcz/flexibee-client)

# flexibee-client
Jednoduchý curl client, který se stará o správné složení requestu do systému Flexibee a následné vrácení výsledků.

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

`$authSessionId - Hodnota authentikační session id fro Flexibee`

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
$evidenceItemId = $client->save($evidenceData, null);
```
Pokud vše proběhne v pořádku, vratí se ID záznamu ze systému Flexibee. Nastane-li chyba, vyhodí se výjimka
`EcomailFlexibeeRequestError::class`. Pro editaci záznamu stačí vyplnit druhý parametr `$id`.

## Vrácení záznamu dle parmetrů
Nalezení záznamu dle id s vyhozením výjimky, pokud záznam neexistuje  
```
$evidenceItem = $client->getById($evidenceItemId, $queryParams);
$evidenceItem = $client->getByCustomId($evidenceItemId, $queryParams);
```

Vrácení prázného záznamu, pokud neexistuje ve Flexibee (bez vyhození výjimky)  
```
$evidenceItem = $client->findById($evidenceItemId, $queryParams);
$evidenceItem = $client->findByCustomId($evidenceItemId, $queryParams);
```

## Smazání záznamu
```
$client->deleteById($id);
$client->deleteByCustomId($id);
```

## Generování PDF
Systém Flexibee umožňuje vrátit vygenerované faktury.
```
$client->getPdfById($id, $queryParams);
```

## Vyhledávání v evidenci
Systém Flexibee umožňuje vyhledávat nas evidencí. (https://www.flexibee.eu/api/dokumentace/ref/filters/)
```
$client->searchInEvidence($query);
```
## Vytváření vlastních requestů
Client nabízí možnost vytváření vlastních requestů. Stačí zavolat:  
```
$responseData = $client->makeRequest(Method $httpMethod, string $uri, array $postFields);
$responseData = $client->makeRequestPrepared(Method $httpMethod, string $uri);
$responseData = $client->makeRawPrepared(Method $httpMethod, string $uri);
```
Následně máte k dispozici data vrácená z Flexibee. Chyby jsou ošetřeny vyhozením kontrétních výjimek.

## Flexibee API
https://www.flexibee.eu/api/dokumentace/
