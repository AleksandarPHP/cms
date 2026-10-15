(function ($) {
    var root = document.getElementById('plan-app');
    if (!root) return;

    var page = root.getAttribute('data-page');
    var boot = window.PLAN_BOOT || {};
    var filter = 'all';
    var openId = null;
    var busy = false;
    var pumping = false;

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function urlFor(name, id) {
        return String((boot.urls && boot.urls[name]) || '').replace('__ID__', id);
    }

    function request(method, url, data) {
        var options = {
            url: url,
            method: method,
            dataType: 'json',
            timeout: 180000,
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

    function errorMessage(xhr, fallback) {
        var body = xhr && xhr.responseJSON;
        if (body && body.errors) {
            var first = Object.keys(body.errors)[0];
            if (first && body.errors[first] && body.errors[first][0]) return body.errors[first][0];
        }
        if (body && body.message) return body.message;
        return fallback;
    }

    function showError(message) {
        $('#planAlertText').text(message || 'Something went wrong.');
        $('#planAlert').show();
    }

    function hideError() {
        $('#planAlert').hide();
    }

    function localValue(value) {
        if (!value) return '';
        return String(value).replace(' ', 'T').slice(0, 16);
    }

    function serverValue(value) {
        if (!value) return '';
        return String(value).replace('T', ' ').slice(0, 16);
    }

    function tags(value) {
        return String(value || '').split(',').map(function (tag) {
            return $.trim(tag).replace(/^#/, '');
        }).filter(Boolean);
    }

    function statusLabel(status) {
        return (boot.statuses && boot.statuses[status]) || status;
    }

    function typeLabel(type) {
        type = String(type || '');
        return type ? type.charAt(0).toUpperCase() + type.slice(1) : '';
    }

    function whenLabel(value) {
        if (!value || !window.moment) return value || '—';
        return moment(value, 'YYYY-MM-DD HH:mm').format('D MMM YYYY, HH:mm');
    }

    function isPast(value) {
        if (!value || !window.moment) return false;
        return moment(value, 'YYYY-MM-DD HH:mm').isBefore(moment());
    }

    function applyState(data) {
        boot.plan = data.plan;
        boot.posts = data.posts || [];
        boot.counts = data.counts || {};
        boot.statuses = data.statuses || boot.statuses;
        if (typeof data.advanced !== 'undefined') boot.advanced = data.advanced;
        render();
    }

    function visiblePosts() {
        return (boot.posts || []).filter(function (post) {
            return filter === 'all' || post.status === filter;
        });
    }

    function cardPayload(card) {
        return {
            post_title: $.trim(card.find('[data-field="title"]').val()),
            facebook: card.find('[data-field="facebook"]').val() || '',
            instagram: card.find('[data-field="instagram"]').val() || '',
            hashtags: tags(card.find('[data-field="hashtags"]').val()),
            generated_image_prompt: card.find('[data-field="image"]').val() || '',
            scheduled_at: serverValue(card.find('[data-field="when"]').val()),
            timezone: boot.plan.timezone
        };
    }

    function renderSummary() {
        var plan = boot.plan || {};
        var counts = boot.counts || {};
        var total = counts.total || 0;
        var written = total - (counts.generating || 0);
        var width = total ? Math.round((written / total) * 100) : 0;
        var days = (plan.publish_days || []).map(function (day) {
            return day.charAt(0).toUpperCase() + day.slice(1);
        }).join(', ');
        var horizon = (plan.duration_months || 1) + ' month' + (plan.duration_months === 1 ? '' : 's');
        var meta = horizon + ' · ' + (plan.posts_per_week || 0) + '/week · ' + (plan.publish_time || '') + ' · ' + (plan.timezone || '');
        if (days) meta += ' · ' + days;
        if (plan.day_type_summary && plan.day_type_summary.length) meta += ' · ' + plan.day_type_summary.join(', ');
        var progress = '';
        if (plan.status === 'generating') {
            progress = '<div class="sm-plan-progress"><span style="width:' + width + '%"></span></div>' +
                '<p>Writing posts ' + written + ' of ' + total + '. You can review the ones that are already ready.</p>';
            if (plan.error_message) {
                progress += '<p class="sm-plan-warn">' + esc(plan.error_message) + '</p>' +
                    '<button type="button" class="button" id="planContinue">Continue generation</button>';
            }
        } else if (plan.status === 'failed') {
            progress = '<p class="sm-plan-warn">' + esc(plan.error_message || 'Generation stopped before every post was written.') + '</p>' +
                '<button type="button" class="button" id="planRetry">Try generation again</button>';
        } else {
            progress = '<p>' + (counts.review || 0) + ' waiting for review · ' + (counts.scheduled || 0) + ' scheduled · ' + (counts.published || 0) + ' published.</p>' +
                '<p class="sm-help">Approved posts appear on the <a href="' + esc(boot.urls.calendar) + '">calendar</a> and are published automatically when their time arrives.</p>';
        }
        var research = plan.research_summary
            ? '<details class="sm-plan-research"><summary>Research notes</summary><p>' + esc(plan.research_summary) + '</p></details>'
            : '';
        $('#planSummary').html(
            '<h3>' + esc(plan.title || 'Content plan') + '</h3>' +
            '<p class="sm-plan-meta">' + esc(meta) + '</p>' +
            progress + research
        );
    }

    function renderCard(post) {
        var open = Number(openId) === Number(post.id);
        var locked = post.status === 'published';
        var tagsValue = (post.hashtags || []).join(', ');
        var past = isPast(post.scheduled_at) && post.status !== 'published';
        var image = post.has_image
            ? '<img class="sm-thumb" src="' + esc(post.image_url) + '" alt="">'
            : '';
        var error = post.error ? '<p class="sm-plan-warn">' + esc(post.error) + '</p>' : '';
        var editor = open ? (
            '<div class="sm-plan-editor">' +
                '<label>Title<input class="input-default" data-field="title" value="' + esc(post.post_title || post.title) + '"' + (locked ? ' disabled' : '') + '></label>' +
                '<label>Scheduled<input type="datetime-local" class="input-default" data-field="when" value="' + esc(localValue(post.scheduled_at)) + '"' + (locked ? ' disabled' : '') + '></label>' +
                (past ? '<p class="sm-plan-warn is-wide">This time has already passed. Approving it publishes the post on the next scheduler run.</p>' : '') +
                '<label class="is-wide">Facebook<textarea class="textarea-default" rows="4" data-field="facebook"' + (locked ? ' disabled' : '') + '>' + esc(post.facebook) + '</textarea></label>' +
                '<label class="is-wide">Instagram<textarea class="textarea-default" rows="5" data-field="instagram"' + (locked ? ' disabled' : '') + '>' + esc(post.instagram) + '</textarea></label>' +
                '<label>Hashtags<input class="input-default" data-field="hashtags" value="' + esc(tagsValue) + '"' + (locked ? ' disabled' : '') + '></label>' +
                '<label>Image prompt<textarea class="textarea-default" rows="3" data-field="image"' + (locked ? ' disabled' : '') + '>' + esc(post.generated_image_prompt) + '</textarea></label>' +
                '<div class="sm-plan-actions">' +
                    (locked ? '' : '<button type="button" class="button js-save" data-id="' + post.id + '">Save</button>') +
                    (locked ? '' : '<button type="button" class="button button-white js-regenerate" data-id="' + post.id + '">Regenerate copy</button>') +
                    (locked ? '' : '<button type="button" class="button button-white js-image-prompt" data-id="' + post.id + '">New image prompt</button>') +
                    (locked ? '' : '<button type="button" class="button button-white js-image" data-id="' + post.id + '">Generate image</button>') +
                    '<a class="button button-white" href="' + esc(urlFor('show_post', post.id)) + '">Open post</a>' +
                '</div>' +
            '</div>'
        ) : '';
        return '<article class="sm-plan-card' + (open ? ' is-open' : '') + '" data-id="' + post.id + '" data-status="' + esc(post.status) + '">' +
            '<div class="sm-plan-card__head">' +
                (locked ? '' : '<input type="checkbox" class="js-pick" value="' + post.id + '">') +
                image +
                '<div class="sm-plan-card__copy">' +
                    '<strong>' + esc(post.title) + '</strong>' +
                    '<span>' + esc(whenLabel(post.scheduled_at)) + ((post.post_type_label || post.post_type) ? ' · ' + esc(post.post_type_label || typeLabel(post.post_type)) : '') + ' · ' + esc(post.timezone || '') + '</span>' +
                    '<p>' + esc(post.excerpt || '') + '</p>' +
                    error +
                '</div>' +
                '<span class="sm-badge sm-badge--' + esc(post.status) + '">' + esc(statusLabel(post.status)) + '</span>' +
            '</div>' +
            '<div class="sm-plan-actions">' +
                '<button type="button" class="button button-white js-toggle" data-id="' + post.id + '">' + (open ? 'Close' : 'Edit') + '</button>' +
                (locked || post.status === 'processing' ? '' : '<button type="button" class="button js-approve" data-id="' + post.id + '">Approve</button>') +
                (locked ? '' : '<button type="button" class="button button-white js-reject" data-id="' + post.id + '">Reject</button>') +
            '</div>' +
            editor +
        '</article>';
    }

    function render() {
        renderSummary();
        var posts = visiblePosts();
        $('#planList').html(posts.map(renderCard).join(''));
        $('#planEmpty').toggleClass('is-hidden', posts.length > 0);
        $('#planSelectAll').prop('checked', false);
        $('#plan-app').toggleClass('is-busy', busy);
    }

    function run(action) {
        if (busy) return;
        busy = true;
        render();
        hideError();
        action().always(function () {
            busy = false;
            render();
        });
    }

    function saveResult(xhr) {
        showError(errorMessage(xhr, 'The post could not be saved.'));
    }

    function pump() {
        if (pumping || busy) return;
        if (!boot.plan || boot.plan.status !== 'generating' || boot.plan.error_message) return;
        pumping = true;
        request('POST', boot.urls.advance).done(function (data) {
            applyState(data);
            pumping = false;
            if (data.plan && data.plan.status === 'generating' && !data.plan.error_message) {
                if (data.advanced) {
                    pump();
                } else {
                    window.setTimeout(pump, 3000);
                }
            }
        }).fail(function (xhr) {
            pumping = false;
            showError(errorMessage(xhr, 'Generation paused. Continue when you are ready.'));
        });
    }

    function checkedIds() {
        return $('#planList .js-pick:checked').map(function () {
            return Number(this.value);
        }).get();
    }

    function readyIds() {
        return (boot.posts || []).filter(function (post) {
            return post.status === 'pending' && (post.facebook || post.instagram);
        }).map(function (post) { return post.id; });
    }

    if (page === 'create') {
        var maxPosts = Number(root.getAttribute('data-max-posts') || 72);

        function syncDayTypes() {
            var any = false;
            $('#planDays input[type="checkbox"]').each(function () {
                var row = $('#planDayTypes').find('[data-day="' + this.value + '"]');
                var field = row.find('select');
                row.toggleClass('is-hidden', !this.checked);
                field.prop('disabled', !this.checked);
                field.prop('required', !!this.checked);
                if (this.checked) any = true;
            });
            $('#planDayTypes').toggleClass('is-hidden', !any);
        }

        function estimate() {
            var months = Number($('input[name="duration_months"]:checked').val() || 1);
            var selectedDays = $('#planDays input:checked').length;
            var perWeek = selectedDays || Number($('#planFrequency').val() || 2);
            var count = Math.round(months * 4.345 * perWeek);
            if (count > maxPosts) count = maxPosts;
            var note = 'About ' + count + ' posts';
            if (count >= maxPosts) note += '. Plans stop at ' + maxPosts + ' posts.';
            if (selectedDays) note += ' Specific days replace the weekly frequency.';
            $('#planEstimate').text(note);
        }

        $('#planDays').on('change', 'input', syncDayTypes);
        $('#plan-app').on('change', 'input, select', estimate);
        syncDayTypes();
        $('#planForm').on('submit', function () {
            $('#planSubmit').prop('disabled', true).text('Creating plan...');
        });
        $('#plan-app').on('change', '.sm-platform input', function () {
            $(this).closest('.sm-platform').toggleClass('is-on', this.checked);
        });
        estimate();
        return;
    }

    if (page !== 'show') return;

    $('#planAlert').on('click', '.alert__btn', hideError);

    $('#planFilters').on('click', '.sm-filter', function () {
        filter = $(this).data('status');
        $('#planFilters .sm-filter').removeClass('is-active');
        $(this).addClass('is-active');
        render();
    });

    $('#planSelectAll').on('change', function () {
        $('#planList .js-pick').prop('checked', this.checked);
    });

    $('#planSummary').on('click', '#planContinue', function () {
        boot.plan.error_message = '';
        hideError();
        pump();
    });

    $('#planSummary').on('click', '#planRetry', function () {
        if (busy) return;
        busy = true;
        render();
        hideError();
        request('POST', boot.urls.retry).done(function (data) {
            applyState(data);
        }).fail(function (xhr) {
            showError(errorMessage(xhr, 'Generation could not be restarted.'));
        }).always(function () {
            busy = false;
            render();
            pump();
        });
    });

    $('#planApproveSelected, #planApproveReady').on('click', function () {
        var ids = this.id === 'planApproveReady' ? readyIds() : checkedIds();
        if (!ids.length) {
            showError(this.id === 'planApproveReady' ? 'No reviewed posts are ready to approve.' : 'Select at least one post.');
            return;
        }
        run(function () {
            return request('POST', boot.urls.approve_many, { ids: ids }).done(applyState).fail(saveResult);
        });
    });

    $('#planRejectSelected').on('click', function () {
        var ids = checkedIds();
        if (!ids.length) {
            showError('Select at least one post.');
            return;
        }
        if (!window.confirm('Remove the selected posts from this plan?')) return;
        run(function () {
            return request('POST', boot.urls.reject_many, { ids: ids }).done(applyState).fail(saveResult);
        });
    });

    $('#planList').on('click', '.js-toggle', function () {
        var id = Number($(this).data('id'));
        openId = openId === id ? null : id;
        render();
    });

    $('#planList').on('click', '.js-save, .js-approve', function () {
        var id = Number($(this).data('id'));
        var card = $(this).closest('.sm-plan-card');
        var approving = $(this).hasClass('js-approve');
        if (!card.find('[data-field="facebook"]').length) {
            openId = id;
            render();
            card = $('#planList .sm-plan-card[data-id="' + id + '"]');
        }
        var payload = cardPayload(card);
        run(function () {
            return request(approving ? 'POST' : 'PUT', urlFor(approving ? 'approve' : 'update', id), payload)
                .done(function (data) {
                    openId = id;
                    applyState(data);
                })
                .fail(saveResult);
        });
    });

    $('#planList').on('click', '.js-reject', function () {
        var id = Number($(this).data('id'));
        if (!window.confirm('Remove this post from the plan?')) return;
        run(function () {
            return request('POST', urlFor('reject', id)).done(applyState).fail(saveResult);
        });
    });

    $('#planList').on('click', '.js-regenerate, .js-image-prompt, .js-image', function () {
        var id = Number($(this).data('id'));
        var button = $(this);
        var name = button.hasClass('js-regenerate') ? 'regenerate' : (button.hasClass('js-image-prompt') ? 'image_prompt' : 'image');
        openId = id;
        run(function () {
            return request('POST', urlFor(name, id)).done(function (data) {
                if (name === 'image' && data.post) {
                    boot.posts = (boot.posts || []).map(function (post) {
                        if (Number(post.id) !== Number(data.post.id)) return post;
                        post.image_url = data.post.image_url;
                        post.has_image = true;
                        post.generated_image_prompt = data.post.generated_image_prompt;
                        return post;
                    });
                    render();
                    return;
                }
                applyState(data);
            }).fail(saveResult);
        });
    });

    render();
    pump();
})(jQuery);
