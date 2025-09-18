// S.P.R.I.Z. Calculator - IN.CAS.
// Utility functions for European number formatting
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
  return `€ ${formattedNumber}/G`;
}

// Constants validated from Excel file
const CONSTANTS = {
  // Base prices in Euro
  CANISTER_PRICE: 1.56,
  SACCHE_TI_PRICE: 4.00,
  SACCHE_DEG_PRICE: 5.50,
  SMALTIMENTO_PRICE: 1.43,
  BIO_BOX_PRICE: 6.00,
  SPRIZ_DAILY_COST: 40.00,

  // CO₂ parameters
  CO2_STANDARD_PER_LITER: 0.082,
  CO2_SPRIZ_BASE: 1.03,
  CO2_SPRIZ_PER_LITER: 0.0048,

  // Container logic
  CANISTER_CAPACITY: 3,        // 1 canister every 3 liters
  BIO_BOX_FREQUENCY: 3,        // 1 bio box every 3 canisters (URO)
  BIO_BOX_FREQUENCY_OTHER: 20, // 1 bio box every 20 kg (ICU/WARD)
  SACCHE_DEG_CAPACITY: 5,      // 5L ward bags
};

// Application state
let appState = {
  currentStep: 1,
  selectedDepartment: null,
  parameters: {},
  results: {},
  formSubmitted: false
};

// Initialization
document.addEventListener('DOMContentLoaded', function() {
  initializeApp();
});

function initializeApp() {
  setupEventListeners();
  updateProgressBar();
}

function setupEventListeners() {
  // Step 1: Department selection
  const departmentRadios = document.querySelectorAll('input[name="department"]');
  departmentRadios.forEach(radio => {
    radio.addEventListener('change', handleDepartmentSelection);
  });

  // Navigation
  document.getElementById('nextToStep2')?.addEventListener('click', () => goToStep(2));
  document.getElementById('backToStep1')?.addEventListener('click', () => goToStep(1));
  document.getElementById('nextToStep3')?.addEventListener('click', () => goToStep(3));
  document.getElementById('backToStep2')?.addEventListener('click', () => goToStep(2));

  // Lead-wall form
  document.getElementById('leadForm')?.addEventListener('submit', handleFormSubmission);
}

function handleDepartmentSelection(event) {
  appState.selectedDepartment = event.target.value;
  document.getElementById('nextToStep2').disabled = false;
}

function goToStep(stepNumber) {
  // Hide all steps
  document.querySelectorAll('.step-container').forEach(step => {
    step.classList.remove('active');
  });

  // Show current step
  document.getElementById(`step${stepNumber}`).classList.add('active');

  // Update state
  appState.currentStep = stepNumber;
  updateProgressBar();

  // Step-specific actions
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
      formHTML = generateUrologyORForm();
      break;
    case 'terapia-intensiva':
      formHTML = generateICUForm();
      break;
    case 'urologia-degenza':
      formHTML = generateUrologyWardForm();
      break;
  }

  form.innerHTML = formHTML;
  setupParametersEventListeners();
}

function generateUrologyORForm() {
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

function generateICUForm() {
  return `
    <div class="param-group">
      <h3>Intensive Care Unit</h3>

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
          <span class="radio-label">5L bags</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="capacita-sacche" value="8">
          <span class="radio-label">8L bags</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="capacita-sacche" value="10">
          <span class="radio-label">10L bags</span>
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

function generateUrologyWardForm() {
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
      results = calculateUrologyOR(appState.parameters);
      break;
    case 'terapia-intensiva':
      results = calculateICU(appState.parameters);
      break;
    case 'urologia-degenza':
      results = calculateUrologyWard(appState.parameters);
      break;
  }

  appState.results = results;
}

function calculateUrologyOR(params) {
  const { litriGiorno, giorniOperativita } = params;

  // Traditional method (Canisters)
  const canisterPerGiorno = litriGiorno / CONSTANTS.CANISTER_CAPACITY;
  const costoCanisterGiorno = canisterPerGiorno * CONSTANTS.CANISTER_PRICE;
  const costoSmaltimentoGiorno = litriGiorno * CONSTANTS.SMALTIMENTO_PRICE;
  const bioBoxGiorno = (canisterPerGiorno / CONSTANTS.BIO_BOX_FREQUENCY);
  const costoBioBoxGiorno = bioBoxGiorno * CONSTANTS.BIO_BOX_PRICE;

  const costoTradizioneGiorno = costoCanisterGiorno + costoSmaltimentoGiorno + costoBioBoxGiorno;
  const costoTradizioneAnno = costoTradizioneGiorno * giorniOperativita;

  // S.P.R.I.Z.
  const costoSprizGiorno = CONSTANTS.SPRIZ_DAILY_COST;
  const costoSprizAnno = costoSprizGiorno * giorniOperativita;

  // Economic savings
  const risparmioGiorno = costoTradizioneGiorno - costoSprizGiorno;
  const risparmioAnno = risparmioGiorno * giorniOperativita;
  const risparmioPercentuale = (risparmioGiorno / costoTradizioneGiorno) * 100;

  // CO₂
  const co2TradizioneGiorno = litriGiorno * CONSTANTS.CO2_STANDARD_PER_LITER;
  const co2SprizGiorno = CONSTANTS.CO2_SPRIZ_BASE + (litriGiorno * CONSTANTS.CO2_SPRIZ_PER_LITER);
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

function calculateICU(params) {
  const { pazientiCRRT, litriPaziente, capacitaSacche, giorniOperativita } = params;

  const litriTotaliPerPaziente = litriPaziente;

  // Traditional method - Updated formula from new Excel
  const sacchePerPaziente = litriTotaliPerPaziente / capacitaSacche;
  const costoSacchePerPaziente = sacchePerPaziente * CONSTANTS.SACCHE_TI_PRICE;
  const costoSmaltimentoPerPaziente = litriTotaliPerPaziente * CONSTANTS.SMALTIMENTO_PRICE;
  const bioBoxPerPaziente = litriTotaliPerPaziente / CONSTANTS.BIO_BOX_FREQUENCY_OTHER;
  const costoBioBoxPerPaziente = bioBoxPerPaziente * CONSTANTS.BIO_BOX_PRICE;

  const costoTradizioneGiorno = (costoSacchePerPaziente + costoSmaltimentoPerPaziente + costoBioBoxPerPaziente) * pazientiCRRT;
  const costoTradizioneAnno = costoTradizioneGiorno * giorniOperativita;

  // S.P.R.I.Z.
  const costoSprizGiorno = CONSTANTS.SPRIZ_DAILY_COST;
  const costoSprizAnno = costoSprizGiorno * giorniOperativita;

  // Economic savings
  const risparmioGiorno = costoTradizioneGiorno - costoSprizGiorno;
  const risparmioAnno = risparmioGiorno * giorniOperativita;
  const risparmioPercentuale = (risparmioGiorno / costoTradizioneGiorno) * 100;

  // CO₂ - ICU
  const litriTotaliCO2 = pazientiCRRT * litriPaziente;
  const co2TradizioneGiorno = litriTotaliCO2 * CONSTANTS.CO2_STANDARD_PER_LITER;
  const co2SprizGiorno = CONSTANTS.CO2_SPRIZ_BASE + (litriTotaliCO2 * CONSTANTS.CO2_SPRIZ_PER_LITER);
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

function calculateUrologyWard(params) {
  const { pazientiGiorno, litriPaziente, giorniOperativita } = params;

  const litriTotali = litriPaziente;

  // Traditional method - Updated formula from new Excel
  const sacche = litriTotali / CONSTANTS.SACCHE_DEG_CAPACITY;
  const costoSacche = sacche * CONSTANTS.SACCHE_DEG_PRICE;
  const costoSmaltimento = litriTotali * CONSTANTS.SMALTIMENTO_PRICE;
  const bioBox = litriTotali / CONSTANTS.BIO_BOX_FREQUENCY_OTHER;
  const costoBioBox = bioBox * CONSTANTS.BIO_BOX_PRICE;

  const costoTradizioneGiorno = (costoSacche + costoSmaltimento + costoBioBox) * pazientiGiorno;
  const costoTradizioneAnno = costoTradizioneGiorno * giorniOperativita;

  // S.P.R.I.Z.
  const costoSprizGiorno = CONSTANTS.SPRIZ_DAILY_COST;
  const costoSprizAnno = costoSprizGiorno * giorniOperativita;

  // Economic savings
  const risparmioGiorno = costoTradizioneGiorno - costoSprizGiorno;
  const risparmioAnno = risparmioGiorno * giorniOperativita;
  const risparmioPercentuale = (risparmioGiorno / costoTradizioneGiorno) * 100;

  // CO₂ - Urology Ward
  const litriTotaliCO2 = pazientiGiorno * litriPaziente;
  const co2TradizioneGiorno = litriTotaliCO2 * CONSTANTS.CO2_STANDARD_PER_LITER;
  const co2SprizGiorno = CONSTANTS.CO2_SPRIZ_BASE + (litriTotaliCO2 * CONSTANTS.CO2_SPRIZ_PER_LITER);
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

  // Results cards with Euro formatting
  document.getElementById('savingsDaily').textContent = `${formatCurrency(results.savings.economicDaily)}`;
  document.getElementById('savingsAnnual').textContent = `${formatCurrency(results.savings.economicAnnual)}`;

  document.getElementById('co2Daily').textContent = `${formatEuropeanNumber(results.savings.co2Daily)} kg`;
  document.getElementById('co2Annual').textContent = `${formatEuropeanNumber(results.savings.co2Annual)} kg`;

  // Animate circular charts
  updateProgressCircle('savingsProgress', 'savingsProgressText', results.savings.economicPercentage);
  updateProgressCircle('co2Progress', 'co2ProgressText', results.savings.co2Percentage);

  // Activate chart interactivity
  setupProgressCircleInteraction();

  // Method comparison
  document.getElementById('traditionalCost').textContent = `${formatDailyCost(results.traditional.costoGiorno)}`;
  document.getElementById('traditionalCO2').textContent = `${results.traditional.co2Giorno.toFixed(2)} kg`;
  document.getElementById('sprizCost').textContent = `${formatDailyCost(results.spriz.costoGiorno)}`;
  document.getElementById('sprizCO2').textContent = `${results.spriz.co2Giorno.toFixed(2)} kg`;

  // Daily savings highlight
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
        <div>${formatDailyCost(results.traditional.costoGiorno)}</div>
        <div style="font-weight: 600;">${formatDailyCost(results.spriz.costoGiorno)}</div>
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
        <div>${formatUSNumber(results.traditional.co2Anno)} kg</div>
        <div style="color: #4B5055; font-weight: 600;">${formatUSNumber(results.spriz.co2Anno)} kg</div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #FDC32D 0%, #f0b429 100%); color: #4B5055; border-radius: 8px; font-weight: 600; grid-template-rows: auto auto auto;">
        <div style="text-align: center; grid-column: 1; grid-row: 1; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">ECONOMIC SAVINGS</div>
        <div style="text-align: center; grid-column: 2; grid-row: 1; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">CO₂ REDUCTION</div>
        <div style="text-align: center; grid-column: 1; grid-row: 2; font-size: 1.4rem; font-weight: 800; line-height: 1.2;">${formatCurrency(results.savings.economicAnnual)}</div>
        <div style="text-align: center; grid-column: 2; grid-row: 2; font-size: 1.4rem; font-weight: 800; line-height: 1.2;">${formatEuropeanNumber(results.savings.co2Annual)} kg</div>
        <div style="text-align: center; grid-column: 1; grid-row: 3; font-size: 0.8rem; opacity: 0.8;">per year</div>
        <div style="text-align: center; grid-column: 2; grid-row: 3; font-size: 0.8rem; opacity: 0.8;">per year</div>
      </div>
    </div>
  `;

  document.getElementById('breakdownTable').innerHTML = breakdownHTML;
}

function handleFormSubmission(event) {
  event.preventDefault();

  // Simulate form submission (should integrate with Contact Form 7)
  const formData = new FormData(event.target);

  // Check required fields
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

  // Simulate success
  setTimeout(() => {
    appState.formSubmitted = true;
    unlockResults();
  }, 1000);
}

function unlockResults() {
  document.getElementById('leadWall').classList.remove('active');
  document.getElementById('resultsContent').classList.remove('blurred');
  // Re-generate breakdown after results are unlocked
  generateDetailedBreakdown();
}

// Function to animate circular charts
function updateProgressCircle(circleId, textId, percentage) {
  const circle = document.getElementById(circleId);
  const text = document.getElementById(textId);

  if (!circle || !text) return;

  const radius = circle.r.baseVal.value;
  const circumference = radius * 2 * Math.PI;
  const offset = circumference - (percentage / 100) * circumference;

  circle.style.strokeDasharray = `${circumference} ${circumference}`;

  // Animation with delay
  setTimeout(() => {
    circle.style.strokeDashoffset = offset;

    // Text animation
    let currentValue = 0;
    const increment = percentage / 50;
    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= percentage) {
        currentValue = percentage;
        clearInterval(timer);
      }
      // Add signs: minus for CO₂, plus for savings
      const isAnyProgressCo2 = text.closest('.progress-circle').classList.contains('co2');
      const isAnyProgressSavings = text.closest('.progress-circle').classList.contains('savings');
      const sign = isAnyProgressCo2 ? '-' : isAnyProgressSavings ? '+' : '';
      text.textContent = `${sign}${Math.round(currentValue)}%`;
    }, 20);
  }, 500);
}

// Add interactivity to circular charts
function setupProgressCircleInteraction() {
  document.querySelectorAll('.progress-circle').forEach(circle => {
    circle.addEventListener('click', function() {
      // "Pulse" effect when clicked
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