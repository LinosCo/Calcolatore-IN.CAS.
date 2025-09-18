// Calcolatore S.P.R.I.Z. - IN.CAS.
// Utilità per formattazione numeri in formato europeo
function formatEuropeanNumber(number) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(Math.round(number));
}

function formatCurrency(number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(Math.round(number));
}

// Costanti validate dal file Excel
const COSTANTI = {
  // Prezzi base
  CANISTER_PRICE: 1.56,
  SACCHE_TI_PRICE: 4.00,
  SACCHE_DEG_PRICE: 5.50,
  SMALTIMENTO_PRICE: 1.43,
  BIO_BOX_PRICE: 6.00,
  SPRIZ_DAILY_COST: 40.00,

  // CO₂ parametri
  CO2_STANDARD_PER_LITER: 0.082,
  CO2_SPRIZ_BASE: 1.03,
  CO2_SPRIZ_PER_LITER: 0.0048,

  // Logiche contenitori
  CANISTER_CAPACITY: 3,      // 1 canister ogni 3 litri
  BIO_BOX_FREQUENCY: 3,      // 1 bio box ogni 3 canister (URO)
  BIO_BOX_FREQUENCY_OTHER: 20, // 1 bio box ogni 20 kg (TI/DEG)
  SACCHE_DEG_CAPACITY: 5,    // Sacche degenza da 5L
};

// Stato dell'applicazione
let appState = {
  currentStep: 1,
  selectedDepartment: null,
  parameters: {},
  results: {},
  formSubmitted: false,
  currentLanguage: 'en'
};

// Sistema di internazionalizzazione
const translations = {
  it: {
    document: {
      title: "Calcolatore S.P.R.I.Z. - IN.CAS. Medical Safety Innovation"
    },
    progress: {
      department: "Reparto",
      parameters: "Parametri",
      results: "Risultati"
    },
    step1: {
      title: "Seleziona il Reparto Ospedaliero",
      subtitle: "Scegli il reparto per cui vuoi calcolare il risparmio con S.P.R.I.Z.",
      departments: {
        'urologia-operatoria': {
          title: "Sala Operatoria Urologia",
          description: "Interventi urologici con aspirazione liquidi biologici"
        },
        'terapia-intensiva': {
          title: "Terapia Intensiva",
          description: "Pazienti CRRT con dialisi continua"
        },
        'urologia-degenza': {
          title: "Urologia Degenza",
          description: "Pazienti degenti in reparto urologico"
        }
      },
      continue: "Continua →"
    },
    step2: {
      title: "Inserisci i Parametri",
      subtitle: "Configura i parametri specifici per il tuo reparto",
      back: "← Indietro",
      calculate: "Calcola Risparmio →"
    },
    step3: {
      title: "I Tuoi Risultati",
      subtitle: "Ecco quanto puoi risparmiare con S.P.R.I.Z.",
      leadWall: {
        title: "Risultati Disponibili!",
        description: "Per visualizzare i calcoli dettagliati del risparmio economico e ambientale, compila il form sottostante.",
        emailInfo: "I risultati verranno inviati anche via email",
        emailSuffix: "per una consultazione futura.",
        submit: "Visualizza Risultati"
      },
      form: {
        name: "Nome *",
        email: "Email *",
        phone: "Telefono",
        company: "Azienda/Ospedale *",
        message: "Messaggio (opzionale)",
        privacy: "Accetto il trattamento dei dati personali secondo la",
        privacyPolicy: "Privacy Policy",
        marketing: "Accetto di ricevere comunicazioni commerciali da IN.CAS."
      },
      comparison: {
        title: "Confronto dei Metodi",
        traditional: {
          title: "Metodo Tradizionale",
          subtitle: "Costo con l'attuale sistema ospedaliero"
        },
        spriz: {
          title: "S.P.R.I.Z.",
          subtitle: "Costo con il sistema S.P.R.I.Z.",
          badge: "SOLUZIONE INNOVATIVA"
        },
        perDay: "al giorno",
        co2PerDay: "CO₂/giorno",
        savingsText: "Risparmi {amount} al giorno!",
        environmental: "Impatto Ambientale",
        ecoFriendly: "Eco-friendly • Riduzione CO₂"
      },
      results: {
        economicSavings: "Risparmio Economico",
        co2Reduction: "Riduzione CO₂",
        daily: "al giorno",
        annual: "all'anno",
        savingsPercent: "% di risparmio",
        reductionPercent: "% in meno"
      },
      actions: {
        back: "← Modifica Parametri",
        export: "Esporta Dati CSV"
      },
      breakdown: {
        title: "Dettaglio Calcoli"
      }
    },
    footer: {
      copyright: "© 2024 IN.CAS. S.r.l. - Medical Safety Innovation",
      product: "S.P.R.I.Z. - Aspiratore a circuito chiuso per liquidi biologici"
    }
  },
  en: {
    document: {
      title: "S.P.R.I.Z. Calculator - IN.CAS. Medical Safety Innovation"
    },
    progress: {
      department: "Department",
      parameters: "Parameters",
      results: "Results"
    },
    step1: {
      title: "Select Hospital Department",
      subtitle: "Choose the department for which you want to calculate savings with S.P.R.I.Z.",
      departments: {
        'urologia-operatoria': {
          title: "Urology Operating Room",
          description: "Urological procedures with biological fluid aspiration"
        },
        'terapia-intensiva': {
          title: "Intensive Care",
          description: "CRRT patients with continuous dialysis"
        },
        'urologia-degenza': {
          title: "Urology Ward",
          description: "Inpatients in urology department"
        }
      },
      continue: "Continue →"
    },
    step2: {
      title: "Enter Parameters",
      subtitle: "Configure specific parameters for your department",
      back: "← Back",
      calculate: "Calculate Savings →"
    },
    step3: {
      title: "Your Results",
      subtitle: "Here's how much you can save with S.P.R.I.Z.",
      leadWall: {
        title: "Results Available!",
        description: "To view detailed calculations of economic and environmental savings, please fill out the form below.",
        emailInfo: "Results will also be sent via email",
        emailSuffix: "for future consultation.",
        submit: "View Results"
      },
      form: {
        name: "Name *",
        email: "Email *",
        phone: "Phone",
        company: "Company/Hospital *",
        message: "Message (optional)",
        privacy: "I accept the processing of personal data according to the",
        privacyPolicy: "Privacy Policy",
        marketing: "I accept to receive commercial communications from IN.CAS."
      },
      comparison: {
        title: "Method Comparison",
        traditional: {
          title: "Traditional Method",
          subtitle: "Cost with current hospital system"
        },
        spriz: {
          title: "S.P.R.I.Z.",
          subtitle: "Cost with S.P.R.I.Z. system",
          badge: "INNOVATIVE SOLUTION"
        },
        perDay: "per day",
        co2PerDay: "CO₂/day",
        savingsText: "You save {amount} per day!",
        environmental: "Environmental Impact",
        ecoFriendly: "Eco-friendly • CO₂ Reduction"
      },
      results: {
        economicSavings: "Economic Savings",
        co2Reduction: "CO₂ Reduction",
        daily: "per day",
        annual: "per year",
        savingsPercent: "% savings",
        reductionPercent: "% less"
      },
      actions: {
        back: "← Modify Parameters",
        export: "Export CSV Data"
      },
      breakdown: {
        title: "Calculation Details"
      }
    },
    footer: {
      copyright: "© 2024 IN.CAS. S.r.l. - Medical Safety Innovation",
      product: "S.P.R.I.Z. - Closed-circuit aspirator for biological fluids"
    }
  }
};

// Funzioni di internazionalizzazione
function getTranslation(key) {
  const keys = key.split('.');
  let value = translations[appState.currentLanguage];

  for (const k of keys) {
    value = value[k];
    if (!value) return key; // Fallback alla chiave se traduzione non trovata
  }

  return value;
}

function updateLanguage(lang) {
  appState.currentLanguage = lang;

  // Aggiorna toggle lingua
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.lang === lang);
  });

  // Aggiorna tutti i testi con data-i18n
  document.querySelectorAll('[data-i18n]').forEach(element => {
    const key = element.getAttribute('data-i18n');
    element.textContent = getTranslation(key);
  });

  // Aggiorna placeholder e attributi
  document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
    const key = element.getAttribute('data-i18n-placeholder');
    element.placeholder = getTranslation(key);
  });

  // Aggiorna title del documento
  document.title = getTranslation('document.title');

  // Re-renderizza i dipartimenti se siamo nello step 1
  if (appState.currentStep === 1) {
    renderStep1();
  }

  // Re-renderizza i parametri se siamo nello step 2
  if (appState.currentStep === 2) {
    renderStep2();
  }
}

function setupLanguageToggle() {
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const lang = btn.dataset.lang;
      updateLanguage(lang);
    });
  });
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
  initializeApp();
  setupLanguageToggle();
  // Initialize with English
  updateLanguage('en');
});

function initializeApp() {
  setupEventListeners();
  updateProgressBar();
}

function setupEventListeners() {
  // Step 1: Selezione reparto
  const departmentRadios = document.querySelectorAll('input[name="department"]');
  departmentRadios.forEach(radio => {
    radio.addEventListener('change', handleDepartmentSelection);
  });

  // Navigazione
  document.getElementById('nextToStep2')?.addEventListener('click', () => goToStep(2));
  document.getElementById('backToStep1')?.addEventListener('click', () => goToStep(1));
  document.getElementById('nextToStep3')?.addEventListener('click', () => goToStep(3));
  document.getElementById('backToStep2')?.addEventListener('click', () => goToStep(2));

  // Form lead-wall
  document.getElementById('leadForm')?.addEventListener('submit', handleFormSubmission);

  // Export CSV
  document.getElementById('exportData')?.addEventListener('click', handleExportData);
}

function handleDepartmentSelection(event) {
  appState.selectedDepartment = event.target.value;
  document.getElementById('nextToStep2').disabled = false;
}

function goToStep(stepNumber) {
  // Nascondi tutti gli step
  document.querySelectorAll('.step-container').forEach(step => {
    step.classList.remove('active');
  });

  // Mostra step corrente
  document.getElementById(`step${stepNumber}`).classList.add('active');

  // Aggiorna stato
  appState.currentStep = stepNumber;
  updateProgressBar();

  // Azioni specifiche per step
  if (stepNumber === 2) {
    generateParametersForm();
  } else if (stepNumber === 3) {
    calculateResults();
    showResults();
  }
}

function updateProgressBar() {
  document.querySelectorAll('.progress-step').forEach((step, index) => {
    const stepNumber = index + 1;
    if (stepNumber <= appState.currentStep) {
      step.classList.add('active');
    } else {
      step.classList.remove('active');
    }
  });
}

function generateParametersForm() {
  const form = document.getElementById('parametersForm');
  if (!form || !appState.selectedDepartment) return;

  let formHTML = '';

  switch (appState.selectedDepartment) {
    case 'urologia-operatoria':
      formHTML = generateUrologiaOperatoriaForm();
      break;
    case 'terapia-intensiva':
      formHTML = generateTerapiaIntensivaForm();
      break;
    case 'urologia-degenza':
      formHTML = generateUrologiaDegenzaForm();
      break;
  }

  form.innerHTML = formHTML;
  setupParametersEventListeners();
}

function generateUrologiaOperatoriaForm() {
  return `
    <div class="param-group">
      <h3>Urology Operating Room</h3>

      <div class="slider-container">
        <label class="slider-label">Liters produced per day</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="litri-giorno"
                 min="30" max="90" value="60" step="1">
          <div class="slider-value" id="litri-giorno-value">60</div>
        </div>
      </div>

      <div class="radio-group">
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="120">
          <span class="radio-label">120 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="150">
          <span class="radio-label">150 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="180" checked>
          <span class="radio-label">180 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="210">
          <span class="radio-label">210 days/year</span>
        </label>
      </div>
    </div>
  `;
}

function generateTerapiaIntensivaForm() {
  return `
    <div class="param-group">
      <h3>Intensive Care</h3>

      <div class="slider-container">
        <label class="slider-label">Number of CRRT patients</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="pazienti-crrt"
                 min="1" max="5" value="3" step="1">
          <div class="slider-value" id="pazienti-crrt-value">3</div>
        </div>
      </div>

      <div class="slider-container">
        <label class="slider-label">Liters per patient</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="litri-paziente"
                 min="40" max="80" value="60" step="5">
          <div class="slider-value" id="litri-paziente-value">60</div>
        </div>
      </div>

      <div class="radio-group">
        <label class="radio-option">
          <input type="radio" name="capacita-sacche" value="5" checked>
          <span class="radio-label">5L</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="capacita-sacche" value="8">
          <span class="radio-label">8L</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="capacita-sacche" value="10">
          <span class="radio-label">10L</span>
        </label>
      </div>

      <div class="radio-group">
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-ti" value="120">
          <span class="radio-label">120 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-ti" value="150">
          <span class="radio-label">150 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-ti" value="180" checked>
          <span class="radio-label">180 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-ti" value="210">
          <span class="radio-label">210 days/year</span>
        </label>
      </div>
    </div>
  `;
}

function generateUrologiaDegenzaForm() {
  return `
    <div class="param-group">
      <h3>Urology Ward</h3>

      <div class="slider-container">
        <label class="slider-label">Number of patients/day</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="pazienti-giorno"
                 min="2" max="5" value="3" step="1">
          <div class="slider-value" id="pazienti-giorno-value">3</div>
        </div>
      </div>

      <div class="slider-container">
        <label class="slider-label">Liters per patient</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="litri-paziente-deg"
                 min="20" max="40" value="30" step="5">
          <div class="slider-value" id="litri-paziente-deg-value">30</div>
        </div>
      </div>

      <div class="radio-group">
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="120">
          <span class="radio-label">120 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="150">
          <span class="radio-label">150 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="180" checked>
          <span class="radio-label">180 days/year</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="210">
          <span class="radio-label">210 days/year</span>
        </label>
      </div>
    </div>
  `;
}

function setupParametersEventListeners() {
  // Slider updates
  document.querySelectorAll('.slider').forEach(slider => {
    slider.addEventListener('input', function() {
      const valueDisplay = document.getElementById(this.id + '-value');
      if (valueDisplay) {
        valueDisplay.textContent = this.value;
      }
    });
  });
}

function collectParameters() {
  const params = {};

  switch (appState.selectedDepartment) {
    case 'urologia-operatoria':
      params.litriGiorno = parseInt(document.getElementById('litri-giorno')?.value || 60);
      params.giorniOperativita = parseInt(document.querySelector('input[name="giorni-operativita"]:checked')?.value || 180);
      break;

    case 'terapia-intensiva':
      params.pazientiCRRT = parseInt(document.getElementById('pazienti-crrt')?.value || 3);
      params.litriPaziente = parseInt(document.getElementById('litri-paziente')?.value || 60);
      params.capacitaSacche = parseInt(document.querySelector('input[name="capacita-sacche"]:checked')?.value || 5);
      params.giorniOperativita = parseInt(document.querySelector('input[name="giorni-operativita-ti"]:checked')?.value || 180);
      break;

    case 'urologia-degenza':
      params.pazientiGiorno = parseInt(document.getElementById('pazienti-giorno')?.value || 3);
      params.litriPaziente = parseInt(document.getElementById('litri-paziente-deg')?.value || 30);
      params.giorniOperativita = parseInt(document.querySelector('input[name="giorni-operativita-deg"]:checked')?.value || 180);
      break;
  }

  appState.parameters = params;
}

function calculateResults() {
  collectParameters();

  let results = {};

  switch (appState.selectedDepartment) {
    case 'urologia-operatoria':
      results = calculateUrologiaOperatoria(appState.parameters);
      break;
    case 'terapia-intensiva':
      results = calculateTerapiaIntensiva(appState.parameters);
      break;
    case 'urologia-degenza':
      results = calculateUrologiaDegenza(appState.parameters);
      break;
  }

  appState.results = results;
}

function calculateUrologiaOperatoria(params) {
  const { litriGiorno, giorniOperativita } = params;

  // Metodo tradizionale (Canister)
  const canisterPerGiorno = litriGiorno / COSTANTI.CANISTER_CAPACITY;
  const costoCanisterGiorno = canisterPerGiorno * COSTANTI.CANISTER_PRICE;
  const costoSmaltimentoGiorno = litriGiorno * COSTANTI.SMALTIMENTO_PRICE;
  const bioBoxGiorno = (canisterPerGiorno / COSTANTI.BIO_BOX_FREQUENCY);
  const costoBioBoxGiorno = bioBoxGiorno * COSTANTI.BIO_BOX_PRICE;

  const costoTradizioneGiorno = costoCanisterGiorno + costoSmaltimentoGiorno + costoBioBoxGiorno;
  const costoTradizioneAnno = costoTradizioneGiorno * giorniOperativita;

  // S.P.R.I.Z.
  const costoSprizGiorno = COSTANTI.SPRIZ_DAILY_COST;
  const costoSprizAnno = costoSprizGiorno * giorniOperativita;

  // Risparmio economico
  const risparmioGiorno = costoTradizioneGiorno - costoSprizGiorno;
  const risparmioAnno = risparmioGiorno * giorniOperativita;
  const risparmioPercentuale = (risparmioGiorno / costoTradizioneGiorno) * 100;

  // CO₂
  const co2TradizioneGiorno = litriGiorno * COSTANTI.CO2_STANDARD_PER_LITER;
  const co2SprizGiorno = COSTANTI.CO2_SPRIZ_BASE + (litriGiorno * COSTANTI.CO2_SPRIZ_PER_LITER);
  const co2RisparmioGiorno = co2TradizioneGiorno - co2SprizGiorno;
  const co2RisparmioAnno = co2RisparmioGiorno * giorniOperativita;
  const co2RisparmioPercentuale = (co2RisparmioGiorno / co2TradizioneGiorno) * 100;

  return {
    traditional: {
      costoGiorno: costoTradizioneGiorno,
      costoAnno: costoTradizioneAnno,
      co2Giorno: co2TradizioneGiorno,
      co2Anno: co2TradizioneGiorno * giorniOperativita
    },
    spriz: {
      costoGiorno: costoSprizGiorno,
      costoAnno: costoSprizAnno,
      co2Giorno: co2SprizGiorno,
      co2Anno: co2SprizGiorno * giorniOperativita
    },
    savings: {
      economicDaily: risparmioGiorno,
      economicAnnual: risparmioAnno,
      economicPercentage: risparmioPercentuale,
      co2Daily: co2RisparmioGiorno,
      co2Annual: co2RisparmioAnno,
      co2Percentage: co2RisparmioPercentuale
    }
  };
}

function calculateTerapiaIntensiva(params) {
  const { pazientiCRRT, litriPaziente, capacitaSacche, giorniOperativita } = params;

  const litriTotaliPerPaziente = litriPaziente;

  // Metodo tradizionale - Formula aggiornata dal nuovo Excel
  const sacchePerPaziente = litriTotaliPerPaziente / capacitaSacche;
  const costoSacchePerPaziente = sacchePerPaziente * COSTANTI.SACCHE_TI_PRICE;
  const costoSmaltimentoPerPaziente = litriTotaliPerPaziente * COSTANTI.SMALTIMENTO_PRICE;
  const bioBoxPerPaziente = litriTotaliPerPaziente / COSTANTI.BIO_BOX_FREQUENCY_OTHER;
  const costoBioBoxPerPaziente = bioBoxPerPaziente * COSTANTI.BIO_BOX_PRICE;

  // Formula Excel: ((C9*C10)+(C4*C11)+((C4/20)*C12))*Tabella113 (per giorno)
  const costoTradizioneGiorno = (costoSacchePerPaziente + costoSmaltimentoPerPaziente + costoBioBoxPerPaziente) * pazientiCRRT;
  const costoTradizioneAnno = costoTradizioneGiorno * giorniOperativita;

  // S.P.R.I.Z.
  const costoSprizGiorno = COSTANTI.SPRIZ_DAILY_COST;
  const costoSprizAnno = costoSprizGiorno * giorniOperativita;

  // Risparmio economico
  const risparmioGiorno = costoTradizioneGiorno - costoSprizGiorno;
  const risparmioAnno = risparmioGiorno * giorniOperativita;
  const risparmioPercentuale = (risparmioGiorno / costoTradizioneGiorno) * 100;

  // CO₂ - Terapia Intensiva
  const litriTotaliCO2 = pazientiCRRT * litriPaziente;
  const co2TradizioneGiorno = litriTotaliCO2 * COSTANTI.CO2_STANDARD_PER_LITER;
  const co2SprizGiorno = COSTANTI.CO2_SPRIZ_BASE + (litriTotaliCO2 * COSTANTI.CO2_SPRIZ_PER_LITER);
  const co2RisparmioGiorno = co2TradizioneGiorno - co2SprizGiorno;
  const co2RisparmioAnno = co2RisparmioGiorno * giorniOperativita;
  const co2RisparmioPercentuale = (co2RisparmioGiorno / co2TradizioneGiorno) * 100;

  return {
    traditional: {
      costoGiorno: costoTradizioneGiorno,
      costoAnno: costoTradizioneAnno,
      co2Giorno: co2TradizioneGiorno,
      co2Anno: co2TradizioneGiorno * giorniOperativita
    },
    spriz: {
      costoGiorno: costoSprizGiorno,
      costoAnno: costoSprizAnno,
      co2Giorno: co2SprizGiorno,
      co2Anno: co2SprizGiorno * giorniOperativita
    },
    savings: {
      economicDaily: risparmioGiorno,
      economicAnnual: risparmioAnno,
      economicPercentage: risparmioPercentuale,
      co2Daily: co2RisparmioGiorno,
      co2Annual: co2RisparmioAnno,
      co2Percentage: co2RisparmioPercentuale
    }
  };
}

function calculateUrologiaDegenza(params) {
  const { pazientiGiorno, litriPaziente, giorniOperativita } = params;

  const litriTotali = litriPaziente; // Per paziente

  // Metodo tradizionale - Formula aggiornata dal nuovo Excel
  const sacche = litriTotali / COSTANTI.SACCHE_DEG_CAPACITY;
  const costoSacche = sacche * COSTANTI.SACCHE_DEG_PRICE;
  const costoSmaltimento = litriTotali * COSTANTI.SMALTIMENTO_PRICE;
  const bioBox = litriTotali / COSTANTI.BIO_BOX_FREQUENCY_OTHER;
  const costoBioBox = bioBox * COSTANTI.BIO_BOX_PRICE;

  // Formula Excel: ((C8*C9)+(C4*C10)+((C4/20)*C11))*Tabella126 (per paziente * numero pazienti)
  const costoTradizioneGiorno = (costoSacche + costoSmaltimento + costoBioBox) * pazientiGiorno;
  const costoTradizioneAnno = costoTradizioneGiorno * giorniOperativita;

  // S.P.R.I.Z.
  const costoSprizGiorno = COSTANTI.SPRIZ_DAILY_COST;
  const costoSprizAnno = costoSprizGiorno * giorniOperativita;

  // Risparmio economico
  const risparmioGiorno = costoTradizioneGiorno - costoSprizGiorno;
  const risparmioAnno = risparmioGiorno * giorniOperativita;
  const risparmioPercentuale = (risparmioGiorno / costoTradizioneGiorno) * 100;

  // CO₂ - Urologia Degenza
  const litriTotaliCO2 = pazientiGiorno * litriPaziente;
  const co2TradizioneGiorno = litriTotaliCO2 * COSTANTI.CO2_STANDARD_PER_LITER;
  const co2SprizGiorno = COSTANTI.CO2_SPRIZ_BASE + (litriTotaliCO2 * COSTANTI.CO2_SPRIZ_PER_LITER);
  const co2RisparmioGiorno = co2TradizioneGiorno - co2SprizGiorno;
  const co2RisparmioAnno = co2RisparmioGiorno * giorniOperativita;
  const co2RisparmioPercentuale = (co2RisparmioGiorno / co2TradizioneGiorno) * 100;

  return {
    traditional: {
      costoGiorno: costoTradizioneGiorno,
      costoAnno: costoTradizioneAnno,
      co2Giorno: co2TradizioneGiorno,
      co2Anno: co2TradizioneGiorno * giorniOperativita
    },
    spriz: {
      costoGiorno: costoSprizGiorno,
      costoAnno: costoSprizAnno,
      co2Giorno: co2SprizGiorno,
      co2Anno: co2SprizGiorno * giorniOperativita
    },
    savings: {
      economicDaily: risparmioGiorno,
      economicAnnual: risparmioAnno,
      economicPercentage: risparmioPercentuale,
      co2Daily: co2RisparmioGiorno,
      co2Annual: co2RisparmioAnno,
      co2Percentage: co2RisparmioPercentuale
    }
  };
}

function showResults() {
  const results = appState.results;

  // Hero Section rimossa - skip aggiornamento hero

  // Card risultati con formatazione europea
  document.getElementById('savingsDaily').textContent = `${formatCurrency(results.savings.economicDaily)}`;
  document.getElementById('savingsAnnual').textContent = `${formatCurrency(results.savings.economicAnnual)}`;
  document.getElementById('savingsPercent').textContent = `${results.savings.economicPercentage.toFixed(1)}${getTranslation('step3.results.savingsPercent')}`;

  document.getElementById('co2Daily').textContent = `${formatEuropeanNumber(results.savings.co2Daily)} kg`;
  document.getElementById('co2Annual').textContent = `${formatEuropeanNumber(results.savings.co2Annual)} kg`;
  document.getElementById('co2Percent').textContent = `${results.savings.co2Percentage.toFixed(1)}% less`;

  // Animazione grafici circolari
  updateProgressCircle('savingsProgress', 'savingsProgressText', results.savings.economicPercentage);
  updateProgressCircle('co2Progress', 'co2ProgressText', results.savings.co2Percentage);

  // Nuovo confronto metodi
  document.getElementById('traditionalCost').textContent = `${formatCurrency(results.traditional.costoGiorno)}`;
  document.getElementById('traditionalCO2').textContent = `${results.traditional.co2Giorno.toFixed(2)} kg`;
  document.getElementById('sprizCost').textContent = `${formatCurrency(results.spriz.costoGiorno)}`;
  document.getElementById('sprizCO2').textContent = `${results.spriz.co2Giorno.toFixed(2)} kg`;

  // Highlight del risparmio giornaliero
  document.getElementById('dailySavings').textContent = formatCurrency(results.savings.economicDaily);

  generateDetailedBreakdown();
}

function generateDetailedBreakdown() {
  const results = appState.results;
  const params = appState.parameters;

  let breakdownHTML = `
    <div style="display: grid; gap: 1rem;">
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; font-weight: 600; background: #f8f9fa; padding: 0.8rem; border-radius: 8px;">
        <div>Parameter</div>
        <div>Traditional Method</div>
        <div style="color: #4B5055; font-weight: 700;">S.P.R.I.Z.</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem;">
        <div>Daily cost</div>
        <div>${formatCurrency(results.traditional.costoGiorno)}</div>
        <div style="font-weight: 600;">${formatCurrency(results.spriz.costoGiorno)}</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem; background: #f8f9fa;">
        <div>Annual cost</div>
        <div>${formatCurrency(results.traditional.costoAnno)}</div>
        <div style="font-weight: 600;">${formatCurrency(results.spriz.costoAnno)}</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem;">
        <div>Daily CO₂</div>
        <div>${results.traditional.co2Giorno.toFixed(2)} kg</div>
        <div style="color: #4B5055; font-weight: 600;">${results.spriz.co2Giorno.toFixed(2)} kg</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem; background: #f8f9fa;">
        <div>Annual CO₂</div>
        <div>${formatEuropeanNumber(results.traditional.co2Anno)} kg</div>
        <div style="color: #4B5055; font-weight: 600;">${formatEuropeanNumber(results.spriz.co2Anno)} kg</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #FDC32D 0%, #f0b429 100%); color: #4B5055; border-radius: 8px; font-weight: 600;">
        <div style="text-align: center;">
          <div style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">ECONOMIC SAVINGS</div>
          <div style="font-size: 1.4rem; font-weight: 800;">${formatCurrency(results.savings.economicAnnual)}</div>
          <div style="font-size: 0.8rem; opacity: 0.8;">per year</div>
        </div>
        <div style="text-align: center;">
          <div style="font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">CO₂ REDUCTION</div>
          <div style="font-size: 1.4rem; font-weight: 800;">${formatEuropeanNumber(results.savings.co2Annual)} kg</div>
          <div style="font-size: 0.8rem; opacity: 0.8;">per year</div>
        </div>
      </div>
    </div>
  `;

  document.getElementById('breakdownTable').innerHTML = breakdownHTML;
}

function handleFormSubmission(event) {
  event.preventDefault();

  // Simula invio form (in realtà dovrebbe integrare Contact Form 7)
  const formData = new FormData(event.target);

  // Controlla campi obbligatori
  const requiredFields = ['nome', 'email', 'azienda', 'privacy'];
  let isValid = true;

  requiredFields.forEach(field => {
    const value = formData.get(field);
    if (!value || (field === 'privacy' && value !== 'on')) {
      isValid = false;
    }
  });

  if (!isValid) {
    alert('Please fill in all required fields and accept the privacy policy.');
    return;
  }

  // Simula successo invio
  setTimeout(() => {
    appState.formSubmitted = true;
    unlockResults();
  }, 1000);
}

function unlockResults() {
  document.getElementById('leadWall').classList.remove('active');
  document.getElementById('resultsContent').classList.remove('blurred');
  document.getElementById('exportData').disabled = false;
  // Re-generate breakdown after results are unlocked
  generateDetailedBreakdown();
}

function handleExportData() {
  if (!appState.formSubmitted) {
    alert('You must first fill out the form to download the data.');
    return;
  }

  // Genera CSV
  const csvData = generateCSVData();
  downloadCSV(csvData, 'spriz-calculator-results.csv');
}

function generateCSVData() {
  const results = appState.results;
  const params = appState.parameters;
  const timestamp = new Date().toISOString();

  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Timestamp,Department,Parameters,Traditional Cost (€/day),S.P.R.I.Z. Cost (€/day),Savings (€/day),Savings (€/year),Savings %,Traditional CO₂ (kg/day),S.P.R.I.Z. CO₂ (kg/day),CO₂ Savings (kg/day),CO₂ Savings (kg/year),CO₂ Savings %\n";

  const parametriString = JSON.stringify(params).replace(/"/g, '""');

  csvContent += [
    timestamp,
    appState.selectedDepartment,
    `"${parametriString}"`,
    results.traditional.costoGiorno.toFixed(2),
    results.spriz.costoGiorno.toFixed(2),
    results.savings.economicDaily.toFixed(2),
    results.savings.economicAnnual.toFixed(2),
    results.savings.economicPercentage.toFixed(2),
    results.traditional.co2Giorno.toFixed(2),
    results.spriz.co2Giorno.toFixed(2),
    results.savings.co2Daily.toFixed(2),
    results.savings.co2Annual.toFixed(2),
    results.savings.co2Percentage.toFixed(2)
  ].join(",");

  return csvContent;
}

function downloadCSV(csvContent, filename) {
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Funzione per animare i grafici circolari
function updateProgressCircle(circleId, textId, percentage) {
  const circle = document.getElementById(circleId);
  const text = document.getElementById(textId);

  if (!circle || !text) return;

  const radius = circle.r.baseVal.value;
  const circumference = radius * 2 * Math.PI;
  const offset = circumference - (percentage / 100) * circumference;

  circle.style.strokeDasharray = `${circumference} ${circumference}`;

  // Animazione con delay
  setTimeout(() => {
    circle.style.strokeDashoffset = offset;

    // Animazione del testo
    let currentValue = 0;
    const increment = percentage / 50;
    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= percentage) {
        currentValue = percentage;
        clearInterval(timer);
      }
      text.textContent = `${Math.round(currentValue)}%`;
    }, 20);
  }, 500);
}