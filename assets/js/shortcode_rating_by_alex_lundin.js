function setCookie(name, value) {
    document.cookie = name + "=" + value;
}

function getCookie(name) {
    const r = document.cookie.match("(^|;) ?" + name + "=([^;]*)(;|$)");
    if (r) return r[2];
    else return "";
}

function Change(elem) {
    let id = elem.getAttribute('id');
    const data = {
        action: 'my_action',
        id: id,
    };
    if (getCookie(id) === 'clicked') {
            alert("You’ve already voted");
    } else {
        // 'ajaxurl' не определена во фронте, поэтому мы добавили её аналог с помощью wp_localize_script()
        jQuery.post(myajax.url, data, function (response) {
            let count = elem.querySelector('.count');
            count.innerHTML = response;
            // jQuery(elem).prop("disabled", "true");
            setCookie(id, 'clicked');
            // console.log(getCookie(id));
        });
    }
}

