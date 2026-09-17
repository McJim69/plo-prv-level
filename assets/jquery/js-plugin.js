
  <script src="js/jquery-1.7.1.min.js"></script>
  <script src="js/jquery.imagesloaded.js"></script>
  <script src="js/jquery.wookmark.js"></script>
  <script type="text/javascript">
    $('#tiles').imagesLoaded(function() {
      var options = {
        autoResize: true,
        container: $('#main'),
        offset: 10
      };
      var handler = $('#tiles li');
      handler.wookmark(options);
    });
  </script>