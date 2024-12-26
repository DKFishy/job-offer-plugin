jQuery(document).ready(function ($) {
    $(document).on('change', '#job-filter-form input[type="checkbox"]', function () {
        submitFilterForm(1);
    });

    $(document).on('click', '#pagination button', function (e) {
        e.preventDefault();

        const paged = $(this).data('page');
        submitFilterForm(paged);
    });
    $(document).on('click', '#clear-filters', function (e) {
        e.preventDefault();
        $('#job-filter-form')[0].reset();
        submitFilterForm(1);
    });

        $(document).on('click', '.collapsible span', function () {
        const content = $(this).next('.content');
        const icon = $(this).find('i');
        content.stop(true, true).slideToggle(200);
        icon.toggleClass('fa-chevron-down fa-chevron-up');
        $(this).parent().toggleClass('active');
    });

    function submitFilterForm(paged) {
    const formData = $('#job-filter-form').serialize() + '&paged=' + paged;

    $.ajax({
        url: ajax_object.ajax_url,
        type: 'POST',
        data: {
            action: 'job_offers_build',
            form_data: formData,
        },
        success: function (response) {
            $('#job-results').html(response.html);
            $('#job-offer-count').text(response.count);
        },
        error: function () {
            alert('Failed to load job offers.');
        },
    });
}
});
