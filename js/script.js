jQuery(document).ready(function ($) {
    $(document).on('click', '#pagination button', function (e) {
        e.preventDefault();

        const paged = $(this).data('page');

        $.ajax({
            url: ajax_object.ajax_url, // AJAX URL from WordPress
            type: 'POST',
            data: {
                action: 'job_offers_pagination',
                paged: paged,
            },
            success: function (response) {
                console.log(response); // Log the response for debugging
                $('#job-offers-container').html(response); // Update the container
                console.log('Job offers updated'); // Check if this is logged
            },
            error: function () {
                alert('Failed to load job offers.');
            },
        });
    });
});