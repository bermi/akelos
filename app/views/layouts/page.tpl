<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <title>_{Akelos Framework}</title>
    <style type="text/css" media="screen">
        body {
            margin: 0;
            margin-bottom: 25px;
            padding: 0;
            background-color: #f0f0f0;
            font-family: "Lucida Grande", "Bitstream Vera Sans", "Verdana";
            font-size: 13px;
            color: #333;
          }
          
          h1 {
            font-size: 28px;
            color: #000;
          }
          
          a  {color: #03c}
          a:hover {
            background-color: #03c;
            color: white;
            text-decoration: none;
          }
          
          
          #page {
            background: #f0f0f0;
            width: 750px;
            margin: 0;
            margin-left: auto;
            margin-right: auto;
          }
          
          #content {
            float: left;
            background: #fff url('<?=$asset_tag_helper->image_path('akelos_framework_logo')?>') 10px 10px no-repeat;
            border: 3px solid #aaa;
            border-top: none;
            padding: 25px;
            width: 650px;
          }
          
          #footer {
            clear: both;
          }
          
    
          #header, #about, #main-content {
            padding-left: 75px;
            padding-right: 30px;
          }
    
    
          #header {
            margin:0 0 35px 100px;
          }
          
          #header h1, #header h2 {margin: 0}
          #header h2 {
            color: #888;
            font-weight: normal;
            font-size: 16px;
          }
          
          #main-content {
            border-top: 1px solid #ccc;
            margin-top: 25px;
            padding-top: 15px;
          }
          #main-content h1 {
            margin: 0;
            font-size: 20px;
          }
          #main-content h2 {
            margin: 0;
            font-size: 14px;
            font-weight: normal;
            color: #333;
            margin-bottom: 25px;
          }
          #main-content ol {
            margin-left: 0;
            padding-left: 0;
          }
          #main-content li {
            font-size: 18px;
            color: #888;
            margin-bottom: 25px;
          }
          #main-content li h2 {
            margin: 0;
            font-weight: normal;
            font-size: 18px;
            color: #333;
          }
          #main-content li p {
            color: #555;
            font-size: 13px;
          }
          fieldset {
              width:400px;
          }
          label {
              margin:0 10px 0 0;
              display:block;
          }
          input{
              width:100%;
          }   
          .sqlite_database_name {
            width:180px;
          }    
          #next-step {
              text-align: right;
          }  
          .important{
            font-weight: bold;
            font-size: 140%;
          }
        .flash{
            margin-left:160px;
            border:2px solid red;
            padding:5px;
        }
    </style>
  </head>
  <body>
    <div id="page">
      <div id="content">
      <?= $text_helper->flash();?>
      <?php echo $content_for_layout; ?>
      
      </div>
      <div id="footer"> </div>
    </div>
  </body>
</html>