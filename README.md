# S.P.R.I.Z. Calculator - IN.CAS. Medical Safety Innovation

Calcolatore interattivo per il risparmio economico e ambientale con il sistema di aspirazione liquidi biologici S.P.R.I.Z.

## 📁 Struttura del Progetto

```
incas/
├── WORDPRESS.html          # Versione italiana del calcolatore
├── WORDPRESS-EN.html       # Versione inglese del calcolatore
├── contact-form-ita.md     # Configurazione Contact Form 7 italiano
├── contact-form-en.md      # Configurazione Contact Form 7 inglese
└── README.md              # Questo file
```

## 🌐 Versioni Disponibili

### Versione Italiana
- **File**: `WORDPRESS.html`
- **Lingua**: Italiano (it)
- **Contact Form 7 ID**: `2afd5de`
- **Shortcode**: `[contact-form-7 id="2afd5de" title="Calcolatore SPRIZ ita"]`

### Versione Inglese
- **File**: `WORDPRESS-EN.html`
- **Lingua**: Inglese (en)
- **Contact Form 7 ID**: `75511fa`
- **Shortcode**: `[contact-form-7 id="75511fa" title="Spriz calculator"]`

## 🎯 Funzionalità

Il calcolatore permette di:
1. **Selezionare il reparto ospedaliero**:
   - Sala Operatoria Urologia (Urology Operating Room)
   - Terapia Intensiva (Intensive Care)
   - Urologia Degenza (Urology Ward)

2. **Configurare i parametri specifici** per ogni reparto

3. **Visualizzare i risultati** con:
   - Confronto economico tra metodo tradizionale e S.P.R.I.Z.
   - Risparmio economico annuale
   - Riduzione CO₂ annuale
   - Dettaglio calcoli
   - Vantaggi aggiuntivi del sistema

## 📋 Parametri per Reparto

### Sala Operatoria Urologia / Urology Operating Room
- **Litri per seduta** (30-90L)
- **Giorni operativi della sala in un anno** (120/150/180/210 giorni)

### Terapia Intensiva / Intensive Care
- **Numero pazienti CRRT** (1-5)
- **Litri per paziente prodotti al giorno** (40-80L)
- **Capacità sacche** (5L/8L/10L)
- **Giornate CRRT in un anno** (120/150/180/210 giorni)

### Urologia Degenza / Urology Ward
- **Numero pazienti/giorno** (2-5)
- **Litri per paziente prodotti al giorno** (20-40L)
- **Giornate di cistoclisi in un anno** (120/150/180/210 giorni)

## 📊 Costanti di Calcolo

```javascript
CANISTER_PRICE: 1.56€
SACCHE_TI_PRICE: 4.00€
SACCHE_DEG_PRICE: 5.50€
SMALTIMENTO_PRICE: 1.43€
BIO_BOX_PRICE: 6.00€
SPRIZ_DAILY_COST: 40.00€
CO2_STANDARD_PER_LITER: 0.082 kg
CO2_SPRIZ_BASE: 1.03 kg
CO2_SPRIZ_PER_LITER: 0.0048 kg
CANISTER_CAPACITY: 3L
BIO_BOX_FREQUENCY: 3
BIO_BOX_FREQUENCY_OTHER: 20
SACCHE_DEG_CAPACITY: 5L
```

## 📧 Contact Form 7 - Configurazione

### Form Italiano

#### Campi del Form
```html
<div class="form-row">
    <label> Nome *
    [text* nome] </label>
    <label> Email *
    [email* email] </label>
</div>

<div class="form-row">
    <label> Telefono
    [tel telefono] </label>
    <label> Azienda/Ospedale *
    [text* azienda] </label>
</div>

<label> Messaggio (opzionale)
[textarea messaggio] </label>

[acceptance privacy] Accetto il trattamento dei dati personali secondo la <a href="https://www.incasmed.com/informative/privacy-policy" target="_blank">Privacy Policy</a>* [/acceptance]

[checkbox marketing "Accetto di ricevere comunicazioni commerciali da IN.CAS."]

<div style="display: none;">
    [hidden calc-reparto]
    [hidden calc-parametri]
    [hidden calc-risparmio]
    [hidden calc-co2]
    [hidden testo-footer]
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.wpcf7-form');
    const marketingCheckbox = form.querySelector('input[name="marketing[]"]');
    const testoFooter = form.querySelector('input[name="testo-footer"]');

    function updateFooter() {
        if (marketingCheckbox && marketingCheckbox.checked) {
            testoFooter.value = "Un nostro responsabile ti contatterà presto per approfondire la soluzione S.P.R.I.Z.";
        } else {
            testoFooter.value = "Se ti interessa approfondire e scoprire di più sui risultati ottenuti, non esitare a contattarci all'indirizzo export@incasmed.com";
        }
    }

    if (marketingCheckbox) {
        marketingCheckbox.addEventListener('change', updateFooter);
        updateFooter();
    }
});
</script>

<style>
.wpcf7-list-item label {
    font-size: inherit !important;
    font-weight: normal !important;
}
</style>

[submit "VISUALIZZA RISULTATI"]
```

#### Email per Utente (Italiano)
```
Oggetto: Risultati Calcolatore S.P.R.I.Z.

Gentile [nome],

Grazie per aver utilizzato il nostro calcolatore S.P.R.I.Z.

REPARTO SELEZIONATO: [calc-reparto]
PARAMETRI: [calc-parametri]

RISULTATI:
- Risparmio economico annuale: [calc-risparmio]
- Riduzione CO₂ annuale: [calc-co2]

[testo-footer]

Cordiali saluti,
Il team IN.CAS.
```

#### Email per Azienda (Italiano)
```
Oggetto: Nuovo calcolo dal Calcolatore S.P.R.I.Z.

Nuovo calcolo dal Calcolatore S.P.R.I.Z.

DATI PERSONALI:
Nome: [nome]
Email: [email]
Telefono: [telefono]
Azienda/Ospedale: [azienda]
Messaggio: [messaggio]

RISULTATI DEL CALCOLO:
Reparto: [calc-reparto]
Parametri: [calc-parametri]
Risparmio Economico: [calc-risparmio]
Riduzione CO₂: [calc-co2]

Privacy Policy: Accettata
Marketing: [marketing]
```

### Form Inglese

#### Campi del Form
```html
<div class="form-row">
    <label> Name *
    [text* nome] </label>
    <label> Email *
    [email* email] </label>
</div>

<div class="form-row">
    <label> Phone
    [tel telefono] </label>
    <label> Company/Hospital *
    [text* azienda] </label>
</div>

<label> Message (optional)
[textarea messaggio] </label>

[acceptance privacy] I accept the processing of personal data according to the <a href="https://www.incasmed.com/informative/privacy-policy" target="_blank">Privacy Policy</a>* [/acceptance]

[checkbox marketing "I agree to receive commercial communications from IN.CAS."]

<div style="display: none;">
    [hidden calc-reparto]
    [hidden calc-parametri]
    [hidden calc-risparmio]
    [hidden calc-co2]
    [hidden testo-footer]
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.wpcf7-form');
    const marketingCheckbox = form.querySelector('input[name="marketing[]"]');
    const testoFooter = form.querySelector('input[name="testo-footer"]');

    function updateFooter() {
        if (marketingCheckbox && marketingCheckbox.checked) {
            testoFooter.value = "One of our representatives will contact you soon to discuss the S.P.R.I.Z. solution in detail.";
        } else {
            testoFooter.value = "If you are interested in learning more about the results obtained, please do not hesitate to contact us at export@incasmed.com";
        }
    }

    if (marketingCheckbox) {
        marketingCheckbox.addEventListener('change', updateFooter);
        updateFooter();
    }
});
</script>

<style>
.wpcf7-list-item label {
    font-size: inherit !important;
    font-weight: normal !important;
}
</style>

[submit "VIEW RESULTS"]
```

#### Email per Utente (Inglese)
```
Subject: S.P.R.I.Z. Calculator Results

Dear [nome],

Thank you for using our S.P.R.I.Z. calculator.

SELECTED DEPARTMENT: [calc-reparto]
PARAMETERS: [calc-parametri]

RESULTS:
- Annual economic savings: [calc-risparmio]
- Annual CO₂ reduction: [calc-co2]

[testo-footer]

Best regards,
The IN.CAS. team
```

#### Email per Azienda (Inglese)
```
Subject: New calculation from S.P.R.I.Z. Calculator

New calculation from S.P.R.I.Z. Calculator

PERSONAL DATA:
Name: [nome]
Email: [email]
Phone: [telefono]
Company/Hospital: [azienda]
Message: [messaggio]

CALCULATION RESULTS:
Department: [calc-reparto]
Parameters: [calc-parametri]
Economic Savings: [calc-risparmio]
CO₂ Reduction: [calc-co2]

Privacy Policy: Accepted
Marketing: [marketing]
```

## 🎨 Branding

### Colori IN.CAS.
- **Yellow**: `#FDC32D`
- **Gray**: `#4B5055`
- **White**: `#FFFFFF`
- **Black**: `#000000`

### Font
- **Primario**: Raleway (300, 400, 600, 800)
- **Secondario**: Lato (300, 400, 700)

## 🚀 Installazione su WordPress

1. Vai su **Pagine** > **Aggiungi Nuova**
2. Crea una nuova pagina
3. Passa alla modalità **HTML/Codice**
4. Incolla il contenuto di `WORDPRESS.html` (per italiano) o `WORDPRESS-EN.html` (per inglese)
5. Assicurati di aver creato i form Contact Form 7 corrispondenti
6. Pubblica la pagina

## 📝 Campi Nascosti del Form

I seguenti campi vengono popolati automaticamente dal JavaScript:

- `calc-reparto`: Nome del reparto selezionato
- `calc-parametri`: Parametri configurati (formattati leggibilmente)
- `calc-risparmio`: Risparmio economico annuale calcolato
- `calc-co2`: Riduzione CO₂ annuale calcolata
- `testo-footer`: Testo dinamico basato sull'accettazione marketing

### Mappatura Etichette Parametri

**Italiano:**
```javascript
'litriGiorno': 'Litri per seduta'
'giorniOperativita': 'Giorni operativi/anno'
'pazientiCRRT': 'Pazienti CRRT'
'litriPaziente': 'Litri per paziente/giorno'
'capacitaSacche': 'Capacità sacche (L)'
'pazientiGiorno': 'Pazienti/giorno'
```

**Inglese:**
```javascript
'litriGiorno': 'Liters per session'
'giorniOperativita': 'Operating days/year'
'pazientiCRRT': 'CRRT patients'
'litriPaziente': 'Liters per patient/day'
'capacitaSacche': 'Bag capacity (L)'
'pazientiGiorno': 'Patients/day'
```

## 🔧 Funzionalità Tecniche

### Lead Wall
Il calcolatore mostra una schermata di blocco (lead wall) che richiede la compilazione del form prima di visualizzare i risultati completi.

### Formattazione Numeri
- **Valuta**: formato europeo con separatore migliaia (es: € 45.087)
- **CO₂**: formato europeo senza decimali (es: 1.243 kg)

### Animazioni
- Progress circles con animazione percentuale
- Transizioni smooth tra gli step
- Hover effects su card e bottoni

## 📱 Responsive Design

Il calcolatore è completamente responsive con breakpoints:
- Desktop: > 768px
- Tablet: 480px - 768px
- Mobile: < 480px

## 🛡️ Sicurezza

- Validazione lato client dei campi obbligatori
- Accettazione obbligatoria della Privacy Policy
- Integrazione Contact Form 7 per la gestione sicura dei dati

## 📞 Contatti

**IN.CAS. Medical Safety Innovation**
- Email: export@incasmed.com
- Website: https://www.incasmed.com

---

**Note**: Ricorda di aggiornare gli ID dei form Contact Form 7 se necessario e di verificare che i link alla Privacy Policy siano corretti.
