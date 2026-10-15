(function ($) {
    var KEY = 'cms_ai_posts_state';
    var NOTICE = 'cms_ai_posts_notice';
    var boot = window.SM_BOOT || { posts: [], accounts: {}, urls: {}, brand: {} };
    var currentStep = 1;
    var furthest = 1;
    var draftId = null;
    var isCopy = false;
    var imageData = '';
    var imageUrl = '';
    var processingTimer = null;
    var processingDone = false;
    var lastItems = [];
    var pickersReady = false;
    var finished = false;
    var pendingDeleteId = null;
    var pendingErrorId = null;
    var manageNetwork = null;
    var connectNetwork = null;
    var calCursor = window.moment ? moment() : null;
    var calMode = 'month';
    var imageRemoved = false;

    function urlFor(name, id) {
        return String((boot.urls && boot.urls[name]) || '').replace('__ID__', id).replace('__PLATFORM__', id);
    }

    function errorMessage(xhr, fallback) {
        var body = xhr && xhr.responseJSON;
        if (body && body.errors) {
            var first = Object.keys(body.errors)[0];
            if (first && body.errors[first] && body.errors[first][0]) return body.errors[first][0];
        }
        if (body && body.message) return body.message;
        return fallback;
    }

    function request(method, url, data) {
        var options = {
            url: url,
            method: method,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        };
        if (data) {
            options.data = JSON.stringify(data);
            options.contentType = 'application/json; charset=UTF-8';
        }
        return $.ajax(options);
    }

    function remember(post) {
        if (!post) return;
        boot.posts = boot.posts || [];
        var found = false;
        boot.posts = boot.posts.map(function (item) {
            if (Number(item.id) === Number(post.id)) {
                found = true;
                return post;
            }
            return item;
        });
        if (!found) boot.posts.unshift(post);
    }

    function formPayload(action) {
        return {
            action: action,
            prompt: $.trim($('#smPrompt').val()),
            source_url: $.trim($('#smSource').val()),
            tone: $('#smTone').val(),
            research: $('input[name="research"]:checked').val() === '1',
            platforms: selectedPlatforms(),
            facebook: $('#smFbText').val() || '',
            instagram: $('#smIgText').val() || '',
            hashtags: readTags(),
            timezone: $('#smTz').val() || 'Europe/Zurich',
            image_instructions: $.trim($('#smImageInstructions').val()),
            generated_image_prompt: $.trim($('#smGeneratedImagePrompt').val()),
            image_data: imageData && imageData.indexOf('data:image/') === 0 ? imageData : '',
            remove_image: imageRemoved,
            duplicate_id: isCopy && boot.duplicateId ? boot.duplicateId : null
        };
    }

    function savePost(action) {
        var updating = draftId && !isCopy;
        return request(updating ? 'PUT' : 'POST', updating ? urlFor('update', draftId) : boot.urls.store, formPayload(action)).done(function (res) {
            if (!res.post) return;
            draftId = res.post.id;
            isCopy = false;
            remember(res.post);
        });
    }

    function applyGenerated(post) {
        if (!post) return;
        if (post.facebook) $('#smFbText').val(post.facebook);
        if (post.instagram) $('#smIgText').val(post.instagram);
        if (post.hashtags && post.hashtags.length) renderTags(post.hashtags);
        if (post.generated_image_prompt) $('#smGeneratedImagePrompt').val(post.generated_image_prompt);
        if (post.image_url && post.image_url !== boot.placeholder) {
            imageData = '';
            imageUrl = post.image_url;
            imageRemoved = false;
        }
    }

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function safeSrc(src) {
        src = String(src || '');
        if (/^data:image\/(png|jpe?g|gif|webp);base64,/i.test(src)) return src;
        if (/^https?:\/\//i.test(src)) return src;
        if (src.charAt(0) === '/') return src;
        return boot.placeholder || '';
    }

    function loadState() {
        try {
            return JSON.parse(sessionStorage.getItem(KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    function saveState(state) {
        try {
            sessionStorage.setItem(KEY, JSON.stringify(state));
        } catch (e) {
            (state.created || []).forEach(function (post) { post.image_data = ''; });
            Object.keys(state.updates || {}).forEach(function (id) { state.updates[id].image_data = ''; });
            try { sessionStorage.setItem(KEY, JSON.stringify(state)); } catch (err) {}
        }
    }

    function hasState(state) {
        state = state || loadState();
        return (state.created && state.created.length) || (state.deleted && state.deleted.length) || (state.updates && Object.keys(state.updates).length);
    }

    function mergePosts(posts) {
        return (posts || []).slice();
    }

    function findPost(id) {
        id = Number(id);
        var posts = mergePosts(boot.posts || []);
        for (var i = 0; i < posts.length; i++) {
            if (Number(posts[i].id) === id) return posts[i];
        }
        return null;
    }

    function nextId() {
        var max = 200;
        mergePosts(boot.posts || []).forEach(function (post) {
            if (Number(post.id) > max) max = Number(post.id);
        });
        return max + 1;
    }

    function persist(post) {
        var state = loadState();
        state.created = state.created || [];
        state.updates = state.updates || {};
        state.deleted = (state.deleted || []).filter(function (id) { return Number(id) !== Number(post.id); });
        var seed = (boot.posts || []).some(function (item) { return Number(item.id) === Number(post.id); });
        var createdIndex = -1;
        state.created.forEach(function (item, index) {
            if (Number(item.id) === Number(post.id)) createdIndex = index;
        });
        if (seed && createdIndex === -1) {
            state.updates[post.id] = post;
        } else if (createdIndex === -1) {
            state.created.unshift(post);
        } else {
            state.created[createdIndex] = post;
        }
        saveState(state);
        draftId = post.id;
    }

    function showAlert(kind, message) {
        var $box = $('#smAlert');
        var $alert = $box.find('.alert');
        $alert.removeClass('alert-success alert-danger').addClass(kind === 'error' ? 'alert-danger' : 'alert-success');
        $alert.find('.alert__icon i').attr('class', kind === 'error' ? 'fas fa-exclamation-triangle' : 'fas fa-check');
        $('#smAlertText').text(message);
        $alert.show();
        $box.show();
    }

    function consumeNotice() {
        var message = sessionStorage.getItem(NOTICE);
        if (!message) return;
        sessionStorage.removeItem(NOTICE);
        showAlert('success', message);
    }

    function formError(message) {
        $('#smFormAlertText').text(message);
        $('#smFormAlert').show().find('.alert').show();
        var top = $('#smFormAlert').offset();
        if (top) $('html, body').animate({ scrollTop: top.top - 20 }, 200);
    }

    function statusLabel(status) {
        return (boot.statuses && boot.statuses[status]) || status;
    }

    function formatWhen(value) {
        if (!value || !window.moment) return '—';
        var date = moment(value, 'YYYY-MM-DD HH:mm');
        return date.isValid() ? date.format('D MMM YYYY, HH:mm') : '—';
    }

    function formatCreated(value) {
        if (!value || !window.moment) return '—';
        var date = moment(value, 'YYYY-MM-DD HH:mm');
        return date.isValid() ? date.format('D MMM YYYY') : '—';
    }

    function actionButtons(post) {
        var icons = boot.icons || {};
        var base = boot.urls.index;
        return '<div class="sm-actions">' +
            '<a href="' + base + '/' + post.id + '" class="btn_action" title="View"><img src="' + esc(icons.show) + '" alt="View"></a>' +
            '<a href="' + base + '/' + post.id + '/edit" class="btn_action" title="Edit"><img src="' + esc(icons.edit) + '" alt="Edit"></a>' +
            '<a href="' + boot.urls.create + '?duplicate=' + post.id + '" class="btn_action" title="Duplicate"><img src="' + esc(icons.copy) + '" alt="Duplicate"></a>' +
            '<a href="#" class="btn_action js-delete" data-id="' + post.id + '" data-title="' + esc(post.title) + '" title="Delete"><img src="' + esc(icons.delete) + '" alt="Delete"></a>' +
            '</div>';
    }

    function selectedPlatforms() {
        var list = [];
        if ($('#smPlatformFacebook').is(':checked')) list.push('facebook');
        if ($('#smPlatformInstagram').is(':checked')) list.push('instagram');
        return list;
    }

    function titleFromPrompt(prompt, existing) {
        prompt = $.trim(prompt || '');
        if (isCopy && existing && existing.title) return 'Copy of ' + existing.title.replace(/^Copy of /, '');
        if (/summer/i.test(prompt) && /(20|discount|promotion)/i.test(prompt)) return 'Summer collection, 20% off';
        var clean = prompt.replace(/^write a post about\s+/i, '').replace(/\.$/, '');
        if (!clean) return existing && existing.title ? existing.title : 'Untitled post';
        clean = clean.charAt(0).toUpperCase() + clean.slice(1);
        if (clean.length > 64) clean = clean.slice(0, 64).trim() + '…';
        return clean;
    }

    function copyFor(prompt, tone) {
        if (/summer/i.test(prompt) && /(20|discount|promotion)/i.test(prompt)) {
            if (tone === 'casual') {
                return {
                    facebook: 'Summer is here, and so is 20% off the new collection. Linen, balcony lights, the pieces we actually use. In the Zurich showroom and online through 31 October.',
                    instagram: 'Summer is here. 20% off the new collection through 31 October. Come by Josefstrasse or shop from the sofa.',
                    hashtags: ['summer', 'sale', 'discount', 'shopping', 'newcollection']
                };
            }
            if (tone === 'educational') {
                return {
                    facebook: 'A lighter room starts with what you take out. Then add one thing from the summer collection: linen, a lamp, a chair you can move outside. Selected pieces are 20% off through 31 October.',
                    instagram: 'How we reset a room for summer: fewer objects, lighter linen, one lamp. Selected pieces are 20% off through 31 October.',
                    hashtags: ['summer', 'interiors', 'linen', 'styling', 'nordlichthome']
                };
            }
            if (tone === 'professional') {
                return {
                    facebook: 'The summer collection is now in the Zurich showroom. Selected furniture, linen, and lighting are 20% off through 31 October, in store and online.',
                    instagram: 'The summer collection is in. Selected pieces are 20% off through 31 October, in the Zurich showroom and online.',
                    hashtags: ['summer', 'sale', 'zurich', 'interiors', 'nordlichthome']
                };
            }
            return {
                facebook: 'Summer is here! Discover our new summer collection and enjoy 20% off selected products — lightweight linen, balcony lighting, and the pieces we bring out every June. The offer runs through 31 October in the Zurich showroom and online.',
                instagram: 'Summer is here. The new collection has landed, and selected pieces are 20% off through 31 October. Linen, lighting, and a few things we wish we could keep on the floor.',
                hashtags: ['summer', 'sale', 'discount', 'shopping', 'newcollection']
            };
        }
        var idea = $.trim(prompt).replace(/\.$/, '');
        var open = {
            professional: 'From Nordlicht Home: ',
            casual: 'A note from the showroom. ',
            promotional: 'Now in store: ',
            educational: 'Worth knowing this month: '
        }[tone] || '';
        var tags = ['nordlichthome', 'zurich', 'interiors'];
        idea.split(/\s+/).forEach(function (word) {
            var clean = word.toLowerCase().replace(/[^a-z0-9]/g, '');
            if (clean.length > 4 && tags.length < 5 && tags.indexOf(clean) === -1) tags.push(clean);
        });
        return {
            facebook: open + idea + '. Visit the Zurich showroom or read more on nordlichthome.ch.',
            instagram: open + idea + '.',
            hashtags: tags
        };
    }

    function readTags() {
        var tags = [];
        $('#smHashtags .sm-chip').each(function () {
            var tag = $(this).attr('data-tag');
            if (tag) tags.push(tag);
        });
        return tags;
    }

    function renderTags(tags) {
        $('#smHashtags').html((tags || []).map(function (tag) {
            tag = String(tag).replace(/#/g, '').toLowerCase();
            return '<span class="sm-chip" data-tag="' + esc(tag) + '">#' + esc(tag) + ' <button type="button" aria-label="Remove hashtag">&times;</button></span>';
        }).join(''));
    }

    function showChosenImage() {
        if (!imageUrl) {
            $('#smImagePreview').addClass('is-hidden');
            $('#smDrop').removeClass('is-hidden');
            return;
        }
        $('#smImageTag').attr('src', safeSrc(imageUrl));
        $('#smImageLink').attr('href', safeSrc(imageUrl));
        $('#smImagePreview').removeClass('is-hidden');
        $('#smDrop').addClass('is-hidden');
    }

    function readImage(file) {
        if (!file) return;
        if (!/^image\/(jpeg|png|gif|webp)$/.test(file.type)) {
            formError('Please choose a JPG, PNG, GIF or WebP image.');
            return;
        }
        if (file.size > 1500000) {
            formError('Please choose an image under 1.5 MB for this preview.');
            return;
        }
        var reader = new FileReader();
        reader.onload = function () {
            imageData = String(reader.result || '');
            imageUrl = imageData;
            imageRemoved = false;
            showChosenImage();
            syncPreview();
            $('#smFormAlert').hide();
        };
        reader.readAsDataURL(file);
    }

    function syncPreview() {
        var facebook = $('#smFbText').val() || '';
        var instagram = $('#smIgText').val() || '';
        var tags = readTags();
        var src = safeSrc(imageUrl || boot.placeholder);
        var platforms = selectedPlatforms();
        var preview = $('#smPreviewSwitch .is-active').data('preview') || 'both';
        var showFb = platforms.indexOf('facebook') >= 0 && preview !== 'instagram';
        var showIg = platforms.indexOf('instagram') >= 0 && preview !== 'facebook';

        $('#smFbCopyText').text(facebook);
        $('#smFbCopy').toggleClass('is-empty', !$.trim(facebook));
        $('#smIgCopyText').text(instagram);
        $('#smIgCopy').toggleClass('is-empty', !$.trim(instagram));
        $('#smIgTags').text(tags.map(function (tag) { return '#' + tag; }).join(' '));
        $('#smFbImg, #smIgImg, #smMediaThumb').attr('src', src);
        $('#smFbVisual, #smIgVisual').toggleClass('is-empty', !imageUrl);
        $('#smFbCount').text(facebook.length + ' / 500').toggleClass('is-over', facebook.length > 500);
        $('#smIgCount').text(instagram.length + ' / 2200').toggleClass('is-over', instagram.length > 2200);

        var source = $.trim($('#smSource').val());
        if (/^https?:\/\//i.test(source)) {
            var host = source;
            try { host = new URL(source).host; } catch (e) {}
            $('#smFbLinkHost').text(host);
            $('#smFbLinkTitle').text(titleFromPrompt($('#smPrompt').val()));
            $('#smFbLink').attr('href', source).removeClass('is-hidden');
        } else {
            $('#smFbLink').addClass('is-hidden');
        }

        var when = readSchedule();
        $('#smFbTime').text(when && window.moment ? moment(when, 'YYYY-MM-DD HH:mm').format('D MMMM YYYY [at] HH:mm') : 'Preview · Not scheduled');
        $('#smColFb').toggle(showFb);
        $('#smColIg').toggle(showIg);
        $('#smPreviewSwitch [data-preview="facebook"]').prop('disabled', platforms.indexOf('facebook') === -1);
        $('#smPreviewSwitch [data-preview="instagram"]').prop('disabled', platforms.indexOf('instagram') === -1);
    }

    function readSchedule() {
        var date = $.trim($('#smDateInput').val());
        var time = $.trim($('#smTimeInput').val()) || '09:00';
        if (!date || !window.moment) return null;
        var parsed = moment(date + ' ' + time, 'DD.MM.YYYY HH:mm', true);
        return parsed.isValid() ? parsed.format('YYYY-MM-DD HH:mm') : null;
    }

    function ensureScheduleDefaults() {
        if ($('#smDateInput').val() || !window.moment) return;
        var date = moment().add(1, 'day');
        $('#smDateInput, #smModalDateInput').val(date.format('DD.MM.YYYY'));
        $('#smTimeInput, #smModalTimeInput').val('09:00');
    }

    function pickerLayer() {
        var $layer = $('#smPickerLayer');
        if (!$layer.length) {
            $layer = $('<div id="smPickerLayer"></div>').css({
                position: 'absolute',
                top: 0,
                left: 0,
                width: 0,
                height: 0,
                overflow: 'visible',
                zIndex: 10060
            }).appendTo(document.body);
        }
        return $layer;
    }

    function placePicker($wrap) {
        var $widget = $('#smPickerLayer > .bootstrap-datetimepicker-widget:visible').last();
        if (!$wrap.length || !$widget.length) return;
        var rect = $wrap[0].getBoundingClientRect();
        var widgetHeight = $widget.outerHeight() || 0;
        var widgetWidth = $widget.outerWidth() || 0;
        var top = rect.bottom + window.pageYOffset + 2;
        var left = rect.left + window.pageXOffset;
        if (widgetHeight && rect.bottom + widgetHeight > window.innerHeight - 8 && rect.top > widgetHeight) {
            top = rect.top + window.pageYOffset - widgetHeight - 2;
        }
        if (widgetWidth && left + widgetWidth > window.pageXOffset + window.innerWidth - 8) {
            left = Math.max(window.pageXOffset + 8, rect.right + window.pageXOffset - widgetWidth);
        }
        $widget.css({ position: 'absolute', top: top, left: left, bottom: 'auto', right: 'auto' });
    }

    function initPickers() {
        if (pickersReady || !$.fn.datetimepicker) return;
        var shared = {
            locale: 'en',
            widgetParent: pickerLayer(),
            icons: {
                time: 'far fa-clock',
                date: 'far fa-calendar-alt',
                up: 'fas fa-chevron-up',
                down: 'fas fa-chevron-down',
                previous: 'fas fa-chevron-left',
                next: 'fas fa-chevron-right'
            }
        };
        try {
            $('#smDateWrap, #smModalDate').datetimepicker($.extend({ format: 'DD.MM.YYYY' }, shared));
            $('#smTimeWrap, #smModalTime').datetimepicker($.extend({ format: 'HH:mm' }, shared));
            var $openPicker = null;
            $('#smDateWrap, #smTimeWrap, #smModalDate, #smModalTime').on('dp.show', function () {
                var $wrap = $(this);
                $openPicker = $wrap;
                placePicker($wrap);
                window.requestAnimationFrame(function () { placePicker($wrap); });
            }).on('dp.hide', function () {
                if ($openPicker && $openPicker.is(this)) $openPicker = null;
            }).on('dp.change', function () {
                syncPreview();
                renderStep4();
            });
            $(window).on('resize.smPickers', function () {
                window.setTimeout(function () {
                    if ($openPicker) placePicker($openPicker);
                }, 0);
            });
            pickersReady = true;
        } catch (e) {}
    }

    function updateStepper() {
        $('#smStepper li').each(function () {
            var step = Number($(this).data('step'));
            $(this).toggleClass('is-current', step === currentStep);
            $(this).toggleClass('is-done', step < currentStep);
            $(this).toggleClass('is-open', step > currentStep && step <= furthest);
            $(this).toggleClass('is-locked', step > furthest);
        });
    }

    function renderProgress(items) {
        lastItems = items;
        $('#smProgress').html(items.map(function (item) {
            var icon = 'far fa-circle';
            if (item.state === 'done') icon = 'fas fa-check';
            if (item.state === 'active') icon = 'fas fa-spinner fa-spin';
            if (item.state === 'skip') icon = 'fas fa-minus';
            return '<li class="is-' + item.state + '"><i class="' + icon + '"></i>' + esc(item.text) + '</li>';
        }).join(''));
    }

    function progressItems(state) {
        var platforms = selectedPlatforms();
        var research = $('input[name="research"]:checked').val() === '1';
        var items = [
            { text: 'Reading source content', state: state },
            { text: 'Analyzing uploaded image', state: imageUrl ? state : 'skip' },
            { text: 'Researching current trends', state: research ? state : 'skip' }
        ];
        if (platforms.indexOf('facebook') >= 0) items.push({ text: 'Generating Facebook content', state: state });
        if (platforms.indexOf('instagram') >= 0) items.push({ text: 'Generating Instagram content', state: state });
        items.push({ text: 'Preparing visual', state: state });
        return items;
    }

    function watchGeneration(id) {
        if (processingTimer) clearInterval(processingTimer);
        processingTimer = setInterval(function () {
            request('GET', urlFor('status', id)).done(function (res) {
                var post = res.post;
                if (!post || post.status === 'processing') return;
                clearInterval(processingTimer);
                remember(post);
                applyGenerated(post);
                showChosenImage();
                syncPreview();
                renderProgress(progressItems(post.status === 'failed' ? 'done' : 'done'));
                processingDone = true;
                furthest = Math.max(furthest, 3);
                $('#smContinue').prop('disabled', false);
                updateStepper();
                if (post.status === 'failed') {
                    formError(post.error || 'Generation failed.');
                }
            });
        }, 1500);
    }

    function startProcessing() {
        renderProgress(progressItems('active'));
        $('#smContinue').prop('disabled', true);
        processingDone = false;
        if (draftId) watchGeneration(draftId);
    }

    function showProcessingComplete() {
        if (!lastItems.length) {
            renderProgress([
                { text: 'Reading source content', state: 'done' },
                { text: 'Analyzing uploaded image', state: 'done' },
                { text: 'Generating Facebook content', state: 'done' },
                { text: 'Generating Instagram content', state: 'done' },
                { text: 'Preparing visual', state: 'done' }
            ]);
        } else {
            renderProgress(lastItems.map(function (item) {
                return { text: item.text, state: item.state === 'skip' ? 'skip' : 'done' };
            }));
        }
        $('#smContinue').prop('disabled', false);
    }

    function goToStep(step, silent) {
        step = Number(step);
        if (step > furthest) return;
        currentStep = step;
        $('.sm-step').removeClass('is-on');
        $('#smStep' + step).addClass('is-on');
        updateStepper();
        if (step === 2) {
            if (processingDone) showProcessingComplete();
            else startProcessing();
        }
        if (step === 3) syncPreview();
        if (step === 4) {
            ensureScheduleDefaults();
            initPickers();
            renderStep4();
            if (finished) {
                $('#smScheduleForm').addClass('is-hidden');
                $('#smSuccess').removeClass('is-hidden');
            }
        }
        if (!silent) {
            var top = $('#smStepper').offset();
            if (top) $('html, body').animate({ scrollTop: top.top - 10 }, 180);
        }
    }

    function validate() {
        if (!$.trim($('#smPrompt').val())) {
            formError('Add a short prompt before generating a post.');
            $('#smPrompt').css('border-color', 'red').focus();
            return false;
        }
        $('#smPrompt').css('border-color', '');
        if (!selectedPlatforms().length) {
            formError('Select Facebook, Instagram, or both.');
            return false;
        }
        $('#smFormAlert').hide();
        return true;
    }

    function buildGenerated(force) {
        var pack = copyFor($('#smPrompt').val(), $('#smTone').val());
        if (force || !$('#smFbText').val()) $('#smFbText').val(pack.facebook);
        if (force || !$('#smIgText').val()) $('#smIgText').val(pack.instagram);
        if (force || !readTags().length) renderTags(pack.hashtags);
        syncPreview();
    }

    function stamp(post, status) {
        var activity = (post.activity || []).slice();
        function add(label) {
            if (!activity.some(function (event) { return event.label === label; })) {
                activity.push({ label: label, at: moment().format('D MMM YYYY, HH:mm') });
            }
        }
        add('Created');
        if (status !== 'draft') add('AI processing');
        if (status === 'pending' || status === 'scheduled' || status === 'published') add('Content generated');
        if (status === 'scheduled' || status === 'published') {
            add('Approved');
            add('Scheduled');
        }
        if (status === 'published') add('Published');
        post.activity = activity;
        post.status = status;
        return post;
    }

    function collect(status) {
        var existing = draftId ? findPost(draftId) : null;
        var id = draftId || nextId();
        var scheduled = null;
        if (status === 'scheduled') scheduled = readSchedule();
        if (status === 'published' && window.moment) scheduled = moment().format('YYYY-MM-DD HH:mm');
        var post = {
            id: id,
            title: titleFromPrompt($('#smPrompt').val(), existing),
            excerpt: ($('#smFbText').val() || $('#smIgText').val() || $('#smPrompt').val()).slice(0, 160),
            prompt: $.trim($('#smPrompt').val()),
            source_url: $.trim($('#smSource').val()),
            tone: $('#smTone').val(),
            research: $('input[name="research"]:checked').val() === '1',
            platforms: selectedPlatforms(),
            status: status,
            scheduled_at: scheduled,
            calendar_at: (status === 'scheduled' || status === 'published') ? scheduled : null,
            timezone: $('#smTz').val() || 'Europe/Zurich',
            created_at: (existing && existing.created_at) || moment().format('YYYY-MM-DD HH:mm'),
            image: imageData ? 'upload' : 'generated',
            image_url: imageData ? imageUrl : (imageUrl || boot.generatedImage),
            image_data: imageData || '',
            facebook: $('#smFbText').val(),
            instagram: $('#smIgText').val(),
            hashtags: readTags(),
            link: $.trim($('#smSource').val()),
            error: null,
            activity: existing && existing.activity ? existing.activity.slice() : []
        };
        if (isCopy) {
            post.title = 'Copy of ' + titleFromPrompt($('#smPrompt').val()).replace(/^Copy of /, '');
            post.activity = [];
            post.created_at = moment().format('YYYY-MM-DD HH:mm');
            isCopy = false;
        }
        return stamp(post, status);
    }

    function saveDraft() {
        if (!$.trim($('#smPrompt').val())) {
            formError('Add a short prompt before saving a draft.');
            return;
        }
        savePost('draft').done(function () {
            showAlert('success', 'Draft saved. You can leave this page and come back to it from Posts.');
        }).fail(function (xhr) {
            formError(errorMessage(xhr, 'The draft could not be saved.'));
        });
    }

    function finish(status) {
        ensureScheduleDefaults();
        if (status === 'scheduled' && !readSchedule()) {
            showAlert('error', 'Choose a valid date and time before scheduling.');
            goToStep(4);
            return;
        }
        if (!$.trim($('#smFbText').val()) && !$.trim($('#smIgText').val())) {
            showAlert('error', 'Generate the post before scheduling or publishing.');
            return;
        }
        var $buttons = $('#smScheduleBtn, #smPublishNow, #smPublishStep4, #smModalSchedule');
        $buttons.prop('disabled', true);
        savePost('review').done(function (res) {
            var id = res.post.id;
            var next = status === 'published'
                ? request('POST', urlFor('publish', id), {})
                : request('POST', urlFor('schedule', id), {
                    scheduled_at: readSchedule(),
                    timezone: $('#smTz').val() || 'Europe/Zurich'
                });
            next.done(function (done) {
                remember(done.post);
                finished = true;
                furthest = Math.max(furthest, 4);
                var when = done.post.scheduled_at && window.moment ? moment(done.post.scheduled_at, 'YYYY-MM-DD HH:mm') : null;
                var message = done.post.status === 'published'
                    ? 'Your post was published.'
                    : 'Your post is scheduled for ' + when.format('MMMM D, YYYY') + ' at ' + when.format('HH:mm') + ' (' + done.post.timezone + ').';
                $('#smSuccessText').text(message);
                $('#smSuccessPreview').html(miniHtml());
                $('#smSuccessView').attr('href', boot.urls.index + '/' + done.post.id);
                $('#smScheduleForm').addClass('is-hidden');
                $('#smSuccess').removeClass('is-hidden');
                $('#smScheduleModal').modal('hide');
                goToStep(4);
            }).fail(function (xhr) {
                showAlert('error', errorMessage(xhr, 'The post could not be published.'));
                if (xhr.responseJSON && xhr.responseJSON.post) remember(xhr.responseJSON.post);
            }).always(function () {
                $buttons.prop('disabled', false);
            });
        }).fail(function (xhr) {
            $buttons.prop('disabled', false);
            showAlert('error', errorMessage(xhr, 'The post could not be saved.'));
        });
    }

    function miniHtml() {
        var text = $('#smFbText').val() || $('#smIgText').val() || '';
        return '<img src="' + safeSrc(imageUrl || boot.placeholder) + '" alt=""><div><strong>' + esc(titleFromPrompt($('#smPrompt').val())) + '</strong><p>' + esc(text.slice(0, 180)) + '</p></div>';
    }

    function renderStep4() {
        var platforms = selectedPlatforms();
        var html = '';
        if (platforms.indexOf('facebook') >= 0) html += '<li><i class="fas fa-check"></i> Facebook</li>';
        if (platforms.indexOf('instagram') >= 0) html += '<li><i class="fas fa-check"></i> Instagram</li>';
        if (!html) html = '<li>No platform selected.</li>';
        $('#smStep4Platforms').html(html);
        if (!$('#smSuccess').is(':visible') || $('#smSuccess').hasClass('is-hidden')) {
            $('#smStep4Preview').html(miniHtml());
        }
    }

    function fillForm(post) {
        $('#smPrompt').val(post.prompt || '');
        $('#smSource').val(post.source_url || '');
        $('#smImageInstructions').val(post.image_instructions || '');
        $('#smGeneratedImagePrompt').val(post.generated_image_prompt || '');
        $('#smTone').val(post.tone || 'professional');
        $('input[name="research"][value="' + (post.research ? '1' : '0') + '"]').prop('checked', true);
        $('#smPlatformFacebook').prop('checked', (post.platforms || []).indexOf('facebook') >= 0);
        $('#smPlatformInstagram').prop('checked', (post.platforms || []).indexOf('instagram') >= 0);
        $('.sm-platform').each(function () {
            $(this).toggleClass('is-on', $(this).find('input').is(':checked'));
        });
        $('#smFbText').val(post.facebook || '');
        $('#smIgText').val(post.instagram || '');
        renderTags(post.hashtags || []);
        imageData = post.image_data || '';
        imageUrl = imageData || post.image_url || '';
        if (!imageData && imageUrl === boot.placeholder) imageUrl = '';
        imageRemoved = false;
        showChosenImage();
        if (post.scheduled_at && window.moment) {
            var date = moment(post.scheduled_at, 'YYYY-MM-DD HH:mm');
            if (date.isValid()) {
                $('#smDateInput, #smModalDateInput').val(date.format('DD.MM.YYYY'));
                $('#smTimeInput, #smModalTimeInput').val(date.format('HH:mm'));
            }
        }
        if (post.timezone) $('#smTz, #smModalTz').val(post.timezone);
        if (boot.intent !== 'duplicate') draftId = post.id;
        syncPreview();
    }

    function applyRefine(note) {
        var facebook = $('#smFbText').val();
        var instagram = $('#smIgText').val();
        function first(text) {
            var match = String(text).match(/^.*?[.!?](\s|$)/);
            return $.trim(match ? match[0] : text);
        }
        if (/short/i.test(note)) {
            facebook = first(facebook);
            instagram = first(instagram);
        } else if (/casual/i.test(note)) {
            facebook = 'Quick one from the showroom: ' + facebook.charAt(0).toLowerCase() + facebook.slice(1);
            instagram = 'Quick one: ' + instagram.charAt(0).toLowerCase() + instagram.slice(1);
        } else if (/call to action|cta|shop/i.test(note)) {
            facebook += ' Shop the edit today at nordlichthome.ch.';
            instagram += ' Shop via the link in bio.';
        } else {
            facebook += ' ' + note.replace(/\.$/, '') + '.';
            instagram += ' ' + note.replace(/\.$/, '') + '.';
        }
        $('#smFbText').val(facebook);
        $('#smIgText').val(instagram);
        syncPreview();
    }

    function renderRow(post) {
        var search = (post.title + ' ' + post.excerpt + ' ' + post.status + ' ' + (post.platforms || []).join(' ')).toLowerCase();
        var icons = '';
        if ((post.platforms || []).indexOf('facebook') >= 0) icons += '<i class="fab fa-facebook-f sm-ico sm-ico--fb" title="Facebook"></i>';
        if ((post.platforms || []).indexOf('instagram') >= 0) icons += '<i class="fab fa-instagram sm-ico sm-ico--ig" title="Instagram"></i>';
        var failed = '';
        if (post.status === 'failed') {
            failed = '<div class="sm-failbox"><p>Something went wrong while publishing this post.</p><button type="button" class="button js-retry" data-id="' + post.id + '">Retry</button> <button type="button" class="button button-white js-error" data-id="' + post.id + '" data-error="' + esc(post.error || '') + '">View Error</button></div>';
        }
        var base = boot.urls.index;
        return '<tr data-id="' + post.id + '" data-status="' + esc(post.status) + '" data-search="' + esc(search) + '" data-title="' + esc(post.title) + '">' +
            '<td class="sm-post" data-label="Post"><div class="sm-post__main"><img class="sm-thumb" src="' + safeSrc(post.image_data || post.image_url) + '" alt=""><div><a class="sm-post__title" href="' + base + '/' + post.id + '">' + esc(post.title) + '</a><p class="sm-excerpt">' + esc(post.excerpt || '') + '</p>' + (post.plan_id ? '<p class="sm-plan-link"><a href="' + base + '/plans/' + post.plan_id + '">' + esc(post.plan_title || 'Content plan') + '</a></p>' : '') + failed + '</div></div></td>' +
            '<td data-label="Platforms">' + icons + '</td>' +
            '<td data-label="Status"><span class="sm-badge sm-badge--' + esc(post.status) + '">' + esc(statusLabel(post.status)) + '</span></td>' +
            '<td data-label="Scheduled">' + esc(formatWhen(post.scheduled_at)) + '</td>' +
            '<td data-label="Created">' + esc(formatCreated(post.created_at)) + '</td>' +
            '<td class="td_action" data-label="Actions">' + actionButtons(post) + '</td></tr>';
    }

    function updateFilterCounts() {
        var counts = { all: $('#smBody tr').length };
        $('#smBody tr').each(function () {
            var status = $(this).attr('data-status');
            counts[status] = (counts[status] || 0) + 1;
        });
        $('#smFilters .sm-filter').each(function () {
            $(this).find('em').text(counts[$(this).data('status')] || 0);
        });
    }

    function applyFilters() {
        var status = $('#smFilters .is-active').data('status') || 'all';
        var query = $.trim($('#smSearch').val() || '').toLowerCase();
        var visible = 0;
        var total = $('#smBody tr').length;
        $('#smBody tr').each(function () {
            var haystack = String($(this).attr('data-search') || '');
            var show = (status === 'all' || $(this).attr('data-status') === status) && (!query || haystack.indexOf(query) !== -1);
            $(this).toggle(show);
            if (show) visible += 1;
        });
        $('#smTableWrap').toggleClass('is-hidden', total === 0 || visible === 0);
        $('#smEmptyAll').toggleClass('is-hidden', total !== 0);
        $('#smEmptyFilter').toggleClass('is-hidden', total === 0 || visible !== 0);
        $('#smShowing').text(total && visible ? 'Showing ' + visible + ' of ' + total + ' posts' : '');
    }

    function renderDetail(post) {
        var when = post.scheduled_at && window.moment ? moment(post.scheduled_at, 'YYYY-MM-DD HH:mm') : null;
        var platforms = '';
        if ((post.platforms || []).indexOf('facebook') >= 0) platforms += '<li><i class="fas fa-check"></i> Facebook <span class="sm-ico sm-ico--fb"><i class="fab fa-facebook-f"></i></span> ' + esc((boot.brand && boot.brand.page) || 'Facebook') + '</li>';
        if ((post.platforms || []).indexOf('instagram') >= 0) platforms += '<li><i class="fas fa-check"></i> Instagram <span class="sm-ico sm-ico--ig"><i class="fab fa-instagram"></i></span> ' + esc((boot.brand && boot.brand.instagram) || 'Instagram') + '</li>';
        var tags = (post.hashtags || []).map(function (tag) { return '<span class="sm-chip">#' + esc(tag) + '</span>'; }).join(' ');
        var activity = (post.activity || []).map(function (event, index, all) {
            return '<li class="' + (index === all.length - 1 ? 'is-last' : '') + '"><strong>' + esc(event.label) + '</strong><em>' + esc(event.at) + '</em></li>';
        }).join('');
        var failed = '';
        if (post.status === 'failed') {
            failed = '<div class="row alert__wrap"><div class="alert alert-danger"><span class="alert__icon"><i class="fas fa-exclamation-triangle"></i></span><p>Something went wrong while publishing this post.</p><p><button type="button" class="button js-retry" data-id="' + post.id + '">Retry</button> <button type="button" class="button button-white js-error" data-id="' + post.id + '" data-error="' + esc(post.error || '') + '">View Error</button></p></div></div>';
        }
        var schedule = when && when.isValid()
            ? '<p class="sm-kv"><span>Date</span> ' + when.format('MMMM D, YYYY') + '</p><p class="sm-kv"><span>Time</span> ' + when.format('HH:mm') + '</p><p class="sm-kv"><span>Timezone</span> ' + esc(post.timezone || 'Europe/Zurich') + '</p>'
            : '<p>No publishing time yet.</p>';
        var cancel = post.status === 'scheduled' ? '<button type="button" class="button button-white js-cancel" data-id="' + post.id + '">Cancel Schedule</button>' : '';
        var source = /^https?:\/\//i.test(post.source_url || '') ? '<a href="' + esc(post.source_url) + '" target="_blank" rel="noopener">' + esc(post.source_url) + '</a>' : '—';
        return failed + '<div class="form__wrap">' +
            '<div class="row form__title"><h3>Status</h3></div><div class="row form__block"><div class="col-md-12"><p><span class="sm-badge sm-badge--' + esc(post.status) + '">' + esc(statusLabel(post.status)) + '</span></p></div></div>' +
            '<div class="row form__title"><h3>Platforms</h3></div><div class="row form__block"><div class="col-md-12"><ul class="sm-check">' + platforms + '</ul></div></div>' +
            '<div class="row form__title"><h3>Content</h3></div><div class="row form__block"><div class="col-md-12"><p class="sm-kv"><span>Original prompt</span> ' + esc(post.prompt || '') + '</p><p class="sm-kv"><span>Source URL</span> ' + source + '</p><p class="sm-kv"><span>Tone of voice</span> ' + esc(post.tone || '') + '</p><p><strong>Facebook</strong></p><p>' + esc(post.facebook || '') + '</p><p><strong>Instagram</strong></p><p>' + esc(post.instagram || '') + '</p><p>' + tags + '</p></div></div>' +
            '<div class="row form__title"><h3>Media</h3></div><div class="row form__block"><div class="col-md-12"><img src="' + safeSrc(post.image_data || post.image_url) + '" alt="" class="sm-detail-image"></div></div>' +
            '<div class="row form__title"><h3>Schedule</h3></div><div class="row form__block"><div class="col-md-12">' + schedule + '</div></div>' +
            '<div class="row form__title"><h3>Activity</h3></div><ul class="sm-timeline">' + activity + '</ul>' +
            '<div class="row form__submit sm-actionbar"><a href="' + boot.urls.index + '/' + post.id + '/edit" class="button">Edit</a><a href="' + boot.urls.create + '?duplicate=' + post.id + '" class="button button-white">Duplicate</a>' + cancel + '</div></div>';
    }

    function retryPost(id) {
        request('POST', urlFor('retry', id)).done(function (res) {
            if (res.post && res.post.status === 'processing') {
                window.location = boot.urls.index + '/' + res.post.id + '/edit?step=2';
                return;
            }
            window.location.reload();
        }).fail(function (xhr) {
            showAlert('error', errorMessage(xhr, 'The post could not be retried.'));
        });
    }

    function postsForCalendar() {
        return mergePosts(boot.posts || []).filter(function (post) {
            return post.calendar_at && (post.status === 'scheduled' || post.status === 'published' || post.status === 'failed');
        });
    }

    function eventsOn(day) {
        return postsForCalendar().filter(function (post) {
            return moment(post.calendar_at, 'YYYY-MM-DD HH:mm').isSame(day, 'day');
        });
    }

    function eventButton(post) {
        var time = moment(post.calendar_at, 'YYYY-MM-DD HH:mm').format('HH:mm');
        return '<button type="button" class="sm-cal-event is-' + esc(post.status) + '" data-id="' + post.id + '">' + esc(time) + ' ' + esc(post.title) + '</button>';
    }

    function renderCalendar() {
        if (!window.moment) return;
        var title = '';
        var html = '';
        var rangeStart;
        var rangeEnd;
        if (calMode === 'month') {
            title = calCursor.format('MMMM YYYY');
            rangeStart = calCursor.clone().startOf('month').startOf('isoWeek');
            rangeEnd = calCursor.clone().endOf('month').endOf('isoWeek');
            html = '<div class="sm-cal-grid">';
            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function (day) {
                html += '<div class="sm-cal-dow">' + day + '</div>';
            });
            var cursor = rangeStart.clone();
            while (cursor.isBefore(rangeEnd) || cursor.isSame(rangeEnd, 'day')) {
                var classes = 'sm-cal-day';
                if (cursor.month() !== calCursor.month()) classes += ' is-out';
                if (cursor.isSame(moment(), 'day')) classes += ' is-today';
                html += '<div class="' + classes + '"><div class="sm-cal-num">' + cursor.date() + '</div>';
                eventsOn(cursor).forEach(function (post) { html += eventButton(post); });
                html += '</div>';
                cursor.add(1, 'day');
            }
            html += '</div>';
        } else if (calMode === 'week') {
            rangeStart = calCursor.clone().startOf('isoWeek');
            rangeEnd = calCursor.clone().endOf('isoWeek');
            title = rangeStart.format('D MMM') + ' – ' + rangeEnd.format('D MMM YYYY');
            html = '<div class="sm-cal-grid">';
            var week = rangeStart.clone();
            for (var i = 0; i < 7; i++) {
                html += '<div class="sm-cal-dow">' + week.format('ddd D') + '</div>';
                week.add(1, 'day');
            }
            week = rangeStart.clone();
            for (var d = 0; d < 7; d++) {
                html += '<div class="sm-cal-day is-week' + (week.isSame(moment(), 'day') ? ' is-today' : '') + '"><div class="sm-cal-num">' + week.format('ddd D') + '</div>';
                eventsOn(week).forEach(function (post) { html += eventButton(post); });
                html += '</div>';
                week.add(1, 'day');
            }
            html += '</div>';
        } else {
            title = calCursor.format('dddd, MMMM D, YYYY');
            rangeStart = calCursor.clone().startOf('day');
            rangeEnd = calCursor.clone().endOf('day');
            var dayEvents = eventsOn(calCursor);
            html = '<div class="sm-day-list">';
            dayEvents.forEach(function (post) {
                html += '<button type="button" class="sm-day-card is-' + esc(post.status) + '" data-id="' + post.id + '"><img src="' + safeSrc(post.image_data || post.image_url) + '" alt=""><div><strong>' + esc(post.title) + '</strong><span>' + moment(post.calendar_at, 'YYYY-MM-DD HH:mm').format('HH:mm') + ' · ' + esc(statusLabel(post.status)) + '</span></div></button>';
            });
            html += '</div>';
        }
        $('#smCalTitle').text(title);
        $('#smCal').html(html);
        var any = postsForCalendar().some(function (post) {
            var date = moment(post.calendar_at, 'YYYY-MM-DD HH:mm');
            return (date.isAfter(rangeStart, 'day') || date.isSame(rangeStart, 'day')) && (date.isBefore(rangeEnd, 'day') || date.isSame(rangeEnd, 'day'));
        });
        $('#smCalEmpty').toggleClass('is-hidden', any);
    }

    function openCalendarPost(id) {
        var post = findPost(id);
        if (!post) return;
        var when = moment(post.calendar_at, 'YYYY-MM-DD HH:mm');
        $('#smCalModalTitle').text(post.title);
        $('#smCalModalBody').html('<p><span class="sm-badge sm-badge--' + esc(post.status) + '">' + esc(statusLabel(post.status)) + '</span></p><p><img src="' + safeSrc(post.image_data || post.image_url) + '" alt="" class="sm-detail-image"></p><p>' + esc(post.excerpt || '') + '</p><p><strong>Scheduled</strong><br>' + when.format('MMMM D, YYYY') + ' at ' + when.format('HH:mm') + '</p>');
        $('#smCalView').attr('href', boot.urls.index + '/' + post.id);
        $('#smCalEdit').attr('href', boot.urls.index + '/' + post.id + '/edit');
        $('#smCalModal').modal('show');
    }

    function savedAccounts() {
        var state = loadState();
        state.accounts = state.accounts || $.extend(true, {}, boot.accounts);
        return state;
    }

    function paintAccounts() {
        var accounts = boot.accounts || {};
        var connected = 0;
        $('.sm-account').each(function () {
            var key = $(this).data('network');
            var account = accounts[key] || {};
            var isOn = !!account.connected;
            $(this).find('.sm-account__on').toggleClass('is-hidden', !isOn);
            $(this).find('.sm-account__off').toggleClass('is-hidden', isOn);
            $(this).find('.js-acc-title').text(account.title || '');
            $(this).find('.js-acc-id').text(account.account_id || '');
            $(this).find('.js-acc-sync').text(account.synced_at || '');
            if (isOn) connected += 1;
        });
        $('#smAccountsEmpty').toggleClass('is-hidden', connected !== 0);
    }

    function initIndex() {
        consumeNotice();
        applyFilters();
        $('#smFilters').on('click', '.sm-filter', function () {
            $('#smFilters .sm-filter').removeClass('is-active');
            $(this).addClass('is-active');
            applyFilters();
        });
        $('#smSearch').on('input', applyFilters);
        $('#smClearFilters').on('click', function () {
            $('#smSearch').val('');
            $('#smFilters .sm-filter').removeClass('is-active');
            $('#smFilters .sm-filter[data-status="all"]').addClass('is-active');
            applyFilters();
        });
    }

    function initCreate() {
        var active = null;
        if (boot.intent === 'edit' && boot.postId) active = findPost(boot.postId);
        if (boot.intent === 'duplicate' && boot.duplicateId) {
            active = findPost(boot.duplicateId);
            isCopy = true;
        }
        if (boot.intent === 'edit') {
            $('#sm-app .main__title h2').html('<i class="fas fa-list"></i>Edit Social Media Post');
        }
        $('#smPlatformFacebook, #smPlatformInstagram').on('change', function () {
            $(this).closest('.sm-platform').toggleClass('is-on', this.checked);
            syncPreview();
        });
        $('#smFile').on('change', function () { if (this.files[0]) readImage(this.files[0]); });
        $('#smReplaceFile').on('change', function () { if (this.files[0]) readImage(this.files[0]); });
        $('#smDrop').on('dragover dragenter', function (event) {
            event.preventDefault();
            $(this).addClass('is-over');
        }).on('dragleave drop', function (event) {
            event.preventDefault();
            $(this).removeClass('is-over');
        }).on('drop', function (event) {
            var files = event.originalEvent.dataTransfer.files;
            if (files && files[0]) readImage(files[0]);
        });
        $('#smRemoveImage').on('click', function (event) {
            event.preventDefault();
            imageData = '';
            imageUrl = '';
            imageRemoved = true;
            $('#smFile').val('');
            showChosenImage();
            syncPreview();
        });
        $('#smGenerate').on('click', function () {
            if (!validate()) return;
            var $button = $(this);
            $button.prop('disabled', true);
            processingDone = false;
            finished = false;
            savePost('generate').done(function (res) {
                if (res && res.post) {
                    applyGenerated(res.post);
                    showChosenImage();
                    syncPreview();
                }
                $('#smScheduleForm').removeClass('is-hidden');
                $('#smSuccess').addClass('is-hidden');
                furthest = Math.max(furthest, 2);
                goToStep(2);
            }).fail(function (xhr) {
                formError(errorMessage(xhr, 'The post could not be generated.'));
            }).always(function () {
                $button.prop('disabled', false);
            });
        });
        $('#smSaveDraft, #smSaveDraftReview').on('click', saveDraft);
        $('#smContinue').on('click', function () {
            furthest = Math.max(furthest, 3);
            goToStep(3);
        });
        $('#smBackContent').on('click', function () { goToStep(1); });
        $('#smStepper').on('click', 'button', function () {
            var step = Number($(this).closest('li').data('step'));
            if (step <= furthest) goToStep(step);
        });
        $('#smFbText, #smIgText, #smSource, #smPrompt').on('input', syncPreview);
        $('#smPreviewSwitch').on('click', 'button', function () {
            if ($(this).prop('disabled')) return;
            $('#smPreviewSwitch button').removeClass('is-active');
            $(this).addClass('is-active');
            syncPreview();
        });
        $('#smHashtagInput').on('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            var tag = $(this).val().replace(/#/g, '').trim().toLowerCase().replace(/\s+/g, '');
            if (!tag || readTags().indexOf(tag) !== -1 || readTags().length >= 12) return;
            $('#smHashtags').append('<span class="sm-chip" data-tag="' + esc(tag) + '">#' + esc(tag) + ' <button type="button" aria-label="Remove hashtag">&times;</button></span>');
            $(this).val('');
            syncPreview();
        });
        $(document).on('click', '.sm-chip button', function () {
            $(this).closest('.sm-chip').remove();
            syncPreview();
        });
        $('#smRefineBtn').on('click', function () {
            var note = $.trim($('#smRefine').val());
            if (!note) {
                showAlert('error', 'Tell AI what you want to change.');
                return;
            }
            if (!$.trim($('#smPrompt').val())) {
                formError('Add a short prompt before refining.');
                return;
            }
            var $button = $(this);
            $button.prop('disabled', true).text('Refining...');
            savePost('review').done(function (res) {
                request('POST', urlFor('refine', res.post.id), { note: note }).done(function (refined) {
                    applyGenerated(refined.post);
                    syncPreview();
                    showAlert('success', 'Content updated from your note.');
                }).fail(function (xhr) {
                    showAlert('error', errorMessage(xhr, 'The content could not be refined.'));
                }).always(function () {
                    $button.prop('disabled', false).text('Refine Content');
                });
            }).fail(function (xhr) {
                $button.prop('disabled', false).text('Refine Content');
                showAlert('error', errorMessage(xhr, 'The post could not be saved.'));
            });
        });
        $('#smReplace').on('click', function () { $('#smReplaceFile').trigger('click'); });
        $('#smGenerateImage').on('click', function () {
            if (!$.trim($('#smPrompt').val())) {
                showAlert('error', 'Add a prompt before generating an image.');
                return;
            }
            var $button = $(this);
            $button.prop('disabled', true).text('Generating...');
            savePost('review').done(function (res) {
                request('POST', urlFor('image', res.post.id), {}).done(function (done) {
                    remember(done.post);
                    imageData = '';
                    imageRemoved = false;
                    imageUrl = done.post.image_url;
                    showChosenImage();
                    syncPreview();
                    showAlert('success', 'A new image is ready.');
                }).fail(function (xhr) {
                    showAlert('error', errorMessage(xhr, 'The image could not be generated.'));
                }).always(function () {
                    $button.prop('disabled', false).html('<i class="fas fa-magic"></i> Generate Image');
                });
            }).fail(function (xhr) {
                $button.prop('disabled', false).html('<i class="fas fa-magic"></i> Generate Image');
                showAlert('error', errorMessage(xhr, 'The post could not be saved.'));
            });
        });
        $('#smApprove').on('click', function () {
            furthest = Math.max(furthest, 4);
            finished = false;
            $('#smScheduleForm').removeClass('is-hidden');
            $('#smSuccess').addClass('is-hidden');
            goToStep(4);
        });
        $('#smOpenModal').on('click', function () {
            ensureScheduleDefaults();
            initPickers();
            $('#smModalDateInput').val($('#smDateInput').val());
            $('#smModalTimeInput').val($('#smTimeInput').val());
            $('#smModalTz').val($('#smTz').val());
            $('#smScheduleModal').modal('show');
        });
        $('#smModalSchedule').on('click', function () {
            $('#smDateInput').val($('#smModalDateInput').val());
            $('#smTimeInput').val($('#smModalTimeInput').val());
            $('#smTz').val($('#smModalTz').val());
            if (!readSchedule()) {
                showAlert('error', 'Choose a valid date and time.');
                return;
            }
            $('#smScheduleModal').modal('hide');
            finish('scheduled');
        });
        $('#smPublishNow, #smPublishStep4').on('click', function () { finish('published'); });
        $('#smScheduleBtn').on('click', function () { finish('scheduled'); });
        $('#smDateInput, #smTimeInput, #smTz').on('change input', function () {
            syncPreview();
            renderStep4();
        });

        if (active) {
            fillForm(active);
            furthest = 4;
            if (active.status !== 'processing') processingDone = true;
        }
        var start = 1;
        var map = { draft: 1, processing: 2, pending: 3, scheduled: 4, published: 3, failed: 3 };
        if (active && boot.intent === 'edit') start = map[active.status] || 3;
        if (boot.step && boot.step <= furthest) start = boot.step;
        ensureScheduleDefaults();
        goToStep(start, true);
    }

    function initShow() {
        $(document).on('click', '.js-cancel', function () {
            var id = $(this).data('id');
            request('POST', urlFor('cancel', id)).done(function (res) {
                remember(res.post);
                $('#sm-detail-mount').html(renderDetail(res.post));
                showAlert('success', 'The publishing time was removed. The post is back in Pending Approval.');
            }).fail(function (xhr) {
                showAlert('error', errorMessage(xhr, 'The schedule could not be cancelled.'));
            });
        });
    }

    function initCalendar() {
        $('#smCalViews').on('click', 'button', function () {
            calMode = $(this).data('view');
            $('#smCalViews button').removeClass('is-active');
            $(this).addClass('is-active');
            renderCalendar();
        });
        $('#smCalPrev').on('click', function () {
            calCursor.subtract(1, calMode);
            renderCalendar();
        });
        $('#smCalNext').on('click', function () {
            calCursor.add(1, calMode);
            renderCalendar();
        });
        $('#smCalToday').on('click', function () {
            calCursor = moment();
            renderCalendar();
        });
        $(document).on('click', '.sm-cal-event, .sm-day-card', function () {
            openCalendarPost($(this).data('id'));
        });
        renderCalendar();
    }

    function initAccounts() {
        paintAccounts();
        $(document).on('click', '.js-manage', function () {
            manageNetwork = $(this).data('network');
            var account = (boot.accounts && boot.accounts[manageNetwork]) || {};
            $('#smManageTitle').text('Manage ' + (account.name || 'account'));
            $('#smManageBody').html('<p class="sm-kv"><span>Name</span> ' + esc(account.title || '') + '</p><p class="sm-kv"><span>Account ID</span> ' + esc(account.account_id || '') + '</p><p class="sm-kv"><span>Last synchronized</span> ' + esc(account.synced_at || '') + '</p><p class="sm-kv"><span>Status</span> ' + (account.connected ? 'Connected' : 'Not connected') + '</p>');
            $('#smManageModal').modal('show');
        });
        $('#smDisconnect').on('click', function () {
            if (!manageNetwork) return;
            var network = manageNetwork;
            request('POST', urlFor('disconnect', network)).done(function (res) {
                boot.accounts = res.accounts || boot.accounts;
                $('#smManageModal').modal('hide');
                paintAccounts();
                showAlert('success', ((boot.accounts[network] && boot.accounts[network].name) || 'Account') + ' was disconnected.');
            }).fail(function (xhr) {
                showAlert('error', errorMessage(xhr, 'The account could not be disconnected.'));
            });
        });
        $(document).on('click', '.js-connect', function () {
            connectNetwork = $(this).data('network');
            var account = boot.accounts[connectNetwork] || {};
            $('#smConnectTitle').text('Connect ' + (account.name || 'account'));
            $('#smConnectCopy').text('Confirm the ' + (account.name || 'social') + ' profile this CMS should publish to.');
            $('#smConnectChoice').html('<option>' + esc(account.title || account.name || '') + '</option>');
            $('#smConnectConfirm').prop('disabled', false).text('Connect');
            $('#smConnectModal').modal('show');
        });
        $('#smConnectConfirm').on('click', function () {
            var $button = $(this);
            var network = connectNetwork;
            $button.prop('disabled', true).text('Connecting...');
            request('POST', urlFor('connect', network)).done(function (res) {
                boot.accounts = res.accounts || boot.accounts;
                $('#smConnectModal').modal('hide');
                paintAccounts();
                showAlert('success', ((boot.accounts[network] && boot.accounts[network].name) || 'Account') + ' is connected.');
            }).fail(function (xhr) {
                showAlert('error', errorMessage(xhr, 'The account could not be connected.'));
            }).always(function () {
                $button.prop('disabled', false).text('Connect');
            });
        });
    }

    function initGlobal() {
        $(document).on('click', '.js-delete', function (event) {
            event.preventDefault();
            pendingDeleteId = Number($(this).data('id'));
            $('#smDeleteName').text($(this).data('title') || 'this post');
            $('#smDeleteModal').modal('show');
        });
        $('#smDeleteConfirm').on('click', function () {
            var id = pendingDeleteId;
            request('DELETE', urlFor('destroy', id)).done(function () {
                boot.posts = (boot.posts || []).filter(function (post) { return Number(post.id) !== id; });
                $('#smDeleteModal').modal('hide');
                if ($('#sm-app').data('page') === 'show') {
                    sessionStorage.setItem(NOTICE, 'Post deleted.');
                    window.location = boot.urls.index;
                    return;
                }
                $('#smBody tr[data-id="' + id + '"]').remove();
                updateFilterCounts();
                applyFilters();
                showAlert('success', 'Post deleted.');
            }).fail(function (xhr) {
                showAlert('error', errorMessage(xhr, 'The post could not be deleted.'));
            });
        });
        $(document).on('click', '.js-error', function () {
            pendingErrorId = Number($(this).data('id'));
            $('#smErrorText').text($(this).attr('data-error') || 'The publishing service did not return any details.');
            $('#smErrorModal').modal('show');
        });
        $('#smErrorRetry').on('click', function () {
            $('#smErrorModal').modal('hide');
            if (pendingErrorId) retryPost(pendingErrorId);
        });
        $(document).on('click', '.js-retry', function () {
            retryPost($(this).data('id'));
        });
    }

    $(function () {
        $('.sm-modal').appendTo('body');
        var page = $('#sm-app').data('page');
        initGlobal();
        if (page === 'index') initIndex();
        if (page === 'create') initCreate();
        if (page === 'show') initShow();
        if (page === 'calendar') initCalendar();
        if (page === 'accounts') initAccounts();
    });
})(jQuery);
