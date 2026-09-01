document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');

    if (status && message) {
        alert(decodeURIComponent(message));
    }

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) {
                event.preventDefault();
                alert('Please enter your username and password.');
                return;
            }
        });
    }

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (event) => {
            const fullname = document.getElementById('fullname').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (!fullname || !username || !email || !password || !confirmPassword) {
                event.preventDefault();
                alert('Please fill in all fields.');
                return;
            }

            if (password.length < 6) {
                event.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }

            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match.');
                return;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                event.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    }
});