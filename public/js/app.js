$(function () {
    // Every AJAX request carries the Laravel CSRF token
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ---------- small helpers ----------
    const money = n => '₱' + Number(n).toLocaleString('en-PH');

    function debounce(fn, wait) {
        let timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, arguments), wait);
        };
    }

    function toast(message, type = 'success') {
        const $t = $(`<div class="toast text-bg-${type} border-0"><div class="toast-body"></div></div>`);
        $t.find('.toast-body').text(message);
        $('#toasts').append($t);
        new bootstrap.Toast($t[0], { delay: 3000 }).show();
    }

    function showError(xhr) {
        const json = xhr.responseJSON || {};
        toast(json.errors ? Object.values(json.errors)[0][0] : (json.message || 'Something went wrong.'), 'danger');
    }

    // Clear timers created by page features (important after AJAX nav)
    let chatTimer = null;
    function clearPageTimers() {
        if (chatTimer) {
            clearInterval(chatTimer);
            chatTimer = null;
        }
    }

    // ---------- 1. Bookmark (heart) button ----------
    $(document).on('click', '.btn-bookmark', function (e) {
        e.preventDefault();
        const $btn = $(this);

        $.post($btn.data('url')).done(res => {
            $btn.find('i').toggleClass('bi-heart', !res.bookmarked).toggleClass('bi-heart-fill', res.bookmarked);
            $btn.find('.label').text(res.bookmarked ? 'Bookmarked' : 'Bookmark');

            if ($btn.data('remove') && !res.bookmarked) {
                $btn.closest('.unit-card').fadeOut(200, function () { $(this).remove(); });
            }
        }).fail(showError);
    });

    // ---------- 2. Forms that submit through AJAX ----------
    $(document).on('submit', '.ajax-form', function (e) {
        e.preventDefault();

        $.ajax({
            url: this.action,
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false
        })
            .done(res => {
                toast(res.message || 'Saved.');
                setTimeout(() => {
                    if (res.redirect) {
                        // prefer AJAX page load when staying in-app
                        loadPage(res.redirect, true);
                    } else {
                        loadPage(window.location.href, false);
                    }
                }, 500);
            })
            .fail(showError);
    });

    // ---------- 3. Unit photos ----------
    $(document).on('click', '.thumb', function () {
        $('#mainPhoto').attr('src', $(this).data('src'));
    });

    // ---------- 7. Host and Admin btn-post ----------
    $(document).on('click', '.btn-post', function () {
        const $btn = $(this);
        const data = {};

        if ($btn.data('confirm') && !confirm($btn.data('confirm'))) return;

        if ($btn.data('prompt')) {
            const text = prompt($btn.data('prompt'));
            if (text === null) return;
            data[$btn.data('field') || 'notes'] = text;
        }

        $btn.prop('disabled', true);
        $.post($btn.data('url'), data)
            .done(res => {
                toast(res.message || 'Done.');
                setTimeout(() => {
                    if (res.redirect) {
                        loadPage(res.redirect, true);
                    } else {
                        loadPage(window.location.href, false);
                    }
                }, 500);
            })
            .fail(xhr => {
                $btn.prop('disabled', false);
                showError(xhr);
            });
    });

    // ---------- 10. Unit form: rate rows ----------
    $(document).on('click', '#addRate', function () {
        $('#rates').append($('#rateTemplate').html().replace(/__i__/g, Date.now()));
    });

    $(document).on('click', '.remove-rate', function () {
        if ($('.rate-row').length > 1) $(this).closest('.rate-row').remove();
    });

    // ---------- 12. Theme ----------
    const setTheme = (theme) => {
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('theme', theme);
        $('[data-theme-set]').each(function () {
            $(this).toggleClass('on', $(this).data('theme-set') === theme);
        });
    };
    setTheme(localStorage.getItem('theme') || 'light');
    $(document).on('click', '[data-theme-set]', function () {
        setTheme($(this).data('theme-set'));
    });

    // =========================================================
    // Page features that must re-run after AJAX navigation
    // =========================================================
    function initPageFeatures() {
        clearPageTimers();

        // 4. Browse units: filters / search
        const $filters = $('#filters');
        if ($filters.length) {
            const search = () => $.get(
                $filters.attr('action'),
                $filters.serialize() + '&q=' + encodeURIComponent($('#topSearch').val() || ''),
                html => $('#results').html(html)
            );

            $filters.off('input.ajaxnav change.ajaxnav submit.ajaxnav');
            $('#topSearchForm').off('submit.ajaxnav');
            $('#clearFilters').off('click.ajaxnav');

            $filters.on('input.ajaxnav change.ajaxnav', 'input', debounce(search, 300));
            $filters.on('submit.ajaxnav', e => e.preventDefault());
            $('#topSearchForm').on('submit.ajaxnav', e => {
                e.preventDefault();
                search();
            });
            $('#clearFilters').on('click.ajaxnav', function () {
                $filters.find('input[type=text], input[type=number]').val('');
                $filters.find(':checkbox').prop('checked', false);
                $('#topSearch').val('');
                search();
            });
        }

        // 5. Reservation: calendar + live quote
        const $reservation = $('#reservationForm');
        if ($reservation.length && window.flatpickr) {
            const $quote = $('#quote');
            const maxGuests = $reservation.data('max-guests');
            const addDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);

            $.getJSON($reservation.data('availability-url'), function (ranges) {
                const options = { altInput: true, altFormat: 'm/d/Y', dateFormat: 'Y-m-d' };

                // destroy old instances if any (avoid duplicates after AJAX nav)
                const inEl = document.querySelector('#check_in_date');
                const outEl = document.querySelector('#check_out_date');
                if (inEl && inEl._flatpickr) inEl._flatpickr.destroy();
                if (outEl && outEl._flatpickr) outEl._flatpickr.destroy();

                flatpickr('#check_in_date', {
                    ...options,
                    minDate: 'today',
                    disable: ranges,
                    onChange: ([date]) => limitCheckOut(date),
                });

                const checkOut = flatpickr('#check_out_date', {
                    ...options,
                    minDate: addDays(new Date(), 1),
                    onChange: updateQuote
                });

                function limitCheckOut(checkIn) {
                    const start = flatpickr.formatDate(checkIn, 'Y-m-d');
                    const next = ranges.map(r => r.from).filter(from => from > start).sort()[0];

                    checkOut.set('minDate', addDays(checkIn, 1));
                    checkOut.set('maxDate', next ? flatpickr.parseDate(next, 'Y-m-d') : new Date(2100, 0, 1));
                    checkOut.clear();
                    updateQuote();
                }

                function updateQuote() {
                    const checkIn = $('#check_in_date').val();
                    const checkOutVal = $('#check_out_date').val();
                    const guests = parseInt($('#guest_count').val(), 10);

                    if (!checkIn || !checkOutVal || !guests || guests < 1 || guests > maxGuests) {
                        $quote.prop('hidden', true);
                        return;
                    }

                    $.post(
                        $reservation.data('quote-url'),
                        { check_in_date: checkIn, check_out_date: checkOutVal, guest_count: guests },
                        q => {
                            $quote.toggleClass('error', !q.available).prop('hidden', false)
                                .text(q.available
                                    ? `${q.nights} night(s) x ${money(q.rate)} = ${money(q.total)}`
                                    : 'Those dates are not available.');
                            $('#nextBtn').prop('disabled', !q.available);
                        }
                    );
                }

                $('#guest_count').off('input.ajaxnav').on('input.ajaxnav', debounce(updateQuote, 250));
            });
        }

        // 6. Messages
        const $chat = $('.chat-layout');
        if ($chat.length) {
            const $list = $('#conversationList');
            const $thread = $('#thread');
            const $body = $('#messageBody');
            let activeId = null;

            const markActive = () => $list.find('.conversation').removeClass('active')
                .filter(`[data-id="${activeId}"]`).addClass('active');

            function loadThread(forceScroll) {
                if (!activeId) return;
                $.get($chat.data('thread-url') + '/' + activeId, html => {
                    const old = $thread.find('.chat-messages')[0];
                    const atBottom = !old || old.scrollHeight - old.scrollTop - old.clientHeight < 60;
                    $thread.html(html);
                    if (forceScroll || atBottom) {
                        const box = $thread.find('.chat-messages')[0];
                        if (box) box.scrollTop = box.scrollHeight;
                    }
                });
            }

            function loadList() {
                $.get($chat.data('list-url'), { q: $('#conversationSearch').val() }, html => {
                    $list.html(html);
                    markActive();
                });
            }

            function openChat(id) {
                activeId = id;
                $body.prop('disabled', false).focus();
                $('#sendBtn').prop('disabled', false);
                markActive();
                loadThread(true);
            }

            $list.off('click.ajaxnav').on('click.ajaxnav', '.conversation', function () {
                openChat($(this).data('id'));
            });
            $('#newChat').off('click.ajaxnav').on('click.ajaxnav', '.start-chat', function () {
                openChat($(this).data('id'));
            });
            $('#conversationSearch').off('input.ajaxnav').on('input.ajaxnav', debounce(loadList, 300));

            $('#messageForm').off('submit.ajaxnav').on('submit.ajaxnav', function (e) {
                e.preventDefault();
                const text = $.trim($body.val());
                if (!text || !activeId) return;
                $.post(this.action, { receiver_id: activeId, body: text }).done(() => {
                    $body.val('');
                    loadThread(true);
                    loadList();
                }).fail(showError);
            });

            if ($chat.data('open')) openChat($chat.data('open'));

            chatTimer = setInterval(() => {
                loadThread(false);
                loadList();
            }, 5000);
        }

        // 8. Live search tables
        $('.live-search').each(function () {
            const $form = $(this);
            const run = () => $.get($form.attr('action'), $form.serialize(), html => $('#liveResults').html(html));
            $form.off('input.ajaxnav change.ajaxnav submit.ajaxnav');
            $form.on('input.ajaxnav change.ajaxnav', 'input, select', debounce(run, 300));
            $form.on('submit.ajaxnav', e => {
                e.preventDefault();
                run();
            });
        });

        // 9. Date range boxes
        if (window.flatpickr) {
            document.querySelectorAll('.date-range').forEach(el => {
                if (el._flatpickr) el._flatpickr.destroy();
                flatpickr(el, { mode: 'range', dateFormat: 'Y-m-d' });
            });
        }
    }

    // =========================================================
    // AJAX navigation (no full reload)
    // =========================================================
    let navBusy = false;

    function setActiveNav(url) {
        const path = new URL(url, window.location.origin).pathname;
        $('.sidebar a.ajax-nav').each(function () {
            const hrefPath = new URL(this.href, window.location.origin).pathname;
            $(this).toggleClass('active', hrefPath === path);
        });
    }

    function sameOrigin(url) {
        try {
            const u = new URL(url, window.location.origin);
            return u.origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function loadPage(url, push) {
        if (navBusy) return;
        navBusy = true;

        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-AJAX-NAV': '1' },
            dataType: 'html'
        })
            .done(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newMain = doc.querySelector('#ajax-main') || doc.querySelector('.main');
                if (!newMain) {
                    window.location.href = url; // fallback
                    return;
                }

                // Swap topbar + content only (sidebar stays)
                $('#ajax-main').html(newMain.innerHTML);

                // Title
                const newTitle = doc.querySelector('title');
                if (newTitle) document.title = newTitle.textContent;

                // Active menu
                setActiveNav(url);

                // History
                if (push) history.pushState({ ajaxNav: true }, '', url);

                // Re-bind page JS
                initPageFeatures();

                // NOTE: no window.scrollTo / no forced jump to top
            })
            .fail(() => {
                window.location.href = url; // fallback to normal navigation
            })
            .always(() => {
                navBusy = false;
            });
    }

    // Sidebar menu clicks
    $(document).on('click', 'a.ajax-nav', function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.which === 2) return;

        e.preventDefault();
        const url = this.href;
        if (url === window.location.href) return;
        loadPage(url, true);
    });

    // In-page links inside main area (same-origin GET links only)
    $(document).on('click', '#ajax-main a[href]', function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.which === 2) return;

        const href = this.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (this.target && this.target !== '_self') return;
        if ($(this).closest('form').length) return; // let form UIs alone
        if (!sameOrigin(this.href)) return;

        // file download-ish URLs: allow normal behavior
        if (/\/files\//.test(this.href)) return;

        e.preventDefault();
        if (this.href === window.location.href) return;
        loadPage(this.href, true);
    });

    // Browser back / forward
    window.addEventListener('popstate', function () {
        loadPage(window.location.href, false);
    });

    // First page load
    initPageFeatures();
});