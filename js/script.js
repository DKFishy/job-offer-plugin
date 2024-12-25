jQuery(document).ready(function ($) {
    // Handle pagination button clicks
    $(document).on('click', '#pagination button', function (e) {
        e.preventDefault();

        const paged = $(this).data('page');
        submitFilterForm(paged);
    });

    // Handle filter form submission
    $(document).on('submit', '#job-filter-form', function (e) {
        e.preventDefault();
        submitFilterForm(1); // Always start from page 1 when filtering
    });

    // Handle clear filters button
    $(document).on('click', '#clear-filters', function (e) {
        e.preventDefault();
        $('#job-filter-form')[0].reset();
        submitFilterForm(1); // Always start from page 1 when clearing filters
    });

    function submitFilterForm(paged) {
        const formData = $('#job-filter-form').serialize() + '&paged=' + paged;

        $.ajax({
            url: ajax_object.ajax_url, // AJAX URL from WordPress
            type: 'POST',
            data: {
                action: 'job_offers_pagination',
                form_data: formData,
            },
            success: function (response) {
                $('#job-results').html(response); // Update the results container
            },
            error: function () {
                alert('Failed to load job offers.');
            },
        });
    }
});
