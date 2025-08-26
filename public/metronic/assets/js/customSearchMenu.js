var processs = function (search) {
    var timeout = setTimeout(function () {


        var searching = search.inputElement.value
        $.ajax({
            url: "/admin/searchmenu",
            method: "get",
            data: { search: searching },
            success: function (response) {
                if (response.success === true) {
                    resultsElement.innerHTML = response.menu
                    // Show results
                    resultsElement.classList.remove("d-none");
                    // Hide empty message
                    emptyElement.classList.add("d-none");
                } else {

                    resultsElement.classList.add("d-none");
                    // Show empty message
                    emptyElement.classList.remove("d-none");
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status === 422) {
                    var errors = jqXHR.responseJSON.errors;
                    Swal.fire(
                        'Peringatan',
                        errors[0],
                        'warning'
                    )
                    $('#btn-add').show()
                    $('#loadingnih').remove()
                } else {
                    Swal.fire(
                        'Upps !!',
                        'Sepertinya Ada Sesuatu Yang Tidak Beres !<br>' + errorThrown,
                        'warning'
                    )

                }
            }
        })


        // Complete search
        search.complete();
    }, 1500);
}

var clear = function (search) {
    // Show recently viewed
    // Hide results
    resultsElement.classList.add("d-none");
    // Hide empty message
    emptyElement.classList.add("d-none");
}

// Elements
element = document.querySelector("#kt_docs_search_handler_responsive");

// if (!element) {
//     console.log('gaketemu');
//     return false;
// }

wrapperElement = element.querySelector("[data-kt-search-element='wrapper']");
resultsElement = element.querySelector("[data-kt-search-element='results']");
emptyElement = element.querySelector("[data-kt-search-element='empty']");


// Initialize search handler
searchObject = new KTSearch(element);

// Search handler
searchObject.on("kt.search.process", processs);

// Clear handler
searchObject.on("kt.search.clear", clear);


