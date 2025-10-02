// Calcolatore S.P.R.I.Z. - IN.CAS.
// Utilità per formattazione numeri in formato europeo
function formatEuropeanNumber(number) {
  return new Intl.NumberFormat('it-IT', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(Math.round(number));
}

function formatCurrency(number) {
  const formattedNumber = formatEuropeanNumber(Math.round(number));
  return `€ ${formattedNumber}`;
}

function formatDailyCost(number) {
  const formattedNumber = formatEuropeanNumber(Math.round(number));
  return `€ ${formattedNumber}`;
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
  formSubmitted: false
};



// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
  initializeApp();
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
      <h3>Sala Operatoria Urologia</h3>

      <div class="slider-container">
        <label class="slider-label">Litri prodotti al giorno</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="litri-giorno"
                 min="30" max="90" value="60" step="1">
          <div class="slider-value" id="litri-giorno-value">60</div>
        </div>
      </div>

      <div class="radio-group">
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="120">
          <span class="radio-label">120 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="150">
          <span class="radio-label">150 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="180" checked>
          <span class="radio-label">180 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita" value="210">
          <span class="radio-label">210 giorni/anno</span>
        </label>
      </div>
    </div>
  `;
}

function generateTerapiaIntensivaForm() {
  return `
    <div class="param-group">
      <h3>Terapia Intensiva</h3>

      <div class="slider-container">
        <label class="slider-label">Numero pazienti CRRT</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="pazienti-crrt"
                 min="1" max="5" value="3" step="1">
          <div class="slider-value" id="pazienti-crrt-value">3</div>
        </div>
      </div>

      <div class="slider-container">
        <label class="slider-label">Litri per paziente</label>
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
          <span class="radio-label">120 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-ti" value="150">
          <span class="radio-label">150 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-ti" value="180" checked>
          <span class="radio-label">180 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-ti" value="210">
          <span class="radio-label">210 giorni/anno</span>
        </label>
      </div>
    </div>
  `;
}

function generateUrologiaDegenzaForm() {
  return `
    <div class="param-group">
      <h3>Urologia Degenza</h3>

      <div class="slider-container">
        <label class="slider-label">Numero pazienti/giorno</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="pazienti-giorno"
                 min="2" max="5" value="3" step="1">
          <div class="slider-value" id="pazienti-giorno-value">3</div>
        </div>
      </div>

      <div class="slider-container">
        <label class="slider-label">Litri per paziente</label>
        <div class="slider-wrapper">
          <input type="range" class="slider" id="litri-paziente-deg"
                 min="20" max="40" value="30" step="5">
          <div class="slider-value" id="litri-paziente-deg-value">30</div>
        </div>
      </div>

      <div class="radio-group">
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="120">
          <span class="radio-label">120 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="150">
          <span class="radio-label">150 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="180" checked>
          <span class="radio-label">180 giorni/anno</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="giorni-operativita-deg" value="210">
          <span class="radio-label">210 giorni/anno</span>
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
  
  // Summary values rimossi - ora mostrati solo nella sezione gialla dettaglio calcoli

  // Animazione grafici circolari
  updateProgressCircle('savingsProgress', 'savingsProgressText', results.savings.economicPercentage);
  updateProgressCircle('co2Progress', 'co2ProgressText', results.savings.co2Percentage);

  // Attiva interattività grafici
  setupProgressCircleInteraction();
  
  // Nuovo confronto metodi
  document.getElementById('traditionalCost').textContent = formatDailyCost(results.traditional.costoGiorno);
  document.getElementById('traditionalCostAnnual').textContent = formatCurrency(results.traditional.costoAnno);
  document.getElementById('traditionalCO2').textContent = `${formatEuropeanNumber(results.traditional.co2Anno)} kg`;
  document.getElementById('sprizCost').textContent = formatDailyCost(results.spriz.costoGiorno);
  document.getElementById('sprizCostAnnual').textContent = formatCurrency(results.spriz.costoAnno);
  document.getElementById('sprizCO2').textContent = `${formatEuropeanNumber(results.spriz.co2Anno)} kg`;

  // Highlight del risparmio giornaliero e annuale
  document.getElementById('dailySavings').textContent = formatCurrency(results.savings.economicDaily);
  document.getElementById('annualSavings').textContent = formatCurrency(results.savings.economicAnnual);

  
  generateDetailedBreakdown();
}

function generateDetailedBreakdown() {
  const results = appState.results;
  const params = appState.parameters;
  
  let breakdownHTML = `
    <div style="display: grid; gap: 1rem;">
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; font-weight: 600; background: #f8f9fa; padding: 0.8rem; border-radius: 8px;">
        <div>Parametro</div>
        <div>Metodo Tradizionale</div>
        <div style="color: #4B5055; font-weight: 700;">S.P.R.I.Z.</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem;">
        <div>Costo giornaliero</div>
        <div>${formatCurrency(results.traditional.costoGiorno)}</div>
        <div style="font-weight: 600;">${formatCurrency(results.spriz.costoGiorno)}</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem; background: #f8f9fa;">
        <div>Costo annuale</div>
        <div>${formatCurrency(results.traditional.costoAnno)}</div>
        <div style="font-weight: 600;">${formatCurrency(results.spriz.costoAnno)}</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem;">
        <div>CO₂ giornaliera</div>
        <div>${results.traditional.co2Giorno.toFixed(2)} kg</div>
        <div style="color: #4B5055; font-weight: 600;">${results.spriz.co2Giorno.toFixed(2)} kg</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; padding: 0.8rem; background: #f8f9fa;">
        <div>CO₂ annuale</div>
        <div>${formatEuropeanNumber(results.traditional.co2Anno)} kg</div>
        <div style="color: #4B5055; font-weight: 600;">${formatEuropeanNumber(results.spriz.co2Anno)} kg</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #FDC32D 0%, #f0b429 100%); color: #4B5055; border-radius: 8px; font-weight: 600; grid-template-rows: auto auto auto;">
        <div style="text-align: center; grid-column: 1; grid-row: 1; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">RISPARMIO ECONOMICO</div>
        <div style="text-align: center; grid-column: 2; grid-row: 1; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">RIDUZIONE CO₂</div>
        <div style="text-align: center; grid-column: 1; grid-row: 2; font-size: 1.4rem; font-weight: 800; line-height: 1.2;">${formatCurrency(results.savings.economicAnnual)}</div>
        <div style="text-align: center; grid-column: 2; grid-row: 2; font-size: 1.4rem; font-weight: 800; line-height: 1.2;">${formatEuropeanNumber(results.savings.co2Annual)} kg</div>
        <div style="text-align: center; grid-column: 1; grid-row: 3; font-size: 1.2rem; font-weight: 700; opacity: 1; text-transform: uppercase; letter-spacing: 1px; color: #2d3748; margin-top: 0.3rem;">ALL'ANNO</div>
        <div style="text-align: center; grid-column: 2; grid-row: 3; font-size: 1.2rem; font-weight: 700; opacity: 1; text-transform: uppercase; letter-spacing: 1px; color: #2d3748; margin-top: 0.3rem;">ALL'ANNO</div>
      </div>
    </div>
  `;
  
  document.getElementById('breakdownTable').innerHTML = breakdownHTML;
}

function handleFormSubmission(event) {
  event.preventDefault();

  // Rimuovi errori precedenti
  clearFormErrors();

  // Simula invio form (in realtà dovrebbe integrare Contact Form 7)
  const formData = new FormData(event.target);

  // Controlla campi obbligatori
  let isValid = true;
  let errors = [];

  // Verifica nome
  if (!formData.get('nome')) {
    showFieldError('nome', 'Il nome è obbligatorio');
    errors.push('nome');
    isValid = false;
  }

  // Verifica email
  const email = formData.get('email');
  if (!email) {
    showFieldError('email', 'L\'email è obbligatoria');
    errors.push('email');
    isValid = false;
  } else if (!isValidEmail(email)) {
    showFieldError('email', 'Inserisci un\'email valida');
    errors.push('email');
    isValid = false;
  }

  // Verifica azienda
  if (!formData.get('azienda')) {
    showFieldError('azienda', 'Il campo Azienda/Ospedale è obbligatorio');
    errors.push('azienda');
    isValid = false;
  }

  // Verifica privacy policy
  if (!formData.get('privacy')) {
    showFieldError('privacy', 'Devi accettare la Privacy Policy per continuare');
    errors.push('privacy');
    isValid = false;
  }

  if (!isValid) {
    // Scorri al primo errore
    const firstErrorField = document.getElementById(errors[0]);
    if (firstErrorField) {
      firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
      firstErrorField.focus();
    }
    return;
  }

  // Simula successo invio
  setTimeout(() => {
    appState.formSubmitted = true;
    unlockResults();
  }, 1000);
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function showFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const formGroup = field.closest('.form-group, .checkbox-group');

  // Aggiungi classe errore
  formGroup.classList.add('error');
  field.classList.add('error');

  // Crea messaggio errore
  const errorDiv = document.createElement('div');
  errorDiv.className = 'error-message';
  errorDiv.textContent = message;

  // Inserisci messaggio errore
  if (fieldId === 'privacy') {
    formGroup.appendChild(errorDiv);
  } else {
    formGroup.appendChild(errorDiv);
  }
}

function clearFormErrors() {
  // Rimuovi tutte le classi e messaggi di errore
  document.querySelectorAll('.error-message').forEach(msg => msg.remove());
  document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
}

function unlockResults() {
  document.getElementById('leadWall').classList.remove('active');
  document.getElementById('resultsContent').classList.remove('blurred');
  // Re-generate breakdown after results are unlocked
  generateDetailedBreakdown();
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
      // Aggiungi segni: meno per CO₂ e risparmio (riduzione costi)
      const isAnyProgressCo2 = text.closest('.progress-circle').classList.contains('co2');
      const isAnyProgressSavings = text.closest('.progress-circle').classList.contains('savings');
      const sign = (isAnyProgressCo2 || isAnyProgressSavings) ? '-' : '';
      text.textContent = `${sign}${Math.round(currentValue)}%`;
    }, 20);
  }, 500);
}

// Card interattive
function initInteractiveCards() {
  const methodCards = document.querySelectorAll('.method-card');

  methodCards.forEach(card => {
    card.addEventListener('click', function() {
      // Rimuovi classe active da tutte le card
      methodCards.forEach(c => c.classList.remove('card-active'));

      // Aggiungi classe active alla card cliccata
      this.classList.add('card-active');

      // Effetto di "pulse" quando cliccata
      this.style.transform = 'translateY(-12px) scale(1.02)';
      setTimeout(() => {
        this.style.transform = 'translateY(-8px) scale(1)';
      }, 200);
    });
  });
}

// Aggiungi interattività ai grafici circolari
function setupProgressCircleInteraction() {
  document.querySelectorAll('.progress-circle').forEach(circle => {
    circle.addEventListener('click', function() {
      // Effetto di "pulse" quando cliccato
      this.style.transform = 'scale(0.95)';
      setTimeout(() => {
        this.style.transform = 'scale(1.05)';
        setTimeout(() => {
          this.style.transform = 'scale(1)';
        }, 150);
      }, 100);
    });

    circle.addEventListener('mouseenter', function() {
      this.style.transform = 'scale(1.1)';
    });

    circle.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  });
}