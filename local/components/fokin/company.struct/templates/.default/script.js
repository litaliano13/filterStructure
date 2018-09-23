BX.ready(function () {
    BX.bind(
        BX('submit-btn'),
        'click',
        function (event) {
            parameters = BX.ajax.prepareData((BX.ajax.prepareForm(BX('filter')).data));
            BX.ajax({
                url: '/local/components/fokin/company.struct/ajax.php',
                method: 'POST',
                data: parameters,
                onsuccess: function (data) {
                    BX.adjust(BX('structure-result-block'), {html: data});
                },
                onerror: function (xhr, ajaxOptions, thrownError) {
                    console.log('error...', xhr);
                }
            });
            return BX.PreventDefault(event);
        }
    );
});
