// State management
let currentUser = null;
let currentStep = 1;

// DOM Elements
const loginSection = document.getElementById('login-section');
const homepage = document.getElementById('homepage');
const customizationSection = document.getElementById('customization-section');

// Login functionality
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    // Simple login validation
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (email && password) {
        currentUser = { email: email };
        showHomepage();
    } else {
        alert('Please fill in both email and password.');
    }
});

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = event.target;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '👁️‍🗨️'; // Eye with slash
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

function showHomepage() {
    loginSection.classList.add('hidden');
    homepage.classList.remove('hidden');
    customizationSection.classList.add('hidden');
}

function showPreMadeBouquets() {
    alert('Showing pre-made bouquets at Flower n\' Go!');
    // You can add pre-made bouquets functionality here
}

function showCustomizeBouquet() {
    homepage.classList.add('hidden');
    customizationSection.classList.remove('hidden');
    showStep(1);
}

function showStep(stepNumber) {
    currentStep = stepNumber;
    
    // Hide all steps
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Show current step
    document.getElementById(`wrapper-step`).classList.toggle('active', stepNumber === 1);
    document.getElementById(`flower-step`).classList.toggle('active', stepNumber === 2);
    document.getElementById(`addons-step`).classList.toggle('active', stepNumber === 3);
    
    // Load content based on step
    if (stepNumber === 1) loadWrapperOptions();
    else if (stepNumber === 2) loadFlowerOptions();
    else if (stepNumber === 3) loadAddonsOptions();
}

function loadWrapperOptions() {
    const wrapperContainer = document.querySelector('.wrapper-options');
    wrapperContainer.innerHTML = `
        <div class="option-grid">
            <div class="option-item" onclick="selectWrapper('paper')">
                <img src="https://via.placeholder.com/150?text=Paper+Wrap" alt="Paper Wrapper">
                <p>Paper Wrap</p>
            </div>
            <div class="option-item" onclick="selectWrapper('fabric')">
                <img src="https://via.placeholder.com/150?text=Fabric+Wrap" alt="Fabric Wrapper">
                <p>Fabric Wrap</p>
            </div>
            <div class="option-item" onclick="selectWrapper('kraft')">
                <img src="https://via.placeholder.com/150?text=Kraft+Wrap" alt="Kraft Wrapper">
                <p>Kraft Wrap</p>
            </div>
        </div>
    `;
}

function selectWrapper(wrapperType) {
    console.log('Selected wrapper:', wrapperType);
    document.querySelectorAll('.option-item').forEach(item => {
        item.classList.remove('selected');
    });
    event.target.closest('.option-item').classList.add('selected');
    
    setTimeout(() => showStep(2), 500);
}

function loadFlowerOptions() {
    const flowerContainer = document.querySelector('.flower-options');
    flowerContainer.innerHTML = `
        <div class="option-grid">
            <div class="option-item" onclick="selectFlower('roses')">
                <img src="https://via.placeholder.com/150?text=Roses" alt="Roses">
                <p>Roses</p>
            </div>
            <div class="option-item" onclick="selectFlower('tulips')">
                <img src="https://via.placeholder.com/150?text=Tulips" alt="Tulips">
                <p>Tulips</p>
            </div>
            <div class="option-item" onclick="selectFlower('sunflowers')">
                <img src="https://via.placeholder.com/150?text=Sunflowers" alt="Sunflowers">
                <p>Sunflowers</p>
            </div>
        </div>
    `;
}

function selectFlower(flowerType) {
    console.log('Selected flower:', flowerType);
    document.querySelectorAll('.option-item').forEach(item => {
        item.classList.remove('selected');
    });
    event.target.closest('.option-item').classList.add('selected');
    
    setTimeout(() => showStep(3), 500);
}

function loadAddonsOptions() {
    const addonsList = document.querySelector('.addons-list');
    const orderActions = document.querySelector('.order-actions');
    
    addonsList.innerHTML = `
        <h4>Additional Flowers & Extras:</h4>
        <div class="checkbox-group">
            <label><input type="checkbox" value="daisies"> Daisies - $2.50</label><br>
            <label><input type="checkbox" value="lilies"> Lilies - $3.00</label><br>
            <label><input type="checkbox" value="baby-breath"> Baby's Breath - $1.50</label><br>
            <label><input type="checkbox" value="eucalyptus"> Eucalyptus - $2.00</label><br>
            <label><input type="checkbox" value="ribbon"> Decorative Ribbon - $1.00</label><br>
        </div>
    `;
    
    orderActions.innerHTML = `
        <button class="buy-btn" onclick="buyBouquet()">Buy Now</button>
        <button class="cart-btn" onclick="addToCart()">Add to Cart</button>
        <button onclick="showStep(2)">Back</button>
    `;
}

function buyBouquet() {
    alert('Your bouquet is ready to be delivered by Flower n\' Go! 🌸');
}

function addToCart() {
    alert('Added to your Flower n\' Go cart! 🛒');
}

// Prevent default behavior for registration link to ensure it works
document.addEventListener('DOMContentLoaded', function() {
    const registerLink = document.querySelector('.forgot-signup a[href="register.php"]');
    if (registerLink) {
        registerLink.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'register.php';
        });
    }
});