jQuery(document).ready(function ($) {
    // Apply filters immediately on change
    $(document).on('change', '#job-filter-form input[type="checkbox"]', function () {
        submitFilterForm(1); // Always start from page 1 when filtering
    });

    // Handle pagination button clicks
    $(document).on('click', '#pagination button', function (e) {
        e.preventDefault();

        const paged = $(this).data('page');
        submitFilterForm(paged);
    });

    // Handle clear filters button
    $(document).on('click', '#clear-filters', function (e) {
        e.preventDefault();
        $('#job-filter-form')[0].reset();
        submitFilterForm(1); // Always start from page 1 when clearing filters
    });

    // Handle collapsible content with smooth transition
    $(document).on('click', '.collapsible ', function () {
        const content = $(this).next('.content');
        const icon = $(this).find('i');
        content.stop(true, true).slideToggle(200); // Use stop() to prevent animation queue buildup
        icon.toggleClass('fa-chevron-down fa-chevron-up');
        $(this).parent().toggleClass('active');
    });

    function submitFilterForm(paged) {
        const formData = $('#job-filter-form').serialize() + '&paged=' + paged;

        $.ajax({
            url: ajax_object.ajax_url, // AJAX URL from WordPress
            type: 'POST',
            data: {
                action: 'job_offers_build',
                form_data: formData,
            },
            success: function (response) {
                $('#job-results').html(response); // Update the results container
                
                // Update the number of offers displayed
                const totalOffers = $(response).find('#job-offer-count').text();
                $('#job-offer-count').text(totalOffers);
            },
            error: function () {
                alert('Failed to load job offers.');
            },
        });
    }
});
