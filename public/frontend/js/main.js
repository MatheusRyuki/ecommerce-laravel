$(function () {

    "use strict";

    if ($.fn.slick) {
        var $forSlider = $('.slider-forFive').not('.slick-initialized');
        var $navSlider = $('.slider-navFive').not('.slick-initialized');

        if ($forSlider.length) {
            var navCount = $navSlider.children().length;
            var forSettings = {
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: true,
                nextArrow: '<i class="fas fa-angle-right nextArrow"></i>',
                prevArrow: '<i class="fas fa-angle-left prevArrow"></i>',
                adaptiveHeight: false,
                responsive: [
                    {
                        breakpoint: 576,
                        settings: {
                            arrows: false,
                        }
                    }
                ]
            };

            if (navCount > 0) {
                forSettings.asNavFor = '.slider-navFive';
            }

            $forSlider.slick(forSettings);
        }

        if ($navSlider.length && $navSlider.children().length > 0) {
            var thumbCount = $navSlider.children().length;
            $navSlider.slick({
                slidesToShow: Math.min(5, thumbCount),
                slidesToScroll: 1,
                asNavFor: '.slider-forFive',
                arrows: false,
                dots: false,
                centerMode: thumbCount > 1,
                focusOnSelect: true,
                centerPadding: 0,
                responsive: [
                    {
                        breakpoint: 576,
                        settings: {
                            arrows: false,
                            slidesToShow: Math.min(3, thumbCount),
                        }
                    }
                ]
            });
        }
    }

    if ($.fn.select2) {
        $('.select_2').select2();
    }

    $('#add-to-cart-form .wsus__product_quantity .plus').on('click', function () {
        var input = $('#add-to-cart-form input[name="quantity"]');
        var value = parseInt(input.val(), 10) || 1;
        input.val(value + 1);
    });

    $('#add-to-cart-form .wsus__product_quantity .minus').on('click', function () {
        var input = $('#add-to-cart-form input[name="quantity"]');
        var value = parseInt(input.val(), 10) || 1;
        if (value > 1) {
            input.val(value - 1);
        }
    });

    var syncCartQtyPending = function (form) {
        var input = form.find('input[name="quantity"]');
        var pending = form.find('.cart-qty-pending');
        var saved = parseInt(input.data('saved'), 10);
        var current = parseInt(input.val(), 10);
        if (current !== saved) {
            pending.removeClass('d-none');
        } else {
            pending.addClass('d-none');
        }
    };

    $('.cart-qty-form .plus').on('click', function () {
        var form = $(this).closest('.cart-qty-form');
        var input = form.find('input[name="quantity"]');
        var value = parseInt(input.val(), 10) || 1;
        input.val(value + 1);
        syncCartQtyPending(form);
    });

    $('.cart-qty-form .minus').on('click', function () {
        var form = $(this).closest('.cart-qty-form');
        var input = form.find('input[name="quantity"]');
        var value = parseInt(input.val(), 10) || 1;
        if (value > 1) {
            input.val(value - 1);
        }
        syncCartQtyPending(form);
    });

    $('.cart-qty-form input[name="quantity"]').on('input', function () {
        syncCartQtyPending($(this).closest('.cart-qty-form'));
    });

});
