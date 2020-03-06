function Bliskapaczka()
{
}

Bliskapaczka.showMap = function (operators, googleMapApiKey, testMode) {
    console.log('Show map -  test');
    aboutPoint = document.getElementById('bpWidget_aboutPoint');
    aboutPoint.style.display = 'none';

    bpWidget = document.getElementById('bpWidget');
    bpWidget.style.display = 'block';

    Bliskapaczka.updateSelectedCarrier();

    BPWidget.init(
        bpWidget,
        {
            googleMapApiKey: googleMapApiKey,
            callback: function (data) {
                posCodeForm = document.getElementById('bliskapaczka_posCode')
                posOperatorForm = document.getElementById('bliskapaczka_posOperator')

                posCodeForm.value = data.code;
                posOperatorForm.value = data.operator;

                Bliskapaczka.pointSelected(data, operators);
                Bliskapaczka.updatePriceForMap();
            },
            operators: operators,
            posType: 'DELIVERY',
            testMode: testMode
        }
    );
}

Bliskapaczka.pointSelected = function (data, operators) {
    Bliskapaczka.updatePrice(data.operator, operators);

    bpWidget = document.getElementById('bpWidget');
    bpWidget.style.display = 'none';

    aboutPoint = document.getElementById('bpWidget_aboutPoint');
    aboutPoint.style.display = 'block';

    posDataBlock = document.getElementById('bpWidget_aboutPoint_posData');

    posDataBlock.innerHTML =  data.operator + '</br>'
      + ((data.description) ? data.description + '</br>': '')
      + data.street + '</br>'
      + ((data.postalCode) ? data.postalCode + ' ': '') + data.city
}

Bliskapaczka.updatePrice = function (posOperator, operators) {
    item = Bliskapaczka.getTableRow();
    if (item) {
        priceDiv = item.find('.delivery_option_price').first();

        for (var i = 0; i < operators.length; i++) {
            if (operators[i].operator == posOperator) {
                price = operators[i].price;
            }
        }

        priceDiv.html(priceDiv.text().replace(/([\d\.,]{2,})/, price));
    }
}

Bliskapaczka.updateSelectedCarrier = function () {
    item = Bliskapaczka.getTableRow();

    if (item) {
        input = item.find('input.delivery_option_radio').first();
        // Magic because in themes/default-bootstrap/js/order-carrier.js is defined event onchanged input
        input.click();

        items = jQuery('td.delivery_option_radio span')
        items.each(function (index, element) {
            jQuery(this).removeClass('checked');
        });
        item.find('td.delivery_option_radio span').first().addClass('checked');
    }

    radiForCourier = jQuery.find('.bliskapaczka_courier_wrapper input[type="radio"]')
    jQuery(radiForCourier).prop('checked', false);
    jQuery('.bliskapaczka_courier_item_wrapper').removeClass('checked')
}

Bliskapaczka.getTableRow = function () {
    item = null;

    posCodeImput = jQuery.find('#bliskapaczka_posCode')
    itemList = jQuery(posCodeImput).closest('.delivery_option')

    if (itemList.length > 0) {
        item = itemList
    }

    return item;
}

Bliskapaczka.selectPoint = function () {
    item = Bliskapaczka.getTableRow();

    if (item) {
        input = item.find('input.delivery_option_radio').first();
        if (!input.is(':checked')) {
            return true;
        }
    } else {
        return true;
    }

    posCode = jQuery('#bliskapaczka_posCode').val()
    posOperator = jQuery('#bliskapaczka_posOperator').val()
    if (typeof msg_bliskapaczka_select_point != 'undefined' && (!posCode || !posOperator)) {
        if (!!$.prototype.fancybox) {
            $.fancybox.open(
                [
                  {
                        type: 'inline',
                        autoScale: true,
                        minHeight: 30,
                        content: '<p class="fancybox-error">' + msg_bliskapaczka_select_point + '</p>'
                  }],
                {
                    padding: 0
                }
            );
        } else {
            alert(msg_bliskapaczka_select_point);
        }
    } else {
        return true;
    }
    return false;
}

Bliskapaczka.selectCourier = function (button) {
    item = jQuery(button).closest('.delivery_option')

    if (item) {
        input = item.find('input.delivery_option_radio').first();
        // Magic because in themes/default-bootstrap/js/order-carrier.js is defined event onchanged input
        input.click();

        items = jQuery('td.delivery_option_radio span')
        items.each(function (index, element) {
            jQuery(this).removeClass('checked');
        });
        item.find('td.delivery_option_radio span').first().addClass('checked');
    }

    jQuery('.bliskapaczka_courier_item_wrapper').removeClass('checked')
    jQuery(button).addClass('checked')

    jQuery(button).find('input[name="bliskapaczka_courier_posOperator"]').prop('checked', true)

    posCodeForm = document.getElementById('bliskapaczka_posCode')
    posOperatorForm = document.getElementById('bliskapaczka_posOperator')

    posCodeForm.value = '';
    posOperatorForm.value = '';
}

Bliskapaczka.checkFirstCourier = function () {
    if ($('.bliskapaczka_courier_item_wrapper.checked').length === 0) {
        if ($('.bliskapaczka_courier_item_wrapper').length !== 0) {
            $($('.bliskapaczka_courier_item_wrapper')[0]).addClass('checked');
            $('#bliskapaczka_posOperator').val($($('.bliskapaczka_courier_item_wrapper')[0])
              .attr('data-operator'));
        }
    }
}

Bliskapaczka.updatePriceForCourier = function () {
    var courierPrice = $('.bliskapaczka_courier_item_wrapper.checked >' +
      ' .bliskapaczka_courier_item > ' +
      '.bliskapaczka_courier_item_price ' +
      'span').text().split(' ')[0];
    var localPrice = $('.delivery_option[data-name="'+ id_carrier_bliskapaczka_courier +'"] div.delivery_option_price')
      .text().trim();
    $('.delivery_option[data-name="'+ id_carrier_bliskapaczka_courier +'"] div.delivery_option_price')
      .text(courierPrice + ' ' + localPrice.split(/ (.*)/)[1]);
}

Bliskapaczka.updatePriceForMap = function () {
    var isCod = $('#bliskapaczka_isCod');
    var operatorName = $('#bliskapaczka_posOperator');
    var price = 0;
    isCod = !!JSON.parse(String(isCod.val()).toLowerCase());
    for (var i = 0; i < operators.length; i++) {
        if (operators[i].operator === operatorName.val()) {
            price = operators[i].price;
            if (isCod === true) {
                price = price + operators[i].cod;
            }
        }
    }
    console.log(price);
}
$(document).ready(function () {
    
    if (typeof is_cod !== "undefined") {
        var hookPayment = $('#HOOK_PAYMENT');
        var childrens = hookPayment.children();
        childrens.each(function (i, e) {
            var methodName = $(e).children().children().children().attr('class');
            if (is_cod == 1) {
                if (methodName !== 'cash') {
                    $(e).hide();
                }
            } else {
                if (methodName == 'cash') {
                    $(e).hide();
                }
            }
        })
    }
    operators = JSON.parse(operators);
    if (!!$.prototype.fancybox) {
        $("a.iframe").fancybox({
            'type': 'iframe',
            'width': 600,
            'height': 600
        });
    }
    var isCod = document.getElementById('bliskapaczka_isCod');
    $(document).on('submit', 'form[name=carrier_area]', function () {
        return Bliskapaczka.selectPoint();
    });

    $(document).on('click', '.bliskapaczka_courier_item_wrapper', function () {
        Bliskapaczka.selectCourier(this);
        return  Bliskapaczka.updatePriceForCourier();
    });

    $(document).on('click', 'input[id="cod"]', function () {
        var prices = $('.bliskapaczka_courier_item_price');
        for (var i = 0; i < prices.length; i++) {
            var cod = parseFloat($(prices[i]).data('cod'));
            var price = parseFloat($(prices[i]).data('price'));
            if ($(this).is(':checked') === true) {
                $($(prices[i]).children()[0]).text(price + cod +' zł');
                isCod.value = '1';
            } else {
                $($(prices[i]).children()[0]).text(price + ' zł');
                isCod.value = '0';
            }
        }
        Bliskapaczka.updatePriceForCourier();
    })

    $(document).on('click', '.checkbox-block', function () {
        var elements = $('.bp-filter-show-price');
        $.each(elements, function (i, val) {
            var op = $(val).find('input[type=checkbox]');
            var opName = $(op).attr('name');
            for (var j = 0; j < operators.length; j++) {
                if (operators[j].operator === opName.toUpperCase()) {
                    var price = operators[j].price;
                    var cod = operators[j].cod;
                    var span = $(val).find('span');
                    if ($('#BPFilterCOD').is(':checked') === true) {
                        $(span).text(price + cod + ' zł');
                        isCod.value = '1';
                    } else {
                        $(span).text(price + ' zł');
                        isCod.value = '0';
                    }
                }
            }
        })
    })
    $('.delivery_option_radio').on('click', function () {
        Bliskapaczka.updatePriceForCourier();
        if ($(this).attr('data-name') === id_carrier_bliskapaczka_courier) {
            Bliskapaczka.checkFirstCourier();
        } else {
            $('#bliskapaczka_posOperator').val();
        }
    })

    Bliskapaczka.checkFirstCourier();
});
