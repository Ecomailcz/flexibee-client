# flexibee-client
Jednoduchý curl client, který se stará o správné složení requestu do systému Flexibee a následného vráceni výsledků.

## Instalace
```composer require ecomailcz/flexibee-client```

## Implementace
```
$client = new Client($accountUrl, $comapnyCode, $restApiUserName, $restApiPassword, $evidenceName, $selfSignedCertificate);
```

## Vytvoření či editace záznamu
```
$client = new Client('https://demo.flexibee.eu', 'demo', 'winstrom', 'winstrom', 'adresar');
$evidenceData['kod'] = 'prvnizaznam'
$evidenceData['nazev'] = 'První kontaktní adresa'
$evidenceItemId = $client->save($evidenceData, null);
```
Pokud vše proběhne v pořádku, vratí se ID záznamu ze systému Flexibeee. Nastane-li chyba vyhodí se vyjímka
`EcomailFlexibeeRequestError::class`. Pro editaci záznamu stačí vyplnit druhý parametr `$id`.

## Vrácení záznamu dle parmetrů
Nalezení záznamu dle id s vyhozením vyjímky, pokud záznam neexistuje  
```
$evidenceItem = $client->getById($evidenceItemId);
```

Vrácení prázného záznamu, pokud neexistuje ve Flexibee (bez vyhození vyjímky)  
```
$evidenceItem = $client->findById($evidenceItemId);
```

## Smazání záznamu
```
$client->deleteById($id)
```

## Generování PDF
Systém Flexibee umožňuje vrátit vygenerované faktury.
```
$client->getPdfById($id);
```
## Vytváření vlastních requestů
Client nabízí možnost vytváření vlastních requestů. Stačí zavolat:  
```
$responseData = $client->makeRequest(Method $httpMethod, string $uri, array $postFields);
```
Následně máte k dispozici data vrácená z Flexibee. Chyby jsou ošetřeny vyhozením kontrétních vyjímek.

## Flexibee API
https://www.flexibee.eu/api/dokumentace/
