// +----------------------------------------------------------------------
// | Static Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-static
// | github 代码仓库：https://github.com/zoujingli/think-plugs-static
// +----------------------------------------------------------------------

$(function () {

    window.$body = $('body');
    let loginI18n = window.taLoginI18n || {};

    function t(key, fallback) {
        return typeof loginI18n[key] === 'string' && loginI18n[key].length > 0 ? loginI18n[key] : fallback;
    }

    /*! 登录界面背景切换 */
    $('[data-bg-transition]').each(function (i, el) {
        el.idx = 0, el.imgs = [], el.SetBackImage = function (css) {
            window.setTimeout(function () {
                $(el).removeClass(el.imgs.join(' ')).addClass(css)
            }, 1000) && $body.removeClass(el.imgs.join(' ')).addClass(css)
        }, el.lazy = window.setInterval(function () {
            el.imgs.length > 0 && el.SetBackImage(el.imgs[++el.idx] || el.imgs[el.idx = 0]);
        }, 5000) && el.dataset.bgTransition.split(',').forEach(function (image) {
            layui.img(image, function (img, cssid, style) {
                style = document.createElement('style'), cssid = 'LoginBackImage' + (el.imgs.length + 1);
                style.innerHTML = '.' + cssid + '{background-image:url("' + encodeURI(image) + '")!important}';
                document.head.appendChild(style) && el.imgs.push(cssid);
            });
        });
    });

    function decodeBase64ToArrayBuffer(value) {
        let binary = atob(value), bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes.buffer;
    }

    function encodeArrayBufferToBase64(buffer) {
        let bytes = new Uint8Array(buffer), binary = '';
        for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
        return btoa(binary);
    }

    async function encryptPassword(form, password) {
        let publicKey = form.dataset.loginPasswordKey || '';
        if (!publicKey || !window.crypto || !window.crypto.subtle || typeof TextEncoder === 'undefined' || window.isSecureContext === false) {
            return {mode: 'plain', value: password};
        }
        try {
            // PHP openssl_private_decrypt with OAEP padding only interoperates with the SHA-1 variant here.
            let key = await window.crypto.subtle.importKey(
                'spki',
                decodeBase64ToArrayBuffer(publicKey),
                {name: 'RSA-OAEP', hash: 'SHA-1'},
                false,
                ['encrypt']
            );
            let result = await window.crypto.subtle.encrypt({name: 'RSA-OAEP'}, key, new TextEncoder().encode(password));
            return {mode: 'rsa', value: encodeArrayBufferToBase64(result)};
        } catch (e) {
            return {mode: 'plain', value: password};
        }
    }

    function reloadLoginPage() {
        try {
            let url = new URL(location.href);
            url.hash = '';
            url.searchParams.set('_login_reload', String(Date.now()));
            location.replace(url.toString());
        } catch (e) {
            location.href = location.pathname + '?_login_reload=' + Date.now();
        }
    }

    function createLoginSlider(form) {
        let $form = $(form), $row = $form.find('.verify'), $panel = $form.find('[data-login-slider-panel]');
        if ($panel.length < 1) {
            return {
                syncError: $.noop,
                ensureVerified: function () {
                    return true;
                }
            };
        }

        let $bg = $panel.find('[data-slider-bg]');
        let $piece = $panel.find('[data-slider-piece]');
        let $stage = $panel.find('.slider-stage');
        let $track = $panel.find('[data-slider-track]');
        let $message = $panel.find('[data-slider-message]');
        let $status = $panel.find('[data-slider-status]');
        let $handle = $panel.find('[data-slider-handle]');
        let $refresh = $panel.find('[data-slider-refresh]');
        let $uniqid = $form.find('[name="uniqid"]');
        let $verify = $form.find('[name="verify"]');
        let $mode = $form.find('[name="password_mode"]');
        let request = form.dataset.loginSlider || '';
        let check = form.dataset.loginCheck || '';
        let state = {
            bgWidth: 0,
            currentLeft: 0,
            dragging: false,
            loaded: false,
            maxLeft: 0,
            originX: 0,
            pieceWidth: 100,
            sourceWidth: 600,
            startLeft: 0,
            verified: false,
            working: false,
        };

        function setStatus(text, type) {
            $panel.removeClass('is-error is-success');
            type && $panel.addClass(type);
            $message.text(text);
            $status.text(text);
        }

        function setPosition(left) {
            state.currentLeft = Math.max(0, Math.min(left, state.maxLeft));
            $handle.css('left', state.currentLeft + 'px');
            $track.css('width', (state.currentLeft + $handle.outerWidth()) + 'px');
            $piece.css('left', state.currentLeft + 'px');
        }

        function recalculate() {
            state.bgWidth = $stage.innerWidth();
            state.maxLeft = Math.max(state.bgWidth - $handle.outerWidth(), 0);
            $piece.css('width', (state.pieceWidth / state.sourceWidth * 100) + '%');
            setPosition(state.currentLeft);
        }

        function resetChallenge() {
            state.verified = false;
            state.working = false;
            $uniqid.val('');
            $verify.val('');
            setPosition(0);
            setStatus(t('dragToVerify', '请按住滑块，拖动完成验证'));
        }

        function loadChallenge() {
            if (request.length < 5) return $.msg.tips(t('sliderApiMissing', '请设置滑块验证接口'));
            resetChallenge();
            $.form.load(request, {token: form.dataset.loginToken || ''}, 'post', function (ret) {
                if (parseInt(ret.code) !== 1) {
                    ret.data && ret.data.reload && reloadLoginPage();
                    return false;
                }
                state.sourceWidth = parseInt(ret.data.width || 600);
                state.pieceWidth = parseInt(ret.data.piece_width || 100);
                state.loaded = true;
                $uniqid.val(ret.data.uniqid || '');
                $bg.attr('src', ret.data.bgimg || '');
                $piece.attr('src', ret.data.water || '');
                (window.requestAnimationFrame || window.setTimeout)(recalculate, 0);
                setStatus(t('dragToVerify', '请按住滑块，拖动完成验证'));
                return false;
            }, false);
        }

        function showChallenge(refresh) {
            $row.removeClass('layui-hide');
            if (refresh || !$uniqid.val()) loadChallenge();
        }

        function verifyCurrentPosition() {
            if (state.working || !$uniqid.val() || check.length < 5) return;
            state.working = true;
            setStatus(t('verifying', '正在校验...'));
            $.form.load(check, {
                uniqid: $uniqid.val(),
                verify: Math.round(state.currentLeft * state.sourceWidth / Math.max(state.bgWidth, 1))
            }, 'post', function (ret) {
                state.working = false;
                let value = Math.round(state.currentLeft * state.sourceWidth / Math.max(state.bgWidth, 1));
                let result = parseInt(ret.data && ret.data.state || -1);
                if (result === 1) {
                    state.verified = true;
                    $verify.val(String(value));
                    $panel.removeClass('is-error').addClass('is-success');
                    $message.text(t('verifyPassedContinue', '验证通过，请继续登录'));
                    $status.text(t('sliderVerified', '滑块验证通过'));
                } else if (result === 0) {
                    state.verified = false;
                    $verify.val('');
                    $panel.removeClass('is-success').addClass('is-error');
                    $message.text(t('wrongPositionRetry', '位置不正确，请重试'));
                    $status.text(t('wrongPositionRetry', '位置不正确，请重试'));
                    window.setTimeout(function () {
                        if (!state.verified) {
                            $panel.removeClass('is-error');
                            setPosition(0);
                            setStatus(t('dragToVerify', '请按住滑块，拖动完成验证'));
                        }
                    }, 500);
                } else {
                    loadChallenge();
                }
                return false;
            }, false);
        }

        function getPoint(event) {
            return event.touches && event.touches[0] ? event.touches[0] : event;
        }

        function startDrag(event) {
            if (state.working || state.verified || !$uniqid.val()) return;
            let point = getPoint(event);
            state.dragging = true;
            state.originX = point.clientX;
            state.startLeft = state.currentLeft;
            $handle.addClass('is-active');
            event.preventDefault();
        }

        function moveDrag(event) {
            if (!state.dragging) return;
            let point = getPoint(event);
            setPosition(state.startLeft + point.clientX - state.originX);
            event.preventDefault();
        }

        function endDrag() {
            if (!state.dragging) return;
            state.dragging = false;
            $handle.removeClass('is-active');
            verifyCurrentPosition();
        }

        $bg.on('load', recalculate);
        $(window).on('resize', recalculate);
        $handle.on('mousedown touchstart', startDrag);
        $(document).on('mousemove touchmove', moveDrag);
        $(document).on('mouseup touchend touchcancel', endDrag);
        $refresh.on('click', function () {
            showChallenge(true);
        });

        return {
            syncError: function (data) {
                if (data && data.need_verify) showChallenge(!!data.refresh_verify);
            },
            ensureVerified: function () {
                if (!$row.hasClass('layui-hide') && !state.verified) {
                    $.msg.tips(t('needVerifyFirst', '请先完成滑块验证'));
                    return false;
                }
                $mode.val('plain');
                return true;
            },
            setPasswordMode: function (mode) {
                $mode.val(mode);
            }
        };
    }

    /*! 后台登录提交处理 */
    $body.find('form[data-login-form]').each(function (idx, form) {
        let slider = createLoginSlider(form);
        $(form).vali(function (data) {
            if (!slider.ensureVerified()) return false;
            encryptPassword(form, data.password || '').then(function (cipher) {
                let payload = $.extend({}, data, {password: cipher.value, password_mode: cipher.mode});
                slider.setPasswordMode(cipher.mode);
                $.form.load(location.href, payload, "post", function (ret) {
                    if (parseInt(ret.code) !== 1) {
                        if (ret.data && ret.data.reload) {
                            reloadLoginPage();
                            return false;
                        }
                        slider.syncError(ret.data || {});
                        return false;
                    }
                }, null, null, 'false');
            });
        });
    });

});
