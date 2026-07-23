"use strict";
let base_url = $("#base_url_").val();
$(document).ready(function(){
  function getCurrentSalesFilters() {
    return {
      from_datetime: $("#from_datetime").val().trim(),
      to_datetime: $("#to_datetime").val().trim(),
      search_value: $("#datatable_filter input[type='search']").val() || ""
    };
  }

  function buildExportDailySalesUrl(format) {
    let filters = getCurrentSalesFilters();
    if (format) {
      filters.format = format;
    }
    let params = $.param(filters);
    return base_url + "Sale/exportDailySales" + (params ? "?" + params : "");
  }

  function openSalesExport(format) {
    let url = buildExportDailySalesUrl(format);
    if (format === "print" || format === "pdf") {
      window.open(url, "_blank");
      return;
    }
    window.location.href = url;
  }

  function copySalesExport() {
    $.getJSON(buildExportDailySalesUrl("json"), function (response) {
      let rows = response && response.data ? response.data : [];
      let text = rows.map(function (row) {
        return row.join("\t");
      }).join("\n");

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text);
        return;
      }

      let textarea = $("<textarea>").val(text).appendTo("body").select();
      document.execCommand("copy");
      textarea.remove();
    });
  }

  if ($.fn.datetimepicker) {
    $(".salesDateTimePicker").each(function () {
      $(this).datetimepicker({
        format: "YYYY-MM-DD HH:mm",
        useCurrent: false,
        sideBySide: true,
        toolbarPlacement: "bottom",
        showTodayButton: false,
        showClose: false,
        widgetParent: $(this).closest(".sale-date-time-picker-wrapper"),
        widgetPositioning: {
          horizontal: "left",
          vertical: "bottom"
        },
        icons: {
          time: "fa fa-clock",
          date: "fa fa-calendar",
          up: "fa fa-angle-up",
          down: "fa fa-angle-down",
          previous: "fa fa-angle-left",
          next: "fa fa-angle-right",
          today: "fa fa-calendar-check",
          clear: "fa fa-trash",
          close: "fa fa-xmark"
        }
      });
    });

    $("#from_datetime").on("dp.change", function (e) {
      $("#to_datetime").data("DateTimePicker").minDate(e.date || false);
    });

    $("#to_datetime").on("dp.change", function (e) {
      $("#from_datetime").data("DateTimePicker").maxDate(e.date || false);
    });

    $(".salesDateTimePicker").on("dp.show", function () {
      let picker = $(this).data("DateTimePicker");
      if (picker) {
        picker.widgetPositioning({
          horizontal: "left",
          vertical: "bottom"
        });
      }
    });
  }

  let salesTable = $("#datatable").DataTable({
   
  // The issue is that DataTables expects the backend to handle pagination based on sent parameters:
  // 'start' and 'length', which it sends with each request.
  // If the backend is not receiving these parameters (because they're not sent), it will return all data.
  // Solution: pass the DataTables paging parameters in the AJAX data function, so they're sent with the request.

      autoWidth: false,
      ordering: true,
      processing: true,
      serverSide: true, // Enable server-side processing, so DataTables will send paging/length/etc.
      order: [[0, "desc"]],
      lengthMenu: [10, 25, 50, 100],
      pageLength: 10,
      lengthChange: true,
      paging: true,
      searching: true,
      ordering: true,
      info: true,
      autoWidth: false,
      ordering: true,
      ajax: {
          url: base_url + "Sale/getAjaxData",
          type: "POST",
          dataType: "json",
          data: function(d) {
              let filters = getCurrentSalesFilters();
              return {
                  start: d.start,
                  length: d.length,
                  search: d.search,
                  search_value: filters.search_value,
                  draw: d.draw,
                  from_datetime: filters.from_datetime,
                  to_datetime: filters.to_datetime
              };
          }
      },
      columnDefs: [
          { orderable: true, targets: [ 5, 7, 8 ] }
      ],
      dom: '<"top-left-item col-sm-12 col-md-6"lf> <"top-right-item col-sm-12 col-md-6"B> t <"bottom-left-item col-sm-12 col-md-6 "i><"bottom-right-item col-sm-12 col-md-6 "p>',
      buttons:[
        {
          extend:    'print',
          text:      '<i class="fa-solid fa-print"></i> Print',
          titleAttr: 'print',
          action: function () {
            openSalesExport("print");
          }
        },
        {
            extend:    'copyHtml5',
            text:      '<i class="fa-solid fa-copy"></i> Copy',
            titleAttr: 'Copy',
            action: copySalesExport
        },
        {
            extend:    'excelHtml5',
            text:      '<i class="fa-solid fa-file-excel"></i> Excel',
            titleAttr: 'Excel',
            action: function () {
              openSalesExport("xlsx");
            }
        },
        {
            extend:    'csvHtml5',
            text:      '<i class="fa-solid fa-file-csv"></i> CSV',
            titleAttr: 'CSV',
            action: function () {
              openSalesExport("csv");
            }
        },
        {
            extend:    'pdfHtml5',
            text:      '<i class="fa-solid fa-file-pdf"></i> PDF',
            titleAttr: 'PDF',
            action: function () {
              openSalesExport("pdf");
            }
        }
    ],
      language: {
        paginate: {
          previous: "Previous",
          next: "Next",
        },
      },
  });

  $(document).on("click", "#filter_datetime_btn, #sale_search_btn", function () {
      salesTable.ajax.reload(null, true);
  });

  $(document).on("click", "#clear_datetime_btn", function () {
      $("#from_datetime").val("");
      $("#to_datetime").val("");
      if ($("#from_datetime").data("DateTimePicker")) {
        $("#from_datetime").data("DateTimePicker").clear();
      }
      if ($("#to_datetime").data("DateTimePicker")) {
        $("#to_datetime").data("DateTimePicker").clear();
      }
      salesTable.ajax.reload(null, true);
  });

  $(document).on("keypress", "#from_datetime, #to_datetime", function (e) {
      if (e.which === 13) {
        salesTable.ajax.reload(null, true);
      }
  });

  $(document).on("click", 'a[href*="Sale/exportDailySales"]', function (e) {
      e.preventDefault();
      window.location.href = buildExportDailySalesUrl("xlsx");
  });
});

 $("#change_date_sale_modal").datepicker({
   dateFormat: "yy-mm-dd",
   changeYear: true,
   changeMonth: true,
   autoclose: true,
   showMonthAfterYear: true,
   maxDate: 0,
 });

let warning = $("#warning").val();
let a_error = $("#a_error").val();
let ok = $("#ok").val();
let cancel = $("#cancel").val();

function viewInvoice(id) {
  let view_invoice = Number($("#view_invoice").val());
  
  if(view_invoice){
      let newWindow = open(
        base_url+"Sale/print_invoice/" + id,
          "Print Invoice",
          "width=450,height=550"
      );
      newWindow.focus();

      newWindow.onload = function () {
          newWindow.document.body.insertAdjacentHTML("afterbegin");
      };
  }else{
      let menu_not_permit_access = $("#menu_not_permit_access").val();
      swal({
          title: warning,
          text: menu_not_permit_access,
          confirmButtonText: ok,
          confirmButtonColor: '#3c8dbc'
      });
  }

}
let edit_return_id  = Number($("#edit_return_id").val());
let view_invoice = Number($("#view_invoice").val());
if(edit_return_id && view_invoice){
  let base_url = $("#base_url_").val();
    let newWindow = open(
      base_url+"Sale/print_invoice/" + edit_return_id,
        "Print Invoice",
        "width=450,height=550"
    );
    newWindow.focus();

    newWindow.onload = function () {
        newWindow.document.body.insertAdjacentHTML("afterbegin");
    };
}

function change_date(id) {
    let change_date = Number($("#change_date").val());
    if(change_date){
        $("#change_date_sale_modal").val("");
        $("#sale_id_hidden").val(id);
        $("#change_date_modal").modal("show");
    }else{
        let menu_not_permit_access = $("#menu_not_permit_access").val();
        swal({
            title: warning,
            text: menu_not_permit_access,
            confirmButtonText: ok,
            confirmButtonColor: '#3c8dbc'
        });
    }


  // $('#myModal').modal('hide');
  // alert(id);
}

$(document).on("click", ".change_delivery_details", function () {
   let id = $(this).attr("data-id");
   let status = $(this).attr("data-status");

    let change_delivery_address = Number($("#change_delivery_address").val());
    if(change_delivery_address){
        $("#change_date_sale_modal_d").val("");
        $("#sale_id_hidden_d").val(id);
        $("#status").val(status).change();
        $("#change_delivery_address_update").modal("show");
    }else{
        let menu_not_permit_access = $("#menu_not_permit_access").val();
        swal({
            title: warning,
            text: menu_not_permit_access,
            confirmButtonText: ok,
            confirmButtonColor: '#3c8dbc'
        });
    }

});

$(document).on("click", "#close_change_date_modal", function () {
  $("#change_date_sale_modal").val("");
  $("#sale_id_hidden").val("");
});
$(document).on("click", "#save_change_date", function () {
  let change_date = $("#change_date_sale_modal").val();
  let sale_id = $("#sale_id_hidden").val();
  let csrf_name_ = $("#csrf_name_").val();
  let csrf_value_ = $("#csrf_value_").val();
  $.ajax({
    url: base_url + "Sale/change_date_of_a_sale_ajax",
    method: "POST",
    data: {
      sale_id: sale_id,
      change_date: change_date,
      csrf_name_: csrf_value_,
    },
    success: function (response) {
      $("#change_date_sale_modal").val("");
      $("#sale_id_hidden").val("");
      $("#change_date_modal").modal("hide");
    },
    error: function () {
      alert("error");
    },
  });
});
$(document).on("click", "#save_change_status", function () {
  let status = $("#status").val();
  let sale_id = $("#sale_id_hidden_d").val();
  let csrf_name_ = $("#csrf_name_").val();
  let csrf_value_ = $("#csrf_value_").val();
  $.ajax({
    url: base_url + "Sale/change_status_of_a_sale_ajax",
    method: "POST",
    data: {
      sale_id: sale_id,
      status: status,
      csrf_name_: csrf_value_,
    },
    success: function (response) {
        $("#change_delivery_address_update").modal("hide");
        let status_changed_successfully = $("#status_changed_successfully").val();
        swal(
            {
                title: 'Alert',
                text: status_changed_successfully,
                confirmButtonColor: "#3c8dbc",
                confirmButtonText: "OK",
                showCancelButton: false,
            },
            function () {
                location.reload();
            }
        );
    },
    error: function () {
      alert("error");
    },
  });
});

$(document).on("click", ".getDetailsRefund", function () {
    let id = $(this).attr('data-id');
    $("#refund_modal").modal('show');
    $.ajax({
        url: base_url + "Sale/getDetailsRefund",
        method: "POST",
        dataType:'json',
        data: {
            sale_id: id,
            csrf_name_: csrf_value_,
        },
        success: function (response) {
            $(".refund_date").html(response.refund_date_time);
            $("#sale_refund_cart").html(response.html);
        },
        error: function (response) {
           
        },
    });

});
