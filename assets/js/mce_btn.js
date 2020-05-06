(function() {
    tinymce.create("tinymce.plugins.btn_rating", {
        init : function(ed) {
            ed.addButton("btn_rating", {
                text: 'Likes',
                onclick: function() {
                    setTimeout(()=>jQuery('#rating_search').focus(), 500);
                    jQuery(this).magnificPopup({
                        items: {
                            src: '#popup_rating',
                            type: 'inline',
                            alignTop: true,

                        },
                    }).magnificPopup('open');
                },

            });
            let elements = document.querySelectorAll('.select_rating');
            console.log(elements.length);
            Array.from(elements).forEach(function (element) {
                element.addEventListener('click', function (e) {
                    let elem = e.target;
                    let id = elem.parentNode.id;
                    let return_text = '[rating id=' + id + ']';
                    ed.execCommand("mceInsertContent", 0, return_text);
                    document.querySelector('#rating_search').value= '';
                    jQuery.magnificPopup.close();
                })
            });
            return false;
        },

        createControl : function(n, cm) {
            return null;
        }
    });
    tinymce.PluginManager.add("btn_rating", tinymce.plugins.btn_rating);

    document.querySelector('#rating_search').oninput= function () {
        var val = this.value.trim();
        var rating_items = document.querySelectorAll('#popup_rating tbody .tb_second');

        if (val != '') {
            rating_items.forEach(function (item) {
                let itemLow = item.innerHTML.toLowerCase();
                if (itemLow.search(val) == -1)
                {
                    item.parentNode.classList.add('hide');
                    item.classList.remove('vis');
                }else{
                    item.parentNode.classList.remove('hide');
                    item.classList.add('vis');
                }
            });
        }else{
            rating_items.forEach(function (item) {
                item.parentNode.classList.remove('hide');
                item.classList.add('vis');

            });
        }
        let wordForm = function(num,word){
            let cases = [2, 0, 1, 1, 1, 2];
            return word[ (num%100>4 && num%100<20)? 2 : cases[(num%10<5)?num%10:5] ];
        }

        let count = document.querySelectorAll('#popup_rating .vis').length;
        let text = wordForm(count, ['Found ', 'Found ', 'Found '])+ count + wordForm(count, [' rating',' rating',' rating',]);
        jQuery('#popup_rating .small-right').text(text);
        if(count == 0){
            jQuery('#popup_rating table').fadeOut();
        }else{
            jQuery('#popup_rating table').fadeIn();
        }
    }

})();
