@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main sm-module" id="sm-app" data-page="create">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'Create Social Media Post',
        'description' => 'Write the brief, review Facebook and Instagram, then schedule or publish.',
    ])

    <ol class="sm-stepper" id="smStepper">
        <li data-step="1" class="is-current"><button type="button"><span>1</span> Content</button></li>
        <li data-step="2" class="is-locked"><button type="button"><span>2</span> AI Processing</button></li>
        <li data-step="3" class="is-locked"><button type="button"><span>3</span> Review &amp; Edit</button></li>
        <li data-step="4" class="is-locked"><button type="button"><span>4</span> Schedule / Publish</button></li>
    </ol>

    <div class="sm-step is-on" id="smStep1" data-step="1">
        <div class="form__wrap">
            <div class="row alert__wrap" id="smFormAlert" style="display:none">
                <div class="alert alert-danger">
                    <span class="alert__icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <p id="smFormAlertText"></p>
                    <span class="alert__btn"><i class="fas fa-times"></i></span>
                </div>
            </div>
            <div class="row form__title"><h3>Content Prompt</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">What would you like to post about?</label></div>
                <div class="col-md-9">
                    <textarea id="smPrompt" rows="5" class="textarea-default" placeholder="Write a post about our new summer promotion with 20% discount on all products."></textarea>
                </div>
            </div>
            <div class="row form__title"><h3>Source URL</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Source URL (optional)</label></div>
                <div class="col-md-9">
                    <input type="url" id="smSource" class="input-default" placeholder="https://example.com/blog/article">
                    <p class="sm-help">Add a blog post, product page or other source URL.</p>
                </div>
            </div>
            <div class="row form__title"><h3>Media</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Upload Image</label></div>
                <div class="col-md-9">
                    <label class="gallery-upload" id="smDrop">
                        <img src="{{ asset('cmsfiles/images/photo.png') }}" alt="" class="sm-drop-ph">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <strong>Drag &amp; drop your image here</strong>
                        <p>or browse files</p>
                        <input type="file" id="smFile" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                    <div class="add-image is-hidden" id="smImagePreview">
                        <div class="add-image-wrap">
                            <a href="#" id="smImageLink"><img src="" alt="Uploaded image" id="smImageTag"></a>
                            <span id="smRemoveImage" title="Remove">&times;</span>
                        </div>
                    </div>
                    <p class="sm-help">JPG, PNG, GIF or WebP. Maximum 1.5 MB.</p>
                </div>
            </div>
            <div class="row form__title"><h3>Image instructions</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left" for="smImageInstructions">How should the image look?</label></div>
                <div class="col-md-9">
                    <textarea id="smImageInstructions" rows="4" class="textarea-default" placeholder="Postavi ovaj proizvod na plažu i u gornjem desnom uglu napiši 20% popusta"></textarea>
                    <p class="sm-help">Describe a new visual, or how an uploaded image should be changed. Leave this empty to keep the photo as it is.</p>
                </div>
            </div>
            <div class="row form__title"><h3>Platforms</h3></div>
            <div class="row form__block">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-sm-6">
                            <label class="sm-platform is-on">
                                <i class="fab fa-facebook-f sm-ico sm-ico--fb"></i>
                                <input type="checkbox" id="smPlatformFacebook" value="facebook" checked>
                                <strong>Facebook</strong>
                                <small>Facebook Page</small>
                                <em>{{ $brand['page'] }}</em>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="sm-platform is-on">
                                <i class="fab fa-instagram sm-ico sm-ico--ig"></i>
                                <input type="checkbox" id="smPlatformInstagram" value="instagram" checked>
                                <strong>Instagram</strong>
                                <small>Instagram</small>
                                <em>{{ $brand['instagram'] }}</em>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row form__title"><h3>Post Settings</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Tone of Voice</label></div>
                <div class="col-md-9">
                    <select class="select-default" id="smTone">
                        <option value="professional">Professional</option>
                        <option value="casual">Casual</option>
                        <option value="promotional">Promotional</option>
                        <option value="educational">Educational</option>
                    </select>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Web Research &amp; Trend Matching</label></div>
                <div class="col-md-9">
                    <div class="onof">
                        <label><input type="radio" name="research" value="1" checked> <span>On</span></label>
                        <label><input type="radio" name="research" value="0"> <span>Off</span></label>
                    </div>
                    <p class="sm-help">Use current web trends and relevant topics to improve the generated content.</p>
                </div>
            </div>
            <div class="row form__submit sm-actionbar">
                <button type="button" class="button" id="smGenerate">Generate Post</button>
                <button type="button" class="button button-white" id="smSaveDraft">Save as Draft</button>
            </div>
        </div>
    </div>

    <div class="sm-step" id="smStep2" data-step="2">
        <div class="form__wrap">
            <div class="row form__title"><h3>Preparing your social media post</h3></div>
            <ul class="sm-progress" id="smProgress"></ul>
            <div class="row form__submit sm-actionbar">
                <button type="button" class="button" id="smContinue" disabled>Continue to Review</button>
                <button type="button" class="button button-white" id="smBackContent">Back to Content</button>
            </div>
        </div>
    </div>

    <div class="sm-step" id="smStep3" data-step="3">
        <div class="form__wrap">
            <div class="row form__title"><h3>Review Your Posts</h3></div>
            <div class="row form__block">
                <div class="col-md-12">
                    <div class="sm-media-bar">
                        <img src="{{ asset('cmsfiles/images/photo.png') }}" alt="" id="smMediaThumb">
                        <div>
                            <button type="button" class="button button-white" id="smReplace"><i class="fas fa-upload"></i> Replace Image</button>
                            <button type="button" class="button button-white" id="smGenerateImage"><i class="fas fa-magic"></i> Generate Image</button>
                            <input type="file" id="smReplaceFile" class="is-hidden" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                    </div>
                    <label class="label-left" for="smGeneratedImagePrompt">Prepared visual prompt</label>
                    <textarea id="smGeneratedImagePrompt" rows="5" class="textarea-default" placeholder="The visual instruction appears here after generation."></textarea>
                    <p class="sm-help">This is the instruction used when you generate the image. Edit it before generating if you want a different result.</p>
                    <div class="sm-switch" id="smPreviewSwitch">
                        <button type="button" class="button button-white is-active" data-preview="both">Both</button>
                        <button type="button" class="button button-white" data-preview="facebook">Facebook</button>
                        <button type="button" class="button button-white" data-preview="instagram">Instagram</button>
                    </div>
                    <div class="row">
                        <div class="col-sm-6" id="smColFb">
                            <article class="sm-card sm-card--fb">
                                <div class="sm-card__head">
                                    <i class="fab fa-facebook-f sm-ico sm-ico--fb"></i>
                                    <span class="sm-avatar">N</span>
                                    <strong>{{ $brand['page'] }}</strong>
                                    <span id="smFbTime">Preview · Not scheduled</span>
                                </div>
                                <div class="sm-copy" id="smFbCopy">
                                    <div class="sm-copy-text" id="smFbCopyText"></div>
                                    <div class="sm-copy-empty">No generated content.</div>
                                </div>
                                <div class="sm-visual" id="smFbVisual">
                                    <img src="" alt="" id="smFbImg">
                                    <div class="sm-visual-empty">No image yet</div>
                                </div>
                                <a class="sm-linkbar is-hidden" id="smFbLink" href="#" target="_blank" rel="noopener">
                                    <small id="smFbLinkHost"></small>
                                    <span id="smFbLinkTitle"></span>
                                </a>
                                <div class="sm-social-actions">
                                    <span><i class="far fa-thumbs-up"></i> Like</span>
                                    <span><i class="far fa-comment"></i> Comment</span>
                                    <span><i class="fas fa-share"></i> Share</span>
                                </div>
                            </article>
                            <label class="label-left">Facebook Content</label>
                            <textarea id="smFbText" rows="6" class="textarea-default"></textarea>
                            <div class="sm-counter" id="smFbCount">0 / 500</div>
                        </div>
                        <div class="col-sm-6" id="smColIg">
                            <article class="sm-card sm-card--ig">
                                <div class="sm-card__head">
                                    <i class="fab fa-instagram sm-ico sm-ico--ig"></i>
                                    <span class="sm-avatar">N</span>
                                    <strong>{{ $brand['handle'] }}</strong>
                                    <span>Instagram</span>
                                </div>
                                <div class="sm-visual" id="smIgVisual">
                                    <img src="" alt="" id="smIgImg">
                                    <div class="sm-visual-empty">No image yet</div>
                                </div>
                                <div class="sm-ig-actions">
                                    <span><i class="far fa-heart"></i></span>
                                    <span><i class="far fa-comment"></i></span>
                                    <span><i class="far fa-paper-plane"></i></span>
                                    <span class="sm-ig-save"><i class="far fa-bookmark"></i></span>
                                </div>
                                <div class="sm-caption" id="smIgCopy">
                                    <strong>{{ $brand['handle'] }}</strong>
                                    <span class="sm-copy-text" id="smIgCopyText"></span>
                                    <div class="sm-copy-empty">No generated content.</div>
                                </div>
                                <div class="sm-tags" id="smIgTags"></div>
                            </article>
                            <label class="label-left">Instagram Caption</label>
                            <textarea id="smIgText" rows="6" class="textarea-default"></textarea>
                            <div class="sm-counter" id="smIgCount">0 / 2200</div>
                            <label class="label-left">Hashtags</label>
                            <div id="smHashtags"></div>
                            <input type="text" id="smHashtagInput" class="input-default" placeholder="Add a hashtag and press Enter">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row form__title"><h3>AI Refine</h3></div>
            <div class="row form__block">
                <div class="col-md-12">
                    <textarea id="smRefine" rows="3" class="textarea-default" placeholder="Make it shorter"></textarea>
                    <p class="sm-help">For example: Add a stronger call to action, or make the tone more casual.</p>
                    <button type="button" class="button" id="smRefineBtn">Refine Content</button>
                </div>
            </div>
            <div class="row form__submit sm-actionbar">
                <button type="button" class="button button-white" id="smSaveDraftReview">Save Draft</button>
                <button type="button" class="button" id="smApprove">Approve &amp; Schedule</button>
                <button type="button" class="button button-white" id="smOpenModal">Schedule Post</button>
                <button type="button" class="button button-white" id="smPublishNow">Publish Now</button>
            </div>
        </div>
    </div>

    <div class="sm-step" id="smStep4" data-step="4">
        <div class="form__wrap">
            <div class="row form__title"><h3>Schedule Your Post</h3></div>
            <div id="smScheduleForm">
                <div class="row form__block">
                    <div class="col-md-3"><label class="label-left">Selected Platforms</label></div>
                    <div class="col-md-9">
                        <ul class="sm-check" id="smStep4Platforms"></ul>
                    </div>
                </div>
                <div class="row form__title"><h3>Publishing Time</h3></div>
                <div class="row form__block">
                    <div class="col-sm-4">
                        <label>Date</label>
                        <div class="dt-picker">
                            <div class="input-group date" id="smDateWrap">
                                <input type="text" class="form-control" id="smDateInput" placeholder="DD.MM.YYYY">
                                <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label>Time</label>
                        <div class="dt-picker">
                            <div class="input-group date" id="smTimeWrap">
                                <input type="text" class="form-control" id="smTimeInput" placeholder="HH:mm">
                                <span class="input-group-addon"><i class="far fa-clock"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label>Timezone</label>
                        <select class="select-default" id="smTz">
                            <option value="Europe/Zurich">Europe/Zurich</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>
                </div>
                <div class="row form__title"><h3>Preview</h3></div>
                <div class="row form__block">
                    <div class="col-md-12">
                        <div class="sm-mini" id="smStep4Preview"></div>
                    </div>
                </div>
                <div class="row form__submit sm-actionbar">
                    <button type="button" class="button" id="smScheduleBtn">Schedule Post</button>
                    <button type="button" class="button button-white" id="smPublishStep4">Publish Now</button>
                </div>
            </div>
            <div id="smSuccess" class="is-hidden">
                <div class="row alert__wrap">
                    <div class="alert alert-success">
                        <span class="alert__icon"><i class="fas fa-check"></i></span>
                        <p id="smSuccessText"></p>
                    </div>
                </div>
                <div class="row form__block">
                    <div class="col-md-12">
                        <div class="sm-mini" id="smSuccessPreview"></div>
                    </div>
                </div>
                <div class="row form__submit sm-actionbar">
                    <a href="#" class="button" id="smSuccessView">View post</a>
                    <a href="{{ url('cms/ai-posts') }}" class="button button-white">Back to posts</a>
                </div>
            </div>
        </div>
    </div>
    @include('cms.ai-posts.partials.modals')
</div>
@endsection

@section('scripts')
@include('cms.ai-posts.partials.scripts')
@endsection
