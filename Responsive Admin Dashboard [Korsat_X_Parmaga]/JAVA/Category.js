// js/Category.js

document.addEventListener('DOMContentLoaded', () => {
    const imageUpload = document.getElementById('category_image') || document.getElementById('image-upload');
    const categoryImage = document.getElementById('category-image');

    // Update Image Preview when a new image is selected
    if (imageUpload) {
        imageUpload.addEventListener('change', (event) => {
            const [file] = imageUpload.files;
            if (file) {
                categoryImage.src = URL.createObjectURL(file);
            }
        });
    }

    // Optional: Handle Add, Save, Update, Delete buttons if using AJAX
    // Currently, Add and Update are handled via form submissions
});
