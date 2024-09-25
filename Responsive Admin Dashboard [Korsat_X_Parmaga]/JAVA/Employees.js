// js/Employees.js

document.addEventListener('DOMContentLoaded', () => {
    const registerBtn = document.getElementById('register-btn');
    const deleteBtn = document.getElementById('delete-btn');
    const registerForm = document.getElementById('register-form');
    const employeeImage = document.getElementById('employee-image');
    const employeeImageUpload = document.getElementById('employee_image');

    // Toggle Registration Form
    registerBtn.addEventListener('click', () => {
        if (registerForm.style.display === 'none' || registerForm.style.display === '') {
            registerForm.style.display = 'block';
        } else {
            registerForm.style.display = 'none';
        }
    });

    // Update Image Preview when a new image is selected
    employeeImageUpload.addEventListener('change', (event) => {
        const [file] = employeeImageUpload.files;
        if (file) {
            employeeImage.src = URL.createObjectURL(file);
        }
    });
});
