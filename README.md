[![Build Status](https://travis-ci.org/Ecomailcz/flexibee-client.svg?branch=master)](https://travis-ci.org/Ecomailcz/flexibee-client)

# flexibee-client
Jednoduchý curl client, který se stará o správné složení requestu do systému Flexibee a následné vrácení výsledků.

## Instalace
```composer require ecomailcz/flexibee-client```

## Implementace
```
$client = new Client(
$accountUrl, 
$comapnyCode, 
$restApiUserName, 
$restApiPassword, 
$evidenceName, 
$enableSelfSignedCertificate
);
```

## Vytvoření či editace záznamu
```
$client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', 'adresar');
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
## Vytváření vlastních requestů
Client nabízí možnost vytváření vlastních requestů. Stačí zavolat:  
```
$responseData = $client->makeRequest(Method $httpMethod, string $uri, array $postFields);
```
Následně máte k dispozici data vrácená z Flexibee. Chyby jsou ošetřeny vyhozením kontrétních výjimek.

## Flexibee API
https://www.flexibee.eu/api/dokumentace/
