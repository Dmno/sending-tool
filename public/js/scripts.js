$checkboxes = $('.deleteCheck');
$serverCheckboxes = $('.serverDeleteCheck');
let checkArray = [];
let serverCheckArray = [];

$checkboxes.change(function(){
    let countCheckedCheckboxes = $checkboxes.filter(':checked').length;
    let element = this.id;

    if ($('#' + element).is(":checked")) {
        checkArray.push(this.id);
    } else {
        for (var i=checkArray.length-1; i>=0; i--) {
            if (checkArray[i] === element) {
                checkArray.splice(i, 1);
            }
        }
    }

    if (countCheckedCheckboxes !== 0) {
        $('#checkedButton').prop('disabled', false);
    } else {
        $('#checkedButton').prop('disabled', true);
    }
});

$serverCheckboxes.change(function(){
    let countCheckedCheckboxes = $serverCheckboxes.filter(':checked').length;
    let element = this.id;

    if ($('#' + element).is(":checked")) {
        serverCheckArray.push(this.id);
    } else {
        for (var i=serverCheckArray.length-1; i>=0; i--) {
            if (serverCheckArray[i] === element) {
                serverCheckArray.splice(i, 1);
            }
        }
    }

    if (countCheckedCheckboxes !== 0) {
        $('#serverCheckedButton').prop('disabled', false);
    } else {
        $('#serverCheckedButton').prop('disabled', true);
    }
});

$('#checkedButton').click(function () {
    let link = "remove/bytask";
    ajaxRequest(checkArray, link);
});

$('#serverCheckedButton').click(function () {
    let link = "remove/server";
    ajaxRequest(serverCheckArray, link);
});

function ajaxRequest(array, link) {
    let url = window.location.href;
    let batchId = url.substring(url.lastIndexOf('/') + 1);
    $('#loading').show();

    $.ajax({
        type: "POST",
        url: link,
        data: {
            "ids": array,
            "batch": batchId
        },
        dataType: "json",
        success: function(response) {
            location.href = url;
        }
    });
}

$('.goBackToBatches, .batchDeleteButton').click(function() {
    let batch = this.id;
    sessionStorage.setItem('batch', batch);
});