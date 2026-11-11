setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) {
        alert.classList.remove('show');
        alert.classList.add('hide');
    }
}, 3000);


function confirmAction(event, message) {
    event.preventDefault();

    const confirmed = confirm(`Are you sure you want to ${message}`);

    if (confirmed) {
        window.location.href = event.currentTarget.href; // Correct element reference
    }

    return false; 
}