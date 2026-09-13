<!-- markdownlint-disable MD013 -->

# KomArena Blog — article/card template v3

Tento dokument je záväzný redakčný a vizuálny štandard pre KomArena blog. Cieľom nie je vyrábať generické SEO články, ale praktický technický obsah, ktorý používateľovi pomôže rozhodnúť sa, zapojiť systém správne, vyhnúť sa chybe alebo nadviazať na konkrétny produkt či ďalší návod.

## 1. Základné pravidlo

Každý článok musí mať jasnú odpoveď na otázku: **Čo si používateľ po prečítaní dokáže vybrať, nastaviť, overiť alebo vyriešiť?**

Ak článok iba všeobecne vysvetľuje pojem bez praktickej hodnoty, nie je pripravený na publikovanie.

Zakázané sú:

- generické AI formulácie bez konkrétneho prínosu,
- dlhé úvody bez odpovede,
- marketingové frázy bez dôkazu,
- univerzálne CTA nalepené na každý článok,
- umelé predlžovanie textu kvôli SEO.

## 2. Jazyk — Slovak first

Verejný obsah je predvolene po slovensky.

- nadpisy, vysvetlenia, popisy obrázkov, CTA a navigačné texty písať prirodzenou slovenčinou,
- oficiálne názvy Home Assistant, ESPHome, Wi‑Fi, Zigbee, Thread, Matter, Bluetooth Proxy, Arduino, ESP32 a názvy produktov neprekladať,
- menej známy anglický termín pri prvom použití vysvetliť po slovensky,
- `smart home` → `inteligentná domácnosť`, ak nejde o názov,
- `logs` → `záznamy`, `checklist` → `kontrolný zoznam`, `workflow` → `postup`,
- preklad nesmie znieť mechanicky ani úradnícky.

## 3. Povinná obsahová štruktúra

Každý článok musí obsahovať podľa typu témy:

1. **H1 s jasným problémom alebo rozhodnutím.**
2. **Krátky úvod** — najviac 2–3 odseky.
3. **Rýchla odpoveď / verdikt** — používateľ dostane jadro odpovede hneď.
4. **Praktický blok** — konkrétne kroky, porovnanie, rozhodovací strom alebo reálny príklad.
5. **Čo sa môže pokaziť** — najčastejšie chyby a diagnostika.
6. **Bezpečnosť a limity**, ak sú relevantné.
7. **Súvisiace produkty** iba po živom product gate.
8. **Súvisiace články** — 2 až 5 interných odkazov.
9. **Čo spraviť ďalej** — jeden jasný nasledujúci krok.
10. **Primárne zdroje** a dátum technického overenia.

Pri návodovej téme musí byť čitateľ schopný podľa článku vykonať aspoň jeden konkrétny krok bez ďalšieho hľadania.

## 4. KomArena vizuálny štandard v3

Referenčný smer: tmavý prémiový technologický interiér, čierna/antracitová plocha, jemné tyrkysové svetelné línie, teplé ambientné svetlo a čistá technická hierarchia. Vizuál má pôsobiť ako jednotná séria KomArena, nie ako náhodný AI obrázok.

### Povinné vlastnosti

- hlavný pomer strán **16:9**,
- pracovný master **1600 × 900 px**,
- export **WebP**, cieľ približne do 250 kB,
- dôležitý obsah v centrálnej bezpečnej zóne,
- maximálne jeden hlavný vizuálny koncept na obrázok,
- vizuál musí vysvetľovať tému: sieť, automatizáciu, porovnanie, diagnostiku, produkt alebo architektúru,
- slovenské texty, ak je text v obrázku vôbec potrebný,
- žiadne dodávateľské názvy, interné SKU, nákupné ceny alebo sourcing poznámky.

### Text v titulnom obrázku

Text môže byť použitý, ak je súčasťou vysvetľujúcej infografiky, ale musí byť stručný a čitateľný. Neopakovať celý titulok článku iba dekoratívne.

### Reálne produkty

Ak obrázok tvrdí alebo ukazuje konkrétny produkt, použiť presnú produktovú fotografiu. Generická podobizeň nesmie byť prezentovaná ako konkrétny predávaný model.

### Obrázky v článku

Každý článok musí mať:

- 1 featured image,
- minimálne 1 ďalší užitočný vizuál v tele,
- pri praktických článkoch preferenčne 2–4 vizuály: schéma, screenshot, produkt, diagnostický strom alebo detail.

Každý obrázok má slovenský alt text. Popis pod obrázkom má vysvetľovať, čo z neho má používateľ pochopiť.

## 5. Produktové odkazy

Produktové CTA použiť iba ak produkt:

- reálne rieši problém z článku,
- je verejne publikovaný a viditeľný,
- je objednateľný alebo má korektný povolený režim dostupnosti,
- kompatibilita je overená,
- permalink je načítaný zo živého WooCommerce produktu.

Nikdy nepoužívať:

- vypredaný/skrytý produkt ako nákupné CTA,
- náhodný cross-sell kvôli marži,
- hardcoded cenu alebo sklad v evergreen texte,
- interné sourcing informácie.

## 6. Interné linkovanie

Každý článok má mať:

- 1 nadradenú sekciu/hub,
- 2–5 relevantných článkov,
- 0–3 produktové odkazy podľa reálnej relevancie,
- spätné inbound odkazy zo starších článkov, keď to tematicky dáva zmysel.

Odkazový text musí pomenovať cieľ. Nepoužívať „kliknite sem“.

## 7. Mobil a čitateľnosť

- preferovať krátke odseky,
- široké tabuľky nahradiť kartami alebo samostatnými blokmi,
- kód a YAML musia byť kopírovateľné,
- dlhý diagnostický postup deliť na očíslované kroky,
- obrázky musia zostať zrozumiteľné aj po zmenšení na mobil.

## 8. SEO

SEO je dôsledok kvalitného obsahu, nie jeho náhrada.

Pred publikovaním:

- SEO title približne do 60 znakov,
- meta description približne 145–160 znakov,
- jedna prirodzená focus keyword téma,
- canonical na vlastný permalink,
- index/follow,
- Article alebo TechArticle podľa obsahu,
- žiadne násilné opakovanie kľúčového slova.

## 9. Technická dôveryhodnosť

Poradie zdrojov:

1. výrobca / oficiálna dokumentácia,
2. Home Assistant / ESPHome / Espressif / Arduino / datasheet,
3. sekundárne zdroje iba ako doplnok.

Pri verziovo závislej informácii uviesť alebo interne evidovať dátum overenia.

Pri elektrických zapojeniach vždy explicitne rozlišovať 3,3 V / 5 V / nízke DC napätie a nevytvárať nebezpečné 230 V DIY návody.

## 10. Publikačný gate

Článok môže ísť von až keď prejde:

- obsahovým auditom,
- jazykovým auditom,
- obrazovým auditom,
- internými odkazmi,
- produktovým gate, ak obsahuje produkty,
- SEO a schema kontrolou,
- mobilnou čitateľnosťou,
- kontrolou, že verejný text neobsahuje dodávateľské alebo interné údaje.

Stavový tok:

`draft` → `ready-for-review` → `approved-for-publish` → `published`
