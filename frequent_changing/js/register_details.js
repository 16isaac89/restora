$(function () {
    "use strict";
    let base_url = $("#base_url_customer").val();
    let register_debug = true;
    function regLog(label, data) {
        if (!register_debug || !window.console) return;
        try {
            console.log("[REGISTER_DEBUG] " + label, data || "");
        } catch (e) {}
    }
    function regWarn(label, data) {
        if (!register_debug || !window.console) return;
        try {
            console.warn("[REGISTER_DEBUG] " + label, data || "");
        } catch (e) {}
    }
    function regErr(label, data) {
        if (!register_debug || !window.console) return;
        try {
            console.error("[REGISTER_DEBUG] " + label, data || "");
        } catch (e) {}
    }
    function safeJsonParse(response) {
        try {
            return JSON.parse(response);
        } catch (e) {
            return null;
        }
    }
    function initRegisterDataTable(table_id) {
        if(!$(table_id).length || !$.fn.DataTable){
            return;
        }
        if ($.fn.DataTable.isDataTable(table_id)) {
            $(table_id).DataTable().destroy();
        }
        $(table_id).DataTable({
            'autoWidth'   : false,
            'ordering'    : false,
            'paging'    : false,
            'bFilter'    : false,
            dom: 'Blfrtip',
            buttons: [
                {
                    extend: "print",
                    text: '<i class="fa fa-print"></i> Print',
                    titleAttr: "print",
                },
                {
                    extend: "excelHtml5",
                    text: '<i class="fa fa-file-excel-o"></i> Excel',
                    titleAttr: "Excel",
                },
                {
                    extend: "csvHtml5",
                    text: '<i class="fa fa-file-text-o"></i> CSV',
                    titleAttr: "CSV",
                },
                {
                    extend: "pdfHtml5",
                    text: '<i class="fa fa-file-pdf-o"></i> PDF',
                    titleAttr: "PDF",
                },
            ]
        });
    }
    function show_details_for_details_page() {
        let csrf_value_ = $("#csrf_value_").val();
        regLog("show_details_for_details_page:start", {base_url: base_url});
        $.ajax({
            url: base_url + "Sale/registerDetailCalculationToShowAjax",
            method: "POST",
            dataType: "json",
            data: {
                csrf_name_: csrf_value_,
            },
            success: function (response) {
                regLog("show_details_for_details_page:success", response);
                if(typeof response === "string"){
                    response = safeJsonParse(response);
                }
                if(!response || !response.html_content_for_div){
                    regWarn("show_details_for_details_page:invalid_response", response);
                    return;
                }

                $(".html_content").html(response.html_content_for_div);
                initRegisterDataTable("#datatable");
            },
            error: function (xhr, textStatus, errorThrown) {
                regErr("show_details_for_details_page:error", {
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    status: xhr ? xhr.status : "",
                    responseText: xhr && xhr.responseText ? xhr.responseText.substring(0, 1000) : ""
                });
                alert("error");
            },
        });
    }
    show_details_for_details_page();

    $(document).on("click", "#register_close_details", function (e) {
        let menu_not_permit_access = $("#menu_not_permit_access").val();
        let pos_21 = Number($("#pos_21").val());
        let txt_err_pos_2 = $("#txt_err_pos_2").val();
        let warning = $("#warning").val();
        let ok = $("#ok").val();

        if(pos_21){
            let csrf_name_ = $("#csrf_name_").val();
            let csrf_value_ = $("#csrf_value_").val();
            swal(
                {
                    title: warning + "!",
                    text: txt_err_pos_2,
                    confirmButtonColor: "#3c8dbc",
                    confirmButtonText: ok,
                    showCancelButton: true,
                },
                function () {
                    $.ajax({
                        url: base_url + "Sale/closeRegister",
                        method: "POST",
                        data: {
                            csrf_name_: csrf_value_,
                        },
                        success: function (response) {
                            $("#close_register_button").hide();
                            let redirect_url = base_url + "Register/openRegister";
                            try{
                                let response_obj = JSON.parse(response);
                                if(response_obj.register_encrypted_id){
                                    redirect_url = base_url + "Report/cashierZReport/" + response_obj.register_encrypted_id;
                                }
                            }catch (e) {}
                            window.location.href = redirect_url;
                        },
                        error: function () {
                            alert("error");
                        },
                    });
                }
            );
        }else{
           
        }
  
    });

    function checkInternetConnection(){
        let base_url_r = $("#base_url_customer").val();
        let status = false;
        $.ajax({
            url: base_url_r+"authentication/is_online",
            async: false,
            error: function(jqXHR) {
                if(jqXHR.status==0) {
                    status = false;
                }
            },
            success: function() {
                status = true;
            }
        });
        return status;
    }
    $(document).on("click", ".register_details", function (e) {
        regLog("register_details:clicked", {target: e && e.target ? e.target.className : ""});
        let status = true;
        if(!checkInternetConnection()){
            toastr.options = {
                positionClass:'toast-bottom-right'
            };
            let register_error = $("#register_error").val();
            status = false;
            regWarn("register_details:offline_blocked");
            toastr['error']((register_error), '');
        }
        if(status){
            let not_closed_yet = $("#not_closed_yet").val();
            let base_url = $("#base_url_customer").val();
            let csrf_value_ = $("#csrf_value_").val();
            regLog("register_details:ajax_request", {url: base_url + "Sale/registerDetailCalculationToShowAjax"});
            $.ajax({
                url: base_url + "Sale/registerDetailCalculationToShowAjax",
                method: "POST",
                dataType: "json",
                data: {
                    csrf_name_: csrf_value_,
                },
                success: function (response) {
                    regLog("register_details:ajax_success_raw", response);
                if(typeof response === "string"){
                    response = safeJsonParse(response);
                }
                if(!response || !response.html_content_for_div){
                    regErr("register_details:invalid_json_or_missing_html", response);
                    toastr['error']("Unable to load register details.", '');
                    return;
                }
                    regLog("register_details:modal_opening", {
                        opening_date_time: response.opening_date_time,
                        closing_date_time: response.closing_date_time
                    });

                    $("#register_modal").addClass("active");
                    $(".pos__modal__overlay").fadeIn(200);
                    $("#opening_register_time").html(response.opening_date_time);
                    $(".html_content").html(response.html_content_for_div);
                initRegisterDataTable("#datatable");


                },
                error: function (xhr, textStatus, errorThrown) {
                    regErr("register_details:ajax_error", {
                        textStatus: textStatus,
                        errorThrown: errorThrown,
                        status: xhr ? xhr.status : "",
                        responseText: xhr && xhr.responseText ? xhr.responseText.substring(0, 1500) : ""
                    });
                    toastr['error']("Register details request failed. Check console for REGISTER_DEBUG logs.", '');
                },
            });
        }

    });

    function getReservation(){
        let not_closed_yet = $("#not_closed_yet").val();
        let base_url = $("#base_url_customer").val();
        let csrf_value_ = $("#csrf_value_").val();
        $.ajax({
            url: base_url + "authentication/getReservations",
            method: "POST",
            data: {
                csrf_name_: csrf_value_,
            },
            success: function (response) {
                response = JSON.parse(response);

                $("#reservation_modal").addClass("active");
                $(".pos__modal__overlay").fadeIn(200);
                $(".html_content").html(response.html_content_for_div);

                $(`#datatable111111`).DataTable({
                    'autoWidth'   : false,
                    'ordering'    : false,
                    'paging'    : false,
                    'bFilter'    : false,
                    dom: 'Blfrtip',
                    buttons: [
                        {
                            extend: "print",
                            text: '<i class="fa fa-print"></i> Print',
                            titleAttr: "print",
                        },
                        {
                            extend: "excelHtml5",
                            text: '<i class="fa fa-file-excel-o"></i> Excel',
                            titleAttr: "Excel",
                        },
                        {
                            extend: "csvHtml5",
                            text: '<i class="fa fa-file-text-o"></i> CSV',
                            titleAttr: "CSV",
                        },
                        {
                            extend: "pdfHtml5",
                            text: '<i class="fa fa-file-pdf-o"></i> PDF',
                            titleAttr: "PDF",
                        },
                    ]
                });


            },
            error: function () {
                alert("error");
            },
        });
    }
    $(document).on("click", ".reservation_list", function (e) {
        let title = $(this).attr('data-title');
        $(".title_custom").html(title);
        $("#register_close").hide();

        let status = true;
        if(!checkInternetConnection()){
            toastr.options = {
                positionClass:'toast-bottom-right'
            };
            let reservation_list_error = $("#reservation_list_error").val();
            status = false;
            toastr['error']((reservation_list_error), '');
        }

        if(status){
            getReservation();
        }

    });
    $(document).on("change", ".change_status_reservation", function (e) {
        let status = $(this).val();
        let id = $(this).find(':selected').attr('data-id');
        let base_url = $("#base_url_customer").val();
        $.ajax({
            url: base_url + "authentication/changeReservation",
            method: "POST",
            dataType:'json',
            data: {
                id:id,status:status,
            },
            success: function (response) {
                getReservation();
                toastr.options = {
                    positionClass:'toast-bottom-right'
                };
                toastr['success']((response.msg), '');
            },
            error: function () {
                alert("error");
            },
        });
    });
    $(document).on("click", ".remove_reservation_row", function (e) {
        let id = $(this).attr('data-id');
        let base_url = $("#base_url_customer").val();
        let warning = $("#warning").val();
        let a_error = $("#a_error").val();
        let ok = $("#ok").val();
        let cancel = $("#cancel").val();
        let are_you_sure = $("#are_you_sure").val();
        let this_action = $(this);
        swal(
            {
                title: warning + "!",
                text: are_you_sure,
                confirmButtonColor: "#3c8dbc",
                confirmButtonText: ok,
                showCancelButton: true,
            },
            function () {
                this_action.parent().parent().remove();
                $.ajax({
                    url: base_url + "authentication/removeReservation",
                    method: "POST",
                    dataType:'json',
                    data: {
                        id:id,
                    },
                    success: function (response) {
                        toastr.options = {
                            positionClass:'toast-bottom-right'
                        };
                        toastr['success']((response.msg), '');
                    },
                    error: function () {
                        alert("error");
                    },
                });
            }
        );
    });
});
