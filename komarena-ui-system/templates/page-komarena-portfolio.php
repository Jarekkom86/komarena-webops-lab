<?php
/** KomArena portfolio page template. */
defined('ABSPATH') || exit;
get_header();
$projects = array(
    array(
        'number' => '01', 'type' => 'Vlastný web a e-shop', 'title' => 'KomArena.sk / ReSmart',
        'lead' => 'WordPress a WooCommerce projekt prepájajúci e-shop, technický obsah a praktické riešenia pre otvorenú domácu automatizáciu.',
        'problem' => 'Rozvíjať funkčný e-shop a pritom zjednotiť produktové stránky, obsah, technickú údržbu a kontrolu nákupného workflow.',
        'work' => 'Tvorba a správa produktových stránok, obsahová architektúra, priebežná technická údržba, kontrola funkčnosti a bezpečné vizuálne úpravy. ReSmart sa posúva k Home Assistant, ESPHome, ESP32/ESP8266 a lokálnym otvoreným automatizáciám.',
        'tools' => array('WordPress', 'WooCommerce', 'PHP', 'CSS', 'Git/GitHub', 'SEO a obsah', 'QA workflow'),
        'result' => 'Aktívny e-shop a obsahový projekt v priebežnom rozvoji. Nový smer je verejne komunikovaný na homepage; portfólio a ďalšie prelinkovanie sú predmetom tejto etapy.',
        'links' => array('KomArena.sk' => 'https://komarena.sk/', 'ReSmart servis' => 'https://komarena.sk/resmart/', 'GitHub – U-enie' => 'https://github.com/Jarekkom86/U-enie'),
    ),
    array(
        'number' => '02', 'type' => 'Externý klientsky projekt', 'title' => 'TIP-TOP Kuchyne',
        'lead' => 'Prevzatie staršieho WordPress/WooCommerce webu s technickým dlhom a jeho postupná stabilizácia a modernizácia.',
        'problem' => 'Problematický zdedený web vyžadoval identifikovať chyby, obnoviť zrozumiteľnú štruktúru a databázové zázemie a zaviesť udržateľnú správu katalógu.',
        'work' => 'Audit chýb a technického dlhu, obnova štruktúry a dátového zázemia, správa katalógu a priebežná obsahová i technická správa. Prémiový facelift prebieha postupne, aby neohrozil existujúce funkcie.',
        'tools' => array('WordPress', 'WooCommerce', 'Elementor', 'Databázová diagnostika', 'Katalóg produktov', 'Technická údržba'),
        'result' => 'Web je aktívny a spravovaný. Stabilizácia, katalógová správa a vizuálny facelift sú priebežná práca; stránka sa neprezentuje ako uzavretý alebo definitívne dokončený projekt.',
        'links' => array('TIP-TOP Kuchyne' => 'https://www.tiptopkuchyne.sk/'),
    ),
    array(
        'number' => '03', 'type' => 'AI a automatizácia práce', 'title' => 'JARO OS',
        'lead' => 'Vlastný systém na organizáciu práce, diagnostiku, hľadanie chýb a zlepšovanie opakovateľných procesov.',
        'problem' => 'Rôznorodé webové a prevádzkové úlohy potrebovali jednotný spôsob rozhodovania, kontroly, obnovy po chybe a zachovania pracovného kontextu.',
        'work' => 'Návrh pravidiel práce, diagnostických a QA postupov, backlogu, rozhodovacích záznamov a rollback-ready vykonávania cez oddelené vetvy.',
        'tools' => array('AI asistenti', 'GitHub', 'Automatizačné workflow', 'PowerShell', 'Dokumentované QA', 'Prevádzková diagnostika'),
        'result' => 'Funkčný interný systém, ktorý sa ďalej rozvíja podľa reálnych potrieb. Verejný repozitár dokumentuje vybrané pravidlá a pracovné postupy, nie celý interný kontext.',
        'links' => array('JARO OS dokumentácia' => 'https://github.com/Jarekkom86/U-enie/blob/main/docs/jaro-os-native-first-execution-policy-v1.md'),
    ),
    array(
        'number' => '04', 'type' => 'Home Assistant projekt', 'title' => 'Inteligentná vírivka + fotovoltika',
        'lead' => 'Prestavba nefunkčného ovládania na vlastné smart riešenie integrované do Home Assistant.',
        'problem' => 'Pôvodné ovládanie vírivky prestalo plniť svoju funkciu a samostatná fotovoltika neposkytovala automatické využitie dostupných prebytkov.',
        'work' => 'Návrh vlastného riadenia, integrácia stavov a ovládania do Home Assistant a automatizačná logika pre využívanie prebytkov fotovoltiky podľa dostupnej energie a prevádzkových podmienok.',
        'tools' => array('Home Assistant', 'ESP / mikrokontroléry', 'ESPHome', 'Senzory a relé', 'Energetické dáta', 'Automatizácie'),
        'result' => 'Reálne používané riešenie v priebežnom ladení. Konkrétne elektrické zapojenie ani výkonové parametre tu nie sú publikované; zásahy do 230 V patria odborne spôsobilej osobe.',
        'links' => array('KomArena Build Lab' => 'https://komarena.sk/'),
    ),
);
?>
<main id="primary" class="ka-portfolio">
  <section class="ka-portfolio__hero">
    <div><p class="ka-eyebrow">KomArena • WordPress • otvorené automatizácie</p><h1>Weby, e-shopy a automatizácie, ktoré riešia reálny problém.</h1><p class="ka-portfolio__intro">Portfólio práce na WordPress/WooCommerce projektoch, technickej správe a vlastných systémoch postavených okolo Home Assistant, ESPHome a praktickej automatizácie.</p><div class="ka-portfolio__actions"><a class="ka-button" href="#projekty">Pozrieť projekty</a><a class="ka-button ka-button--ghost" href="https://komarena.sk/kontakt/">Kontakt</a></div></div>
    <aside><span>Hlavné zameranie</span><strong>WordPress + WooCommerce</strong><strong>Home Assistant + ESPHome</strong><strong>Diagnostika + automatizácia</strong></aside>
  </section>
  <section class="ka-portfolio__trust" aria-label="Spôsob práce"><span>Bez vymyslených metrík</span><span>Bezpečné vetvy a rollback</span><span>Kontrola pred nasadením</span><span>Reálny stav projektu</span></section>
  <section id="projekty" class="ka-portfolio__projects">
    <header><p class="ka-eyebrow">Vybrané projekty</p><h2>Od technického dlhu po vlastnú automatizáciu</h2></header>
    <?php foreach ($projects as $project) : ?>
      <article class="ka-project">
        <div class="ka-project__head"><span class="ka-project__number"><?php echo esc_html($project['number']); ?></span><div><p class="ka-eyebrow"><?php echo esc_html($project['type']); ?></p><h3><?php echo esc_html($project['title']); ?></h3><p class="ka-project__lead"><?php echo esc_html($project['lead']); ?></p></div></div>
        <div class="ka-project__grid"><div><h4>Problém / zadanie</h4><p><?php echo esc_html($project['problem']); ?></p></div><div><h4>Čo bolo urobené</h4><p><?php echo esc_html($project['work']); ?></p></div><div><h4>Výsledok / aktuálny stav</h4><p><?php echo esc_html($project['result']); ?></p></div><div><h4>Technológie a nástroje</h4><ul><?php foreach ($project['tools'] as $tool) : ?><li><?php echo esc_html($tool); ?></li><?php endforeach; ?></ul></div></div>
        <nav class="ka-project__links" aria-label="Relevantné odkazy"><?php foreach ($project['links'] as $label => $url) : ?><a href="<?php echo esc_url($url); ?>"<?php echo strpos($url, 'komarena.sk') === false ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html($label); ?> <span aria-hidden="true">↗</span></a><?php endforeach; ?></nav>
      </article>
    <?php endforeach; ?>
  </section>
  <section class="ka-portfolio__cta"><p class="ka-eyebrow">Spolupráca</p><h2>WordPress, WooCommerce alebo automatizácia?</h2><p>Hľadáte človeka, ktorý vie prevziať existujúci web, pomenovať problémy a posúvať ho bezpečne po overiteľných krokoch?</p><a class="ka-button" href="https://komarena.sk/kontakt/">Ozvať sa KomArena</a></section>
</main>
<?php get_footer(); ?>
