    </div>
</div>
<footer class="container footer">
    <div class="row">
        <div class="col-sm-6">
            <p class="copyright">&copy; Copyright {{ date('Y') }} - All rights reserved.</p>
        </div>
        <div class="col-sm-6">
            <p class="creator">Powered by <img src="{{ asset('cmsfiles/images/symple_icon.png') }}" alt="symple-icon"></p>
        </div>
    </div>
</footer>

    <!-- SCRIPTS -->
    <script src="{{ asset('cmsfiles/js/jquery.1.12.4.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/jquery-ui.1.12.1.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/jquery.ui.touch-punch.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/moment-with-locales.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/fancybox/dist/jquery.fancybox.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/custom.js?v=1.2') }}"></script>
    <script>
        $('#cmsLang').change(function () {
            var optionSelected = $(this).find("option:selected");
            var valueSelected  = optionSelected.val();
            var url = "{{ url('/cms/language') }}/"+valueSelected; 
            window.location = url;
        });
    </script>
    @yield('scripts')

</body>

</html>