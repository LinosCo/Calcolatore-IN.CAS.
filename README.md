# README - Calcolatore S.P.R.I.Z. per WordPress

## Panoramica

Il file **WORDPRESS.html** contiene il calcolatore S.P.R.I.Z. - un'applicazione web interattiva per calcolare il risparmio economico e ambientale derivante dall'utilizzo del sistema di aspirazione liquidi biologici S.P.R.I.Z. rispetto ai metodi tradizionali negli ospedali.

## Caratteristiche principali

### 🎯 Funzionalità Complete
- **Selezione Reparto**: 3 tipologie (Sala Operatoria Urologia, Terapia Intensiva, Urologia Degenza)
- **Parametri Personalizzabili**: Slider e radio button per configurare scenari specifici
- **Calcoli in Tempo Reale**: Confronto economico e ambientale automatico
- **Lead-Wall Integrato**: Form Contact Form 7 per acquisizione contatti
- **Visualizzazione Dati**: Grafici circolari, confronto costi, sezione benefici

### 🎨 Design Professionale
- **Colori Brand IN.CAS.**: Giallo (#FDC32D), Grigio (#4B5055)
- **Typography**: Font Raleway e Lato
- **Responsive**: Layout adattivo mobile-first
- **Animazioni**: Transizioni smooth e micro-interazioni

### 📋 Integrazione Contact Form 7
- Shortcode: `[contact-form-7 id="2afd5de" title="Calcolatore SPRIZ ita"]`
- Campi nascosti popolati automaticamente con i risultati dei calcoli
- Form validation personalizzata JavaScript

## Struttura del File

### HTML
```html
<!DOCTYPE html>
<html lang="it">
<head>
    <!-- Meta tags, fonts, CSS embedded -->
</head>
<body>
    <!-- Barra di progresso (3 step) -->
    <!-- Step 1: Selezione reparto -->
    <!-- Step 2: Configurazione parametri -->
    <!-- Step 3: Risultati con lead-wall -->
    <!-- JavaScript embedded -->
</body>
</html>
```

### CSS Embedded
Tutti gli stili sono integrati nel tag `<style>` del file:
- Layout e grid system
- Componenti UI (card, slider, form)
- Palette colori brand
- Media queries responsive
- Animazioni e transizioni

### JavaScript Embedded
Tutta la logica applicativa è integrata nel tag `<script>` del file:
- Gestione step e navigazione
- Calcoli economici e CO₂
- Form validation
- Integrazione Contact Form 7
- Progress circles animati

## Reparti Supportati

### 1. Sala Operatoria Urologia
**Applicazioni**: TURP, TURB, PCNL

**Parametri configurabili**:
- Litri per seduta: 30-90L (slider)
- Giorni operativi/anno: 120/150/180/210 (radio button)

**Calcoli**:
- Canister 3L vs S.P.R.I.Z.
- Costi smaltimento e bio-box
- Emissioni CO₂

### 2. Terapia Intensiva
**Applicazioni**: Procedure CRRT con dialisi continua

**Parametri configurabili**:
- Numero pazienti CRRT: 1-5 (slider)
- Litri per paziente/giorno: 40-80L (slider)
- Capacità sacche: 5L/8L/10L (radio button)
- Giornate CRRT/anno: 120/150/180/210 (radio button)

**Calcoli**:
- Sacche raccolta vs S.P.R.I.Z.
- Costi per paziente
- Impatto ambientale totale

### 3. Urologia Degenza
**Applicazioni**: Procedure cistoclisi

**Parametri configurabili**:
- Numero pazienti/giorno: 2-5 (slider)
- Litri per paziente/giorno: 20-40L (slider)
- Giornate cistoclisi/anno: 120/150/180/210 (radio button)

**Calcoli**:
- Sacche degenza 5L vs S.P.R.I.Z.
- Costi giornalieri aggregati
- Riduzione CO₂

## Parametri di Calcolo

### Costanti Economiche
```javascript
const COSTANTI = {
  CANISTER_PRICE: 1.56,        // € per canister 3L
  SACCHE_TI_PRICE: 4.00,       // € per sacca terapia intensiva
  SACCHE_DEG_PRICE: 5.50,      // € per sacca degenza
  SMALTIMENTO_PRICE: 1.43,     // € per litro smaltimento
  BIO_BOX_PRICE: 6.00,         // € per bio-box
  SPRIZ_DAILY_COST: 40.00,     // € costo giornaliero S.P.R.I.Z.
};
```

### Costanti Ambientali (CO₂)
```javascript
const COSTANTI = {
  CO2_STANDARD_PER_LITER: 0.082,  // kg CO₂ per litro metodo standard
  CO2_SPRIZ_BASE: 1.03,            // kg CO₂ base S.P.R.I.Z.
  CO2_SPRIZ_PER_LITER: 0.0048,    // kg CO₂ per litro S.P.R.I.Z.
};
```

### Frequenze
```javascript
const COSTANTI = {
  CANISTER_CAPACITY: 3,              // Litri
  BIO_BOX_FREQUENCY: 3,              // Canister per bio-box
  BIO_BOX_FREQUENCY_OTHER: 20,       // Litri per bio-box (altri reparti)
  SACCHE_DEG_CAPACITY: 5,            // Litri
};
```

## Formule di Calcolo

### Urologia Operatoria
```javascript
// Costi tradizionali
canisterPerGiorno = litriGiorno / 3
costoCanisterGiorno = canisterPerGiorno × 1.56
costoSmaltimentoGiorno = litriGiorno × 1.43
bioBoxGiorno = canisterPerGiorno / 3
costoBioBoxGiorno = bioBoxGiorno × 6.00

// Totale
costoTradizioneGiorno = costoCanisterGiorno + costoSmaltimentoGiorno + costoBioBoxGiorno
costoTradizioneAnno = costoTradizioneGiorno × giorniOperativita

// S.P.R.I.Z.
costoSprizGiorno = 40.00
costoSprizAnno = 40.00 × giorniOperativita

// Risparmio
risparmioAnno = costoTradizioneAnno - costoSprizAnno
```

### Terapia Intensiva
```javascript
// Per paziente
litriTotaliPerPaziente = litriPaziente
sacchePerPaziente = litriTotaliPerPaziente / capacitaSacche
costoSacchePerPaziente = sacchePerPaziente × 4.00
costoSmaltimentoPerPaziente = litriTotaliPerPaziente × 1.43
bioBoxPerPaziente = litriTotaliPerPaziente / 20
costoBioBoxPerPaziente = bioBoxPerPaziente × 6.00

// Totale giornaliero
costoTradizioneGiorno = (costoSacchePerPaziente + costoSmaltimentoPerPaziente + costoBioBoxPerPaziente) × pazientiCRRT
```

### Urologia Degenza
```javascript
// Per paziente
litriTotali = litriPaziente
sacche = litriTotali / 5
costoSacche = sacche × 5.50
costoSmaltimento = litriTotali × 1.43
bioBox = litriTotali / 20
costoBioBox = bioBox × 6.00

// Totale giornaliero
costoTradizioneGiorno = (costoSacche + costoSmaltimento + costoBioBox) × pazientiGiorno
```

### Calcolo CO₂
```javascript
// Metodo tradizionale
co2TradizioneGiorno = litriTotali × 0.082

// S.P.R.I.Z.
co2SprizGiorno = 1.03 + (litriTotali × 0.0048)

// Risparmio
co2RisparmioAnno = (co2TradizioneGiorno - co2SprizGiorno) × giorniOperativita
co2RisparmioPercentuale = ((co2TradizioneGiorno - co2SprizGiorno) / co2TradizioneGiorno) × 100
```

## Risultati Visualizzati

### 1. Confronto Costi
Due card affiancate:

**Metodo Tradizionale**:
- Costo giornaliero (grande, prominente)
- Costo annuale (secondario)
- Impatto CO₂ annuale

**S.P.R.I.Z.** (evidenziato):
- Badge "SOLUZIONE INNOVATIVA"
- Costo giornaliero: € 40
- Costo annuale
- Impatto CO₂ annuale (verde)

### 2. Fasce Risparmio

**Fascia Gialla - Risparmio Economico**:
- Titolo: "RISPARMIO ECONOMICO CON L'ASPIRATORE S.P.R.I.Z."
- Valore annuale grande
- Progress circle con percentuale

**Fascia Verde - Riduzione CO₂**:
- Titolo: "RIDUZIONE CO₂ CON L'ASPIRATORE S.P.R.I.Z."
- Kg CO₂ risparmiati annualmente
- Progress circle con percentuale

### 3. Dettaglio Calcoli
Tabella comparativa:
- Costo giornaliero
- Costo annuale
- CO₂ giornaliera
- CO₂ annuale

### 4. Sezione Benefici
8 icone con vantaggi:
- Protezione antivirale
- Riduzione cross-contamination
- Maggiore sicurezza operatori
- Utilizzo multidisciplinare
- Procedure più veloci
- Nessun sollevamento carichi
- Ottimizzazione personale
- Riduzione costi

### 5. Sezione Sicurezza
Testo + immagine dispositivo:
- Rischio contaminazione biologica
- Sollevamento e spostamento
- Disinfezione liquido aspirato
- Auto-disinfezione apparecchiatura

## Integrazione Contact Form 7

### Shortcode da Sostituire
```html
[contact-form-7 id="2afd5de" title="Calcolatore SPRIZ ita"]
```

Sostituire con l'ID del vostro form Contact Form 7.

### Campi Nascosti Required
Il form deve avere questi campi nascosti per ricevere i dati dei calcoli:

```html
<!-- Campi visibili utente -->
[text* nome placeholder "Nome*"]
[text* cognome placeholder "Cognome*"]
[email* email placeholder "Email*"]
[tel telefono placeholder "Telefono"]
[text* azienda placeholder "Azienda/Ospedale*"]
[textarea messaggio placeholder "Messaggio"]

<!-- Campi nascosti popolati da JavaScript -->
[hidden calc-reparto]
[hidden calc-parametri]
[hidden calc-risparmio]
[hidden calc-co2]

<!-- Checkbox privacy -->
[acceptance privacy] Accetto la <a href="/privacy-policy/">Privacy Policy</a> [/acceptance]
[checkbox marketing "Desidero ricevere comunicazioni commerciali"]
```

### JavaScript Integration
Il JavaScript popola automaticamente i campi nascosti:

```javascript
function populateHiddenFields(form) {
  const repartoField = form.querySelector('input[name="calc-reparto"]');
  if (repartoField) {
    repartoField.value = selectedDepartment;
  }

  const parametriField = form.querySelector('input[name="calc-parametri"]');
  if (parametriField) {
    parametriField.value = JSON.stringify(parameters);
  }

  const risparmioField = form.querySelector('input[name="calc-risparmio"]');
  if (risparmioField) {
    risparmioField.value = formatCurrency(savings.economicAnnual);
  }

  const co2Field = form.querySelector('input[name="calc-co2"]');
  if (co2Field) {
    co2Field.value = formatNumber(savings.co2Annual) + ' kg';
  }
}
```

### Event Listener
```javascript
document.addEventListener('wpcf7mailsent', function(event) {
  appState.formSubmitted = true;
  unlockResults(); // Sblocca visualizzazione risultati
}, false);
```

## Installazione su WordPress

### Metodo 1: Custom HTML Block (Consigliato)

1. **Crea nuova pagina WordPress**
   - Dashboard > Pagine > Aggiungi nuova
   - Titolo: "Calcolatore S.P.R.I.Z."

2. **Aggiungi blocco Custom HTML**
   - Click su "+" per aggiungere blocco
   - Cerca "Custom HTML"
   - Inserisci blocco

3. **Copia-incolla il codice**
   - Apri WORDPRESS.html
   - Seleziona tutto (Ctrl+A / Cmd+A)
   - Copia (Ctrl+C / Cmd+C)
   - Incolla nel blocco Custom HTML

4. **Aggiorna ID Contact Form 7**
   - Cerca: `[contact-form-7 id="2afd5de"`
   - Sostituisci con il tuo ID form reale

5. **Pubblica la pagina**

### Metodo 2: Template Page

1. **Crea template personalizzato**
   - Nel tema WordPress: `page-calculator.php`

2. **Aggiungi header WordPress**
```php
<?php
/*
Template Name: Calcolatore S.P.R.I.Z.
*/
get_header();
?>

<!-- Incolla qui il contenuto di WORDPRESS.html (solo <body>) -->

<?php get_footer(); ?>
```

3. **Assegna template alla pagina**
   - Modifica pagina > Template > Calcolatore S.P.R.I.Z.

### Metodo 3: Shortcode Plugin

1. **Crea plugin personalizzato**
```php
<?php
/*
Plugin Name: Calcolatore SPRIZ
*/

function spriz_calculator_shortcode() {
    ob_start();
    include(plugin_dir_path(__FILE__) . 'calculator.html');
    return ob_get_clean();
}
add_shortcode('spriz_calculator', 'spriz_calculator_shortcode');
?>
```

2. **Usa shortcode**
```
[spriz_calculator]
```

## Configurazione Contact Form 7

### 1. Crea nuovo form
Dashboard > Contact > Add New

### 2. Form Template
```
<div class="form-row">
  <p>
    <label>Nome*</label>
    [text* nome placeholder "Nome"]
  </p>
  <p>
    <label>Cognome*</label>
    [text* cognome placeholder "Cognome"]
  </p>
</div>

<div class="form-row">
  <p>
    <label>Email*</label>
    [email* email placeholder "Email"]
  </p>
  <p>
    <label>Telefono</label>
    [tel telefono placeholder "Telefono"]
  </p>
</div>

<p>
  <label>Azienda/Ospedale*</label>
  [text* azienda placeholder "Azienda/Ospedale"]
</p>

<p>
  <label>Messaggio</label>
  [textarea messaggio placeholder "Messaggio"]
</p>

[hidden calc-reparto]
[hidden calc-parametri]
[hidden calc-risparmio]
[hidden calc-co2]

<p class="checkbox-group">
  <label class="checkbox-label">
    [acceptance privacy]
    <span class="checkmark"></span>
    <span>Accetto la <a href="/privacy-policy/" target="_blank">Privacy Policy</a>*</span>
    [/acceptance]
  </label>
</p>

<p class="checkbox-group">
  <label class="checkbox-label">
    [checkbox marketing]
    <span class="checkmark"></span>
    <span>Desidero ricevere comunicazioni commerciali</span>
  </label>
</p>

<p>
  [submit "SCARICA RISULTATI"]
</p>
```

### 3. Email Template
**To**: `info@incas.it`
**Subject**: `Nuovo calcolo S.P.R.I.Z. - [nome] [cognome]`

**Body**:
```
Nuovo calcolo effettuato dal calcolatore S.P.R.I.Z.:

DATI CONTATTO:
Nome: [nome]
Cognome: [cognome]
Email: [email]
Telefono: [telefono]
Azienda/Ospedale: [azienda]

CALCOLO EFFETTUATO:
Reparto: [calc-reparto]
Parametri: [calc-parametri]
Risparmio annuale: [calc-risparmio]
Riduzione CO₂: [calc-co2]

MESSAGGIO:
[messaggio]

PRIVACY:
Consenso privacy: [privacy]
Consenso marketing: [marketing]
```

### 4. Auto-reply Template (Opzionale)
**To**: `[email]`
**Subject**: `I tuoi risultati del calcolatore S.P.R.I.Z.`

**Body**:
```
Gentile [nome] [cognome],

Grazie per aver utilizzato il nostro calcolatore S.P.R.I.Z.!

Ecco un riepilogo dei tuoi risultati:

REPARTO: [calc-reparto]
RISPARMIO ANNUALE: [calc-risparmio]
RIDUZIONE CO₂: [calc-co2]

Il nostro team ti contatterà presto per fornirti maggiori informazioni sul sistema S.P.R.I.Z. e sui vantaggi per la tua struttura.

Cordiali saluti,
Team IN.CAS. Srl
```

## Personalizzazione

### Colori Brand
Modifica le CSS variables nel tag `<style>`:

```css
:root {
  --incas-yellow: #FDC32D;    /* Giallo primario */
  --incas-gray: #4B5055;      /* Grigio testo */
  --incas-white: #FFFFFF;     /* Bianco */
  --incas-black: #000000;     /* Nero */
}
```

### Costanti Calcolo
Modifica le costanti JavaScript nel tag `<script>`:

```javascript
const COSTANTI = {
  SPRIZ_DAILY_COST: 40.00,  // Modifica costo giornaliero S.P.R.I.Z.
  CANISTER_PRICE: 1.56,     // Modifica prezzi materiali
  // ... altre costanti
};
```

### Testi
Tutti i testi sono modificabili direttamente nel codice HTML:
- Titoli sezioni
- Etichette parametri
- Descrizioni reparti
- Testi benefici

## Responsive Design

### Breakpoint
```css
/* Desktop */
Default > 768px

/* Tablet */
@media (max-width: 768px) {
  /* Layout adattato */
}

/* Mobile */
@media (max-width: 480px) {
  /* Layout mobile-first */
}
```

### Comportamenti
- **Desktop**: Grid 2 colonne, card affiancate
- **Tablet**: Grid responsive, alcuni elementi impilati
- **Mobile**: Layout verticale, slider ottimizzati touch

## Browser Supportati

### Desktop
- Chrome/Edge: ultime 2 versioni ✅
- Firefox: ultime 2 versioni ✅
- Safari: ultime 2 versioni ✅

### Mobile
- iOS Safari: 12+ ✅
- Chrome Mobile: ultime 2 versioni ✅
- Samsung Internet: ultime 2 versioni ✅

### Limitazioni
- IE11: Non supportato (richiede polyfill)
- Vecchi browser Android: Funzionalità limitate

## Ottimizzazione Performance

### File Size
- **HTML totale**: ~85 KB
- **CSS embedded**: ~20 KB
- **JavaScript embedded**: ~15 KB
- **Font esterni**: Google Fonts (cached)
- **Icone**: Font Awesome 4.7 CDN (cached)

### Loading Time
- **First Paint**: < 1s
- **Interactive**: < 2s
- **Complete**: < 3s

### Best Practices
- CSS e JS embedded: zero richieste HTTP aggiuntive
- Font preconnect per caricamento veloce
- Immagini ottimizzate con lazy loading
- Animazioni CSS hardware-accelerated

## Troubleshooting

### Form non si sblocca dopo submit

**Problema**: Risultati rimangono bloccati dopo compilazione form

**Soluzione**:
1. Verifica evento Contact Form 7:
```javascript
document.addEventListener('wpcf7mailsent', function(event) {
  console.log('Form inviato con successo');
  unlockResults();
}, false);
```

2. Controlla ID form nel shortcode
3. Verifica JavaScript console per errori

### Calcoli non corretti

**Problema**: Risultati non corrispondono alle attese

**Soluzione**:
1. Verifica costanti in `COSTANTI` object
2. Controlla formule nelle funzioni `calculate*`
3. Confronta con file Excel di riferimento
4. Abilita console.log nei calcoli:
```javascript
console.log('Parametri:', params);
console.log('Risultati:', results);
```

### Slider non funzionano

**Problema**: Slider non aggiornano valori

**Soluzione**:
1. Verifica event listener:
```javascript
slider.addEventListener('input', function() {
  const valueDisplay = document.getElementById(this.id + '-value');
  if (valueDisplay) {
    valueDisplay.textContent = this.value;
  }
});
```

2. Controlla ID elementi DOM
3. Verifica CSS `--slider-progress` variable

### Progress circles non animano

**Problema**: Grafici circolari non mostrano percentuali

**Soluzione**:
1. Verifica funzione `updateProgressCircle()`
2. Controlla SVG `stroke-dasharray` e `stroke-dashoffset`
3. Verifica timeout animazioni:
```javascript
setTimeout(() => {
  circle.style.strokeDashoffset = offset;
}, 500);
```

### Conflitti CSS WordPress

**Problema**: Stili rotti o sovrascrittura tema

**Soluzione**:
1. Aumenta specificità selettori:
```css
.step-container.active { /* invece di .active */ }
```

2. Aggiungi `!important` se necessario (ultimo resort)
3. Wrappa tutto in container con ID unico:
```html
<div id="spriz-calculator">
  <!-- tutto il calcolatore -->
</div>
```

```css
#spriz-calculator .step-container { /* ... */ }
```

### Lead-wall non si apre

**Problema**: Modal non visualizzata

**Soluzione**:
1. Verifica classe `.active`:
```javascript
document.getElementById('leadWall').classList.add('active');
```

2. Controlla z-index e position:
```css
.lead-wall {
  position: fixed;
  z-index: 1000;
  display: flex; /* quando .active */
}
```

## Testing

### Checklist Pre-Deploy
- [ ] Sostituito ID Contact Form 7 reale
- [ ] Verificato URL immagine dispositivo funzionante
- [ ] Testato su desktop (Chrome, Firefox, Safari)
- [ ] Testato su mobile (iOS, Android)
- [ ] Testato form validation
- [ ] Verificato calcoli per tutti e 3 i reparti
- [ ] Controllato console per errori JavaScript
- [ ] Testato responsive layout
- [ ] Verificato animazioni progress circles
- [ ] Controllato testi e traduzioni
- [ ] Testato submit form e unlock risultati

### User Testing
1. **Flow completo**:
   - Selezione reparto
   - Modifica parametri
   - Visualizzazione risultati
   - Compilazione form
   - Ricezione email

2. **Edge cases**:
   - Valori minimi/massimi slider
   - Form incompleto
   - Email non valida
   - Privacy non accettata

3. **Dispositivi**:
   - Desktop 1920x1080
   - Laptop 1366x768
   - Tablet 768x1024
   - Mobile 375x667

## Supporto e Contatti

### Documentazione
- File: `WORDPRESS.html`
- Excel formule: `rev.finale_configuratore_2.xlsx`
- Brand manual: `INCAS - Brand Manual.pdf`
- Brochure: `Brochure SPRIZ_IN.CAS_ITA.pdf`

### Risorse Online
- Sito web: https://www.aspiratoriliquidibiologici.it
- Demo: https://linoscco.github.io/Calcolatore-IN.CAS/

### Assistenza Tecnica
Per supporto tecnico contattare:
- Email: info@incas.it
- Sviluppatore: Alessandro Borsato

## Changelog

### v5.0 - WordPress Integration (23/09/2025)
- ✅ File standalone WORDPRESS.html completo
- ✅ CSS e JavaScript embedded
- ✅ Immagine PNG ottimizzata
- ✅ Progress bar visibile
- ✅ Slider progress bars
- ✅ Form privacy policy funzionante
- ✅ Layout responsive WordPress-ready

### v4.1 - UI Compatta (19/09/2025)
- ✅ Summary cards ridimensionate (320px)
- ✅ Sezione dettagli migliorata
- ✅ CO₂ annuale nelle card confronto
- ✅ JavaScript ottimizzato

### v4.0 - Allineamento Perfetto (19/09/2025)
- ✅ Card confronto allineate verticalmente
- ✅ Grafici circolari allineati
- ✅ Validazione form migliorata

### v3.4 - Titolo Vantaggi (18/09/2025)
- ✅ Titolo sezione vantaggi aggiunto
- ✅ Traduzione inglese

### v3.3 - Costi Ridisegnati (18/09/2025)
- ✅ Card costi professionali
- ✅ Box risparmi ottimizzato
- ✅ Sezione CO₂ semplificata

### v3.2 - Benefici e Sicurezza (18/09/2025)
- ✅ Sezione 8 benefici con icone
- ✅ Sezione sicurezza con immagine
- ✅ Footer rimosso

### v3.1 - Versione Inglese (17/09/2025)
- ✅ Calcolatore inglese funzionale
- ✅ Formato USD e US

### v3.0 - Layout Ottimizzato (17/09/2025)
- ✅ Layout desktop 2 colonne
- ✅ Card CO₂ verde
- ✅ Sezione VS rimossa

### v2.9 - Internazionalizzazione (16/09/2025)
- ✅ Sistema i18n integrato
- ✅ Toggle lingua IT/EN
- ✅ Modal centrata

## Licenza

© 2025 IN.CAS. Medical Safety Innovation Srl
Tutti i diritti riservati.

Questo codice è proprietà di IN.CAS. Srl ed è destinato esclusivamente all'utilizzo sul sito aziendale https://www.aspiratoriliquidibiologici.it.

È vietata la riproduzione, distribuzione o utilizzo non autorizzato.
