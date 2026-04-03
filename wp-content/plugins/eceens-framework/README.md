# Eceens Framework

Plugin voor het beheren van FAQ's en Content op de Eceens website.

**Versie:** 1.7.2

---

## Plugin zip bouwen (belangrijk)

Gebruik voor releases altijd het build-script. Dit voorkomt kapotte zip-paden met `\` in bestandsnamen.

```powershell
cd "C:\Users\Gebruiker\Local Sites\eceens\app\public\wp-content\plugins\eceens-framework"
powershell -ExecutionPolicy Bypass -File ".\scripts\build-plugin-zip.ps1" -OutputDir "C:\Users\Gebruiker\OneDrive\Documents\1_JPWebCreation\Lopende klanten\ecEeens\plugin" -AlsoCanonicalZip
```

Output:
- `eceens-framework-<versie>.zip` (archief)
- `eceens-framework.zip` (install/update)

---

## Wat zit erin?

- **FAQ** -- Veelgestelde vragen met categorieën, kleuren en prioriteit
- **Content** -- Informatieve artikelen met categorieën en prioriteit
- **Gekleurde categorie-pills** -- Shortcodes voor overal op de site
- **Elementor Query IDs** -- Kant-en-klare loops voor featured items
- **Zwevende "Stel je vraag" knop** -- Alleen op FAQ-pagina's

---

## FAQ & Content beheren

### Nieuw item aanmaken

1. Ga naar **FAQ > Nieuwe FAQ** of **Content > Nieuwe Content**
2. Vul titel en tekst in
3. Onder de editor vind je extra velden:

| Veld               | Wat doet het                                         |
|--------------------|------------------------------------------------------|
| Teaser             | Korte samenvatting voor cards                        |
| Featured           | Aanvinken = verschijnt in featured blokken           |
| Homepage Featured  | Aanvinken = verschijnt op de homepage                |
| Prioriteit         | Lager nummer = eerder getoond (1 komt voor 10). Leeg = standaard `999999` (onderaan). Bij gelijke prioriteit: nieuwste eerst. |
| Handmatige titel   | Alternatieve titel voor weergave                     |
| Media Afbeelding   | Extra afbeelding (naast de uitgelichte afbeelding)   |
| Media Video URL    | Link naar een video                                  |

### Categorieën

- **FAQ > FAQ Categorieën** -- beheer categorieën en subcategorieën
- **Content > Content Categorieën** -- idem voor content

Subcategorieën maak je aan door een "Bovenliggende" categorie te kiezen bij het aanmaken. Je kunt een post aan meerdere categorieën tegelijk koppelen.

### FAQ Categorie kleuren

Bij het bewerken van een FAQ Categorie vind je een **kleurkiezer**. Deze kleur wordt gebruikt als achtergrondkleur van de categorie-pills op de website. Subcategorieën erven automatisch de kleur van hun bovenliggende categorie.

---

## Sortering

FAQ's en Content worden automatisch gesorteerd op:

1. **Prioriteit** (laag naar hoog)
2. **Datum** (nieuwst eerst) — bij dezelfde prioriteit wint het nieuwste bericht

Dit geldt voor archiefpagina's en categorie-pagina's.

---

## Elementor Query IDs

Gebruik deze in een **Loop Grid** widget onder Query > Query ID.

| Query ID                           | Toont                        | Max items |
|------------------------------------|------------------------------|-----------|
| `eceens_faq_featured`              | Featured FAQ's               | 6         |
| `eceens_content_featured`          | Featured Content             | 6         |
| `eceens_homepage_faq_featured`     | Featured FAQ's (homepage)    | 3         |
| `eceens_homepage_content_featured` | Featured Content (homepage)  | 3         |
| `eceens_faq_current_category`      | FAQ's van huidige categorie  | alle      |
| `eceens_content_current_category`  | Content van huidige categorie| alle      |

Featured Query IDs tonen alleen items met het **Featured** veld aangevinkt, gesorteerd op prioriteit.

De "current category" Query IDs lezen automatisch de categorie uit de URL. Gebruik deze op taxonomy archive templates.

---

## Elementor Dynamic Tags (Media + Velden)

In Elementor kun je nu onder **Dynamic Tags > Eceens** direct deze tags kiezen:

- `Eceens Media Image` (voor Image widget)
- `Eceens Media Video URL` (voor Video widget of URL-veld)
- `Eceens Teaser`
- `Eceens Manual Title`
- `Eceens Priority`
- `Eceens Featured` (`Ja` / `Nee`)
- `Eceens Homepage Featured` (`Ja` / `Nee`)

Elke tag heeft een **Source** optie:

- `Auto (current post type)` (aanbevolen in Loop Items)
- `FAQ`
- `Content`

Deze lezen de metabox-velden:

- `faq_media_image_id` / `faq_media_video_url`
- `content_media_image_id` / `content_media_video_url`
- `faq_teaser` / `content_teaser`
- `faq_manual_title` / `content_manual_title`
- `faq_priority` / `content_priority`
- `faq_featured` / `content_featured`
- `faq_homepage_featured` / `content_homepage_featured`

---

## Shortcodes

### Titel met handmatige override

```
[eceens_display_title]
```

Toont de **Handmatige titel** als die is ingevuld. Als die leeg is, toont de shortcode de normale WordPress titel.

Optioneel:

```
[eceens_display_title type="faq"]
[eceens_display_title type="content"]
[eceens_display_title tag="h2" class="my-title"]
```

### Alle categorieën tonen

```
[eceens_category_pills type="faq"]
[eceens_category_pills type="content"]
```

Toont alle categorieën als gekleurde pills. Gebruik in een Elementor Container met Flex Row voor volledige layout-controle.

**Opties:**

| Optie     | Waarden                            | Standaard          |
|-----------|------------------------------------|--------------------|
| `type`    | `faq` / `content`                  | `faq`              |
| `level`   | `parent` / `child` / `all`         | `parent`           |
| `parent`  | slug of ID van een hoofdcategorie  | (leeg) = alle      |
| `link`    | (leeg) / `anchor` / `none`         | (leeg) = archief   |
| `columns` | getal (bijv. `3`)                  | (leeg) = geen grid |
| `gap`             | CSS waarde (bijv. `12px`)          | (leeg) = standaard |
| `gap_tablet`      | Gap op tablet (<=1024px)           | (leeg) = zelfde    |
| `gap_mobile`      | Gap op mobiel (<=767px)            | (leeg) = zelfde    |
| `padding`         | CSS waarde (bijv. `8px 16px`)      | (leeg) = standaard |
| `radius`          | CSS waarde (bijv. `10px`)          | (leeg) = standaard |
| `size`            | CSS waarde (bijv. `13px`)          | (leeg) = standaard |
| `size_tablet`     | Font-size op tablet (<=1024px)     | (leeg) = zelfde    |
| `size_mobile`     | Font-size op mobiel (<=767px)      | (leeg) = zelfde    |
| `padding_tablet`  | Padding op tablet                  | (leeg) = zelfde    |
| `padding_mobile`  | Padding op mobiel                  | (leeg) = zelfde    |
| `columns_tablet`  | Kolommen op tablet                 | (leeg) = zelfde    |
| `columns_mobile`  | Kolommen op mobiel                 | (leeg) = zelfde    |

Voorbeelden:
```
[eceens_category_pills type="faq" columns="3" gap="16px" padding="10px 20px"]
[eceens_category_pills type="faq" size="16px" size_tablet="14px" size_mobile="12px"]
[eceens_category_pills type="faq" columns="3" columns_tablet="2" columns_mobile="1" gap="12px"]
[eceens_category_pills type="faq" padding="10px 20px" padding_mobile="6px 12px" radius="8px"]
```

- `gap` -- ruimte tussen de pills
- `padding` -- ruimte binnen elke pill
- `radius` -- hoekafronding van elke pill
- `size` -- tekstgrootte van elke pill
- `parent` -- toon alleen subcategorieën van die hoofdcategorie (slug of ID)
- `columns` -- aantal kolommen in het grid

---

### Categorie-pills van het huidige bericht (in Loop Items)

```
[eceens_post_category_pills type="faq"]
[eceens_post_category_pills type="content"]
```

Gebruik dit in een **Shortcode widget** binnen een Elementor Loop Item template. Toont de categorieën van het huidige bericht in de loop.

**Opties:**

| Optie   | Waarden                          | Standaard          |
|---------|----------------------------------|--------------------|
| `type`  | `faq` / `content`                | auto-detect        |
| `level` | `parent` / `child` / `all`       | `parent`           |
| `link`  | (leeg) / `anchor` / `none`       | (leeg) = archief   |

**Layout:** Pills staan in een flex-row met wrap. Als ze **onder elkaar** blijven, is de **container te smal** (bv. speech-bubblekolom ~190px) of een thema/Elementor-regel zette links op `width:100%` — plugin `1.6.2+` dwingt pills tot `width:auto` zodat ze naast elkaar gaan zodra er breedte is. Zet de shortcode in een **bredere kolom** of op **100% breedte** als je lange categorienamen naast elkaar wilt.

---

### Alle FAQ-categorieën (legacy)

```
[eceens_faq_category_pills]
```

Toont alle top-level FAQ-categorieën. Met `link="anchor"` linken de pills naar `#faq-cat-{id}` voor ankernavigatie op dezelfde pagina.

---

### Huidige categorie info (voor archive pagina's)

Op taxonomy archive pagina's (`/faq-categorie/gezondheid-omgeving/`) kun je de categorie-informatie tonen:

**Categorie naam:**
```
[eceens_current_category_name]
[eceens_current_category_name tag="h2"]
[eceens_current_category_name color="yes"]
[eceens_current_category_name tag="h1" color="yes"]
```

Met `color="yes"` krijgt de naam een gekleurde achtergrond (categorie-kleur). Met `tag="h2"` wordt het in een heading gewrapt.

**Categorie beschrijving:**
```
[eceens_current_category_description]
```

Toont de beschrijving die je bij de categorie hebt ingevuld in WP Admin. Output zit in een `<div class="eceens-current-category-description">` die je kunt stylen.

**Categorie kleur op containers:**
```
[eceens_current_category_color]
```

Plaats dit **eenmaal** op de pagina (bijv. in een HTML widget bovenaan). Het injecteert CSS variabelen en klasses die je op elke container kunt gebruiken:

| CSS klasse         | Wat het doet                              |
|--------------------|-------------------------------------------|
| `eceens-cat-bg`    | Achtergrondkleur = categorie kleur        |
| `eceens-cat-text`  | Tekstkleur = categorie kleur              |
| `eceens-cat-border`| Randkleur = categorie kleur               |

Voeg de klasse toe bij een Elementor container onder **Geavanceerd > CSS-klassen**.

Je kunt ook de CSS variabelen gebruiken in Custom CSS:
```css
selector {
    background: var(--eceens-cat-color);
    color: var(--eceens-cat-text);
}
```

---

## Speech bubble (pijltje onder container)

Voeg de CSS klasse `eceens-bubble` toe aan een Elementor container (Geavanceerd > CSS-klassen). Het pijltje krijgt automatisch dezelfde kleur als de achtergrond van de container.

| Klasse                 | Pijltje positie     |
|------------------------|---------------------|
| `eceens-bubble`        | Links (standaard)   |
| `eceens-bubble eceens-bubble-center` | Midden   |
| `eceens-bubble eceens-bubble-right`  | Rechts   |

Combineer met `eceens-cat-bg` voor de categorie-kleur:
```
CSS klassen: eceens-cat-bg eceens-bubble eceens-bubble-center
```

---

## Pill styling aanpassen

De pills krijgen automatisch de categorie-kleur als achtergrond. Overige styling (grootte, padding, afronding) kun je aanpassen in Elementor via **Geavanceerd > Aangepaste CSS**:

```css
selector .eceens-faq-pill {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 8px;
}
```

Of pas de standaard aan in `assets/pills.css` in de plugin-map.

---

## Zwevende knop

Op alle FAQ-pagina's verschijnt rechtsonder een **"Stel je vraag"** knop die linkt naar `/contact/`. Deze knop is niet zichtbaar op andere pagina's.
