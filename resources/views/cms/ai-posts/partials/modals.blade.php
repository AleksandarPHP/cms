<div class="modal fade sm-modal" id="smDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Delete post</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="smDeleteName">this post</strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-white" data-dismiss="modal">Cancel</button>
                <button type="button" class="button button-red" id="smDeleteConfirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sm-modal" id="smErrorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">View Error</h4>
            </div>
            <div class="modal-body">
                <p><strong>Something went wrong while publishing this post.</strong></p>
                <p id="smErrorText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-white" data-dismiss="modal">Close</button>
                <button type="button" class="button" id="smErrorRetry">Retry</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sm-modal" id="smScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Schedule Post</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6">
                        <label>Date</label>
                        <div class="dt-picker">
                            <div class="input-group date" id="smModalDate">
                                <input type="text" class="form-control" id="smModalDateInput" placeholder="DD.MM.YYYY">
                                <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label>Time</label>
                        <div class="dt-picker">
                            <div class="input-group date" id="smModalTime">
                                <input type="text" class="form-control" id="smModalTimeInput" placeholder="HH:mm">
                                <span class="input-group-addon"><i class="far fa-clock"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <label>Timezone</label>
                <select class="select-default" id="smModalTz">
                    <option value="Europe/Zurich">Europe/Zurich</option>
                    <option value="UTC">UTC</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-white" data-dismiss="modal">Cancel</button>
                <button type="button" class="button" id="smModalSchedule">Schedule Post</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sm-modal" id="smCalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="smCalModalTitle">Post</h4>
            </div>
            <div class="modal-body" id="smCalModalBody"></div>
            <div class="modal-footer">
                <a href="#" class="button button-white" id="smCalEdit">Edit</a>
                <a href="#" class="button" id="smCalView">View</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sm-modal" id="smManageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="smManageTitle">Manage account</h4>
            </div>
            <div class="modal-body" id="smManageBody"></div>
            <div class="modal-footer">
                <button type="button" class="button button-white" data-dismiss="modal">Close</button>
                <button type="button" class="button button-red" id="smDisconnect">Disconnect</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sm-modal" id="smConnectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="smConnectTitle">Connect account</h4>
            </div>
            <div class="modal-body">
                <p id="smConnectCopy"></p>
                <p class="sm-help">Uses the Facebook and Instagram credentials already stored for this CMS.</p>
                <label id="smConnectLabel">Account</label>
                <select class="select-default" id="smConnectChoice"></select>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-white" data-dismiss="modal">Cancel</button>
                <button type="button" class="button" id="smConnectConfirm">Connect</button>
            </div>
        </div>
    </div>
</div>
