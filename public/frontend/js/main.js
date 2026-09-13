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
        var linguagemSelect2 = {
            errorLoading: function () {
                return 'Não foi possível carregar os resultados.';
            },
            inputTooLong: function (args) {
                var excedente = args.input.length - args.maximum;
                return 'Apague ' + excedente + (excedente === 1 ? ' caractere' : ' caracteres');
            },
            inputTooShort: function (args) {
                var restante = args.minimum - args.input.length;
                return 'Digite ' + restante + (restante === 1 ? ' caractere' : ' caracteres');
            },
            loadingMore: function () {
                return 'Carregando mais resultados…';
            },
            maximumSelected: function (args) {
                return 'Você só pode selecionar ' + args.maximum + (args.maximum === 1 ? ' item' : ' itens');
            },
            noResults: function () {
                return 'Nenhum resultado encontrado';
            },
            searching: function () {
                return 'Buscando…';
            },
            removeAllItems: function () {
                return 'Remover todos os itens';
            }
        };

        $('.select_2').select2({
            language: linguagemSelect2,
            width: 'style'
        });

        $('.select_2').on('select2:open', function () {
            var campo = document.querySelector('.select2-container--open .select2-search__field');
            if (campo) {
                campo.setAttribute('aria-label', 'Buscar');
                campo.setAttribute('placeholder', 'Buscar');
            }
        });
    }

    $('#formulario-adicionar-carrinho .wsus__product_quantity .plus').on('click', function () {
        var input = $('#formulario-adicionar-carrinho input[name="quantidade"]');
        var value = parseInt(input.val(), 10) || 1;
        input.val(value + 1);
    });

    $('#formulario-adicionar-carrinho .wsus__product_quantity .minus').on('click', function () {
        var input = $('#formulario-adicionar-carrinho input[name="quantidade"]');
        var value = parseInt(input.val(), 10) || 1;
        if (value > 1) {
            input.val(value - 1);
        }
    });

    var sincronizarQtdCarrinhoPendente = function (form) {
        var input = form.find('input[name="quantidade"]');
        var pendente = form.find('.qtd-carrinho-pendente');
        var salvo = parseInt(input.data('salvo'), 10);
        var atual = parseInt(input.val(), 10);
        if (atual !== salvo) {
            pendente.removeClass('d-none');
        } else {
            pendente.addClass('d-none');
        }
    };

    $('.formulario-qtd-carrinho .plus').on('click', function () {
        var form = $(this).closest('.formulario-qtd-carrinho');
        var input = form.find('input[name="quantidade"]');
        var value = parseInt(input.val(), 10) || 1;
        input.val(value + 1);
        sincronizarQtdCarrinhoPendente(form);
    });

    $('.formulario-qtd-carrinho .minus').on('click', function () {
        var form = $(this).closest('.formulario-qtd-carrinho');
        var input = form.find('input[name="quantidade"]');
        var value = parseInt(input.val(), 10) || 1;
        if (value > 1) {
            input.val(value - 1);
        }
        sincronizarQtdCarrinhoPendente(form);
    });

    $('.formulario-qtd-carrinho input[name="quantidade"]').on('input', function () {
        sincronizarQtdCarrinhoPendente($(this).closest('.formulario-qtd-carrinho'));
    });

});
