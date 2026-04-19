// ============================================
// FitFuel Main JavaScript
// ============================================

// ── Sidebar Toggle ──────────────────────────────────────────
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('open');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

// ── Modal Functions ─────────────────────────────────────────
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('open');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('open');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
    }
});

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('open');
    }
});

// ── Flash Messages Auto-dismiss ─────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('.flash');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-4px)';
            setTimeout(() => flash.remove(), 300);
        }, 4000);
    }
});

// ── Water Tracker ───────────────────────────────────────────
function setWater(glasses, userId, date) {
    console.log('=== WATER TRACKER DEBUG ===');
    console.log('Glasses:', glasses);
    console.log('User ID:', userId);
    console.log('Date:', date);
    
    const csrf = document.getElementById('csrf')?.value;
    console.log('CSRF Token found:', csrf ? 'YES' : 'NO');
    
    if (!csrf) {
        alert('CSRF token not found! Please refresh the page.');
        return;
    }
    
    fetch('water.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `glasses=${glasses}&date=${date}&csrf=${csrf}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success === true) {
            console.log('Success! Reloading page...');
            location.reload();
        } else {
            alert('Error: ' + (data.message || data.error || 'Failed to update water'));
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert('Network error. Check console for details.');
    });
}

// ── Weekly Chart ────────────────────────────────────────────
function renderWeekChart(labels, data, goal) {
    const ctx = document.getElementById('weekChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: data.map(v => v === 0 ? '#e8e6f0' : (v > goal ? '#fca5a5' : '#c4b5fd')),
                borderColor: data.map(v => v === 0 ? '#d1cfe8' : (v > goal ? '#ef4444' : '#6366f1')),
                borderWidth: 1.5,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.parsed.y + ' kcal'
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' }
                },
                y: {
                    grid: { color: '#f0eff6' },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' },
                    afterDataLimits: (scale) => { scale.max = Math.max(scale.max, goal * 1.1); }
                }
            },
            animation: { duration: 800, easing: 'easeInOutQuart' }
        }
    });
}

// ── Weight Chart ────────────────────────────────────────────
function renderWeightChart(labels, data, goal) {
    const ctx = document.getElementById('weightChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Weight (kg)',
                data: data,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79,70,229,.08)',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5',
                fill: true,
                tension: 0.35,
            }, {
                label: 'Goal',
                data: Array(labels.length).fill(goal),
                borderColor: '#10b981',
                borderDash: [6, 4],
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { font: { family: "'Plus Jakarta Sans'" }, boxWidth: 12 }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' }
                },
                y: {
                    grid: { color: '#f0eff6' },
                    ticks: { font: { family: "'Plus Jakarta Sans'" }, color: '#9794ac' }
                }
            },
            animation: { duration: 800 }
        }
    });
}

// ── BMI Live Calculator ─────────────────────────────────────
function updateBMIPreview() {
    const weight = parseFloat(document.getElementById('weight_kg')?.value);
    const height = parseFloat(document.getElementById('height_cm')?.value);
    const preview = document.getElementById('bmiPreview');
    
    if (!preview || !weight || !height) return;
    
    const bmi = (weight / Math.pow(height / 100, 2)).toFixed(1);
    let category = '';
    let color = '';
    
    if (bmi < 18.5) {
        category = 'Underweight';
        color = '#f59e0b';
    } else if (bmi < 25) {
        category = 'Normal';
        color = '#10b981';
    } else if (bmi < 30) {
        category = 'Overweight';
        color = '#f59e0b';
    } else {
        category = 'Obese';
        color = '#ef4444';
    }
    
    preview.textContent = `BMI: ${bmi} — ${category}`;
    preview.style.color = color;
}

// ── Recipe Search/Filter ────────────────────────────────────
function filterRecipes() {
    const searchInput = document.getElementById('recipeSearch');
    const query = searchInput?.value?.toLowerCase() || '';
    const activeChip = document.querySelector('.chip.active');
    const type = activeChip?.dataset?.type || 'all';
    
    document.querySelectorAll('.recipe-card').forEach(card => {
        const cardType = card.dataset.type;
        const cardTitle = card.dataset.title || '';
        const matchesType = type === 'all' || cardType === type;
        const matchesSearch = cardTitle.includes(query);
        card.style.display = matchesType && matchesSearch ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const chips = document.querySelectorAll('.chip[data-type]');
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            filterRecipes();
        });
    });
    
    const searchInput = document.getElementById('recipeSearch');
    if (searchInput) {
        searchInput.addEventListener('input', filterRecipes);
    }
});

// ── Image Preview ───────────────────────────────────────────
function previewImage(input, previewId) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        const img = document.getElementById(previewId);
        if (img) {
            img.src = e.target.result;
            img.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
}

// ── Serving Calculator ──────────────────────────────────────
function updateServingCalories() {
    const base = parseFloat(document.getElementById('base_calories')?.value || 0);
    const servings = parseFloat(document.getElementById('servings')?.value || 1);
    const preview = document.getElementById('servingCalPreview');
    const calInput = document.getElementById('modalCal');
    
    if (base > 0 && preview) {
        const scaled = Math.round(base * servings);
        preview.textContent = `(→ ${scaled} kcal)`;
        if (calInput) calInput.value = scaled;
    } else if (preview) {
        preview.textContent = '';
    }
}

// ── Meal Image Upload ───────────────────────────────────────
function uploadMealImage(mealId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.onchange = async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('image', file);
        formData.append('meal_id', mealId);
        
        const csrfInput = document.getElementById('csrf');
        if (csrfInput) {
            formData.append('csrf', csrfInput.value);
        }
        
        try {
            const response = await fetch('upload-meal-image.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + (result.error || 'Upload failed'));
            }
        } catch (error) {
            alert('Error uploading image. Please try again.');
        }
    };
    input.click();
}

// ── Recipe Image Upload ─────────────────────────────────────
function uploadRecipeImage(recipeId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.onchange = async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('image', file);
        formData.append('recipe_id', recipeId);
        
        const csrfInput = document.getElementById('csrf');
        if (csrfInput) {
            formData.append('csrf', csrfInput.value);
        }
        
        const btn = document.querySelector('#recipeImageUploadBtn');
        if (btn) btn.textContent = 'Uploading...';
        
        try {
            const response = await fetch('upload-recipe-image.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + (result.error || 'Upload failed'));
                if (btn) btn.textContent = 'Upload Image';
            }
        } catch (error) {
            alert('Error uploading image. Please try again.');
            if (btn) btn.textContent = 'Upload Image';
        }
    };
    input.click();
}

function removeRecipeImage(recipeId) {
    if (!confirm('Remove this recipe image?')) return;
    
    fetch('remove-recipe-image.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `recipe_id=${recipeId}&csrf=${document.getElementById('csrf')?.value || ''}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// ============================================
// DARK/LIGHT MODE TOGGLE
// ============================================

// Apply theme immediately when page loads
function initTheme() {
    const savedTheme = localStorage.getItem('fitfuel_theme');
    if (savedTheme === 'dark') {
        setDarkTheme();
    } else {
        setLightTheme();
    }
}

function setDarkTheme() {
    const root = document.documentElement;
    root.style.setProperty('--bg', '#0f0f1a');
    root.style.setProperty('--surface', '#1a1a2e');
    root.style.setProperty('--surface2', '#16213e');
    root.style.setProperty('--border', '#2a2a4e');
    root.style.setProperty('--border2', '#3a3a5e');
    root.style.setProperty('--text', '#ffffff');
    root.style.setProperty('--text2', '#a0a0c0');
    root.style.setProperty('--text3', '#707090');
    root.style.setProperty('--accent-dim', '#2d2a5e');
    
    // Update loading screen for dark mode
    const loader = document.getElementById('loader');
    if (loader) {
        loader.style.background = '#0f0f1a';
    }
    const loaderText = document.querySelector('.loader-text');
    if (loaderText) {
        loaderText.style.color = '#a0a0c0';
    }
    const loaderBar = document.querySelector('.loader-bar');
    if (loaderBar) {
        loaderBar.style.background = '#2a2a4e';
    }
    
    localStorage.setItem('fitfuel_theme', 'dark');
}

function setLightTheme() {
    const root = document.documentElement;
    root.style.setProperty('--bg', '#f5f4f8');
    root.style.setProperty('--surface', '#ffffff');
    root.style.setProperty('--surface2', '#f8f7fc');
    root.style.setProperty('--border', '#e8e6f0');
    root.style.setProperty('--border2', '#d1cfe8');
    root.style.setProperty('--text', '#1a1825');
    root.style.setProperty('--text2', '#5c5870');
    root.style.setProperty('--text3', '#9794ac');
    root.style.setProperty('--accent-dim', '#ede9fe');
    
    // Update loading screen for light mode
    const loader = document.getElementById('loader');
    if (loader) {
        loader.style.background = '#ffffff';
    }
    const loaderText = document.querySelector('.loader-text');
    if (loaderText) {
        loaderText.style.color = '#9794ac';
    }
    const loaderBar = document.querySelector('.loader-bar');
    if (loaderBar) {
        loaderBar.style.background = '#e8e6f0';
    }
    
    localStorage.setItem('fitfuel_theme', 'light');
}

function toggleTheme() {
    const currentTheme = localStorage.getItem('fitfuel_theme');
    if (currentTheme === 'dark') {
        setLightTheme();
        showToast('☀️ Light mode activated');
    } else {
        setDarkTheme();
        showToast('🌙 Dark mode activated');
    }
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed; 
        bottom: 80px; 
        right: 20px; 
        background: var(--accent); 
        color: white; 
        padding: 12px 24px; 
        border-radius: 10px; 
        z-index: 9999; 
        font-size: 0.9rem;
        font-weight: 500;
        animation: fadeInOut 2s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}

// Add animation for toast
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(20px); }
        15% { opacity: 1; transform: translateY(0); }
        85% { opacity: 1; transform: translateY(0); }
        100% { opacity: 0; transform: translateY(-20px); }
    }
`;
document.head.appendChild(style);

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', initTheme);