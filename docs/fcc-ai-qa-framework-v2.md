# FCC AI QA Framework V2

`FCC AI QA framework v2` uvodi strukturirano testiranje koje pokriva:

- `intent` prepoznavanje
- `recommendation payload`
- finalni korisnički odgovor
- `lead_capture`
- multi-turn razgovore
- `forbidden drift` provjere
- conversation-level scoring

Trenutno je fokusiran na javne FCC AI tokove:

- `product_advisor`
- `pets_advisor`

`Coach` ostaje sljedeći korak za poseban interni runner, jer koristi drugačiji message handler i kontekst korisnika.

## Datoteke

- [tmp/fcc_ai_qa_framework_v2.php](/Users/stjepanbelosa/Documents/product/tmp/fcc_ai_qa_framework_v2.php)
- [tmp/fcc_ai_qa_scenarios_v2.php](/Users/stjepanbelosa/Documents/product/tmp/fcc_ai_qa_scenarios_v2.php)

## Kako radi

Runner za svaki scenarij:

1. otvara zaseban privremeni `public` razgovor
2. šalje poruke redom kroz `fcc_ai_handle_public_message()`
3. za svaki turn sprema:
   - korisničku poruku
   - finalni reply
   - `intent`
   - `recommendation_payload`
   - `lead_capture`
4. evaluira rezultat prema deklarativnim očekivanjima
5. računa score po turnu i score za cijeli razgovor

## Što sada možemo testirati

- `single-turn` produktne preporuke
- `doctor-first` i `pedijatar-first` slučajeve
- business / suradnja tokove
- skeptik / otpor komunikaciju
- follow-up razgovore
- zadržavanje teme kroz više poruka
- sprječavanje driftanja u krive proizvode

## Format scenarija

Svaki scenarij ima:

- `id`
- `assistant_type`
- `scope`
- `language`
- `messages`
- opcionalno `conversation`

Primjer:

```php
[
    'id' => 'business_opportunity_stays_business',
    'assistant_type' => 'product_advisor',
    'scope' => 'public_app',
    'language' => 'hr',
    'messages' => [
        [
            'user' => 'Kako mogu početi zarađivati preko vaše aplikacije?',
            'expect' => [
                'intent' => [
                    'truthy' => ['business', 'business_primary'],
                    'equals' => ['lead_type' => 'business_interest'],
                ],
                'reply' => [
                    'must_all' => ['FCC', 'surad'],
                    'forbid_any' => ['Malosi', 'Body Wash'],
                ],
                'lead' => [
                    'recommended' => true,
                    'lead_type' => 'business_interest',
                ],
            ],
        ],
    ],
]
```

## Podržana očekivanja

### `intent`

- `truthy`
- `equals`
- `falsy`

### `payload`

- `primary_product`
- `primary_any`
- `primary_empty`
- `support_any`
- `support_all`
- `condition_keys_any`
- `forbid_any`
- `question_lines_min`
- `question_contains_any`

### `reply`

- `must_all`
- `must_any`
- `forbid_any`

### `lead`

- `recommended`
- `lead_type`

### `conversation`

- `reply_forbid_any`
- `reply_must_any`
- `reply_must_all`
- `payload_forbid_any`
- `payload_must_any`

## Zašto je ovo bolje od starog načina

Stari QA je bio dobar za brze provjere, ali je najčešće radio samo:

- `PASS / FAIL`
- jedna poruka
- grubi string match

Novi okvir omogućuje:

- provjeru više slojeva istog odgovora
- testiranje stvarnog konteksta kroz razgovor
- jasnu kontrolu nad `forbidden drift` proizvodima
- precizno bilježenje gdje je puklo:
  - `intent`
  - `payload`
  - finalni tekst
  - `lead_capture`

## Pokretanje

Lokalno:

```bash
php tmp/fcc_ai_qa_framework_v2.php tmp/fcc_ai_qa_scenarios_v2.php
```

U app containeru:

```bash
php /var/www/html/tmp/fcc_ai_qa_framework_v2.php /var/www/html/tmp/fcc_ai_qa_scenarios_v2.php
```

Rezultat je JSON sa:

- `summary`
- `failed_scenario_ids`
- detaljnim `turn` evaluacijama
- raw `reply`, `intent`, `payload` i `lead_capture` podacima po turnu

## Preporučeni sljedeći koraci

Dodati još 3 bloka scenarija:

1. `live thumbs-down replay`
2. `coach conversation QA`
3. `pets advisor regression bundle`

Tako ćemo imati:

- `product advisor`
- `coach`
- `pets advisor`

u istom QA sustavu, s istim načinom ocjenjivanja.
