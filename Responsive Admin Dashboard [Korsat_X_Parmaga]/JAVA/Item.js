// js/Item.js

document.addEventListener('DOMContentLoaded', () => {
    const imageUpload = document.getElementById('item_image') || document.getElementById('image-upload');
    const itemImage = document.getElementById('item-image');

    // Update Image Preview when a new image is selected
    if (imageUpload) {
        imageUpload.addEventListener('change', (event) => {
            const [file] = imageUpload.files;
            if (file) {
                itemImage.src = URL.createObjectURL(file);
            }
        });
    }

    // Optional: Handle Update and Delete buttons if needed
    // Currently, Update and Delete buttons are handled via links in the table
});
