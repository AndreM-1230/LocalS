let blockShow = false;
let LastCallWithModal = {};
class Statuses {
    static get CheckboxNames() {
        return {
            1: 'Дефицит соответствует норме расхода',
            2: 'Заявка соответствует норме расхода',
            3: 'Поставка соответствует норме расхода',
            4: 'Предъявл. ОТК соответствует норме расхода',
            5: 'Принятое на контр. ОТК соответствует норме расхода',
            6: 'Уведомление соответствует норме расхода',
            7: 'Претензия соответствует норме расхода',
            8: 'Пройденный контроль соответствует норме расхода',
            9: 'Количество на складе соответствует норме расхода',
            10: 'Выдача разрешена, количество соответствует норме расхода',
            11: 'Выдача запрещена, количество соответствует норме расхода',
            12: 'Выдача соответствует норме расхода',
            13: 'Полученная поставка соответствует норме расхода',
        };
    }
}
class ajaxCall {
    static get DOMNodes() {
        return {
            'ModalContainer' : $('.modal_container')
        };
    }
    constructor(request, data){
        this.data       = data;
        this.request    = request;
    }
    doCall(){
        this.request = new Promise((resolve, reject) => {
            $.ajax({
                url         : `${URL}js/material_ajax.php`,
                method      : "POST",
                dataType    : "json",
                data        : {
                    request :   this.request,
                    data    :   this.data
                },
                success: (res) => {
                    if( !res.err ){
                        if( res.type === "modal" ){
                            this.setModal( res.data );
                            if( res.action ){
                                if( res.action === "update1cCounter" ){
                                    this.updateModal1cCounter();
                                }
                            }
                            this.ModalBootstrap.show();
                        }
                        if( res.type === "updateTr" ){
                            if( res.data.badges  ){
                                this.updateBadges( res.data.badges );
                            }
                            this.updateTr( res.data.tr );
                        }
                        if( res.type === "demandToOpenOrders" ){
                            this.TrDemandToOpenOrders.children('td').html(res.data);
                            this.TrDemandToOpenOrders.fadeToggle(300);
                        }
                        resolve(res);
                    } else {
                        alert(res.err);
                        reject(res);
                    }
                },
                error: (res) => {
                    reject(res);
                }
            });
        });
        return this;
    }
    doCallFormData(){
        $.ajax({
            url         : `${URL}js/material_ajax.php`,
            method      : "POST",
            dataType    : "json",
            data        : this.data,
            processData : false,
            contentType : false,
            success: (res) => {
                if( !res.err ) {
                    if (res.type === "updateTr") {
                        this.updateTr(res.data.tr);
                    }
                    if (res.msg) {
                        alert(res.msg);
                    }
                } else {
                    alert(res.err);
                }
            }
        });
    }

    setTr( row ){
        this.row            = row;
        this.badgeContainer = row.closest('.collapse_material_type-js').prev().find('.badges-js');
        return this;
    }

    setTrDemandToOpenOrders( tr ){
        this.TrDemandToOpenOrders = tr;
        return this;
    }

    updateModal1cCounter(){
        let C = this.Modal.find('.storage_1C');
        C.text(
            (
                C.data('quantity') -
                this.Modal.find('input.ModalQuantity_1C').toArray()
                    .reduce( (accumulator, val) => {
                        return accumulator + Number( $(val).val() );
                    }, 0)
            ).toFixed(5)
        );
    }

    setModal(data){
        ajaxCall.DOMNodes.ModalContainer.html( data );
        this.Modal = ajaxCall.DOMNodes.ModalContainer.children().eq(0);
        this.ModalBootstrap = new bootstrap.Modal( this.Modal );
        return this;
    }

    updateBadges( data ){
        this.badgeContainer.html(data);
    }

    updateTr( data ){
        this.row.find(".qty_deficit").text( data.quantity.deficit );
        this.row.find(".qty_storage").text( data.quantity.storage );
        this.row.find(".qty_issued").text(  data.quantity.issued  );
        this.row.find(".qty_material_storage").html( data.quantity.material_storage ? data.quantity.material_storage : "" );
        let time = this.row.find(".time_delivery");
        time.html("");
        $(time).append( data.button );
        this.row.find(".btn-disposal-material").replaceWith( data.disposalMaterialButton );
    }
}

function toggleAll(e) {
    let checkboxs = e.parentElement.getElementsByTagName("input");
    for(let i = 0; i < checkboxs.length ; i++) {
        checkboxs[i].checked = !checkboxs[i].checked;
    }
}

$(document).ready( function(){
    $('head > title').html(`${ $('#head_title').text() } - Материалы`);
    let fly = document.getElementById("fly");
    let $text_change = $(fly).find(".text-change");
    let $datepicker = $("#datepicker");

    $('#toogleCollapseJs').on('change', function (e) {
        let toggle = $('.collapse_material_type-js');
        if( $(this).is(':checked') ){
            toggle.collapse("show");
        } else {
            toggle.collapse("hide");
        }
    });

    $('.textarea-upload-js').on('input', function (e) {
        new ajaxCall(
            'UpdateGroupStatusNote',
            {
                group_id    : $(this).closest('.btn_material_group').data('mdg_id'),
                note        : $(this).val()
            }).doCall();
    });

    $('.demand-js').on('click', function (){
        new ajaxCall(
                'DemandToOpenOrders',
                {
                    material_code   :   $(this).closest('tbody').data('material_code'),
                    disposal_id     :   disposal_id
                })
            .setTrDemandToOpenOrders(
                $(this).closest('tbody').find('.MaterialInAnotherDisposals')
            ).doCall();
    });

    //todo проверить работоспособность
    $('.input_block').on('input', function(event){
        let $mainParent = $(this).parent().parent();
        let overall_sum = Number( $mainParent.data('overall_sum') );
        let maxVal = Number( $(this).attr('max') );
        let sums =
            $mainParent.find('.group_elem:not(.active)')
                .toArray()
                .reduce( (accumulator, val) => {
                    return accumulator + Number( $(val).children().eq(1).val() );
                }, 0);
        let thisVal =
            Number( $(this).val() ) + sums > overall_sum ?
                overall_sum - sums :
                Number( $(this).val() );
        $(this).val( thisVal );
        let balance = overall_sum - sums - thisVal;
        $mainParent.find('.total_balance').text(balance);

        let group_elem 	= $(this).parent().children();
        if( thisVal < maxVal ) {
            group_elem.each( (i, val) => {
                $(val).val(thisVal);
            });
        } else {
            group_elem.each( (i, val) => {
                $(val).val(maxVal);
            });
            return false;
        }
    })
        .hover(
            function(){
                $(this).parent().addClass('active');
            },
            function(){
            $(this).parent().removeClass('active');
        }
        );

    $('.cover').fadeOut(800, 'swing' ,function () {
        $(this).remove();
    });

    $('.load').fadeOut(800, 'swing' ,function () {
        $(this).remove();
    });

    $('body').removeClass('stop-scrolling');

    $('#ApplyNewStatusForAllGroups').on('input', function () {
        if( $(this).is(':checked') ){
            $('#useNorm').prop('disabled',true);
            $('#SearchMaterialInAnotherDisposals').prop('disabled',true);
        } else{
            $('#useNorm').prop('disabled',false);
            $('#SearchMaterialInAnotherDisposals').prop('disabled',false);
        }
    });

    $("[name='radio_status']").on('click', function (e) {
        $text_change.removeClass( $text_change.data('class') ).addClass( $(this).data('class') );
        $text_change.data('class', $(this).data('class') );
        if( $(this).data('status_id') <= 2 ){
            $datepicker[0].valueAsDate = new Date();
            $datepicker.attr( "readonly", "readonly" );
        } else {
            $datepicker.attr( "readonly", false );
        }
        $text_change.html( `${Statuses.CheckboxNames[ $(this).data('status_id') ]} <i class="bi bi-journal-check"></i>`);
    });

    $('[name="radio_status"]').eq(0).click();

    //!!! TEST ZONE
    //$('#SearchMaterialInAnotherDisposals').click();
    //$('#useNorm').click();
    //$('.btn-disposal-material').eq(0).click();
    //!!! TEST ZONE END
});

$(document).on("click", ".group", function(){
    //div
    $(this).data("active", !$(this).data("active"));    //Инвертирование data-атрибута active (Включение/Выключение кнопки)
    // условия
    let buttons = {};
    $(".group").each(function( index, val ) {               //Проверка каждой кнопки на активность
        if( $(val).data("active") === false ){
            $(val).removeClass($(val).data("defaultclass")); //Удаляет класс, который прописан в data-атрибуте defaultclass
        } else {
            $(val).addClass($(val).data("defaultclass")); //Добавляет класс, который прописан в data-атрибуте defaultclass
        }
        buttons[ $(val).data("search") ] = $(val).data("active");
    });
});

$(document).on('change', '.file-upload-js', function(e) {
    $(this).parent().find('.input-group-text').text(e.currentTarget.files[0].name);
});

$(document).on('click', '.btn-material-quantity-1C', function (e) {
    let material_code  = $(this).data('material_code');
    new ajaxCall
    (
        'GetReservedQuantityByMaterialCode',
        material_code
    ).doCall();
});

$(document).on('click', '.mdg_cut-js', function () {
        new ajaxCall
        (
            'MaterialDeliveryGroupCut',
            {
                quantity    : $(this).parent().find('.mdg_cut_quantity').val(),
                mdg_id      : $(this).closest('.btn_material_group').data('mdg_id')
            }
        ).setTr( $(this).closest('.MainRow') ).doCall();
});

$(document).on('click', '.file-upload-js-button', function () {
    let file = $(this).parent().find('.file-upload-js')[0].files[0];
    let formData = new FormData();
    formData.append('file', file);
    formData.append('request', 'UploadFile');
    formData.append('mdg_id', $(this).closest('.btn_material_group').data('mdg_id'));
    formData.append('user_id', user_id);
    new ajaxCall
    (
        null,
        formData
    ).setTr( $(this).closest('.MainRow') ).doCallFormData();
});

$(document).on('click', '.btn-disposal-material', function (e) {
    let send_data = {};
    Object.keys(send_data).forEach(k => delete send_data[k]);
    let dateDb   = $('#datepicker').val();
    let request     = 'GroupOperation';
    if( document.getElementById('ApplyNewStatusForAllGroups').checked ){
        request     = 'ApplyNewStatusForAllGroups';
        send_data   = {
            material_code   : $(this).closest('tbody').data('material_code'),
            badgeMaterials  : $(this).parent('.table-mat_group-js').children('tbody').toArray().map( ( val ) => val.dataset.material_code ),
            disposal_id     : disposal_id,
            user_id         : user_id,
            date            : dateDb,
            status_id       : $('.radio_status:checked').data('status_id')
        };
    } else {
        let quantity;
        let full_quantity = $(this).parent().parent().find('.planQuantity-js:not(.hasProblem )').toArray().reduce( function (a, b) {
            return a + Number(b.innerHTML);
        }, 0 );
        let norma_rashoda = Number( $(this).parent().parent().find('.norm').data('val') );
        if( document.getElementById('useNorm').checked ){
            quantity = 'full';
        } else {
            quantity = +( prompt('Введите количество ('+ ( norma_rashoda - full_quantity ) +')').replaceAll(',', '.') );
            if( quantity <= 0 || isNaN(quantity) ){
                alert('Введено неверное число');
                return false;
            }
        }
        send_data = {
            badgeMaterials                      : $(this).closest('.table-mat_group-js').children('tbody').toArray().map( ( val ) => val.dataset.material_code ),
            disposal_id                         : disposal_id,
            quantity                            : quantity,
            material_code                       : $(this).closest('tbody').data('material_code'),
            user_id                             : user_id,
            date                                : dateDb,
            dateHtml                            : $('#datepicker').val(),
            status_id                           : $('.radio_status:checked').data('status_id'),
            SearchMaterialInAnotherDisposals    : document.getElementById('SearchMaterialInAnotherDisposals').checked
        };
    }
    if( send_data.SearchMaterialInAnotherDisposals ){
        LastCallWithModal = new ajaxCall(request, send_data)
            .setTr( $(this).parent().parent() )
            .doCall();
    } else {
        new ajaxCall(request, send_data)
            .setTr( $(this).parent().parent() )
            .doCall();
    }
});

$(document).on('input', '.ModalQuantity_1C', function () {
    let containerSum    = $(this).closest('.modal-body').find('.storage_1C');
    let quantity        = (
        containerSum.data('quantity') -
        $(this).closest('.quantity_distribution').find('input.ModalQuantity_1C').toArray()
            .reduce( (accumulator, val) => {
                return accumulator + Number( $(val).val() );
            }, 0)
    ).toFixed(5);
    containerSum.text( quantity );
});

$(document).on('click', '.delete_file-js', function (e) {
    new ajaxCall(
        'DeleteFile',
        {
            mds_id : $(this).closest('.btn_material_group').data('mds_id'),
            file_id : $(this).parent().data('file_id')
        }
    ).setTr( $(this).closest('.MainRow') ).doCall();
});

$(document).on('click', '.story_group-js', function (e) {
    let dateDb           = $('#datepicker').val();
    new ajaxCall(
        'InsertGroupStatus',
        {
            material_delivery_group_id  : $(this).closest('.btn_material_group').data('mdg_id'),
            date                        : dateDb,
            badgeMaterials              : $(this).closest('.table-mat_group-js').children('tbody').toArray().map( ( val ) => val.dataset.material_code ),
            status_id                   : $('.radio_status:checked').data('status_id'),
            user_id                     : user_id
        }
    ).setTr( $(this).closest('.MainRow') ).doCall();
});

$(document).on('click', '.delete_status-js', function () {
    new ajaxCall(
        'DeleteGroupStatus',
        {
            mds_id          : $(this).closest('.btn_material_group').data('mds_id'),
            badgeMaterials  : $(this).closest('.table-mat_group-js').children('tbody').toArray().map( ( val ) => val.dataset.material_code ),
            user_id         : user_id,
        }
    ).setTr( $(this).closest('.MainRow') ).doCall();
});

$(document).on('click', '.js-disposal-resolve', function (e) {
    let table = $(document).find('.quantity_distribution').last();
    let send_data = {};
    Object.keys(send_data).forEach(k => delete send_data[k]);
    send_data.data = {};
    send_data.data.date             = table.data('date');
    send_data.data.user_id          = table.data('user_id');
    send_data.data.status_id        = table.data('status_id');
    send_data.data.material_code    = table.data('material_code');
    send_data.data.badgeMaterials   = $(document).find(`tbody[data-material_code="${table.data('material_code')}"]`).closest('.table-mat_group-js').children('tbody').toArray().map( ( val ) => val.dataset.material_code );
    send_data.row = [];
    while (send_data.length) {
        send_data.pop();
    }
    table.find('.disposal_row').each( function (i, val) {
        let value       = Number( $(val).find('.ModalQuantity_1C').val() );
        let disposal_id = $(val).data('disposal_id');
        send_data.row[ i ] = {};
        if( value !== 0 ){
            send_data.row[ i ].quantity = $(val).find('.ModalQuantity_1C').val();
            send_data.row[ i ].current = false;
            send_data.row[ i ].disposal_id = disposal_id;
            if ( $(val).hasClass('current') ){
                send_data.data.disposal_id  = disposal_id;
                send_data.row[ i ].current  = true;
            }
        }
    });
    LastCallWithModal.data      = send_data;
    LastCallWithModal.request   = 'InsertSeveralGroupStatuses';
    LastCallWithModal.doCall().ModalBootstrap.hide();
});